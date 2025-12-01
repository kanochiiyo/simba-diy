<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once(__DIR__ . "/../functions/authentication.php");
require_once(__DIR__ . "/../functions/program.php");
require_once(__DIR__ . "/../functions/submission.php");

if (!isLogged() || !isAdmin()) {
    header("Location: ../auth/login.php");
    exit;
}

$connection = getConnection();

// Get all programs for filter
$programs = getAllPrograms();

// Filter
$filter_program = $_GET['program'] ?? 'all';

// Build statistics based on filter
$whereClause = "1=1";
if ($filter_program != 'all') {
    $whereClause = "p.id_program = " . intval($filter_program);
}

// Total pengajuan
$result = $connection->query("SELECT COUNT(*) as total FROM pengajuan p WHERE $whereClause");
$totalPengajuan = $result->fetch_assoc()['total'];

// By Status
$statusStats = [];
$statuses = ['Menunggu Verifikasi', 'Sedang Diverifikasi', 'Terverifikasi', 'Ditolak'];
foreach ($statuses as $status) {
    $result = $connection->query("SELECT COUNT(*) as total FROM pengajuan p WHERE $whereClause AND p.status = '$status'");
    $statusStats[$status] = $result->fetch_assoc()['total'];
}

// Monthly submissions (last 6 months)
$monthlyData = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $result = $connection->query("SELECT COUNT(*) as total FROM pengajuan p 
                                  WHERE $whereClause AND DATE_FORMAT(p.tanggal_dibuat, '%Y-%m') = '$month'");
    $monthlyData[] = [
        'month' => date('M Y', strtotime("-$i months")),
        'total' => $result->fetch_assoc()['total']
    ];
}

// Program statistics
$programStats = [];
foreach ($programs as $prog) {
    $stats = getProgramStats($prog['id']);
    $programStats[] = [
        'program' => $prog,
        'stats' => $stats
    ];
}
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
    <title>Laporan - SIMBA DIY</title>
</head>

<body>
    <div class="dashboard-wrapper">
        <?php include('../templates/admin_sidebar.php'); ?>

        <div class="main-content" style="background-color: #f0f9ff;">
            <div class="page-header">
                <h1 class="page-title" style="color: #1e40af;">
                    <i class="fas fa-chart-bar" style="color: #2563eb;"></i> Laporan & Statistik
                </h1>
                <p class="page-subtitle">Ringkasan data dan analisis sistem bantuan sosial</p>
            </div>

            <!-- Filter -->
            <div class="form-card">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-8">
                        <label class="form-label">Filter Program</label>
                        <select name="program" class="form-select" onchange="this.form.submit()">
                            <option value="all" <?php echo $filter_program == 'all' ? 'selected' : ''; ?>>Semua Program</option>
                            <?php foreach ($programs as $prog): ?>
                                <option value="<?php echo $prog['id']; ?>" <?php echo $filter_program == $prog['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($prog['nama_program']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <a href="reports.php" class="btn btn-secondary w-100">
                            <i class="fas fa-redo"></i> Reset Filter
                        </a>
                    </div>
                </form>
            </div>

            <!-- Status Distribution -->
            <div class="form-card">
                <h2 class="form-section-title" style="color: #1e40af;">
                    <i class="fas fa-chart-pie" style="color: #2563eb;"></i> Distribusi Status Pengajuan
                </h2>

                <div class="dashboard-cards">
                    <div class="dashboard-card" style="border-left: 4px solid #f59e0b;">
                        <div class="card-icon" style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); color: #92400e;">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="card-title">Menunggu Verifikasi</div>
                        <div class="card-value" style="color: #92400e;"><?php echo $statusStats['Menunggu Verifikasi']; ?></div>
                        <div class="card-description">
                            <?php echo $totalPengajuan > 0 ? round(($statusStats['Menunggu Verifikasi'] / $totalPengajuan) * 100, 1) : 0; ?>% dari total
                        </div>
                    </div>

                    <div class="dashboard-card" style="border-left: 4px solid #3b82f6;">
                        <div class="card-icon" style="background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); color: #1e40af;">
                            <i class="fas fa-search"></i>
                        </div>
                        <div class="card-title">Sedang Diverifikasi</div>
                        <div class="card-value" style="color: #1e40af;"><?php echo $statusStats['Sedang Diverifikasi']; ?></div>
                        <div class="card-description">
                            <?php echo $totalPengajuan > 0 ? round(($statusStats['Sedang Diverifikasi'] / $totalPengajuan) * 100, 1) : 0; ?>% dari total
                        </div>
                    </div>

                    <div class="dashboard-card" style="border-left: 4px solid #10b981;">
                        <div class="card-icon" style="background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); color: #065f46;">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="card-title">Terverifikasi</div>
                        <div class="card-value" style="color: #065f46;"><?php echo $statusStats['Terverifikasi']; ?></div>
                        <div class="card-description">
                            <?php echo $totalPengajuan > 0 ? round(($statusStats['Terverifikasi'] / $totalPengajuan) * 100, 1) : 0; ?>% dari total
                        </div>
                    </div>

                    <div class="dashboard-card" style="border-left: 4px solid #ef4444;">
                        <div class="card-icon" style="background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); color: #991b1b;">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="card-title">Ditolak</div>
                        <div class="card-value" style="color: #991b1b;"><?php echo $statusStats['Ditolak']; ?></div>
                        <div class="card-description">
                            <?php echo $totalPengajuan > 0 ? round(($statusStats['Ditolak'] / $totalPengajuan) * 100, 1) : 0; ?>% dari total
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monthly Trend -->
            <div class="form-card">
                <h2 class="form-section-title" style="color: #1e40af;">
                    <i class="fas fa-chart-line" style="color: #2563eb;"></i> Tren Pengajuan Bulanan
                    <span style="font-size: 14px; font-weight: normal; color: #6b7280; margin-left: 10px;">
                        (6 Bulan Terakhir)
                    </span>
                </h2>

                <div style="overflow-x: auto;">
                    <div style="display: flex; gap: 16px; min-width: 600px; padding: 20px;">
                        <?php
                        $maxValue = max(array_column($monthlyData, 'total'));
                        $maxValue = $maxValue > 0 ? $maxValue : 1;
                        ?>
                        <?php foreach ($monthlyData as $data): ?>
                            <div style="flex: 1; text-align: center;">
                                <div style="height: 200px; display: flex; align-items: flex-end; justify-content: center; margin-bottom: 12px;">
                                    <div style="width: 100%; background: linear-gradient(to top, #2563eb, #60a5fa); border-radius: 8px 8px 0 0; position: relative; height: <?php echo ($data['total'] / $maxValue) * 100; ?>%; min-height: <?php echo $data['total'] > 0 ? '30px' : '5px'; ?>;">
                                        <div style="position: absolute; top: -25px; left: 50%; transform: translateX(-50%); font-weight: 700; color: #1e40af; font-size: 16px;">
                                            <?php echo $data['total']; ?>
                                        </div>
                                    </div>
                                </div>
                                <div style="font-size: 13px; font-weight: 600; color: #6b7280;">
                                    <?php echo $data['month']; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Program Statistics -->
            <div class="form-card">
                <h2 class="form-section-title" style="color: #1e40af;">
                    <i class="fas fa-list-alt" style="color: #2563eb;"></i> Statistik Per Program
                </h2>

                <?php if (empty($programStats)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <div class="empty-state-title">Belum Ada Program</div>
                        <div class="empty-state-description">Buat program baru untuk melihat statistik</div>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Program</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                    <th>Menunggu</th>
                                    <th>Diverifikasi</th>
                                    <th>Terverifikasi</th>
                                    <th>Ditolak</th>
                                    <th>Kuota</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($programStats as $ps):
                                    $prog = $ps['program'];
                                    $stats = $ps['stats'];
                                ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($prog['nama_program']); ?></strong>
                                            <br><small style="color: #6b7280;">
                                                <?php echo date('M Y', strtotime($prog['tanggal_mulai'])); ?> -
                                                <?php echo date('M Y', strtotime($prog['tanggal_selesai'])); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php if ($prog['status'] == 'Aktif'): ?>
                                                <span class="status-badge" style="background-color: #d1fae5; color: #065f46;">
                                                    Aktif
                                                </span>
                                            <?php else: ?>
                                                <span class="status-badge" style="background-color: #fee2e2; color: #991b1b;">
                                                    Tutup
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong><?php echo $stats['total_pengajuan']; ?></strong></td>
                                        <td><?php echo $stats['menunggu']; ?></td>
                                        <td><?php echo $stats['diverifikasi']; ?></td>
                                        <td><strong style="color: #10b981;"><?php echo $stats['terverifikasi']; ?></strong></td>
                                        <td><?php echo $stats['ditolak']; ?></td>
                                        <td>
                                            <strong><?php echo $prog['kuota']; ?></strong>
                                            <?php if ($stats['terverifikasi'] >= $prog['kuota']): ?>
                                                <span style="font-size: 11px; color: #10b981; margin-left: 4px;">
                                                    <i class="fas fa-check"></i> Terpenuhi
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Summary Cards -->
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="form-card" style="background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); border: none;">
                        <h4 style="font-size: 18px; font-weight: 600; color: #1e40af; margin-bottom: 16px;">
                            <i class="fas fa-info-circle"></i> Ringkasan Sistem
                        </h4>
                        <div style="background-color: rgba(255, 255, 255, 0.8); padding: 16px; border-radius: 12px;">
                            <table class="table table-sm">
                                <tr>
                                    <td style="width: 60%;">Total Program</td>
                                    <td><strong><?php echo count($programs); ?></strong></td>
                                </tr>
                                <tr>
                                    <td>Program Aktif</td>
                                    <td><strong style="color: #10b981;"><?php echo count(array_filter($programs, fn($p) => $p['status'] == 'Aktif')); ?></strong></td>
                                </tr>
                                <tr>
                                    <td>Program Tutup</td>
                                    <td><strong style="color: #ef4444;"><?php echo count(array_filter($programs, fn($p) => $p['status'] == 'Tutup')); ?></strong></td>
                                </tr>
                                <tr>
                                    <td>Total Pengajuan</td>
                                    <td><strong><?php echo $totalPengajuan; ?></strong></td>
                                </tr>
                                <tr>
                                    <td>Tingkat Approval</td>
                                    <td>
                                        <strong style="color: #10b981;">
                                            <?php
                                            $approved = $statusStats['Terverifikasi'];
                                            $rejected = $statusStats['Ditolak'];
                                            $processed = $approved + $rejected;
                                            echo $processed > 0 ? round(($approved / $processed) * 100, 1) : 0;
                                            ?>%
                                        </strong>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-card" style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border: none;">
                        <h4 style="font-size: 18px; font-weight: 600; color: #92400e; margin-bottom: 16px;">
                            <i class="fas fa-exclamation-triangle"></i> Perhatian
                        </h4>
                        <div style="background-color: rgba(255, 255, 255, 0.8); padding: 16px; border-radius: 12px;">
                            <ul style="margin: 0; padding-left: 20px; line-height: 2;">
                                <li style="color: #6b7280;">
                                    <strong style="color: #92400e;"><?php echo $statusStats['Menunggu Verifikasi']; ?></strong> pengajuan menunggu verifikasi
                                </li>
                                <li style="color: #6b7280;">
                                    <strong style="color: #92400e;"><?php echo $statusStats['Sedang Diverifikasi']; ?></strong> pengajuan sedang diproses
                                </li>
                                <?php
                                $activeProgram = getActiveProgram();
                                if ($activeProgram):
                                    $activeProgramStats = getProgramStats($activeProgram['id']);
                                    $remaining = $activeProgram['kuota'] - $activeProgramStats['terverifikasi'];
                                ?>
                                    <li style="color: #6b7280;">
                                        Sisa kuota program aktif: <strong style="color: <?php echo $remaining > 0 ? '#10b981' : '#ef4444'; ?>"><?php echo max(0, $remaining); ?></strong>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>