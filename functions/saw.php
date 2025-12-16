<?php
require_once(__DIR__ . "/connection.php");

// ✅ FIXED: Bobot sesuai paper (total harus 100% = 1.0)
define('KRITERIA', [
    'gaji' => [
        'nama' => 'Penghasilan',
        'type' => 'cost',
        'weight' => 0.30,  // 30%
        'satuan' => 'Rupiah'
    ],
    'status_rumah' => [
        'nama' => 'Kepemilikan Rumah',
        'type' => 'cost',
        'weight' => 0.15,  // 15%
        'satuan' => 'Kategori'
    ],
    'daya_listrik' => [
        'nama' => 'Kelistrikan',
        'type' => 'cost',
        'weight' => 0.10,  // 10%
        'satuan' => 'Status'
    ],
    'pengeluaran' => [
        'nama' => 'Pengeluaran',
        'type' => 'cost',
        'weight' => 0.20,  // 20%
        'satuan' => 'Rupiah'
    ],
    'jml_keluarga' => [
        'nama' => 'Jumlah Anggota Keluarga',
        'type' => 'benefit',
        'weight' => 0.15,  // 15%
        'satuan' => 'Orang'
    ],
    'jml_anak_sekolah' => [
        'nama' => 'Keberadaan Anak Usia Sekolah',
        'type' => 'benefit',
        'weight' => 0.10,  // 10%
        'satuan' => 'Anak'
    ]
]);

// ✅ Tabel konversi sesuai paper
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

function konversiNilaiRange($nilai, $konversiArray)
{
    foreach ($konversiArray as $range) {
        if ($nilai >= $range['min'] && $nilai <= $range['max']) {
            return $range['nilai'];
        }
    }
    return 1;
}

function konversiAnakSekolah($jumlah)
{
    if ($jumlah >= 4) {
        return 5;
    }
    return KONVERSI_ANAK_SEKOLAH[$jumlah] ?? 1;
}

/**
 * ✅ FIXED: Perhitungan SAW sesuai paper
 * Langkah:
 * 1. Konversi nilai mentah ke nilai kriteria (1-5)
 * 2. Normalisasi matriks
 * 3. Kalikan dengan bobot
 * 4. Ranking berdasarkan skor total
 */
function calculateSAW($id_program)
{
    $connection = getConnection();

    // ✅ STEP 1: Ambil data pengajuan TERVERIFIKASI dari program ini
    $query = "SELECT * FROM pengajuan 
              WHERE status = 'Terverifikasi' 
              AND id_program = " . intval($id_program);

    $result = $connection->query($query);

    if (!$result || $result->num_rows == 0) {
        error_log("SAW: No verified data for program $id_program");
        return false;
    }

    $pengajuanData = $result->fetch_all(MYSQLI_ASSOC);
    error_log("SAW: Processing " . count($pengajuanData) . " submissions for program $id_program");

    // ✅ STEP 2: Konversi nilai mentah ke nilai kriteria (1-5)
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

    // ✅ STEP 3: Hitung nilai min/max untuk normalisasi
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

    // ✅ STEP 4: Normalisasi matriks
    $dataNormalisasi = [];
    foreach ($dataTerkonversi as $data) {
        $normalized = ['id_pengajuan' => $data['id_pengajuan']];

        foreach (array_keys(KRITERIA) as $key) {
            $nilai = $data[$key];
            $kriteria = KRITERIA[$key];

            if ($kriteria['type'] == 'benefit') {
                // Benefit: nilai / nilai_max
                $normalized[$key] = $nilaiMax[$key] > 0 ? $nilai / $nilaiMax[$key] : 0;
            } else {
                // Cost: nilai_min / nilai
                $normalized[$key] = $nilai > 0 ? $nilaiMin[$key] / $nilai : 0;
            }
        }

        $dataNormalisasi[] = $normalized;
    }

    // ✅ STEP 5: Hitung skor total (Vi = Σ(wj × rij))
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
    }

    // ✅ STEP 6: Sort descending (skor tertinggi = peringkat 1)
    usort($hasilAkhir, function ($a, $b) {
        return $b['skor_total'] <=> $a['skor_total'];
    });

    // ✅ STEP 7: Assign peringkat
    foreach ($hasilAkhir as $index => &$hasil) {
        $hasil['peringkat'] = $index + 1;
    }

    // ✅ STEP 8: Simpan ke database
    // Hapus data lama untuk program ini
    $deleteQuery = "DELETE tn FROM total_nilai tn 
                   JOIN pengajuan p ON tn.id_pengajuan = p.id 
                   WHERE p.id_program = " . intval($id_program);
    $connection->query($deleteQuery);

    // Insert data baru
    $connection->begin_transaction();

    try {
        foreach ($hasilAkhir as $hasil) {
            $id_pengajuan = intval($hasil['id_pengajuan']);
            $skor_total = floatval($hasil['skor_total']);
            $peringkat = intval($hasil['peringkat']);

            $insertQuery = "INSERT INTO total_nilai (id_pengajuan, skor_total, peringkat, tanggal_hitung) 
                           VALUES ($id_pengajuan, $skor_total, $peringkat, NOW())";
            $connection->query($insertQuery);
        }

        $connection->commit();
        error_log("SAW Calculation SUCCESS - Program $id_program - " . count($hasilAkhir) . " records");
        return true;
    } catch (Exception $e) {
        $connection->rollback();
        error_log("SAW Calculation FAILED - " . $e->getMessage());
        return false;
    }
}


function getRankingByProgram($id_program, $limit = null, $onlyWithinKuota = false)
{
    $connection = getConnection();

    $query = "SELECT p.nama_lengkap, p.nik, p.no_hp, tn.skor_total, tn.peringkat, p.id, pb.kuota
              FROM total_nilai tn
              JOIN pengajuan p ON tn.id_pengajuan = p.id
              JOIN program_bantuan pb ON p.id_program = pb.id
              WHERE p.status = 'Terverifikasi' AND p.id_program = " . intval($id_program) . "
              ORDER BY tn.peringkat ASC";

    if ($limit) {
        $query .= " LIMIT " . intval($limit);
    }

    $result = $connection->query($query);
    $rankings = $result->fetch_all(MYSQLI_ASSOC);

    // ✅ Filter jika hanya yang masuk kuota
    if ($onlyWithinKuota && !empty($rankings)) {
        $kuota = $rankings[0]['kuota'];
        $rankings = array_filter($rankings, function ($r) use ($kuota) {
            return $r['peringkat'] <= $kuota;
        });
    }

    return $rankings;
}

/**
 * ✅ Get user ranking untuk program tertentu
 */
function getUserRankingByProgram($id_user, $id_program)
{
    $connection = getConnection();

    $query = "SELECT tn.skor_total, tn.peringkat, p.status, pb.kuota,
                     CASE WHEN tn.peringkat <= pb.kuota THEN 1 ELSE 0 END as is_penerima
              FROM total_nilai tn
              JOIN pengajuan p ON tn.id_pengajuan = p.id
              JOIN program_bantuan pb ON p.id_program = pb.id
              WHERE p.id_user = " . intval($id_user) . " 
              AND p.status = 'Terverifikasi'
              AND p.id_program = " . intval($id_program);

    $result = $connection->query($query);
    return $result->fetch_assoc();
}

/**
 * ✅ Get SAW statistics per program
 */
function getSAWStatisticsByProgram($id_program)
{
    $connection = getConnection();

    $stats = [];

    // Total peserta
    $result = $connection->query("SELECT COUNT(*) as total FROM total_nilai tn 
                                  JOIN pengajuan p ON tn.id_pengajuan = p.id
                                  WHERE p.id_program = " . intval($id_program));
    $stats['total_peserta'] = $result->fetch_assoc()['total'];

    if ($stats['total_peserta'] == 0) {
        return [
            'total_peserta' => 0,
            'skor_tertinggi' => 0,
            'skor_terendah' => 0,
            'rata_rata' => 0
        ];
    }

    // Skor tertinggi
    $result = $connection->query("SELECT MAX(tn.skor_total) as max_skor FROM total_nilai tn 
                                  JOIN pengajuan p ON tn.id_pengajuan = p.id
                                  WHERE p.id_program = " . intval($id_program));
    $stats['skor_tertinggi'] = $result->fetch_assoc()['max_skor'] ?? 0;

    // Skor terendah
    $result = $connection->query("SELECT MIN(tn.skor_total) as min_skor FROM total_nilai tn 
                                  JOIN pengajuan p ON tn.id_pengajuan = p.id
                                  WHERE p.id_program = " . intval($id_program));
    $stats['skor_terendah'] = $result->fetch_assoc()['min_skor'] ?? 0;

    // Rata-rata skor
    $result = $connection->query("SELECT AVG(tn.skor_total) as avg_skor FROM total_nilai tn 
                                  JOIN pengajuan p ON tn.id_pengajuan = p.id
                                  WHERE p.id_program = " . intval($id_program));
    $stats['rata_rata'] = $result->fetch_assoc()['avg_skor'] ?? 0;

    return $stats;
}

/**
 * ✅ Get detail nilai kriteria untuk satu pengajuan
 */
function getKriteriaNilai($id_pengajuan)
{
    $connection = getConnection();

    $query = "SELECT * FROM pengajuan WHERE id = " . intval($id_pengajuan);
    $result = $connection->query($query);
    $data = $result->fetch_assoc();

    if (!$data) {
        return null;
    }

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
