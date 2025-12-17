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

function calculateSAW($id_program)
{
    $conn = getConnection();
    $id_program = intval($id_program);

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn->set_charset('utf8mb4');

    sawLog("START SAW PROGRAM $id_program");

    /* ================= AMBIL SEMUA PENGAJUAN TERVERIFIKASI ================= */
    $res = $conn->query("
        SELECT *
        FROM pengajuan
        WHERE id_program = $id_program
          AND status = 'Terverifikasi'
        ORDER BY id ASC
    ");

    if ($res->num_rows === 0) {
        sawLog("NO VERIFIED SUBMISSIONS", "WARNING");
        return false;
    }

    $data = $res->fetch_all(MYSQLI_ASSOC);
    sawLog("TOTAL VERIFIED: " . count($data));
    sawLog("IDS: " . implode(', ', array_column($data, 'id')));

    /* ================= STEP 1: KONVERSI (JANGAN FILTER APAPUN) ================= */
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

    /* ================= STEP 2: MIN MAX ================= */
    $min = $max = [];
    foreach (KRITERIA as $k => $v) {
        $vals = array_column($converted, $k);
        $min[$k] = min($vals);
        $max[$k] = max($vals);
    }

    /* ================= STEP 3: NORMALISASI ================= */
    $normal = [];
    foreach ($converted as $c) {
        $row = ['id_pengajuan' => $c['id_pengajuan']];
        foreach (KRITERIA as $k => $v) {
            $row[$k] = ($v['type'] === 'benefit')
                ? $c[$k] / $max[$k]
                : $min[$k] / $c[$k];
        }
        $normal[] = $row;
    }

    /* ================= STEP 4: HITUNG SKOR ================= */
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

    /* ================= STEP 5: RANKING ================= */
    usort($hasil, fn($a, $b) => $b['skor_total'] <=> $a['skor_total']);
    foreach ($hasil as $i => &$h) {
        $h['peringkat'] = $i + 1;
    }
    unset($h); // 🔥 WAJIB (hindari reference bug)

    /* ================= STEP 6: SIMPAN ================= */
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
        sawLog("INSERTED pengajuan {$h['id_pengajuan']}");
    }

    sawLog("SAW SUCCESS PROGRAM $id_program");
    return true;
}


function sawDebug($label, $data)
{
    $time = date('Y-m-d H:i:s');
    $log = "[$time][DEBUG][$label] " . print_r($data, true) . PHP_EOL;
    error_log($log, 3, __DIR__ . '/../logs/saw.log');
}


/* =========================================================
   GET RANKING - WITH RECIPIENT STATUS
========================================================= */
function getRankingByProgram($id_program, $limit = null)
{
    $conn = getConnection();

    // ==== DEBUG INPUT ====
    sawDebug('INPUT_RAW', [
        'id_program_raw' => $id_program,
        'limit_raw' => $limit
    ]);

    $id_program = intval($id_program);
    $limit = $limit !== null ? intval($limit) : null;

    // ==== DEBUG AFTER CAST ====
    sawDebug('INPUT_CASTED', [
        'id_program' => $id_program,
        'limit' => $limit
    ]);

    $sql = "
        SELECT 
            p.id,
            p.id_user,
            p.nama_lengkap,
            p.nik,
            p.no_hp,
            tn.skor_total,
            tn.peringkat,
            pb.kuota,
            CASE WHEN tn.peringkat <= pb.kuota THEN 1 ELSE 0 END AS is_penerima
        FROM total_nilai tn
        JOIN pengajuan p ON tn.id_pengajuan = p.id
        JOIN program_bantuan pb ON tn.id_program = pb.id
        WHERE tn.id_program = $id_program
        ORDER BY tn.peringkat ASC
    ";

    if ($limit !== null) {
        $sql .= " LIMIT $limit";
    }

    // ==== DEBUG SQL ====
    sawDebug('SQL_QUERY', $sql);

    $res = $conn->query($sql);

    // ==== DEBUG QUERY RESULT OBJECT ====
    if (!$res) {
        sawDebug('SQL_ERROR', $conn->error);
        return [];
    }

    sawDebug('SQL_META', [
        'num_rows' => $res->num_rows,
        'affected_rows' => $conn->affected_rows
    ]);

    $data = $res->fetch_all(MYSQLI_ASSOC);

    // ==== DEBUG RESULT DATA ====
    sawDebug('RESULT_DATA', $data);

    return $data;
}


/* =========================================================
   GET SAW STATISTICS
========================================================= */
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