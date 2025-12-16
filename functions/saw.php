<?php
require_once(__DIR__ . "/connection.php");

// ==========================================
// DEFINISI KRITERIA & BOBOT
// ==========================================
define('KRITERIA', [
    'gaji' => ['type' => 'cost', 'weight' => 0.30],
    'status_rumah' => ['type' => 'cost', 'weight' => 0.15],
    'daya_listrik' => ['type' => 'cost', 'weight' => 0.10],
    'pengeluaran' => ['type' => 'cost', 'weight' => 0.20],
    'jml_keluarga' => ['type' => 'benefit', 'weight' => 0.15],
    'jml_anak_sekolah' => ['type' => 'benefit', 'weight' => 0.10]
]);

// ==========================================
// TABEL KONVERSI
// ==========================================
define('KONVERSI_PENDAPATAN', [
    ['min' => 0, 'max' => 1000000, 'nilai' => 5],
    ['min' => 1000000, 'max' => 2000000, 'nilai' => 4],
    ['min' => 2000000, 'max' => 3500000, 'nilai' => 3],
    ['min' => 3500000, 'max' => PHP_INT_MAX, 'nilai' => 2]
]);

define('KONVERSI_KELISTRIKAN', [
    'Menumpang' => 5,
    'Pribadi 450 Watt' => 4,
    'Pribadi 900 Watt' => 3,
    'Pribadi 1200 Watt' => 2,
    'Pribadi > 1200 Watt' => 1
]);

define('KONVERSI_KEPEMILIKAN_RUMAH', [
    'Sewa' => 5,
    'Keluarga' => 3,
    'Pribadi' => 1
]);

define('KONVERSI_PENGELUARAN', [
    ['min' => 0, 'max' => 1000000, 'nilai' => 2],
    ['min' => 1000000, 'max' => 2000000, 'nilai' => 3],
    ['min' => 2000000, 'max' => 3500000, 'nilai' => 4],
    ['min' => 3500000, 'max' => PHP_INT_MAX, 'nilai' => 5]
]);

define('KONVERSI_JUMLAH_KELUARGA', [
    ['min' => 1, 'max' => 2, 'nilai' => 1],
    ['min' => 3, 'max' => 3, 'nilai' => 2],
    ['min' => 4, 'max' => 4, 'nilai' => 3],
    ['min' => 5, 'max' => 5, 'nilai' => 4],
    ['min' => 6, 'max' => PHP_INT_MAX, 'nilai' => 5]
]);

define('KONVERSI_ANAK_SEKOLAH', [
    0 => 1,
    1 => 2,
    2 => 3,
    3 => 4,
    4 => 5
]);

// ==========================================
// HELPER FUNCTIONS
// ==========================================
function konversiNilaiRange($nilai, $konversiArray)
{
    $nilai = floatval($nilai); // Pastikan angka
    foreach ($konversiArray as $range) {
        if ($nilai >= $range['min'] && $nilai <= $range['max']) {
            return $range['nilai'];
        }
    }
    return 1;
}

function konversiAnakSekolah($jumlah)
{
    $jumlah = intval($jumlah);
    if ($jumlah >= 4) return 5;
    return KONVERSI_ANAK_SEKOLAH[$jumlah] ?? 1;
}

// ==========================================
// CORE CALCULATION
// ==========================================

function calculateSAW($id_program)
{
    $connection = getConnection();
    $id_program = intval($id_program);

    error_log("=== START SAW CALCULATION FOR PROGRAM $id_program ===");
    // 1. Ambil Data Terverifikasi (Urutkan Terbaru)
    $query = "SELECT * FROM pengajuan 
              WHERE status = 'Terverifikasi' 
              AND id_program = $id_program
              ORDER BY tanggal_dibuat DESC";

    $result = $connection->query($query);

    // Jika tidak ada data, bersihkan nilai lama saja
    if (!$result || $result->num_rows == 0) {
        $connection->query("DELETE FROM total_nilai WHERE id_pengajuan IN (SELECT id FROM pengajuan WHERE id_program = $id_program)");
        return true;
    }

    $rawData = $result->fetch_all(MYSQLI_ASSOC);
    error_log("SAW INFO: Found " . count($rawData) . " verified submissions");

    // 2. Filter Duplikasi (Hanya ambil 1 pengajuan terbaru per User)
    $pengajuanData = [];
    $seenUsers = [];

    foreach ($rawData as $row) {
        $userId = $row['id_user'];
        if (!in_array($userId, $seenUsers)) {
            $seenUsers[] = $userId;
            $pengajuanData[] = $row;
        }
    }

    error_log("SAW INFO: After deduplication: " . count($pengajuanData) . " unique users (skipped " . (count($rawData) - count($pengajuanData)) . " duplicates)");
    if (empty($pengajuanData)) {
        error_log("SAW INFO: No unique submissions after deduplication");
        error_log("=== END SAW CALCULATION (No unique data) ===");
        return true;
    }
    // 3. Konversi Data
    $dataTerkonversi = [];
    foreach ($pengajuanData as $data) {
        $converted = [
            'id_pengajuan' => $data['id'],
            'gaji' => konversiNilaiRange($data['gaji'], KONVERSI_PENDAPATAN),
            'status_rumah' => KONVERSI_KEPEMILIKAN_RUMAH[$data['status_rumah']] ?? 1,
            'daya_listrik' => KONVERSI_KELISTRIKAN[$data['daya_listrik']] ?? 1,
            'pengeluaran' => konversiNilaiRange($data['pengeluaran'], KONVERSI_PENGELUARAN),
            'jml_keluarga' => konversiNilaiRange($data['jml_keluarga'], KONVERSI_JUMLAH_KELUARGA),
            'jml_anak_sekolah' => konversiAnakSekolah($data['jml_anak_sekolah'])
        ];
        $dataTerkonversi[] = $converted;
    }

    // 4. Cari Nilai Min/Max
    $nilaiMax = [];
    $nilaiMin = [];

    foreach (array_keys(KRITERIA) as $key) {
        $values = array_column($dataTerkonversi, $key);
        // Proteksi jika array kosong
        if (empty($values)) {
            $nilaiMax[$key] = 1;
            $nilaiMin[$key] = 1;
        } else {
            if (KRITERIA[$key]['type'] == 'benefit') {
                $nilaiMax[$key] = max($values);
                // Cegah pembagian dengan nol
                if ($nilaiMax[$key] == 0) $nilaiMax[$key] = 1;
            } else {
                $nilaiMin[$key] = min($values);
            }
        }
    }

    // 5. Normalisasi Matriks
    $dataNormalisasi = [];
    foreach ($dataTerkonversi as $data) {
        $normalized = ['id_pengajuan' => $data['id_pengajuan']];
        foreach (array_keys(KRITERIA) as $key) {
            $nilai = floatval($data[$key]);

            if (KRITERIA[$key]['type'] == 'benefit') {
                // Benefit: Nilai / Max
                $normalized[$key] = $nilai / $nilaiMax[$key];
            } else {
                // Cost: Min / Nilai
                // ✅ FIX: Jika Nilai 0 pada Cost (sangat baik), anggap skor 1 (sempurna)
                if ($nilai <= 0) {
                    $normalized[$key] = 1;
                } else {
                    $normalized[$key] = $nilaiMin[$key] / $nilai;
                }
            }
        }
        $dataNormalisasi[] = $normalized;
    }

    // 6. Hitung Skor Total
    $hasilAkhir = [];
    foreach ($dataNormalisasi as $data) {
        $skorTotal = 0;
        foreach (array_keys(KRITERIA) as $key) {
            $skorTotal += $data[$key] * KRITERIA[$key]['weight'];
        }

        // ✅ SAFETY: Pastikan skor valid (bukan NaN)
        if (is_nan($skorTotal) || is_infinite($skorTotal)) {
            $skorTotal = 0;
        }

        $hasilAkhir[] = [
            'id_pengajuan' => $data['id_pengajuan'],
            'skor_total' => $skorTotal
        ];
    }

    // 7. Ranking
    usort($hasilAkhir, function ($a, $b) {
        return $b['skor_total'] <=> $a['skor_total'];
    });

    foreach ($hasilAkhir as $index => &$hasil) {
        $hasil['peringkat'] = $index + 1;
    }

    // 8. Simpan ke Database
    $connection->begin_transaction();
    try {
        // Hapus data lama untuk program ini (Gunakan Subquery Delete yang lebih aman)
        $deleteSql = "DELETE FROM total_nilai WHERE id_pengajuan IN (
                        SELECT id FROM pengajuan WHERE id_program = $id_program
                      )";
        $connection->query($deleteSql);
        error_log("SAW INFO: Old data deleted for program $id_program");

        // Insert data baru - GUNAKAN INSERT IGNORE untuk avoid duplicate
        $stmt = $connection->prepare("INSERT IGNORE INTO total_nilai (id_pengajuan, skor_total, peringkat, tanggal_hitung) VALUES (?, ?, ?, NOW())");

        $insertedCount = 0;
        foreach ($hasilAkhir as $hasil) {
            $stmt->bind_param("idi", $hasil['id_pengajuan'], $hasil['skor_total'], $hasil['peringkat']);
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $insertedCount++;
                    error_log("SAW INFO: Inserted ranking #{$hasil['peringkat']} for submission {$hasil['id_pengajuan']} (score: {$hasil['skor_total']})");
                } else {
                    error_log("SAW WARNING: Submission {$hasil['id_pengajuan']} already exists, skipped");
                }
            } else {
                error_log("SAW ERROR: Failed to insert submission {$hasil['id_pengajuan']}: " . $stmt->error);
            }
        }

        $connection->commit();
        error_log("SAW SUCCESS: Transaction committed, inserted $insertedCount records");
        return true;
    } catch (Exception $e) {
        $connection->rollback();
        error_log("SAW Calculation Error: " . $e->getMessage());
        return false;
    }
}

function getRankingByProgram($id_program, $limit = null, $onlyWithinKuota = false)
{
    $connection = getConnection();
    $id_program = intval($id_program);

    $query = "SELECT p.nama_lengkap, p.nik, p.no_hp, tn.skor_total, tn.peringkat, p.id, pb.kuota
              FROM total_nilai tn
              JOIN pengajuan p ON tn.id_pengajuan = p.id
              JOIN program_bantuan pb ON p.id_program = pb.id
              WHERE p.status = 'Terverifikasi' AND p.id_program = $id_program
              ORDER BY tn.peringkat ASC";

    if ($limit) {
        $query .= " LIMIT " . intval($limit);
    }

    $result = $connection->query($query);
    $rankings = $result->fetch_all(MYSQLI_ASSOC);

    if ($onlyWithinKuota && !empty($rankings)) {
        $kuota = $rankings[0]['kuota'];
        $rankings = array_filter($rankings, function ($r) use ($kuota) {
            return $r['peringkat'] <= $kuota;
        });
    }

    return $rankings;
}

function getSAWStatisticsByProgram($id_program)
{
    $connection = getConnection();
    $id_program = intval($id_program);

    $stats = ['total_peserta' => 0, 'skor_tertinggi' => 0, 'skor_terendah' => 0, 'rata_rata' => 0];

    $query = "SELECT COUNT(*) as total, MAX(skor_total) as max_skor, MIN(skor_total) as min_skor, AVG(skor_total) as avg_skor 
              FROM total_nilai tn
              JOIN pengajuan p ON tn.id_pengajuan = p.id
              WHERE p.id_program = $id_program";

    $result = $connection->query($query);
    if ($row = $result->fetch_assoc()) {
        $stats['total_peserta'] = $row['total'] ?? 0;
        $stats['skor_tertinggi'] = $row['max_skor'] ?? 0;
        $stats['skor_terendah'] = $row['min_skor'] ?? 0;
        $stats['rata_rata'] = $row['avg_skor'] ?? 0;
    }
    return $stats;
}

function getKriteriaNilai($id_pengajuan)
{
    $connection = getConnection();
    $query = "SELECT * FROM pengajuan WHERE id = " . intval($id_pengajuan);
    $result = $connection->query($query);
    $data = $result->fetch_assoc();

    if (!$data) return null;

    $formatRupiah = function ($nilai) {
        if ($nilai < 1000000) return '< Rp 1.000.000';
        if ($nilai < 2000000) return 'Rp 1.000.000 - Rp 1.999.000';
        if ($nilai < 3500000) return 'Rp 2.000.000 - Rp 3.500.000';
        return '> Rp 3.500.000';
    };

    return [
        'Penghasilan' => [
            'nilai_asli' => $data['gaji'],
            'nilai_konversi' => konversiNilaiRange($data['gaji'], KONVERSI_PENDAPATAN),
            'tipe' => 'Cost',
            'bobot' => KRITERIA['gaji']['weight'],
            'keterangan' => $formatRupiah($data['gaji'])
        ],
        'Kepemilikan Rumah' => [
            'nilai_asli' => $data['status_rumah'],
            'nilai_konversi' => KONVERSI_KEPEMILIKAN_RUMAH[$data['status_rumah']] ?? 1,
            'tipe' => 'Cost',
            'bobot' => KRITERIA['status_rumah']['weight'],
            'keterangan' => $data['status_rumah']
        ],
        'Kelistrikan' => [
            'nilai_asli' => $data['daya_listrik'],
            'nilai_konversi' => KONVERSI_KELISTRIKAN[$data['daya_listrik']] ?? 1,
            'tipe' => 'Cost',
            'bobot' => KRITERIA['daya_listrik']['weight'],
            'keterangan' => $data['daya_listrik']
        ],
        'Pengeluaran' => [
            'nilai_asli' => $data['pengeluaran'],
            'nilai_konversi' => konversiNilaiRange($data['pengeluaran'], KONVERSI_PENGELUARAN),
            'tipe' => 'Cost',
            'bobot' => KRITERIA['pengeluaran']['weight'],
            'keterangan' => $formatRupiah($data['pengeluaran'])
        ],
        'Jumlah Anggota Keluarga' => [
            'nilai_asli' => $data['jml_keluarga'],
            'nilai_konversi' => konversiNilaiRange($data['jml_keluarga'], KONVERSI_JUMLAH_KELUARGA),
            'tipe' => 'Benefit',
            'bobot' => KRITERIA['jml_keluarga']['weight'],
            'keterangan' => $data['jml_keluarga'] . ' orang'
        ],
        'Keberadaan Anak Usia Sekolah' => [
            'nilai_asli' => $data['jml_anak_sekolah'],
            'nilai_konversi' => konversiAnakSekolah($data['jml_anak_sekolah']),
            'tipe' => 'Benefit',
            'bobot' => KRITERIA['jml_anak_sekolah']['weight'],
            'keterangan' => $data['jml_anak_sekolah'] . ' anak'
        ]
    ];
}
