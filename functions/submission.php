<?php
require_once(__DIR__ . "/connection.php");
require_once(__DIR__ . "/saw.php");
require_once(__DIR__ . "/program.php"); // ✅ Wajib load ini agar bisa cek validasi program

function ensureUploadDirectory($id_user = null)
{
    $baseDir = __DIR__ . "/../uploads/dokumen/";

    // Buat base directory jika belum ada
    if (!is_dir($baseDir)) {
        mkdir($baseDir, 0755, true);

        // Buat .htaccess untuk security (Mencegah eksekusi PHP di folder upload)
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

    $id = $_SESSION['id']; // Pastikan session sudah start di file yang memanggil ini
    $id_program = isset($formData['id_program']) ? intval($formData['id_program']) : null;

    // Sanitasi Input
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

    // 1. Validasi ID Program
    if (!$id_program) {
        echo "<script>alert('Program tidak valid.');</script>";
        return false;
    }

    // 2. Validasi Status Program
    $program = getProgramById($id_program);
    if (!$program || $program['status'] != 'Aktif') {
        echo "<script>alert('Program tidak aktif atau sudah ditutup.');</script>";
        return false;
    }

    // ✅ 3. Validasi Syarat Program (3 Periode Terakhir & Double Apply)
    // Kita gunakan fungsi canUserApplyToProgram yang ada di functions/program.php
    // Ini penting agar logika validasi terpusat dan konsisten
    if (!canUserApplyToProgram($id, $id_program)) {
        // Pesan detailnya sebenarnya bisa dihandle di UI, tapi ini double protection
        echo "<script>alert('Anda tidak dapat mendaftar. Kemungkinan karena Anda sudah pernah menerima bantuan baru-baru ini atau sudah mendaftar di program ini.');</script>";
        return false;
    }

    // Insert pengajuan
    $insertQuery = "INSERT INTO pengajuan (id_user, id_program, nik, no_kk, nama_lengkap, alamat, no_hp, gaji, status_rumah, daya_listrik, pengeluaran, jml_keluarga, jml_anak_sekolah) 
                    VALUES ('$id', $id_program, '$nik', '$no_kk', '$nama_lengkap', '$alamat', '$no_hp', '$gaji', '$status_rumah', '$daya_listrik', '$pengeluaran', '$jml_keluarga', '$jml_anak_sekolah')";

    if (!$connection->query($insertQuery)) {
        echo "<script>alert('Gagal menyimpan pengajuan: " . $connection->error . "');</script>";
        return false;
    }

    $id_pengajuan = $connection->insert_id;

    // Upload Dokumen
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
            $fileName = generateSafeFilename($key, $files[$key]['name']);

            if ($fileName === false) {
                continue;
            } // Skip invalid extension
            if ($files[$key]['size'] > 2 * 1024 * 1024) {
                continue;
            } // Skip too big

            $targetPath = $uploadDir . $fileName;

            // Hapus file lama jika ada (overwrite)
            if (file_exists($targetPath)) {
                unlink($targetPath);
            }

            if (move_uploaded_file($files[$key]['tmp_name'], $targetPath)) {
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
        echo "<script>alert('Pengajuan dibuat tapi gagal menyimpan dokumen.');</script>";
        return false;
    }

    return $id_pengajuan;
}

function getDocumentPath($relativePath)
{
    if (empty($relativePath)) return null;
    $baseDir = __DIR__ . "/../uploads/dokumen/";
    return $baseDir . $relativePath;
}

function documentExists($relativePath)
{
    if (empty($relativePath)) return false;
    $fullPath = getDocumentPath($relativePath);
    return file_exists($fullPath);
}

function updatePengajuanStatus($id_pengajuan, $new_status, $id_petugas = null, $catatan = null)
{
    $connection = getConnection();

    // Ambil info program dulu sebelum update
    $query = "SELECT id_program FROM pengajuan WHERE id = " . intval($id_pengajuan);
    $result = $connection->query($query);
    $pengajuan = $result->fetch_assoc();

    // Update status pengajuan
    $updateQuery = "UPDATE pengajuan SET status = '$new_status' WHERE id = " . intval($id_pengajuan);
    $connection->query($updateQuery);

    // Insert Log Verifikasi
    if (($new_status == 'Terverifikasi' || $new_status == 'Ditolak') && $id_petugas) {
        $status_verifikasi = $new_status == 'Terverifikasi' ? 'Layak' : 'Tidak Layak';
        $catatan_escaped = mysqli_real_escape_string($connection, $catatan);

        $insertVerifikasi = "INSERT INTO verifikasi (id_pengajuan, id_petugas, status, catatan) 
                             VALUES (" . intval($id_pengajuan) . ", " . intval($id_petugas) . ", '$status_verifikasi', '$catatan_escaped')";
        $connection->query($insertVerifikasi);

        // ✅ AUTO-CALCULATE SAW jika Terverifikasi
        // Agar ranking selalu update real-time setiap admin memverifikasi berkas
        if ($new_status == 'Terverifikasi' && $pengajuan && $pengajuan['id_program']) {
            require_once(__DIR__ . "/saw.php");
            calculateSAW($pengajuan['id_program']);
        }
    }

    return true;
}

function getUserPengajuan($id_user)
{
    $connection = getConnection();
    $query = "SELECT p.*, d.*, pb.nama_program 
              FROM pengajuan p 
              LEFT JOIN dokumen d ON p.id = d.id_pengajuan 
              LEFT JOIN program_bantuan pb ON p.id_program = pb.id
              WHERE p.id_user = '$id_user' 
              ORDER BY p.tanggal_dibuat DESC";
    $result = $connection->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getPengajuanDetail($id_pengajuan)
{
    $connection = getConnection();
    $query = "SELECT p.*, d.*, u.nama as nama_user, v.status as status_verifikasi, v.catatan as catatan_verifikasi, v.tanggal as tanggal_verifikasi, pb.nama_program
              FROM pengajuan p 
              LEFT JOIN dokumen d ON p.id = d.id_pengajuan 
              LEFT JOIN user u ON p.id_user = u.id
              LEFT JOIN verifikasi v ON p.id = v.id_pengajuan
              LEFT JOIN program_bantuan pb ON p.id_program = pb.id
              WHERE p.id = " . intval($id_pengajuan);
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

// ✅ FIXED: Hapus query manual (legacy), gunakan fungsi terpusat dari SAW.php
// Agar jika rumus berubah, tidak perlu ubah di banyak tempat.
function getRanking($id_program = null, $limit = null)
{
    if ($id_program) {
        return getRankingByProgram($id_program, $limit);
    }
    return []; // Ranking hanya valid jika ada ID Program
}

// ✅ FIXED: Hapus query manual, gunakan fungsi terpusat
function getUserRanking($id_user, $id_program = null)
{
    // Jika ID program tidak ada, coba cari program terakhir user yang terverifikasi
    if (!$id_program) {
        $connection = getConnection();
        $q = "SELECT id_program FROM pengajuan WHERE id_user = $id_user AND status = 'Terverifikasi' ORDER BY tanggal_dibuat DESC LIMIT 1";
        $r = $connection->query($q);
        if ($row = $r->fetch_assoc()) {
            $id_program = $row['id_program'];
        }
    }

    if ($id_program) {
        // Ambil ranking user di program tersebut (menggunakan fungsi saw.php yang sudah fix duplikasi)
        // Kita perlu membuat fungsi kecil ini di saw.php atau melakukan query spesifik disini
        // Agar aman, kita query langsung ke tabel total_nilai yang sudah bersih
        $connection = getConnection();
        $query = "SELECT tn.skor_total, tn.peringkat, p.status, pb.kuota
                  FROM total_nilai tn
                  JOIN pengajuan p ON tn.id_pengajuan = p.id
                  JOIN program_bantuan pb ON p.id_program = pb.id
                  WHERE p.id_user = " . intval($id_user) . " 
                  AND p.id_program = " . intval($id_program);

        $result = $connection->query($query);
        return $result->fetch_assoc();
    }

    return null;
}

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

    // Ranking (Ambil dari program terakhir)
    $ranking = getUserRanking($id_user);
    $stats['peringkat'] = $ranking ? $ranking['peringkat'] : null;
    $stats['skor'] = $ranking ? $ranking['skor_total'] : null;

    return $stats;
}

function getAllPengajuan($status = null)
{
    $connection = getConnection();

    $query = "SELECT p.*, u.nama as nama_user, pb.nama_program 
              FROM pengajuan p 
              JOIN user u ON p.id_user = u.id
              LEFT JOIN program_bantuan pb ON p.id_program = pb.id";

    if ($status) {
        $query .= " WHERE p.status = '$status'";
    }

    $query .= " ORDER BY p.tanggal_dibuat DESC";

    $result = $connection->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function validateNIK($nik)
{
    return preg_match('/^[0-9]{16}$/', $nik);
}

function validatePhoneNumber($phone)
{
    return preg_match('/^[0-9]{10,15}$/', $phone);
}
