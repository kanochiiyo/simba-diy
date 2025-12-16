<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once(__DIR__ . "/../functions/authentication.php");
require_once(__DIR__ . "/../functions/submission.php");
require_once(__DIR__ . "/../functions/program.php");

if (!isLogged() || isAdmin()) {
    header("Location: ../auth/login.php");
    exit;
}

$id_user = $_SESSION['id'];
$stats = getUserDashboardStats($id_user);
$userRanking = getUserRanking($id_user);

// ✅ FIX: Ambil pengajuan untuk SEMUA program (riwayat)
$currentPengajuan = getPengajuanStatus($id_user);
$hasRestriction = hasReceivedInLast3Periods($id_user);

// ✅ FIX: Ambil active program & cek apakah user sudah daftar di program INI
$activeProgram = getActiveProgram();
$programStats = null;
$alreadyAppliedToActiveProgram = false; // ← TAMBAHKAN VARIABEL INI

if ($activeProgram) {
    $programStats = getProgramStats($activeProgram['id']);

    // ✅ CEK APAKAH USER SUDAH DAFTAR DI PROGRAM AKTIF
    $currentActiveProgramStatus = getPengajuanStatus($id_user, $activeProgram['id']);
    $alreadyAppliedToActiveProgram = $currentActiveProgramStatus && $currentActiveProgramStatus['status'] != 'Ditolak';
}

$connection = getConnection();
$userData = $connection->query("SELECT nama FROM user WHERE id = '$id_user'")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <title>Dashboard - SIMBA DIY</title>
</head>

<body>
    <div class="dashboard-wrapper">
        <?php include('../templates/user_sidebar.php'); ?>

        <div class="main-content">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">Dashboard</h1>
                <p class="page-subtitle">Selamat datang kembali,
                    <strong><?php echo htmlspecialchars($userData['nama']); ?></strong>
                </p>
            </div>

            <!-- Alert Welcome -->
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <div>
                    <strong>Selamat Datang di SIMBA DIY</strong>
                    <p style="margin: 5px 0 0 0;">Sistem Informasi Bantuan Sosial Daerah Istimewa Yogyakarta. Kelola
                        pengajuan bantuan Anda dengan mudah dan transparan.</p>
                </div>
            </div>
            <?php if ($hasRestriction): ?>
                <div class="alert" style="background-color: #fef3c7; border-color: #fde68a; color: #92400e;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <strong>Informasi Pembatasan Periode</strong>
                        <p style="margin: 8px 0 0 0;">
                            Anda sudah menerima bantuan dalam 3 periode terakhir. Anda tidak dapat mendaftar program baru
                            sampai periode berikutnya tersedia.
                        </p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($activeProgram): ?>
                <div class="alert"
                    style="background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); border-color: #6ee7b7; color: #065f46;">
                    <i class="fas fa-bullhorn"></i>
                    <div>
                        <strong>Program Aktif: <?php echo htmlspecialchars($activeProgram['nama_program']); ?></strong>
                        <p style="margin: 8px 0 0 0;">
                            Program bantuan sedang dibuka! Periode:
                            <?php echo date('d M Y', strtotime($activeProgram['tanggal_mulai'])); ?> -
                            <?php echo date('d M Y', strtotime($activeProgram['tanggal_selesai'])); ?>.
                            <?php if (!$alreadyAppliedToActiveProgram): ?>
                                <a href="apply.php" style="color: #065f46; font-weight: 700; text-decoration: underline;">Ajukan
                                    sekarang!</a>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        <strong>Tidak Ada Program Aktif</strong>
                        <p style="margin: 8px 0 0 0;">Saat ini belum ada program bantuan yang dibuka.</p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Dashboard Cards -->
            <div class="dashboard-cards">
                <div class="dashboard-card">
                    <div class="card-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="card-title">Total Pengajuan</div>
                    <div class="card-value"><?php echo $stats['total_pengajuan']; ?></div>
                    <div class="card-description">Pengajuan yang pernah dibuat</div>
                </div>

                <div class="dashboard-card">
                    <div class="card-icon">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <div class="card-title">Status Terkini</div>
                    <div class="card-value" style="font-size: 18px;">
                        <?php if ($currentPengajuan): ?>
                            <span
                                class="status-badge status-<?php echo strtolower(str_replace(' ', '', $currentPengajuan['status'])); ?>">
                                <?php echo $currentPengajuan['status']; ?>
                            </span>
                        <?php else: ?>
                            <span class="status-badge" style="background-color: #f3f4f6; color: #6b7280;">Belum Ada</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-description">Status pengajuan terakhir</div>
                </div>

                <div class="dashboard-card">
                    <div class="card-icon">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <div class="card-title">Peringkat</div>
                    <div class="card-value">
                        <?php echo $userRanking ? '#' . $userRanking['peringkat'] : '-'; ?>
                    </div>
                    <div class="card-description">
                        <?php echo $userRanking ? 'Skor: ' . number_format($userRanking['skor_total'], 2) : 'Belum ada ranking'; ?>
                    </div>
                </div>

            </div>

            <?php if ($activeProgram): ?>
                <div class="form-card"
                    style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border: 2px solid #93c5fd;">
                    <h2 class="form-section-title" style="color: #1e40af;">
                        <i class="fas fa-clipboard-check" style="color: #2563eb;"></i> Program Bantuan Aktif
                    </h2>

                    <div class="row align-items-center">
                        <div class="col-lg-8">
                            <h3 style="font-size: 20px; font-weight: 700; color: #1e40af; margin-bottom: 12px;">
                                <?php echo htmlspecialchars($activeProgram['nama_program']); ?>
                            </h3>
                            <?php if ($activeProgram['deskripsi']): ?>
                                <p style="font-size: 14px; color: #1e40af; margin-bottom: 16px;">
                                    <?php echo htmlspecialchars($activeProgram['deskripsi']); ?>
                                </p>
                            <?php endif; ?>

                            <div style="display: flex; gap: 24px; flex-wrap: wrap;">
                                <div>
                                    <div style="font-size: 12px; color: #6b7280;">Periode Program</div>
                                    <div style="font-size: 14px; font-weight: 600; color: #1e40af;">
                                        <?php echo date('d M Y', strtotime($activeProgram['tanggal_mulai'])); ?> -
                                        <?php echo date('d M Y', strtotime($activeProgram['tanggal_selesai'])); ?>
                                    </div>
                                </div>
                                <div>
                                    <div style="font-size: 12px; color: #6b7280;">Kuota Penerima</div>
                                    <div style="font-size: 14px; font-weight: 600; color: #1e40af;">
                                        <?php echo $activeProgram['kuota']; ?> Orang
                                    </div>
                                </div>
                                <div>
                                    <div style="font-size: 12px; color: #6b7280;">Pendaftar Terverifikasi</div>
                                    <div style="font-size: 14px; font-weight: 600; color: #10b981;">
                                        <?php echo $programStats['terverifikasi']; ?> Orang
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4" style="text-align: right;">
                            <?php if (!$alreadyAppliedToActiveProgram): ?>
                                <a href="apply.php" class="btn btn-primary w-100" style="padding: 16px;">
                                    <i class="fas fa-plus-circle"></i> Daftar Program Ini
                                </a>
                            <?php else: ?>
                                <div class="d-flex justify-content-center align-items-center"
                                    style="padding: 16px; background-color: rgba(16, 185, 129, 0.1); border-radius: 12px;">
                                    <i class="fas fa-check-circle"
                                        style="font-size: 32px; color: #10b981; margin-right: 8px;"></i>
                                    <div style="font-size: 14px; font-weight: 600; color: #065f46;">
                                        Anda Sudah Mendaftar
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Program Progress Bar -->
                    <?php if ($programStats):
                        $percentage = $activeProgram['kuota'] > 0 ? ($programStats['terverifikasi'] / $activeProgram['kuota']) * 100 : 0;
                        $percentage = min($percentage, 100);
                        ?>
                        <div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid #bfdbfe;">
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <span style="font-size: 13px; font-weight: 600; color: #1e40af;">Progress Kuota</span>
                                <span
                                    style="font-size: 13px; font-weight: 600; color: #1e40af;"><?php echo number_format($percentage, 1); ?>%</span>
                            </div>
                            <div style="height: 12px; background-color: #e0e7ff; border-radius: 6px; overflow: hidden;">
                                <div
                                    style="height: 100%; background: linear-gradient(90deg, #10b981 0%, #059669 100%); width: <?php echo $percentage; ?>%; transition: width 0.3s ease;">
                                </div>
                            </div>
                            <div style="font-size: 12px; color: #6b7280; margin-top: 6px; text-align: center;">
                                <?php echo $programStats['terverifikasi']; ?> dari <?php echo $activeProgram['kuota']; ?> kuota
                                terisi
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Quick Actions -->
            <div class="form-card">
                <h2 class="form-section-title">
                    <i class="fas fa-bolt"></i> Aksi Cepat
                </h2>
                <div class="row g-3">
                    <?php if (!$currentPengajuan || $currentPengajuan['status'] == 'Ditolak'): ?>
                        <div class="col-md-6">
                            <a href="apply.php" class="btn btn-primary w-100" style="padding: 20px;">
                                <i class="fas fa-plus-circle" style="font-size: 24px;"></i>
                                <div style="margin-top: 12px;">
                                    <strong style="display: block; font-size: 16px;">Ajukan Bantuan Baru</strong>
                                    <small style="opacity: 0.9;">Buat pengajuan bantuan sosial</small>
                                </div>
                            </a>
                        </div>
                    <?php endif; ?>

                    <div class="col-md-6">
                        <a href="submission_status.php" class="btn btn-secondary w-100" style="padding: 20px;">
                            <i class="fas fa-clipboard-list" style="font-size: 24px;"></i>
                            <div style="margin-top: 12px;">
                                <strong style="display: block; font-size: 16px;">Status Pengajuan</strong>
                                <small>Cek status pengajuan Anda</small>
                            </div>
                        </a>
                    </div>

                    <div class="col-md-6">
                        <a href="ranking.php" class="btn btn-secondary w-100" style="padding: 20px;">
                            <i class="fas fa-chart-bar" style="font-size: 24px;"></i>
                            <div style="margin-top: 12px;">
                                <strong style="display: block; font-size: 16px;">Hasil Ranking</strong>
                                <small>Lihat hasil perhitungan SAW</small>
                            </div>
                        </a>
                    </div>

                    <div class="col-md-6">
                        <a href="profile.php" class="btn btn-secondary w-100" style="padding: 20px;">
                            <i class="fas fa-user-circle" style="font-size: 24px;"></i>
                            <div style="margin-top: 12px;">
                                <strong style="display: block; font-size: 16px;">Profil Saya</strong>
                                <small>Kelola informasi akun Anda</small>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Status Pengajuan Terkini -->
            <?php if ($currentPengajuan): ?>
                <div class="form-card">
                    <h2 class="form-section-title">
                        <i class="fas fa-tasks"></i> Status Pengajuan Terkini
                    </h2>

                    <div class="row">
                        <div class="col-md-8">
                            <table class="table">
                                <tr>
                                    <td style="width: 180px; font-weight: 600;">ID Pengajuan</td>
                                    <td style="width: 20px;">:</td>
                                    <td>#<?php echo $currentPengajuan['id']; ?></td>
                                </tr>
                                <tr>
                                    <td style="font-weight: 600;">Tanggal Dibuat</td>
                                    <td>:</td>
                                    <td><?php echo date('d F Y, H:i', strtotime($currentPengajuan['tanggal_dibuat'])); ?>
                                        WIB</td>
                                </tr>
                                <tr>
                                    <td style="font-weight: 600;">Status</td>
                                    <td>:</td>
                                    <td>
                                        <span
                                            class="status-badge status-<?php echo strtolower(str_replace(' ', '', $currentPengajuan['status'])); ?>">
                                            <?php echo $currentPengajuan['status']; ?>
                                        </span>
                                    </td>
                                </tr>
                            </table>

                            <a href="submission_status.php" class="btn btn-primary mt-3">
                                <i class="fas fa-arrow-right"></i> Lihat Detail Lengkap
                            </a>
                        </div>

                        <div class="col-md-4">
                            <div
                                style="background: linear-gradient(135deg, var(--color-secondary) 0%, var(--color-background) 100%); padding: 24px; border-radius: 12px; text-align: center;">
                                <i class="fas fa-clock"
                                    style="font-size: 48px; color: var(--color-primary); margin-bottom: 12px;"></i>
                                <h4 style="font-size: 16px; font-weight: 600; margin-bottom: 8px;">Sedang Diproses</h4>
                                <p style="font-size: 13px; color: #6b7280; margin: 0;">Pengajuan Anda sedang dalam tahap
                                    <?php echo strtolower($currentPengajuan['status']); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Info Bantuan -->
            <div class="form-card">
                <h2 class="form-section-title">
                    <i class="fas fa-info-circle"></i> Informasi Penting
                </h2>

                <div class="row g-4">
                    <div class="col-md-4">
                        <div style="text-align: center; padding: 20px;">
                            <div
                                style="width: 64px; height: 64px; background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); border-radius: 14px; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; color: #1e40af; font-size: 28px;">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <h4 style="font-size: 16px; font-weight: 600; margin-bottom: 8px;">Transparan</h4>
                            <p style="font-size: 13px; color: #6b7280; line-height: 1.6;">Proses seleksi menggunakan
                                metode SAW yang objektif dan terukur</p>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div style="text-align: center; padding: 20px;">
                            <div
                                style="width: 64px; height: 64px; background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); border-radius: 14px; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; color: #065f46; font-size: 28px;">
                                <i class="fas fa-check-double"></i>
                            </div>
                            <h4 style="font-size: 16px; font-weight: 600; margin-bottom: 8px;">Terpercaya</h4>
                            <p style="font-size: 13px; color: #6b7280; line-height: 1.6;">Data Anda aman dan hanya
                                digunakan untuk verifikasi kelayakan</p>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div style="text-align: center; padding: 20px;">
                            <div
                                style="width: 64px; height: 64px; background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-radius: 14px; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; color: #92400e; font-size: 28px;">
                                <i class="fas fa-clock"></i>
                            </div>
                            <h4 style="font-size: 16px; font-weight: 600; margin-bottom: 8px;">Cepat</h4>
                            <p style="font-size: 13px; color: #6b7280; line-height: 1.6;">Proses verifikasi dilakukan
                                secara otomatis dan efisien</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>