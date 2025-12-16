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
    $status = "Aktif";

    // ✅ ADDED: Validasi hanya boleh 1 program aktif
    if ($status == 'Aktif') {
        $checkActive = $connection->query("SELECT COUNT(*) as total FROM program_bantuan WHERE status = 'Aktif'");
        $row = $checkActive->fetch_assoc();
        
        if ($row['total'] > 0) {
            echo "<script>alert('Tidak dapat membuat program aktif! Sudah ada program aktif yang sedang berjalan. Tutup program aktif terlebih dahulu.');</script>";
            return false;
        }
    }

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

    // ✅ ADDED: Validasi hanya boleh 1 program aktif
    if ($status == 'Aktif') {
        $checkActive = $connection->query("SELECT COUNT(*) as total FROM program_bantuan WHERE status = 'Aktif' AND id != " . intval($id));
        $row = $checkActive->fetch_assoc();
        
        if ($row['total'] > 0) {
            echo "<script>alert('Tidak dapat mengaktifkan program! Sudah ada program aktif yang sedang berjalan.');</script>";
            return false;
        }
    }

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

    // ✅ ADDED: Cek apakah user sudah pernah menerima bantuan dalam 3 periode terakhir
    if (hasReceivedInLast3Periods($id_user)) {
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

// ✅ NEW FUNCTION: Check if user received aid in last 3 periods
function hasReceivedInLast3Periods($id_user)
{
    $connection = getConnection();

    // Ambil 3 program terakhir yang sudah ditutup
    $query = "SELECT id FROM program_bantuan 
              WHERE status = 'Tutup' 
              ORDER BY tanggal_selesai DESC 
              LIMIT 3";
    
    $result = $connection->query($query);
    $last3Programs = [];
    
    while ($row = $result->fetch_assoc()) {
        $last3Programs[] = $row['id'];
    }

    if (empty($last3Programs)) {
        return false; // Belum ada program yang ditutup
    }

    // Cek apakah user pernah menerima (masuk ranking sesuai kuota) di 3 program terakhir
    $programIds = implode(',', $last3Programs);
    
    $query = "SELECT COUNT(*) as total 
              FROM pengajuan p
              JOIN total_nilai tn ON p.id = tn.id_pengajuan
              JOIN program_bantuan pb ON p.id_program = pb.id
              WHERE p.id_user = " . intval($id_user) . "
              AND p.id_program IN ($programIds)
              AND p.status = 'Terverifikasi'
              AND tn.peringkat <= pb.kuota";
    
    $result = $connection->query($query);
    $row = $result->fetch_assoc();

    return $row['total'] > 0;
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