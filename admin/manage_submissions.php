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

$success = '';
$error = '';
$id_petugas = $_SESSION['id'];

// Handle update status
if (isset($_POST['update_status'])) {
    $id_pengajuan = intval($_POST['id_pengajuan']);
    $new_status = $_POST['new_status'];
    $catatan = $_POST['catatan'] ?? '';

    if (updatePengajuanStatus($id_pengajuan, $new_status, $id_petugas, $catatan)) {
        $success = 'Status pengajuan berhasil diperbarui!';
    } else {
        $error = 'Gagal memperbarui status pengajuan.';
    }
}

// Get filter
$filter_status = $_GET['status'] ?? 'all';
$filter_program = $_GET['program'] ?? 'all';

// Build query
$connection = getConnection();
$query = "SELECT p.*, u.nama as nama_user, pb.nama_program 
          FROM pengajuan p 
          JOIN user u ON p.id_user = u.id
          LEFT JOIN program_bantuan pb ON p.id_program = pb.id
          WHERE 1=1";

if ($filter_status != 'all') {
    $query .= " AND p.status = '" . mysqli_real_escape_string($connection, $filter_status) . "'";
}

if ($filter_program != 'all') {
    $query .= " AND p.id_program = " . intval($filter_program);
}

$query .= " ORDER BY p.tanggal_dibuat DESC";

$submissions = $connection->query($query)->fetch_all(MYSQLI_ASSOC);

// Get all programs for filter
$programs = getAllPrograms();

// Get detail if ID provided
$detailPengajuan = null;
if (isset($_GET['id'])) {
    $detailPengajuan = getPengajuanDetail(intval($_GET['id']));
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
    <title>Kelola Pengajuan - SIMBA DIY</title>
</head>

<body>
    <div class="dashboard-wrapper">
        <?php include('../templates/admin_sidebar.php'); ?>

        <div class="main-content" style="background-color: #f0f9ff;">
            <div class="page-header">
                <h1 class="page-title" style="color: #1e40af;">
                    <i class="fas fa-file-invoice" style="color: #2563eb;"></i> Kelola Pengajuan
                </h1>
                <p class="page-subtitle">Verifikasi dan kelola pengajuan bantuan sosial dari masyarakat</p>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <div><?php echo htmlspecialchars($success); ?></div>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <div><?php echo htmlspecialchars($error); ?></div>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <?php
            $stats = [
                'total' => count($submissions),
                'menunggu' => count(array_filter($submissions, fn($s) => $s['status'] == 'Menunggu Verifikasi')),
                'diverifikasi' => count(array_filter($submissions, fn($s) => $s['status'] == 'Sedang Diverifikasi')),
                'terverifikasi' => count(array_filter($submissions, fn($s) => $s['status'] == 'Terverifikasi')),
                'ditolak' => count(array_filter($submissions, fn($s) => $s['status'] == 'Ditolak'))
            ];
            ?>
            <div class="dashboard-cards">
                <div class="dashboard-card" style="border-left: 4px solid #6b7280;">
                    <div class="card-icon" style="background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%); color: #374151;">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="card-title">Total Pengajuan</div>
                    <div class="card-value" style="color: #374151;"><?php echo $stats['total']; ?></div>
                    <div class="card-description">Semua pengajuan</div>
                </div>

                <div class="dashboard-card" style="border-left: 4px solid #f59e0b;">
                    <div class="card-icon" style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); color: #92400e;">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="card-title">Menunggu Verifikasi</div>
                    <div class="card-value" style="color: #92400e;"><?php echo $stats['menunggu']; ?></div>
                    <div class="card-description">Perlu tindakan</div>
                </div>

                <div class="dashboard-card" style="border-left: 4px solid #3b82f6;">
                    <div class="card-icon" style="background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); color: #1e40af;">
                        <i class="fas fa-search"></i>
                    </div>
                    <div class="card-title">Sedang Diverifikasi</div>
                    <div class="card-value" style="color: #1e40af;"><?php echo $stats['diverifikasi']; ?></div>
                    <div class="card-description">Dalam proses</div>
                </div>

                <div class="dashboard-card" style="border-left: 4px solid #10b981;">
                    <div class="card-icon" style="background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); color: #065f46;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="card-title">Terverifikasi</div>
                    <div class="card-value" style="color: #065f46;"><?php echo $stats['terverifikasi']; ?></div>
                    <div class="card-description">Layak menerima</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="form-card">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Filter Status</label>
                        <select name="status" class="form-select" onchange="this.form.submit()">
                            <option value="all" <?php echo $filter_status == 'all' ? 'selected' : ''; ?>>Semua Status</option>
                            <option value="Menunggu Verifikasi" <?php echo $filter_status == 'Menunggu Verifikasi' ? 'selected' : ''; ?>>Menunggu Verifikasi</option>
                            <option value="Sedang Diverifikasi" <?php echo $filter_status == 'Sedang Diverifikasi' ? 'selected' : ''; ?>>Sedang Diverifikasi</option>
                            <option value="Terverifikasi" <?php echo $filter_status == 'Terverifikasi' ? 'selected' : ''; ?>>Terverifikasi</option>
                            <option value="Ditolak" <?php echo $filter_status == 'Ditolak' ? 'selected' : ''; ?>>Ditolak</option>
                        </select>
                    </div>

                    <div class="col-md-4">
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

                    <div class="col-md-4" style="display: flex; align-items: flex-end;">
                        <a href="manage_submissions.php" class="btn btn-secondary w-100">
                            <i class="fas fa-redo"></i> Reset Filter
                        </a>
                    </div>
                </form>
            </div>

            <!-- Submissions List -->
            <div class="form-card">
                <h2 class="form-section-title" style="color: #1e40af;">
                    <i class="fas fa-list" style="color: #2563eb;"></i> Daftar Pengajuan
                    <span style="font-size: 14px; font-weight: normal; color: #6b7280; margin-left: 10px;">
                        (<?php echo count($submissions); ?> pengajuan)
                    </span>
                </h2>

                <?php if (empty($submissions)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-inbox"></i>
                        </div>
                        <div class="empty-state-title">Tidak Ada Pengajuan</div>
                        <div class="empty-state-description">
                            <?php if ($filter_status != 'all' || $filter_program != 'all'): ?>
                                Tidak ada pengajuan dengan filter yang dipilih. <a href="manage_submissions.php">Reset filter</a>
                            <?php else: ?>
                                Belum ada pengajuan yang masuk ke sistem.
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nama</th>
                                    <th>NIK</th>
                                    <th>Program</th>
                                    <th>Tanggal</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($submissions as $sub): ?>
                                    <tr>
                                        <td><strong>#<?php echo $sub['id']; ?></strong></td>
                                        <td><?php echo htmlspecialchars($sub['nama_lengkap']); ?></td>
                                        <td><?php echo $sub['nik']; ?></td>
                                        <td>
                                            <small style="color: #6b7280;">
                                                <?php echo $sub['nama_program'] ? htmlspecialchars($sub['nama_program']) : 'Tidak ada program'; ?>
                                            </small>
                                        </td>
                                        <td><?php echo date('d M Y', strtotime($sub['tanggal_dibuat'])); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower(str_replace(' ', '', $sub['status'])); ?>">
                                                <?php echo $sub['status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="?id=<?php echo $sub['id']; ?>" class="btn btn-sm" style="background-color: #2563eb; color: white; padding: 6px 12px; border-radius: 6px; font-size: 13px;">
                                                <i class="fas fa-eye"></i> Detail
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Detail Pengajuan (if selected) -->
            <?php if ($detailPengajuan): ?>
                <div class="form-card" style="border: 2px solid #2563eb;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                        <h2 class="form-section-title" style="color: #1e40af; margin: 0;">
                            <i class="fas fa-file-alt" style="color: #2563eb;"></i> Detail Pengajuan #<?php echo $detailPengajuan['id']; ?>
                        </h2>
                        <a href="manage_submissions.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Tutup Detail
                        </a>
                    </div>

                    <!-- Data Pemohon -->
                    <div style="background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); padding: 20px; border-radius: 12px; margin-bottom: 20px;">
                        <h4 style="font-size: 16px; font-weight: 600; color: #1e40af; margin-bottom: 16px;">
                            <i class="fas fa-user"></i> Data Pemohon
                        </h4>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div style="font-size: 12px; color: #1e40af; opacity: 0.8; margin-bottom: 4px;">Nama Lengkap</div>
                                <div style="font-size: 15px; font-weight: 600; color: #1e40af;">
                                    <?php echo htmlspecialchars($detailPengajuan['nama_lengkap']); ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div style="font-size: 12px; color: #1e40af; opacity: 0.8; margin-bottom: 4px;">NIK</div>
                                <div style="font-size: 15px; font-weight: 600; color: #1e40af;">
                                    <?php echo $detailPengajuan['nik']; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div style="font-size: 12px; color: #1e40af; opacity: 0.8; margin-bottom: 4px;">No. KK</div>
                                <div style="font-size: 15px; font-weight: 600; color: #1e40af;">
                                    <?php echo $detailPengajuan['no_kk']; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div style="font-size: 12px; color: #1e40af; opacity: 0.8; margin-bottom: 4px;">No. HP</div>
                                <div style="font-size: 15px; font-weight: 600; color: #1e40af;">
                                    <?php echo $detailPengajuan['no_hp']; ?>
                                </div>
                            </div>
                            <div class="col-12">
                                <div style="font-size: 12px; color: #1e40af; opacity: 0.8; margin-bottom: 4px;">Alamat</div>
                                <div style="font-size: 14px; color: #1e40af;">
                                    <?php echo nl2br(htmlspecialchars($detailPengajuan['alamat'])); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Data Ekonomi & Keluarga -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div style="background-color: #fff; padding: 16px; border: 2px solid #e5e7eb; border-radius: 12px; height: 100%;">
                                <h5 style="font-size: 15px; font-weight: 600; margin-bottom: 12px; color: var(--color-text);">
                                    <i class="fas fa-money-bill-wave" style="color: #10b981;"></i> Data Ekonomi
                                </h5>
                                <table class="table table-sm">
                                    <tr>
                                        <td style="width: 50%;">Penghasilan</td>
                                        <td><strong>Rp <?php echo number_format($detailPengajuan['gaji'], 0, ',', '.'); ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td>Pengeluaran</td>
                                        <td><strong>Rp <?php echo number_format($detailPengajuan['pengeluaran'], 0, ',', '.'); ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td>Status Rumah</td>
                                        <td><strong><?php echo $detailPengajuan['status_rumah']; ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td>Daya Listrik</td>
                                        <td><strong><?php echo $detailPengajuan['daya_listrik']; ?></strong></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div style="background-color: #fff; padding: 16px; border: 2px solid #e5e7eb; border-radius: 12px; height: 100%;">
                                <h5 style="font-size: 15px; font-weight: 600; margin-bottom: 12px; color: var(--color-text);">
                                    <i class="fas fa-users" style="color: #8b5cf6;"></i> Data Keluarga
                                </h5>
                                <table class="table table-sm">
                                    <tr>
                                        <td style="width: 50%;">Jumlah Keluarga</td>
                                        <td><strong><?php echo $detailPengajuan['jml_keluarga']; ?> orang</strong></td>
                                    </tr>
                                    <tr>
                                        <td>Anak Sekolah</td>
                                        <td><strong><?php echo $detailPengajuan['jml_anak_sekolah']; ?> anak</strong></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Dokumen -->
                    <h5 style="font-size: 16px; font-weight: 600; margin-bottom: 16px; color: var(--color-text);">
                        <i class="fas fa-paperclip" style="color: #2563eb;"></i> Dokumen Pendukung
                    </h5>
                    <div class="row g-3 mb-4">
                        <?php
                        $dokumenFields = [
                            'ktp' => ['label' => 'KTP', 'icon' => 'fa-id-card', 'color' => '#3b82f6'],
                            'kk' => ['label' => 'Kartu Keluarga', 'icon' => 'fa-users', 'color' => '#8b5cf6'],
                            'slip_gaji' => ['label' => 'Slip Gaji', 'icon' => 'fa-money-check', 'color' => '#10b981'],
                            'foto_rumah' => ['label' => 'Foto Rumah', 'icon' => 'fa-home', 'color' => '#f59e0b'],
                            'surat_keterangan_rumah' => ['label' => 'Surat Keterangan Rumah', 'icon' => 'fa-file-alt', 'color' => '#ef4444'],
                            'rekening_listrik' => ['label' => 'Rekening Listrik', 'icon' => 'fa-bolt', 'color' => '#06b6d4']
                        ];

                        foreach ($dokumenFields as $field => $info):
                            $hasDoc = !empty($detailPengajuan[$field]);
                        ?>
                            <div class="col-md-4">
                                <div style="padding: 12px; background-color: <?php echo $hasDoc ? '#f0fdf4' : '#f9fafb'; ?>; border: 1px solid <?php echo $hasDoc ? '#86efac' : '#e5e7eb'; ?>; border-radius: 10px; display: flex; align-items: center; gap: 10px;">
                                    <div style="width: 36px; height: 36px; background-color: <?php echo $info['color']; ?>20; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: <?php echo $info['color']; ?>; font-size: 16px;">
                                        <i class="fas <?php echo $info['icon']; ?>"></i>
                                    </div>
                                    <div style="flex: 1;">
                                        <div style="font-size: 13px; font-weight: 600; color: var(--color-text);"><?php echo $info['label']; ?></div>
                                        <?php if ($hasDoc): ?>
                                            <div style="font-size: 11px; color: #10b981;">
                                                <i class="fas fa-check-circle"></i> Ada
                                            </div>
                                        <?php else: ?>
                                            <div style="font-size: 11px; color: #9ca3af;">
                                                <i class="fas fa-times-circle"></i> Tidak ada
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Form Update Status -->
                    <div style="background-color: #fef3c7; padding: 20px; border-radius: 12px; border: 2px solid #fde68a;">
                        <h4 style="font-size: 16px; font-weight: 600; color: #92400e; margin-bottom: 16px;">
                            <i class="fas fa-edit"></i> Update Status Pengajuan
                        </h4>

                        <form method="POST">
                            <input type="hidden" name="id_pengajuan" value="<?php echo $detailPengajuan['id']; ?>">

                            <div class="form-group">
                                <label class="form-label">Status Baru <span class="required">*</span></label>
                                <select name="new_status" class="form-select" required>
                                    <option value="">Pilih Status</option>
                                    <option value="Sedang Diverifikasi" <?php echo $detailPengajuan['status'] == 'Sedang Diverifikasi' ? 'selected' : ''; ?>>
                                        Sedang Diverifikasi
                                    </option>
                                    <option value="Terverifikasi" <?php echo $detailPengajuan['status'] == 'Terverifikasi' ? 'selected' : ''; ?>>
                                        Terverifikasi (Layak)
                                    </option>
                                    <option value="Ditolak" <?php echo $detailPengajuan['status'] == 'Ditolak' ? 'selected' : ''; ?>>
                                        Ditolak (Tidak Layak)
                                    </option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Catatan Verifikasi</label>
                                <textarea name="catatan" class="form-control" rows="3"
                                    placeholder="Catatan untuk pemohon (opsional)"><?php echo htmlspecialchars($detailPengajuan['catatan_verifikasi'] ?? ''); ?></textarea>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" name="update_status" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Simpan Status
                                </button>
                                <a href="manage_submissions.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Kembali
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>