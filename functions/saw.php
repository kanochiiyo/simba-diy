<?php
require_once(__DIR__ . "/connection.php");

/* =========================================================
   LOGGING
========================================================= */
define('SAW_LOG_FILE', __DIR__ . '/../logs/saw.log');

function sawLog($msg, $level = 'INFO')
{
    $time = date('Y-m-d H:i:s');
    error_log("[$time][$level] $msg\n", 3, SAW_LOG_FILE);
}

/* =========================================================
   KRITERIA & BOBOT
========================================================= */
define('KRITERIA', [
    'gaji' => ['type' => 'cost', 'weight' => 0.30],
    'status_rumah' => ['type' => 'cost', 'weight' => 0.15],
    'daya_listrik' => ['type' => 'cost', 'weight' => 0.10],
    'pengeluaran' => ['type' => 'cost', 'weight' => 0.20],
    'jml_keluarga' => ['type' => 'benefit', 'weight' => 0.15],
    'jml_anak_sekolah' => ['type' => 'benefit', 'weight' => 0.10],
]);

/* =========================================================
   KONVERSI
========================================================= */
define('KONVERSI_PENDAPATAN', [
    ['min' => 0, 'max' => 1000000, 'nilai' => 5],
    ['min' => 1000000, 'max' => 2000000, 'nilai' => 4],
    ['min' => 2000000, 'max' => 3500000, 'nilai' => 3],
    ['min' => 3500000, 'max' => PHP_INT_MAX, 'nilai' => 2],
]);

define('KONVERSI_PENGELUARAN', [
    ['min' => 0, 'max' => 1000000, 'nilai' => 2],
    ['min' => 1000000, 'max' => 2000000, 'nilai' => 3],
    ['min' => 2000000, 'max' => 3500000, 'nilai' => 4],
    ['min' => 3500000, 'max' => PHP_INT_MAX, 'nilai' => 5],
]);

define('KONVERSI_KEPEMILIKAN_RUMAH', [
    'sewa' => 5,
    'keluarga' => 3,
    'pribadi' => 1,
]);

define('KONVERSI_KELISTRIKAN', [
    'menumpang' => 5,
    'pribadi 450 watt' => 4,
    'pribadi 900 watt' => 3,
    'pribadi 1200 watt' => 2,
    'pribadi > 1200 watt' => 1,
]);

define('KONVERSI_JUMLAH_KELUARGA', [
    ['min' => 1, 'max' => 2, 'nilai' => 1],
    ['min' => 3, 'max' => 3, 'nilai' => 2],
    ['min' => 4, 'max' => 4, 'nilai' => 3],
    ['min' => 5, 'max' => 5, 'nilai' => 4],
    ['min' => 6, 'max' => PHP_INT_MAX, 'nilai' => 5],
]);

/* =========================================================
   HELPER
========================================================= */
function normalize($text)
{
    return strtolower(trim($text));
}

function konversiRange($nilai, $rules)
{
    foreach ($rules as $r) {
        if ($nilai >= $r['min'] && $nilai <= $r['max']) {
            return $r['nilai'];
        }
    }
    return 1;
}

function konversiAnakSekolah($jumlah)
{
    return ($jumlah >= 4) ? 5 : max(1, intval($jumlah) + 1);
}

/* =========================================================
   CORE SAW (FINAL – LOCK + TRANSACTION)
========================================================= */
function calculateSAW($id_program)
{
    $conn = getConnection();
    $id_program = intval($id_program);

    // STRICT MODE
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn->set_charset('utf8mb4');

    sawLog("START SAW PROGRAM $id_program");

    /* ================= LOCK (ANTI DOUBLE CALL) ================= */
    $lockName = "saw_program_" . $id_program;
    $lockRes = $conn->query("SELECT GET_LOCK('$lockName', 10) AS locked");
    $lockRow = $lockRes->fetch_assoc();

    if (!$lockRow || intval($lockRow['locked']) !== 1) {
        sawLog("FAILED TO ACQUIRE LOCK PROGRAM $id_program", "ERROR");
        return false;
    }

    try {
        /* ================= DATA ================= */
        $res = $conn->query("
            SELECT *
            FROM pengajuan
            WHERE status = 'Terverifikasi'
              AND id_program = $id_program
            ORDER BY tanggal_dibuat ASC
        ");

        if ($res->num_rows === 0) {
            sawLog("NO VERIFIED SUBMISSION", "WARNING");
            $conn->query("SELECT RELEASE_LOCK('$lockName')");
            return false;
        }

        $data = $res->fetch_all(MYSQLI_ASSOC);
        sawLog("TOTAL ALTERNATIF: " . count($data));

        /* ================= KONVERSI ================= */
        $converted = [];
        foreach ($data as $d) {
            $converted[] = [
                'id_pengajuan' => $d['id'],
                'gaji' => konversiRange($d['gaji'], KONVERSI_PENDAPATAN),
                'status_rumah' => KONVERSI_KEPEMILIKAN_RUMAH[normalize($d['status_rumah'])] ?? 1,
                'daya_listrik' => KONVERSI_KELISTRIKAN[normalize($d['daya_listrik'])] ?? 1,
                'pengeluaran' => konversiRange($d['pengeluaran'], KONVERSI_PENGELUARAN),
                'jml_keluarga' => konversiRange($d['jml_keluarga'], KONVERSI_JUMLAH_KELUARGA),
                'jml_anak_sekolah' => konversiAnakSekolah($d['jml_anak_sekolah']),
            ];
        }

        /* ================= MIN MAX ================= */
        $max = $min = [];
        foreach (KRITERIA as $k => $v) {
            $vals = array_column($converted, $k);
            $max[$k] = max($vals) ?: 1;
            $min[$k] = min($vals) ?: 1;
        }

        /* ================= NORMALISASI ================= */
        $normal = [];
        foreach ($converted as $c) {
            $row = ['id_pengajuan' => $c['id_pengajuan']];
            foreach (KRITERIA as $k => $v) {
                $row[$k] = ($v['type'] === 'benefit')
                    ? $c[$k] / $max[$k]
                    : (($c[$k] > 0) ? $min[$k] / $c[$k] : 1);
            }
            $normal[] = $row;
        }

        /* ================= SKOR ================= */
        $hasil = [];
        foreach ($normal as $n) {
            $score = 0;
            foreach (KRITERIA as $k => $v) {
                $score += $n[$k] * $v['weight'];
            }
            $hasil[] = [
                'id_pengajuan' => $n['id_pengajuan'],
                'skor_total' => $score
            ];
        }

        /* ================= RANKING ================= */
        usort($hasil, fn($a, $b) => $b['skor_total'] <=> $a['skor_total']);
        foreach ($hasil as $i => &$h) {
            $h['peringkat'] = $i + 1;
        }

        /* ================= SAVE (IDEMPOTENT) ================= */
        $conn->begin_transaction();

        $conn->query("DELETE FROM total_nilai WHERE id_program = $id_program");

        $stmt = $conn->prepare("
            INSERT INTO total_nilai
            (id_program, id_pengajuan, skor_total, peringkat, tanggal_hitung)
            VALUES (?, ?, ?, ?, NOW())
        ");

        foreach ($hasil as $h) {
            $stmt->bind_param(
                "iidi",
                $id_program,
                $h['id_pengajuan'],
                $h['skor_total'],
                $h['peringkat']
            );
            $stmt->execute();
        }

        $conn->commit();
        $conn->query("SELECT RELEASE_LOCK('$lockName')");
        sawLog("SAW SUCCESS PROGRAM $id_program");

        return true;

    } catch (Throwable $e) {
        $conn->rollback();
        $conn->query("SELECT RELEASE_LOCK('$lockName')");
        sawLog("SAW ERROR: " . $e->getMessage(), "ERROR");
        return false;
    }
}

/* =========================================================
   AMBIL RANKING (UI)
========================================================= */
function getRankingByProgram($id_program, $limit = null)
{
    $conn = getConnection();
    $id_program = intval($id_program);

    $sql = "
        SELECT 
            p.id_user,
            p.nama_lengkap,
            p.nik,
            p.no_hp,
            tn.skor_total,
            tn.peringkat,
            pb.kuota
        FROM total_nilai tn
        JOIN pengajuan p ON tn.id_pengajuan = p.id
        JOIN program_bantuan pb ON tn.id_program = pb.id
        WHERE tn.id_program = $id_program
        ORDER BY tn.peringkat ASC
    ";

    if ($limit) {
        $sql .= " LIMIT " . intval($limit);
    }

    $res = $conn->query($sql);
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

function getSAWStatisticsByProgram($id_program)
{
    $conn = getConnection();
    $id_program = intval($id_program);

    $sql = "
        SELECT
            COUNT(tn.id) AS total_peserta,
            MAX(tn.skor_total) AS skor_tertinggi,
            MIN(tn.skor_total) AS skor_terendah,
            AVG(tn.skor_total) AS rata_rata
        FROM total_nilai tn
        WHERE tn.id_program = $id_program
    ";

    $res = $conn->query($sql);

    if (!$res) {
        return [
            'total_peserta' => 0,
            'skor_tertinggi' => 0,
            'skor_terendah' => 0,
            'rata_rata' => 0
        ];
    }

    $row = $res->fetch_assoc();

    return [
        'total_peserta' => intval($row['total_peserta'] ?? 0),
        'skor_tertinggi' => floatval($row['skor_tertinggi'] ?? 0),
        'skor_terendah' => floatval($row['skor_terendah'] ?? 0),
        'rata_rata' => floatval($row['rata_rata'] ?? 0),
    ];
}
