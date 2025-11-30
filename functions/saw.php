<?php
require_once(__DIR__ . "/connection.php");

/**
 * Fungsi untuk menghitung nilai SAW untuk semua pengajuan yang terverifikasi
 */
function calculateSAW()
{
    $connection = getConnection();

    // Ambil semua pengajuan yang terverifikasi
    $query = "SELECT * FROM pengajuan WHERE status = 'Terverifikasi'";
    $result = $connection->query($query);

    if ($result->num_rows == 0) {
        return false;
    }

    $pengajuanData = $result->fetch_all(MYSQLI_ASSOC);

    // Definisi kriteria sesuai dengan penelitian
    $kriteria = [
        'gaji' => ['type' => 'cost', 'weight' => 0.25],
        'status_rumah' => ['type' => 'cost', 'weight' => 0.15],
        'daya_listrik' => ['type' => 'cost', 'weight' => 0.15],
        'pengeluaran' => ['type' => 'cost', 'weight' => 0.20],
        'jml_keluarga' => ['type' => 'benefit', 'weight' => 0.15],
        'jml_anak_sekolah' => ['type' => 'benefit', 'weight' => 0.10]
    ];

    // Konversi nilai untuk kriteria kategorikal
    $konversiStatusRumah = [
        'Menumpang' => 5,
        'Sewa' => 4,
        'Lainnya' => 3,
        'Milik Sendiri' => 1
    ];

    // Step 1: Konversi nilai dan normalisasi
    $dataNormalisasi = [];
    $nilaiMax = [];
    $nilaiMin = [];

    // Hitung nilai min dan max untuk setiap kriteria
    foreach ($kriteria as $key => $info) {
        $values = [];
        foreach ($pengajuanData as $data) {
            if ($key == 'status_rumah') {
                $nilai = $konversiStatusRumah[$data[$key]] ?? 1;
            } else {
                $nilai = floatval($data[$key]);
            }
            $values[] = $nilai;
        }

        if ($info['type'] == 'benefit') {
            $nilaiMax[$key] = max($values);
        } else {
            $nilaiMin[$key] = min($values);
        }
    }

    // Normalisasi setiap data
    foreach ($pengajuanData as $index => $data) {
        $normalized = ['id_pengajuan' => $data['id']];

        foreach ($kriteria as $key => $info) {
            if ($key == 'status_rumah') {
                $nilai = $konversiStatusRumah[$data[$key]] ?? 1;
            } else {
                $nilai = floatval($data[$key]);
            }

            // Normalisasi sesuai tipe kriteria
            if ($info['type'] == 'benefit') {
                // Untuk benefit: nilai / nilai_max
                $normalized[$key] = $nilai / $nilaiMax[$key];
            } else {
                // Untuk cost: nilai_min / nilai
                $normalized[$key] = $nilai > 0 ? $nilaiMin[$key] / $nilai : 0;
            }
        }

        $dataNormalisasi[] = $normalized;
    }

    // Step 2: Hitung skor total dengan bobot
    $hasilAkhir = [];
    foreach ($dataNormalisasi as $data) {
        $skorTotal = 0;

        foreach ($kriteria as $key => $info) {
            $skorTotal += $data[$key] * $info['weight'];
        }

        $hasilAkhir[] = [
            'id_pengajuan' => $data['id_pengajuan'],
            'skor_total' => $skorTotal
        ];
    }

    // Step 3: Sort berdasarkan skor (descending)
    usort($hasilAkhir, function ($a, $b) {
        return $b['skor_total'] <=> $a['skor_total'];
    });

    // Step 4: Assign peringkat
    foreach ($hasilAkhir as $index => &$hasil) {
        $hasil['peringkat'] = $index + 1;
    }

    // Step 5: Simpan ke database
    // Hapus data lama
    $connection->query("DELETE FROM total_nilai");

    // Insert data baru
    foreach ($hasilAkhir as $hasil) {
        $id_pengajuan = $hasil['id_pengajuan'];
        $skor_total = $hasil['skor_total'];
        $peringkat = $hasil['peringkat'];

        $connection->query("INSERT INTO total_nilai (id_pengajuan, skor_total, peringkat) 
                            VALUES ('$id_pengajuan', '$skor_total', '$peringkat')");
    }

    return true;
}

/**
 * Fungsi untuk mendapatkan detail perhitungan SAW untuk satu pengajuan
 */
function getSAWDetails($id_pengajuan)
{
    $connection = getConnection();

    $query = "SELECT tn.*, p.* 
              FROM total_nilai tn
              JOIN pengajuan p ON tn.id_pengajuan = p.id
              WHERE tn.id_pengajuan = '$id_pengajuan'";

    $result = $connection->query($query);
    return $result->fetch_assoc();
}

/**
 * Fungsi untuk mendapatkan nilai kriteria individual dari pengajuan
 */
function getKriteriaNilai($id_pengajuan)
{
    $connection = getConnection();

    $query = "SELECT * FROM pengajuan WHERE id = '$id_pengajuan'";
    $result = $connection->query($query);
    $data = $result->fetch_assoc();

    if (!$data) {
        return null;
    }

    // Konversi status rumah
    $konversiStatusRumah = [
        'Menumpang' => 5,
        'Sewa' => 4,
        'Lainnya' => 3,
        'Milik Sendiri' => 1
    ];

    return [
        'Penghasilan' => [
            'nilai' => $data['gaji'],
            'tipe' => 'Cost',
            'keterangan' => 'Rp ' . number_format($data['gaji'], 0, ',', '.')
        ],
        'Status Rumah' => [
            'nilai' => $konversiStatusRumah[$data['status_rumah']] ?? 1,
            'tipe' => 'Cost',
            'keterangan' => $data['status_rumah']
        ],
        'Daya Listrik' => [
            'nilai' => $data['daya_listrik'],
            'tipe' => 'Cost',
            'keterangan' => $data['daya_listrik'] . ' VA'
        ],
        'Pengeluaran' => [
            'nilai' => $data['pengeluaran'],
            'tipe' => 'Cost',
            'keterangan' => 'Rp ' . number_format($data['pengeluaran'], 0, ',', '.')
        ],
        'Jumlah Keluarga' => [
            'nilai' => $data['jml_keluarga'],
            'tipe' => 'Benefit',
            'keterangan' => $data['jml_keluarga'] . ' orang'
        ],
        'Anak Usia Sekolah' => [
            'nilai' => $data['jml_anak_sekolah'],
            'tipe' => 'Benefit',
            'keterangan' => $data['jml_anak_sekolah'] . ' anak'
        ]
    ];
}

/**
 * Fungsi untuk mendapatkan statistik perhitungan SAW
 */
function getSAWStatistics()
{
    $connection = getConnection();

    $stats = [];

    // Total peserta
    $result = $connection->query("SELECT COUNT(*) as total FROM total_nilai");
    $stats['total_peserta'] = $result->fetch_assoc()['total'];

    // Skor tertinggi
    $result = $connection->query("SELECT MAX(skor_total) as max_skor FROM total_nilai");
    $stats['skor_tertinggi'] = $result->fetch_assoc()['max_skor'];

    // Skor terendah
    $result = $connection->query("SELECT MIN(skor_total) as min_skor FROM total_nilai");
    $stats['skor_terendah'] = $result->fetch_assoc()['min_skor'];

    // Rata-rata skor
    $result = $connection->query("SELECT AVG(skor_total) as avg_skor FROM total_nilai");
    $stats['rata_rata'] = $result->fetch_assoc()['avg_skor'];

    return $stats;
}

/**
 * Fungsi untuk trigger perhitungan SAW ketika status pengajuan berubah menjadi Terverifikasi
 */
function autoCalculateSAW($id_pengajuan)
{
    // Cek apakah pengajuan terverifikasi
    $connection = getConnection();
    $result = $connection->query("SELECT status FROM pengajuan WHERE id = '$id_pengajuan'");
    $data = $result->fetch_assoc();

    if ($data && $data['status'] == 'Terverifikasi') {
        // Trigger recalculation untuk semua data
        return calculateSAW();
    }

    return false;
}
