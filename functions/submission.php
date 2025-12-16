<?php
require_once(__DIR__ . "/connection.php");
require_once(__DIR__ . "/saw.php");
require_once(__DIR__ . "/program.php"); // Wajib untuk validasi program

/* ======================================================
   UPLOAD HELPER
====================================================== */
function ensureUploadDirectory($id_user = null)
{
    $baseDir = __DIR__ . "/../uploads/dokumen/";

    if (!is_dir($baseDir)) {
        mkdir($baseDir, 0755, true);

        // Security
        file_put_contents(
            $baseDir . ".htaccess",
            "Options -Indexes\nRequire all denied\n<FilesMatch \"\\.(jpg|jpeg|png|pdf)$\">\nRequire all granted\n</FilesMatch>"
        );

        file_put_contents(
            $baseDir . "index.php",
            "<?php header('HTTP/1.0 403 Forbidden'); exit; ?>"
        );
    }

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
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];

    if (!in_array($extension, $allowedExtensions)) {
        return false;
    }

    return $fieldName . '.' . $extension;
}

/* ======================================================
   CREATE PENGAJUAN
====================================================== */
function createPengajuan($formData, $files)
{
    $connection = getConnection();
    $id = $_SESSION['id'];
    $id_program = isset($formData['id_program']) ? intval($formData['id_program']) : null;

    // Sanitasi
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

    // Validasi program
    if (!$id_program) {
        echo "<script>alert('Program tidak valid.');</script>";
        return false;
    }

    $program = getProgramById($id_program);
    if (!$program || $program['status'] != 'Aktif') {
        echo "<script>alert('Program tidak aktif atau sudah ditutup.');</script>";
        return false;
    }

    // Validasi 1 user = 1 pengajuan per program (sudah FINAL)
    if (!canUserApplyToProgram($id, $id_program)) {
        echo "<script>alert('Anda tidak dapat mendaftar pada program ini.');</script>";
        return false;
    }

    // Insert pengajuan
    $insertQuery = "
        INSERT INTO pengajuan
        (id_user, id_program, nik, no_kk, nama_lengkap, alamat, no_hp,
         gaji, status_rumah, daya_listrik, pengeluaran, jml_keluarga, jml_anak_sekolah)
        VALUES
        ('$id', $id_program, '$nik', '$no_kk', '$nama_lengkap', '$alamat',
         '$no_hp', '$gaji', '$status_rumah', '$daya_listrik',
         '$pengeluaran', '$jml_keluarga', '$jml_anak_sekolah')
    ";

    if (!$connection->query($insertQuery)) {
        echo "<script>alert('Gagal menyimpan pengajuan: {$connection->error}');</script>";
        return false;
    }

    $id_pengajuan = $connection->insert_id;

    /* =========================
       UPLOAD DOKUMEN
    ========================= */
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
            if ($fileName === false || $files[$key]['size'] > 2 * 1024 * 1024) {
                continue;
            }

            $targetPath = $uploadDir . $fileName;
            if (file_exists($targetPath))
                unlink($targetPath);

            if (move_uploaded_file($files[$key]['tmp_name'], $targetPath)) {
                $value = "user_" . $id . "/" . $fileName;
            }
        }
    }

    // Insert dokumen
    $insertDokumen = "
        INSERT INTO dokumen
        (id_pengajuan, ktp, kk, slip_gaji, foto_rumah,
         surat_keterangan_rumah, rekening_listrik,
         daftar_pengeluaran, kartu_pelajar_anak)
        VALUES
        ('$id_pengajuan',
         '{$dokumen['ktp']}',
         '{$dokumen['kk']}',
         '{$dokumen['slip_gaji']}',
         '{$dokumen['foto_rumah']}',
         '{$dokumen['surat_keterangan_rumah']}',
         '{$dokumen['rekening_listrik']}',
         '{$dokumen['daftar_pengeluaran']}',
         '{$dokumen['kartu_pelajar_anak']}')
    ";

    if (!$connection->query($insertDokumen)) {
        echo "<script>alert('Pengajuan tersimpan, tapi dokumen gagal.');</script>";
        return false;
    }

    return $id_pengajuan;
}

/* ======================================================
   STATUS & VERIFIKASI
====================================================== */
function updatePengajuanStatus($id_pengajuan, $new_status, $id_petugas = null, $catatan = null)
{
    $connection = getConnection();

    $q = $connection->query(
        "SELECT id_program FROM pengajuan WHERE id = " . intval($id_pengajuan)
    );
    $pengajuan = $q->fetch_assoc();

    $connection->query(
        "UPDATE pengajuan SET status = '$new_status' WHERE id = " . intval($id_pengajuan)
    );

    if (($new_status == 'Terverifikasi' || $new_status == 'Ditolak') && $id_petugas) {
        $status_verifikasi = $new_status == 'Terverifikasi' ? 'Layak' : 'Tidak Layak';
        $catatan = mysqli_real_escape_string($connection, $catatan);

        $connection->query("
            INSERT INTO verifikasi
            (id_pengajuan, id_petugas, status, catatan)
            VALUES
            (" . intval($id_pengajuan) . ",
             " . intval($id_petugas) . ",
             '$status_verifikasi',
             '$catatan')
        ");

        // 🔥 TRIGGER SAW (FINAL: TANPA DEDUP)
        if ($new_status == 'Terverifikasi' && $pengajuan) {
            calculateSAW($pengajuan['id_program']);
        }
    }

    return true;
}

/* ======================================================
   GETTER (DIPAKAI FILE LAIN)
====================================================== */
function getUserPengajuan($id_user)
{
    $connection = getConnection();
    $query = "
        SELECT p.*, d.*, pb.nama_program
        FROM pengajuan p
        LEFT JOIN dokumen d ON p.id = d.id_pengajuan
        LEFT JOIN program_bantuan pb ON p.id_program = pb.id
        WHERE p.id_user = '$id_user'
        ORDER BY p.tanggal_dibuat DESC
    ";
    return $connection->query($query)->fetch_all(MYSQLI_ASSOC);
}

function getPengajuanDetail($id_pengajuan)
{
    $connection = getConnection();
    $query = "
        SELECT p.*, d.*, u.nama as nama_user,
               v.status as status_verifikasi,
               v.catatan as catatan_verifikasi,
               v.tanggal as tanggal_verifikasi,
               pb.nama_program
        FROM pengajuan p
        LEFT JOIN dokumen d ON p.id = d.id_pengajuan
        LEFT JOIN user u ON p.id_user = u.id
        LEFT JOIN verifikasi v ON p.id = v.id_pengajuan
        LEFT JOIN program_bantuan pb ON p.id_program = pb.id
        WHERE p.id = " . intval($id_pengajuan);
    return $connection->query($query)->fetch_assoc();
}

function getPengajuanStatus($id_user, $id_program = null)
{
    $connection = getConnection();
    $query = "SELECT id, id_program, status, tanggal_dibuat FROM pengajuan WHERE id_user = '$id_user'";
    if ($id_program)
        $query .= " AND id_program = " . intval($id_program);
    $query .= " ORDER BY tanggal_dibuat DESC LIMIT 1";
    return $connection->query($query)->fetch_assoc();
}

/* ======================================================
   RANKING (DELEGATE TO SAW)
====================================================== */
function getRanking($id_program = null, $limit = null)
{
    return $id_program ? getRankingByProgram($id_program, $limit) : [];
}

function getUserRanking($id_user, $id_program = null)
{
    if (!$id_program) {
        $connection = getConnection();
        $q = $connection->query("
            SELECT id_program
            FROM pengajuan
            WHERE id_user = $id_user AND status = 'Terverifikasi'
            ORDER BY tanggal_dibuat DESC LIMIT 1
        ");
        if ($row = $q->fetch_assoc())
            $id_program = $row['id_program'];
    }

    if (!$id_program)
        return null;

    $connection = getConnection();
    $query = "
        SELECT tn.skor_total, tn.peringkat, pb.kuota
        FROM total_nilai tn
        JOIN pengajuan p ON tn.id_pengajuan = p.id
        JOIN program_bantuan pb ON tn.id_program = pb.id
        WHERE p.id_user = $id_user
          AND p.id_program = $id_program
    ";
    return $connection->query($query)->fetch_assoc();
}

function getUserDashboardStats($id_user)
{
    $connection = getConnection();

    $total = $connection->query(
        "SELECT COUNT(*) total FROM pengajuan WHERE id_user = '$id_user'"
    )->fetch_assoc()['total'];

    $latest = $connection->query(
        "SELECT status FROM pengajuan WHERE id_user = '$id_user'
         ORDER BY tanggal_dibuat DESC LIMIT 1"
    )->fetch_assoc();

    $ranking = getUserRanking($id_user);

    return [
        'total_pengajuan' => $total,
        'status_terkini' => $latest ? $latest['status'] : 'Belum Ada',
        'peringkat' => $ranking['peringkat'] ?? null,
        'skor' => $ranking['skor_total'] ?? null
    ];
}

function getAllPengajuan($status = null)
{
    $connection = getConnection();
    $query = "
        SELECT p.*, u.nama as nama_user, pb.nama_program
        FROM pengajuan p
        JOIN user u ON p.id_user = u.id
        LEFT JOIN program_bantuan pb ON p.id_program = pb.id
    ";
    if ($status)
        $query .= " WHERE p.status = '$status'";
    $query .= " ORDER BY p.tanggal_dibuat DESC";
    return $connection->query($query)->fetch_all(MYSQLI_ASSOC);
}

/* ======================================================
   VALIDATOR
====================================================== */
function validateNIK($nik)
{
    return preg_match('/^[0-9]{16}$/', $nik);
}

function validatePhoneNumber($phone)
{
    return preg_match('/^[0-9]{10,15}$/', $phone);
}
