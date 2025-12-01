<?php
require_once(__DIR__ . "/connection.php");

// Get all programs
function getAllPrograms($status = null)
{
    $connection = getConnection();

    $query = "SELECT * FROM program_bantuan";
    if ($status) {
        $query .= " WHERE status = '$status'";
    }
    $query .= " ORDER BY tanggal_mulai DESC";

    $result = $connection->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Get active program
function getActiveProgram()
{
    $connection = getConnection();

    $query = "SELECT * FROM program_bantuan WHERE status = 'Aktif' ORDER BY tanggal_mulai DESC LIMIT 1";
    $result = $connection->query($query);

    return $result->fetch_assoc();
}

// Get program by ID
function getProgramById($id)
{
    $connection = getConnection();

    $query = "SELECT * FROM program_bantuan WHERE id = " . intval($id);
    $result = $connection->query($query);

    return $result->fetch_assoc();
}

// Create new program
function createProgram($data)
{
    $connection = getConnection();

    $nama = mysqli_real_escape_string($connection, $data['nama_program']);
    $deskripsi = mysqli_real_escape_string($connection, $data['deskripsi']);
    $kuota = intval($data['kuota']);
    $tanggal_mulai = mysqli_real_escape_string($connection, $data['tanggal_mulai']);
    $tanggal_selesai = mysqli_real_escape_string($connection, $data['tanggal_selesai']);
    $status = mysqli_real_escape_string($connection, $data['status']);

    $query = "INSERT INTO program_bantuan (nama_program, deskripsi, kuota, tanggal_mulai, tanggal_selesai, status) 
              VALUES ('$nama', '$deskripsi', $kuota, '$tanggal_mulai', '$tanggal_selesai', '$status')";

    return $connection->query($query);
}

// Update program
function updateProgram($id, $data)
{
    $connection = getConnection();

    $nama = mysqli_real_escape_string($connection, $data['nama_program']);
    $deskripsi = mysqli_real_escape_string($connection, $data['deskripsi']);
    $kuota = intval($data['kuota']);
    $tanggal_mulai = mysqli_real_escape_string($connection, $data['tanggal_mulai']);
    $tanggal_selesai = mysqli_real_escape_string($connection, $data['tanggal_selesai']);
    $status = mysqli_real_escape_string($connection, $data['status']);

    $query = "UPDATE program_bantuan SET 
              nama_program = '$nama',
              deskripsi = '$deskripsi',
              kuota = $kuota,
              tanggal_mulai = '$tanggal_mulai',
              tanggal_selesai = '$tanggal_selesai',
              status = '$status'
              WHERE id = " . intval($id);

    return $connection->query($query);
}

// Close program
function closeProgram($id)
{
    $connection = getConnection();

    $query = "UPDATE program_bantuan SET status = 'Tutup' WHERE id = " . intval($id);

    if ($connection->query($query)) {
        // Trigger perhitungan SAW untuk program ini
        require_once(__DIR__ . "/saw.php");
        return calculateSAW($id);
    }

    return false;
}

// Delete program
function deleteProgram($id)
{
    $connection = getConnection();

    // Cek apakah ada pengajuan yang terkait
    $check = $connection->query("SELECT COUNT(*) as total FROM pengajuan WHERE id_program = " . intval($id));
    $row = $check->fetch_assoc();

    if ($row['total'] > 0) {
        return false; // Tidak bisa hapus jika ada pengajuan
    }

    $query = "DELETE FROM program_bantuan WHERE id = " . intval($id);
    return $connection->query($query);
}

// Get program statistics
function getProgramStats($id_program)
{
    $connection = getConnection();

    $stats = [];

    // Total pengajuan
    $result = $connection->query("SELECT COUNT(*) as total FROM pengajuan WHERE id_program = " . intval($id_program));
    $stats['total_pengajuan'] = $result->fetch_assoc()['total'];

    // Menunggu verifikasi
    $result = $connection->query("SELECT COUNT(*) as total FROM pengajuan WHERE id_program = " . intval($id_program) . " AND status = 'Menunggu Verifikasi'");
    $stats['menunggu'] = $result->fetch_assoc()['total'];

    // Sedang diverifikasi
    $result = $connection->query("SELECT COUNT(*) as total FROM pengajuan WHERE id_program = " . intval($id_program) . " AND status = 'Sedang Diverifikasi'");
    $stats['diverifikasi'] = $result->fetch_assoc()['total'];

    // Terverifikasi
    $result = $connection->query("SELECT COUNT(*) as total FROM pengajuan WHERE id_program = " . intval($id_program) . " AND status = 'Terverifikasi'");
    $stats['terverifikasi'] = $result->fetch_assoc()['total'];

    // Ditolak
    $result = $connection->query("SELECT COUNT(*) as total FROM pengajuan WHERE id_program = " . intval($id_program) . " AND status = 'Ditolak'");
    $stats['ditolak'] = $result->fetch_assoc()['total'];

    return $stats;
}

// Check if user can apply to program
function canUserApplyToProgram($id_user, $id_program)
{
    $connection = getConnection();

    // Cek apakah program masih aktif
    $program = getProgramById($id_program);
    if (!$program || $program['status'] != 'Aktif') {
        return false;
    }

    // Cek apakah user sudah pernah apply ke program ini
    $query = "SELECT COUNT(*) as total FROM pengajuan 
              WHERE id_user = " . intval($id_user) . " 
              AND id_program = " . intval($id_program);

    $result = $connection->query($query);
    $row = $result->fetch_assoc();

    return $row['total'] == 0;
}

// Get program dengan jumlah penerima
function getProgramWithRecipients($id_program)
{
    $connection = getConnection();

    $query = "SELECT pb.*, 
              (SELECT COUNT(*) FROM pengajuan WHERE id_program = pb.id AND status = 'Terverifikasi') as jumlah_penerima
              FROM program_bantuan pb
              WHERE pb.id = " . intval($id_program);

    $result = $connection->query($query);
    return $result->fetch_assoc();
}
