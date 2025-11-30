<?php
require_once(__DIR__ . "/connection.php");

// Fungsi untuk membuat pengajuan baru
function createPengajuan($formData, $files)
{
    $connection = getConnection();

    $id = $_SESSION['id'];
    $nik = mysqli_real_escape_string($connection, $formData['nik']);
    $no_kk = mysqli_real_escape_string($connection, $formData['no_kk']);
    $nama_lengkap = mysqli_real_escape_string($connection, $formData['nama_lengkap']);
    $alamat = mysqli_real_escape_string($connection, $formData['alamat']);
    $no_hp = mysqli_real_escape_string($connection, $formData['no_hp']);
    $gaji = mysqli_real_escape_string($connection, $formData['gaji']);
    $status_rumah = mysqli_real_escape_string($connection, $formData['status_rumah']);
    $daya_listrik = mysqli_real_escape_string($connection, $formData['daya_listrik']);
    $pengeluaran = mysqli_real_escape_string($connection, $formData['pengeluaran']);
    $jml_keluarga = mysqli_real_escape_string($connection, $formData['jml_keluarga']);
    $jml_anak_sekolah = mysqli_real_escape_string($connection, $formData['jml_anak_sekolah']);

    // Cek apakah user sudah pernah mengajukan
    $checkQuery = "SELECT id FROM pengajuan WHERE id = '$id' AND status != 'Ditolak'";
    $checkResult = $connection->query($checkQuery);

    if ($checkResult->num_rows > 0) {
        echo "<script>alert('Anda sudah memiliki pengajuan yang sedang diproses.');</script>";
        return false;
    }

    // Insert pengajuan
    $insertQuery = "INSERT INTO pengajuan (id, nik, no_kk, nama_lengkap, alamat, no_hp, gaji, status_rumah, daya_listrik, pengeluaran, jml_keluarga, jml_anak_sekolah) 
                    VALUES ('$id', '$nik', '$no_kk', '$nama_lengkap', '$alamat', '$no_hp', '$gaji', '$status_rumah', '$daya_listrik', '$pengeluaran', '$jml_keluarga', '$jml_anak_sekolah')";

    if (!$connection->query($insertQuery)) {
        echo "<script>alert('Gagal menyimpan pengajuan.');</script>";
        return false;
    }

    $id_pengajuan = $connection->insert_id;

    // Upload dan simpan dokumen
    $uploadDir = __DIR__ . "/../uploads/dokumen/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $dokumen = [
        'ktp' => null,
        'kk' => null,
        'slip_gaji' => null,
        'foto_rumah' => null,
        'surat_keterangan_rumah' => null,
        'rekening_listrik' => null,
        'daftar_pengeluaran' => null,
        'kartu_pelajar_anak' => null
    ];

    foreach ($dokumen as $key => &$value) {
        if (isset($files[$key]) && $files[$key]['error'] === 0) {
            $fileName = time() . '_' . $key . '_' . basename($files[$key]['name']);
            $targetPath = $uploadDir . $fileName;

            if (move_uploaded_file($files[$key]['tmp_name'], $targetPath)) {
                $value = $fileName;
            }
        }
    }

    // Insert dokumen
    $insertDokumen = "INSERT INTO dokumen (id_pengajuan, ktp, kk, slip_gaji, foto_rumah, surat_keterangan_rumah, rekening_listrik, daftar_pengeluaran, kartu_pelajar_anak) 
                      VALUES ('$id_pengajuan', '{$dokumen['ktp']}', '{$dokumen['kk']}', '{$dokumen['slip_gaji']}', '{$dokumen['foto_rumah']}', '{$dokumen['surat_keterangan_rumah']}', '{$dokumen['rekening_listrik']}', '{$dokumen['daftar_pengeluaran']}', '{$dokumen['kartu_pelajar_anak']}')";

    if (!$connection->query($insertDokumen)) {
        echo "<script>alert('Gagal menyimpan dokumen.');</script>";
        return false;
    }

    return $id_pengajuan;
}

// Fungsi untuk mendapatkan pengajuan user
function getUserPengajuan($id)
{
    $connection = getConnection();

    $query = "SELECT p.*, d.* 
              FROM pengajuan p 
              LEFT JOIN dokumen d ON p.id = d.id_pengajuan 
              WHERE p.id = '$id' 
              ORDER BY p.tanggal_dibuat DESC";

    $result = $connection->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Fungsi untuk mendapatkan detail pengajuan
function getPengajuanDetail($id_pengajuan)
{
    $connection = getConnection();

    $query = "SELECT p.*, d.*, u.nama as nama_user, v.status as status_verifikasi, v.catatan as catatan_verifikasi, v.tanggal as tanggal_verifikasi
              FROM pengajuan p 
              LEFT JOIN dokumen d ON p.id = d.id_pengajuan 
              LEFT JOIN user u ON p.id = u.id
              LEFT JOIN verifikasi v ON p.id = v.id_pengajuan
              WHERE p.id = '$id_pengajuan'";

    $result = $connection->query($query);
    return $result->fetch_assoc();
}

// Fungsi untuk mendapatkan status pengajuan
function getPengajuanStatus($id)
{
    $connection = getConnection();

    $query = "SELECT id, status, tanggal_dibuat FROM pengajuan WHERE id = '$id' ORDER BY tanggal_dibuat DESC LIMIT 1";

    $result = $connection->query($query);
    return $result->fetch_assoc();
}

// Fungsi untuk mendapatkan ranking
function getRanking($limit = null)
{
    $connection = getConnection();

    $query = "SELECT p.nama_lengkap, p.nik, tn.skor_total, tn.peringkat, p.status
              FROM total_nilai tn
              JOIN pengajuan p ON tn.id_pengajuan = p.id
              WHERE p.status = 'Terverifikasi'
              ORDER BY tn.peringkat ASC";

    if ($limit) {
        $query .= " LIMIT $limit";
    }

    $result = $connection->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Fungsi untuk mendapatkan ranking user tertentu
function getUserRanking($id)
{
    $connection = getConnection();

    $query = "SELECT tn.skor_total, tn.peringkat, p.status
              FROM total_nilai tn
              JOIN pengajuan p ON tn.id_pengajuan = p.id
              WHERE p.id = '$id' AND p.status = 'Terverifikasi'
              ORDER BY tn.peringkat ASC
              LIMIT 1";

    $result = $connection->query($query);
    return $result->fetch_assoc();
}

// Fungsi untuk mendapatkan statistik dashboard user
function getUserDashboardStats($id)
{
    $connection = getConnection();

    $stats = [];

    // Total pengajuan
    $query = "SELECT COUNT(*) as total FROM pengajuan WHERE id = '$id'";
    $result = $connection->query($query);
    $stats['total_pengajuan'] = $result->fetch_assoc()['total'];

    // Status terkini
    $query = "SELECT status FROM pengajuan WHERE id = '$id' ORDER BY tanggal_dibuat DESC LIMIT 1";
    $result = $connection->query($query);
    $row = $result->fetch_assoc();
    $stats['status_terkini'] = $row ? $row['status'] : 'Belum Ada';

    // Ranking (jika ada)
    $ranking = getUserRanking($id);
    $stats['peringkat'] = $ranking ? $ranking['peringkat'] : null;
    $stats['skor'] = $ranking ? $ranking['skor_total'] : null;

    return $stats;
}

// Fungsi untuk validasi NIK
function validateNIK($nik)
{
    return preg_match('/^[0-9]{16}$/', $nik);
}

// Fungsi untuk validasi nomor HP
function validatePhoneNumber($phone)
{
    return preg_match('/^[0-9]{10,15}$/', $phone);
}
