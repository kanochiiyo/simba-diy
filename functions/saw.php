<?php
require_once(__DIR__ . "/connection.php");

define('KRITERIA', [
    'gaji' => [
        'nama' => 'Penghasilan',
        'type' => 'cost',
        'weight' => 0.30,
        'satuan' => 'Rupiah'
    ],
    'status_rumah' => [
        'nama' => 'Kepemilikan Rumah',
        'type' => 'cost',
        'weight' => 0.15,
        'satuan' => 'Kategori'
    ],
    'daya_listrik' => [
        'nama' => 'Kelistrikan',
        'type' => 'cost',
        'weight' => 0.10,
        'satuan' => 'Status'
    ],
    'pengeluaran' => [
        'nama' => 'Pengeluaran',
        'type' => 'cost',
        'weight' => 0.20,
        'satuan' => 'Rupiah'
    ],
    'jml_keluarga' => [
        'nama' => 'Jumlah Anggota Keluarga',
        'type' => 'benefit',
        'weight' => 0.15,
        'satuan' => 'Orang'
    ],
    'jml_anak_sekolah' => [
        'nama' => 'Keberadaan Anak Usia Sekolah',
        'type' => 'benefit',
        'weight' => 0.10,
        'satuan' => 'Anak'
    ]
]);

// Tabel Kriteria Pendapatan (Cost)
define('KONVERSI_PENDAPATAN', [
    ['min' => 0, 'max' => 1000000, 'nilai' => 5],
    ['min' => 1000000, 'max' => 2000000, 'nilai' => 4],
    ['min' => 2000000, 'max' => 3500000, 'nilai' => 3],
    ['min' => 3500000, 'max' => PHP_INT_MAX, 'nilai' => 2]
]);

// Tabel Kriteria Kelistrikan (Cost)
define('KONVERSI_KELISTRIKAN', [
    'Menumpang' => 5,
    'Pribadi 450 Watt' => 4,
    'Pribadi 900 Watt' => 3,
    'Pribadi 1200 Watt' => 2,
    'Pribadi > 1200 Watt' => 1
]);

// Tabel Kriteria Kepemilikan Rumah (Cost)
define('KONVERSI_KEPEMILIKAN_RUMAH', [
    'Sewa' => 5,
    'Keluarga' => 3,
    'Pribadi' => 1
]);

// Tabel Kriteria Pengeluaran (Cost)
define('KONVERSI_PENGELUARAN', [
    ['min' => 0, 'max' => 1000000, 'nilai' => 2],
    ['min' => 1000000, 'max' => 2000000, 'nilai' => 3],
    ['min' => 2000000, 'max' => 3500000, 'nilai' => 4],
    ['min' => 3500000, 'max' => PHP_INT_MAX, 'nilai' => 5]
]);

// Tabel Kriteria Jumlah Anggota Keluarga (Benefit)
define('KONVERSI_JUMLAH_KELUARGA', [
    ['min' => 1, 'max' => 2, 'nilai' => 1],
    ['min' => 3, 'max' => 3, 'nilai' => 2],
    ['min' => 4, 'max' => 4, 'nilai' => 3],
    ['min' => 5, 'max' => 5, 'nilai' => 4],
    ['min' => 6, 'max' => PHP_INT_MAX, 'nilai' => 5]
]);

// Tabel Keberadaan Anak Usia Sekolah (Benefit)
define('KONVERSI_ANAK_SEKOLAH', [
    0 => 1,
    1 => 2,
    2 => 3,
    3 => 4,
    4 => 5  // 4 atau lebih
]);

function konversiNilaiRange($nilai, $konversiArray)
{
    foreach ($konversiArray as $range) {
        if ($nilai >= $range['min'] && $nilai <= $range['max']) {
            return $range['nilai'];
        }
    }
    return 1; // Default jika tidak ada yang cocok
}

function konversiAnakSekolah($jumlah)
{
    if ($jumlah >= 4) {
        return 5;
    }
    return KONVERSI_ANAK_SEKOLAH[$jumlah] ?? 1;
}

/**
 * ✅ FIXED: Calculate SAW untuk program tertentu
 * Perbaikan utama:
 * 1. Query filter yang konsisten
 * 2. Hapus data lama dengan benar
 * 3. Tidak ada duplikasi data
 */
function calculateSAW($id_program = null)
{
    $connection = getConnection();

    // ✅ FIXED: Query dengan filter program yang JELAS
    if ($id_program) {
        // Hitung untuk program spesifik
        $query = "SELECT p.* FROM pengajuan p 
                  WHERE p.status = 'Terverifikasi' 
                  AND p.id_program = " . intval($id_program);
    } else {
        // Hitung untuk semua pengajuan terverifikasi (fallback)
        $query = "SELECT p.* FROM pengajuan p 
                  WHERE p.status = 'Terverifikasi'";
    }

    $result = $connection->query($query);

    if (!$result || $result->num_rows == 0) {
        // Tidak ada data untuk dihitung
        return false;
    }

    $pengajuanData = $result->fetch_all(MYSQLI_ASSOC);

    // ✅ DEBUG: Log data yang akan diproses
    error_log("SAW Calculation - Processing " . count($pengajuanData) . " pengajuan for program ID: " . ($id_program ?? 'ALL'));

    // STEP 1: Konversi nilai mentah ke nilai kriteria (1-5)
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

        // ✅ DEBUG
        error_log("Converted ID: " . $converted['id_pengajuan'] . " | Gaji: " . $converted['gaji'] . " | JmlKeluarga: " . $converted['jml_keluarga']);
    }

    // STEP 2: Hitung nilai min dan max untuk normalisasi
    $nilaiMax = [];
    $nilaiMin = [];

    foreach (array_keys(KRITERIA) as $key) {
        $values = array_column($dataTerkonversi, $key);

        if (KRITERIA[$key]['type'] == 'benefit') {
            $nilaiMax[$key] = max($values);
        } else {
            $nilaiMin[$key] = min($values);
        }
    }

    // STEP 3: Normalisasi setiap data
    $dataNormalisasi = [];
    foreach ($dataTerkonversi as $data) {
        $normalized = ['id_pengajuan' => $data['id_pengajuan']];

        foreach (array_keys(KRITERIA) as $key) {
            $nilai = $data[$key];
            $kriteria = KRITERIA[$key];

            // Normalisasi sesuai tipe kriteria
            if ($kriteria['type'] == 'benefit') {
                // Untuk benefit: nilai / nilai_max
                $normalized[$key] = $nilaiMax[$key] > 0 ? $nilai / $nilaiMax[$key] : 0;
            } else {
                // Untuk cost: nilai_min / nilai
                $normalized[$key] = $nilai > 0 ? $nilaiMin[$key] / $nilai : 0;
            }
        }

        $dataNormalisasi[] = $normalized;
    }

    // STEP 4: Hitung skor total dengan bobot
    $hasilAkhir = [];
    foreach ($dataNormalisasi as $data) {
        $skorTotal = 0;

        foreach (array_keys(KRITERIA) as $key) {
            $skorTotal += $data[$key] * KRITERIA[$key]['weight'];
        }

        $hasilAkhir[] = [
            'id_pengajuan' => $data['id_pengajuan'],
            'skor_total' => $skorTotal
        ];

        // ✅ DEBUG
        error_log("Final Score - ID: " . $data['id_pengajuan'] . " | Score: " . $skorTotal);
    }

    // STEP 5: Sort berdasarkan skor (descending)
    usort($hasilAkhir, function ($a, $b) {
        return $b['skor_total'] <=> $a['skor_total'];
    });

    // STEP 6: Assign peringkat
    foreach ($hasilAkhir as $index => &$hasil) {
        $hasil['peringkat'] = $index + 1;
    }

    // STEP 7: Simpan ke database
    // ✅ FIXED: Hapus data lama dengan benar
    if ($id_program) {
        // Hapus hanya untuk program ini
        $deleteQuery = "DELETE tn FROM total_nilai tn 
                       JOIN pengajuan p ON tn.id_pengajuan = p.id 
                       WHERE p.id_program = " . intval($id_program);

        $connection->query($deleteQuery);
        error_log("Deleted old SAW data for program: " . $id_program);
    } else {
        // Hapus semua (hati-hati!)
        $connection->query("TRUNCATE TABLE total_nilai");
        error_log("Deleted all SAW data");
    }

    // ✅ FIXED: Insert data baru dengan transaksi untuk menghindari duplikasi
    $connection->begin_transaction();

    try {
        $insertCount = 0;
        foreach ($hasilAkhir as $hasil) {
            $id_pengajuan = intval($hasil['id_pengajuan']);
            $skor_total = floatval($hasil['skor_total']);
            $peringkat = intval($hasil['peringkat']);

            // ✅ Cek duplikasi sebelum insert
            $checkQuery = "SELECT id FROM total_nilai WHERE id_pengajuan = $id_pengajuan";
            $checkResult = $connection->query($checkQuery);

            if ($checkResult->num_rows > 0) {
                // Update jika sudah ada
                $updateQuery = "UPDATE total_nilai 
                               SET skor_total = $skor_total, peringkat = $peringkat, tanggal_hitung = NOW()
                               WHERE id_pengajuan = $id_pengajuan";
                $connection->query($updateQuery);
                error_log("Updated existing SAW data for pengajuan ID: " . $id_pengajuan);
            } else {
                // Insert baru
                $insertQuery = "INSERT INTO total_nilai (id_pengajuan, skor_total, peringkat, tanggal_hitung) 
                               VALUES ($id_pengajuan, $skor_total, $peringkat, NOW())";
                $connection->query($insertQuery);
                error_log("Inserted new SAW data for pengajuan ID: " . $id_pengajuan);
            }

            $insertCount++;
        }

        $connection->commit();
        error_log("SAW Calculation SUCCESS - Processed $insertCount records");
        return true;
    } catch (Exception $e) {
        $connection->rollback();
        error_log("SAW Calculation FAILED - Error: " . $e->getMessage());
        return false;
    }
}

function getSAWDetails($id_pengajuan)
{
    $connection = getConnection();

    $query = "SELECT tn.*, p.* 
              FROM total_nilai tn
              JOIN pengajuan p ON tn.id_pengajuan = p.id
              WHERE tn.id_pengajuan = " . intval($id_pengajuan);

    $result = $connection->query($query);
    return $result->fetch_assoc();
}

function getKriteriaNilai($id_pengajuan)
{
    $connection = getConnection();

    $query = "SELECT * FROM pengajuan WHERE id = " . intval($id_pengajuan);
    $result = $connection->query($query);
    $data = $result->fetch_assoc();

    if (!$data) {
        return null;
    }

    // Helper function untuk format label gaji/pengeluaran
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

/**
 * ✅ FIXED: Get statistics per program
 */
function getSAWStatistics($id_program = null)
{
    $connection = getConnection();

    $stats = [];

    // Build WHERE clause
    $whereClause = "1=1";
    if ($id_program) {
        $whereClause = "p.id_program = " . intval($id_program);
    }

    // Total peserta
    $result = $connection->query("SELECT COUNT(*) as total FROM total_nilai tn 
                                  JOIN pengajuan p ON tn.id_pengajuan = p.id
                                  WHERE $whereClause");
    $stats['total_peserta'] = $result->fetch_assoc()['total'];

    // Skor tertinggi
    $result = $connection->query("SELECT MAX(tn.skor_total) as max_skor FROM total_nilai tn 
                                  JOIN pengajuan p ON tn.id_pengajuan = p.id
                                  WHERE $whereClause");
    $stats['skor_tertinggi'] = $result->fetch_assoc()['max_skor'] ?? 0;

    // Skor terendah
    $result = $connection->query("SELECT MIN(tn.skor_total) as min_skor FROM total_nilai tn 
                                  JOIN pengajuan p ON tn.id_pengajuan = p.id
                                  WHERE $whereClause");
    $stats['skor_terendah'] = $result->fetch_assoc()['min_skor'] ?? 0;

    // Rata-rata skor
    $result = $connection->query("SELECT AVG(tn.skor_total) as avg_skor FROM total_nilai tn 
                                  JOIN pengajuan p ON tn.id_pengajuan = p.id
                                  WHERE $whereClause");
    $stats['rata_rata'] = $result->fetch_assoc()['avg_skor'] ?? 0;

    return $stats;
}

/**
 * ✅ Trigger otomatis saat pengajuan diverifikasi
 * Dipanggil dari submission.php
 */
function autoCalculateSAW($id_pengajuan)
{
    $connection = getConnection();
    $result = $connection->query("SELECT status, id_program FROM pengajuan WHERE id = " . intval($id_pengajuan));
    $data = $result->fetch_assoc();

    if ($data && $data['status'] == 'Terverifikasi' && $data['id_program']) {
        // Calculate SAW untuk program terkait
        return calculateSAW($data['id_program']);
    }

    return false;
}
