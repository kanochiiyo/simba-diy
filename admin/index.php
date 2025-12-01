<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once(__DIR__ . "/../functions/authentication.php");
require_once(__DIR__ . "/../functions/submission.php");
require_once(__DIR__ . "/../functions/program.php");

if (!isLogged() || !isAdmin()) {
    header("Location: ../auth/login.php");
    exit;
}

$connection = getConnection();

// Get statistics
$stats = [];

// Total program
$result = $connection->query("SELECT COUNT(*) as total FROM program_bantuan");
$stats['total_program'] = $result->fetch_assoc()['total'];

// Program aktif
$result = $connection->query("SELECT COUNT(*) as total FROM program_bantuan WHERE status = 'Aktif'");
$stats['program_aktif'] = $result->fetch_assoc()['total'];

// Total pengajuan
$result = $connection->query("SELECT COUNT(*) as total FROM pengajuan");
$stats['total_pengajuan'] = $result->fetch_assoc()['total'];

// Menunggu verifikasi
$result = $connection->query("SELECT COUNT(*) as total FROM pengajuan WHERE status = 'Menunggu Verifikasi'");
$stats['menunggu_verifikasi'] = $result->fetch_assoc()['total'];

// Terverifikasi
$result = $connection->query("SELECT COUNT(*) as total FROM pengajuan WHERE status = 'Terverifikasi'");
$stats['terverifikasi'] = $result->fetch_assoc()['total'];

// Total user
$result = $connection->query("SELECT COUNT(*) as total FROM user WHERE role = 'user'");
$stats['total_user'] = $result->fetch_assoc()['total'];

// Get active program
$activeProgram = getActiveProgram();

// Get recent submissions (last 5)
$recentSubmissions = $connection->query("
    SELECT p.*, u.nama as nama_user 
    FROM pengajuan p 
    JOIN user u ON p.id_user = u.id 
    ORDER BY p.tanggal_dibuat DESC 
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <title>Dashboard Admin - SIMBA DIY</title>
</head>

<body>
    <div class="dashboard-wrapper">
        <?php include('../templates/admin_sidebar.php'); ?>

        <div class="main-content" style="background-color: #f0f9ff;">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title" style="color: #1e40af;">
                    <i class="fas fa-tachometer-alt" style="color: #2563eb;"></i> Dashboard Administrator
                </h1>
                <p class="page-subtitle">Kelola program bantuan dan verifikasi pengajuan masyarakat</p>
            </div>

            <!-- Alert Welcome -->
            <div class="alert" style="background-color: #dbeafe; border-color: #93c5fd; color: #1e40af;">
                <i class="fas fa-info-circle"></i>
                <div>
                    <strong>Selamat Datang, Admin!</strong>
                    <p style="margin: 5px 0 0 0;">Panel administrator untuk mengelola sistem bantuan sosial SIMBA DIY.</p>
                </div>
            </div>

            <!-- Program Aktif Banner -->
            <?php if ($activeProgram): ?>
                <div class="form-card" style="background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%); color: white; border: none;">
                    <div class="row align-items-center">
                        <div class="col-lg-8">
                            <div style="display: flex; align-items: start; gap: 20px;">
                                <div style="width: 64px; height: 64px; background: rgba(255, 255, 255, 0.2); border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 28px; flex-shrink: 0;">
                                    <i class="fas fa-bullhorn"></i>
                                </div>
                                <div>
                                    <h3 style="font-size: 22px; font-weight: 700; margin-bottom: 8px;">Program Aktif Saat Ini</h3>
                                    <h4 style="font-size: 18px; font-weight: 600; margin-bottom: 12px; opacity: 0.9;">
                                        <?php echo htmlspecialchars($activeProgram['nama_program']); ?>
                                    </h4>
                                    <div style="display: flex; gap: 24px; flex-wrap: wrap;">
                                        <div>
                                            <div style="font-size: 12px; opacity: 0.8; margin-bottom: 4px;">Periode</div>
                                            <div style="font-size: 14px; font-weight: 600;">
                                                <?php echo date('d M Y', strtotime($activeProgram['tanggal_mulai'])); ?> -
                                                <?php echo date('d M Y', strtotime($activeProgram['tanggal_selesai'])); ?>
                                            </div>
                                        </div>
                                        <div>
                                            <div style="font-size: 12px; opacity: 0.8; margin-bottom: 4px;">Kuota</div>
                                            <div style="font-size: 14px; font-weight: 600;"><?php echo $activeProgram['kuota']; ?> Penerima</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4" style="text-align: right;">
                            <a href="manage_programs.php" class="btn" style="background-color: white; color: #1e40af; padding: 12px 24px; border-radius: 10px; font-weight: 600;">
                                <i class="fas fa-cog"></i> Kelola Program
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <strong>Tidak Ada Program Aktif</strong>
                        <p style="margin: 5px 0 0 0;">Belum ada program bantuan yang aktif. <a href="manage_programs.php" style="color: #92400e; font-weight: 600;">Buat program baru</a></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Dashboard Cards -->
            <div class="dashboard-cards">
                <div class="dashboard-card" style="border-left: 4px solid #2563eb;">
                    <div class="card-icon" style="background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); color: #1e40af;">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div class="card-title">Total Program</div>
                    <div class="card-value" style="color: #1e40af;"><?php echo $stats['total_program']; ?></div>
                    <div class="card-description"><?php echo $stats['program_aktif']; ?> program aktif</div>
                </div>

                <div class="dashboard-card" style="border-left: 4px solid #f59e0b;">
                    <div class="card-icon" style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); color: #92400e;">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="card-title">Menunggu Verifikasi</div>
                    <div class="card-value" style="color: #92400e;"><?php echo $stats['menunggu_verifikasi']; ?></div>
                    <div class="card-description">Pengajuan baru</div>
                </div>

                <div class="dashboard-card" style="border-left: 4px solid #10b981;">
                    <div class="card-icon" style="background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); color: #065f46;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="card-title">Terverifikasi</div>
                    <div class="card-value" style="color: #065f46;"><?php echo $stats['terverifikasi']; ?></div>
                    <div class="card-description">Pengajuan lolos</div>
                </div>

                <div class="dashboard-card" style="border-left: 4px solid #8b5cf6;">
                    <div class="card-icon" style="background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%); color: #5b21b6;">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="card-title">Total User</div>
                    <div class="card-value" style="color: #5b21b6;"><?php echo $stats['total_user']; ?></div>
                    <div class="card-description">Pengguna terdaftar</div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <a href="manage_programs.php" class="btn btn-primary w-100" style="padding: 20px; background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);">
                        <i class="fas fa-plus-circle" style="font-size: 24px;"></i>
                        <div style="margin-top: 12px;">
                            <strong style="display: block; font-size: 16px;">Buat Program Baru</strong>
                            <small style="opacity: 0.9;">Tambah program bantuan</small>
                        </div>
                    </a>
                </div>

                <div class="col-md-4">
                    <a href="manage_submissions.php" class="btn" style="padding: 20px; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; border: none; width: 100%;">
                        <i class="fas fa-file-invoice" style="font-size: 24px;"></i>
                        <div style="margin-top: 12px;">
                            <strong style="display: block; font-size: 16px;">Verifikasi Pengajuan</strong>
                            <small style="opacity: 0.9;"><?php echo $stats['menunggu_verifikasi']; ?> menunggu</small>
                        </div>
                    </a>
                </div>

                <div class="col-md-4">
                    <a href="ranking.php" class="btn" style="padding: 20px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; width: 100%;">
                        <i class="fas fa-trophy" style="font-size: 24px;"></i>
                        <div style="margin-top: 12px;">
                            <strong style="display: block; font-size: 16px;">Lihat Ranking</strong>
                            <small style="opacity: 0.9;">Hasil perhitungan SAW</small>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Recent Submissions -->
            <div class="form-card">
                <h2 class="form-section-title" style="color: #1e40af;">
                    <i class="fas fa-history" style="color: #2563eb;"></i> Pengajuan Terbaru
                </h2>

                <?php if (empty($recentSubmissions)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-inbox"></i>
                        </div>
                        <div class="empty-state-title">Belum Ada Pengajuan</div>
                        <div class="empty-state-description">Belum ada pengajuan yang masuk ke sistem</div>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nama</th>
                                    <th>NIK</th>
                                    <th>Tanggal</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentSubmissions as $sub): ?>
                                    <tr>
                                        <td><strong>#<?php echo $sub['id']; ?></strong></td>
                                        <td><?php echo htmlspecialchars($sub['nama_lengkap']); ?></td>
                                        <td><?php echo $sub['nik']; ?></td>
                                        <td><?php echo date('d M Y', strtotime($sub['tanggal_dibuat'])); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower(str_replace(' ', '', $sub['status'])); ?>">
                                                <?php echo $sub['status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="manage_submissions.php?id=<?php echo $sub['id']; ?>" class="btn btn-sm" style="background-color: #2563eb; color: white; padding: 6px 12px; border-radius: 6px; font-size: 13px;">
                                                <i class="fas fa-eye"></i> Detail
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div style="text-align: center; margin-top: 20px;">
                        <a href="manage_submissions.php" class="btn btn-secondary">
                            <i class="fas fa-list"></i> Lihat Semua Pengajuan
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>