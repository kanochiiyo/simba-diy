<?php
require_once(__DIR__ . "/connection.php");
require_once(__DIR__ . "/saw.php");

function ensureUploadDirectory($id_user = null)
{
    $baseDir = __DIR__ . "/../uploads/dokumen/";

    // Buat base directory jika belum ada
    if (!is_dir($baseDir)) {
        mkdir($baseDir, 0755, true);

        // Buat .htaccess untuk security
        $htaccess = $baseDir . ".htaccess";
        file_put_contents($htaccess, "Options -Indexes\nRequire all denied\n<FilesMatch \"\\.(jpg|jpeg|png|pdf)$\">\n    Require all granted\n</FilesMatch>");

        // Buat index.php untuk mencegah directory listing
        $index = $baseDir . "index.php";
        file_put_contents($index, "<?php header('HTTP/1.0 403 Forbidden'); exit; ?>");
    }

    // Jika ada ID user, buat folder khusus untuk user tersebut
    if ($id_user) {
        $userDir = $baseDir . "user_" . $id_user . "/";
        if (!is_dir($userDir)) {
            mkdir($userDir, 0755, true);
        }
        return $userDir;
    }

    return $baseDir;
}

function generateSafeFilename($fieldName, $originalName)
{
    // Ambil extension
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    // Validasi extension
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
    if (!in_array($extension, $allowedExtensions)) {
        return false;
    }

    return $fieldName . '.' . $extension;
}

function createPengajuan($formData, $files)
{
    $connection = getConnection();

    $id = $_SESSION['id'];
    $id_program = isset($formData['id_program']) ? intval($formData['id_program']) : null; // ADDED
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

    // MODIFIED: Cek apakah program valid dan aktif
    if (!$id_program) {
        echo "<script>alert('Program tidak valid.');</script>";
        return false;
    }

    require_once(__DIR__ . "/program.php");
    $program = getProgramById($id_program);
    if (!$program || $program['status'] != 'Aktif') {
        echo "<script>alert('Program tidak aktif atau tidak ditemukan.');</script>";
        return false;
    }

    // Cek apakah user sudah pernah mengajukan untuk program ini
    $checkQuery = "SELECT id FROM pengajuan 
                   WHERE id_user = '$id' 
                   AND id_program = $id_program 
                   AND status != 'Ditolak'";
    $checkResult = $connection->query($checkQuery);

    if ($checkResult->num_rows > 0) {
        echo "<script>alert('Anda sudah memiliki pengajuan yang sedang diproses untuk program ini.');</script>";
        return false;
    }

    // MODIFIED: Insert pengajuan dengan id_program
    $insertQuery = "INSERT INTO pengajuan (id_user, id_program, nik, no_kk, nama_lengkap, alamat, no_hp, gaji, status_rumah, daya_listrik, pengeluaran, jml_keluarga, jml_anak_sekolah) 
                    VALUES ('$id', $id_program, '$nik', '$no_kk', '$nama_lengkap', '$alamat', '$no_hp', '$gaji', '$status_rumah', '$daya_listrik', '$pengeluaran', '$jml_keluarga', '$jml_anak_sekolah')";

    if (!$connection->query($insertQuery)) {
        echo "<script>alert('Gagal menyimpan pengajuan.');</script>";
        return false;
    }

    $id_pengajuan = $connection->insert_id;

    // Buat folder khusus untuk user ini
    $uploadDir = ensureUploadDirectory($id);

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
            // Generate nama file yang aman
            $fileName = generateSafeFilename($key, $files[$key]['name']);

            if ($fileName === false) {
                echo "<script>alert('Format file $key tidak valid. Hanya JPG, PNG, dan PDF yang diizinkan.');</script>";
                continue;
            }

            // Validasi ukuran file (max 2MB)
            if ($files[$key]['size'] > 2 * 1024 * 1024) {
                echo "<script>alert('Ukuran file $key terlalu besar. Maksimal 2MB.');</script>";
                continue;
            }

            $targetPath = $uploadDir . $fileName;

            // Hapus file lama jika ada (untuk replace)
            if (file_exists($targetPath)) {
                unlink($targetPath);
            }

            if (move_uploaded_file($files[$key]['tmp_name'], $targetPath)) {
                // Simpan path relatif ke database (untuk portabilitas)
                $value = "user_" . $id . "/" . $fileName;
            }
        }
    }

    // Insert dokumen
    $insertDokumen = "INSERT INTO dokumen (id_pengajuan, ktp, kk, slip_gaji, foto_rumah, surat_keterangan_rumah, rekening_listrik, daftar_pengeluaran, kartu_pelajar_anak) 
                      VALUES ('$id_pengajuan', " .
        "'" . ($dokumen['ktp'] ?? '') . "', " .
        "'" . ($dokumen['kk'] ?? '') . "', " .
        "'" . ($dokumen['slip_gaji'] ?? '') . "', " .
        "'" . ($dokumen['foto_rumah'] ?? '') . "', " .
        "'" . ($dokumen['surat_keterangan_rumah'] ?? '') . "', " .
        "'" . ($dokumen['rekening_listrik'] ?? '') . "', " .
        "'" . ($dokumen['daftar_pengeluaran'] ?? '') . "', " .
        "'" . ($dokumen['kartu_pelajar_anak'] ?? '') . "')";

    if (!$connection->query($insertDokumen)) {
        echo "<script>alert('Gagal menyimpan dokumen.');</script>";
        return false;
    }

    return $id_pengajuan;
}

function getDocumentPath($relativePath)
{
    if (empty($relativePath)) {
        return null;
    }

    $baseDir = __DIR__ . "/../uploads/dokumen/";
    return $baseDir . $relativePath;
}

function documentExists($relativePath)
{
    if (empty($relativePath)) {
        return false;
    }

    $fullPath = getDocumentPath($relativePath);
    return file_exists($fullPath);
}

// ✅ PERBAIKAN: Ganti function updatePengajuanStatus di submission.php

function updatePengajuanStatus($id_pengajuan, $new_status, $id_petugas = null, $catatan = null)
{
    $connection = getConnection();

    // ✅ AMBIL DATA PENGAJUAN DULU
    $query = "SELECT id_program FROM pengajuan WHERE id = " . intval($id_pengajuan);
    $result = $connection->query($query);
    $pengajuan = $result->fetch_assoc();

    // Update status pengajuan
    $updateQuery = "UPDATE pengajuan SET status = '$new_status' WHERE id = " . intval($id_pengajuan);
    $connection->query($updateQuery);

    // Jika status berubah menjadi Terverifikasi atau Ditolak, simpan ke tabel verifikasi
    if (($new_status == 'Terverifikasi' || $new_status == 'Ditolak') && $id_petugas) {
        $status_verifikasi = $new_status == 'Terverifikasi' ? 'Layak' : 'Tidak Layak';
        $catatan_escaped = mysqli_real_escape_string($connection, $catatan);

        $insertVerifikasi = "INSERT INTO verifikasi (id_pengajuan, id_petugas, status, catatan) 
                            VALUES (" . intval($id_pengajuan) . ", " . intval($id_petugas) . ", '$status_verifikasi', '$catatan_escaped')";
        $connection->query($insertVerifikasi);

        // ✅ FIXED: Jika terverifikasi, trigger perhitungan SAW untuk PROGRAM SPESIFIK
        if ($new_status == 'Terverifikasi' && $pengajuan && $pengajuan['id_program']) {
            require_once(__DIR__ . "/saw.php");

            // Hitung SAW untuk program ini saja
            $sawResult = calculateSAW($pengajuan['id_program']);

            if ($sawResult) {
                error_log("SAW auto-calculation SUCCESS for program ID: " . $pengajuan['id_program']);
            } else {
                error_log("SAW auto-calculation FAILED for program ID: " . $pengajuan['id_program']);
            }
        }
    }

    return true;
}

// Fungsi untuk mendapatkan pengajuan user
function getUserPengajuan($id_user)
{
    $connection = getConnection();

    $query = "SELECT p.*, d.* 
              FROM pengajuan p 
              LEFT JOIN dokumen d ON p.id = d.id_pengajuan 
              WHERE p.id_user = '$id_user' 
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
              LEFT JOIN user u ON p.id_user = u.id
              LEFT JOIN verifikasi v ON p.id = v.id_pengajuan
              WHERE p.id = '$id_pengajuan'";

    $result = $connection->query($query);
    return $result->fetch_assoc();
}

function getPengajuanStatus($id_user, $id_program = null)
{
    $connection = getConnection();

    $query = "SELECT id, id_program, status, tanggal_dibuat FROM pengajuan WHERE id_user = '$id_user'";
    if ($id_program) {
        $query .= " AND id_program = " . intval($id_program);
    }
    $query .= " ORDER BY tanggal_dibuat DESC LIMIT 1";

    $result = $connection->query($query);
    return $result->fetch_assoc();
}

// Fungsi untuk mendapatkan ranking
function getRanking($id_program = null, $limit = null)
{
    $connection = getConnection();

    $query = "SELECT p.nama_lengkap, p.nik, tn.skor_total, tn.peringkat, p.status, p.id_program, p.id
              FROM total_nilai tn
              JOIN pengajuan p ON tn.id_pengajuan = p.id
              WHERE p.status = 'Terverifikasi'";

    if ($id_program) {
        $query .= " AND p.id_program = " . intval($id_program);
    }

    $query .= " ORDER BY tn.peringkat ASC";

    if ($limit) {
        $query .= " LIMIT $limit";
    }

    $result = $connection->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Fungsi untuk mendapatkan ranking user tertentu
function getUserRanking($id_user, $id_program = null)
{
    $connection = getConnection();

    $query = "SELECT tn.skor_total, tn.peringkat, p.status, p.id_program
              FROM total_nilai tn
              JOIN pengajuan p ON tn.id_pengajuan = p.id
              WHERE p.id_user = '$id_user' AND p.status = 'Terverifikasi'";

    if ($id_program) {
        $query .= " AND p.id_program = " . intval($id_program);
    }

    $query .= " ORDER BY tn.peringkat ASC LIMIT 1";

    $result = $connection->query($query);
    return $result->fetch_assoc();
}

// Fungsi untuk mendapatkan statistik dashboard user
function getUserDashboardStats($id_user)
{
    $connection = getConnection();

    $stats = [];

    // Total pengajuan
    $query = "SELECT COUNT(*) as total FROM pengajuan WHERE id_user = '$id_user'";
    $result = $connection->query($query);
    $stats['total_pengajuan'] = $result->fetch_assoc()['total'];

    // Status terkini
    $query = "SELECT status FROM pengajuan WHERE id_user = '$id_user' ORDER BY tanggal_dibuat DESC LIMIT 1";
    $result = $connection->query($query);
    $row = $result->fetch_assoc();
    $stats['status_terkini'] = $row ? $row['status'] : 'Belum Ada';

    // Ranking (jika ada)
    $ranking = getUserRanking($id_user);
    $stats['peringkat'] = $ranking ? $ranking['peringkat'] : null;
    $stats['skor'] = $ranking ? $ranking['skor_total'] : null;

    return $stats;
}

// Fungsi untuk mendapatkan semua pengajuan (untuk admin)
function getAllPengajuan($status = null)
{
    $connection = getConnection();

    $query = "SELECT p.*, u.nama as nama_user 
              FROM pengajuan p 
              JOIN user u ON p.id_user = u.id";

    if ($status) {
        $query .= " WHERE p.status = '$status'";
    }

    $query .= " ORDER BY p.tanggal_dibuat DESC";

    $result = $connection->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
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

