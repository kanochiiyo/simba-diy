<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once(__DIR__ . "/../functions/authentication.php");
require_once(__DIR__ . "/../functions/submission.php");

if (!isLogged() || isAdmin()) {
    header("Location: ../auth/login.php");
    exit;
}

$id_user = $_SESSION['id'];
$pengajuanList = getUserPengajuan($id_user);
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
    <link rel="stylesheet" href="../css/dashboard.css">
    <title>Status Pengajuan - SIMBA DIY</title>
</head>

<body>
    <div class="dashboard-wrapper">
        <?php include('../templates/user_sidebar.php'); ?>

        <div class="main-content">
            <div class="page-header">
                <h1 class="page-title">Status Pengajuan</h1>
                <p class="page-subtitle">Pantau perkembangan pengajuan bantuan Anda</p>
            </div>

            <?php if (empty($pengajuanList)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        <strong>Belum Ada Pengajuan</strong>
                        <p style="margin: 5px 0 0 0;">Anda belum memiliki riwayat pengajuan. <a href="ajukan.php">Buat
                                pengajuan baru</a></p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($pengajuanList as $pengajuan): ?>
                    <div class="form-card">
                        <div class="row">
                            <div class="col-md-8">
                                <h3 style="font-size: 20px; font-weight: 600; margin-bottom: 15px;">
                                    Pengajuan #<?php echo $pengajuan['id']; ?>
                                </h3>

                                <div class="mb-3">
                                    <div class="row">
                                        <div class="col-md-6 mb-2">
                                            <small class="text-muted">NIK:</small>
                                            <p class="mb-0"><strong><?php echo htmlspecialchars($pengajuan['nik']); ?></strong>
                                            </p>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <small class="text-muted">Nama:</small>
                                            <p class="mb-0">
                                                <strong><?php echo htmlspecialchars($pengajuan['nama_lengkap']); ?></strong>
                                            </p>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <small class="text-muted">Tanggal Dibuat:</small>
                                            <p class="mb-0">
                                                <?php echo date('d F Y, H:i', strtotime($pengajuan['tanggal_dibuat'])); ?></p>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <small class="text-muted">No. HP:</small>
                                            <p class="mb-0"><?php echo htmlspecialchars($pengajuan['no_hp']); ?></p>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <small class="text-muted">Status:</small>
                                    <div class="mt-1">
                                        <span
                                            class="status-badge status-<?php echo strtolower(str_replace(' ', '', $pengajuan['status'])); ?>">
                                            <?php echo $pengajuan['status']; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4 d-flex align-items-center justify-content-end">
                                <button class="btn btn-secondary" onclick="toggleDetail('detail-<?php echo $pengajuan['id']; ?>')">
                                    <i class="fas fa-eye"></i> Lihat Detail
                                </button>
                            </div>
                        </div>

                        <!-- Detail Pengajuan (Hidden by default) -->
                        <div id="detail-<?php echo $pengajuan['id']; ?>" class="mt-4 pt-4"
                            style="display: none; border-top: 1px solid var(--color-stroke);">
                            <h4 style="font-size: 16px; font-weight: 600; margin-bottom: 20px;">Detail Pengajuan</h4>

                            <div class="row">
                                <div class="col-md-6">
                                    <h5 style="font-size: 14px; font-weight: 600; margin-bottom: 15px; color: var(--color-primary);">Data Ekonomi</h5>
                                    <table class="table table-sm">
                                        <tr>
                                            <td>Penghasilan</td>
                                            <td>:</td>
                                            <td><strong>Rp <?php echo number_format($pengajuan['gaji'], 0, ',', '.'); ?></strong></td>
                                        </tr>
                                        <tr>
                                            <td>Pengeluaran</td>
                                            <td>:</td>
                                            <td><strong>Rp <?php echo number_format($pengajuan['pengeluaran'], 0, ',', '.'); ?></strong></td>
                                        </tr>
                                        <tr>
                                            <td>Status Rumah</td>
                                            <td>:</td>
                                            <td><strong><?php echo $pengajuan['status_rumah']; ?></strong></td>
                                        </tr>
                                        <tr>
                                            <td>Daya Listrik</td>
                                            <td>:</td>
                                            <td><strong><?php echo $pengajuan['daya_listrik']; ?> VA</strong></td>
                                        </tr>
                                    </table>
                                </div>

                                <div class="col-md-6">
                                    <h5 style="font-size: 14px; font-weight: 600; margin-bottom: 15px; color: var(--color-primary);">Data Keluarga</h5>
                                    <table class="table table-sm">
                                        <tr>
                                            <td>Jumlah Keluarga</td>
                                            <td>:</td>
                                            <td><strong><?php echo $pengajuan['jml_keluarga']; ?> orang</strong></td>
                                        </tr>
                                        <tr>
                                            <td>Anak Usia Sekolah</td>
                                            <td>:</td>
                                            <td><strong><?php echo $pengajuan['jml_anak_sekolah']; ?> anak</strong></td>
                                        </tr>
                                        <tr>
                                            <td>No. KK</td>
                                            <td>:</td>
                                            <td><strong><?php echo $pengajuan['no_kk']; ?></strong></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <div class="mt-3">
                                <h5 style="font-size: 14px; font-weight: 600; margin-bottom: 15px; color: var(--color-primary);">Dokumen yang Diupload</h5>
                                <div class="row">
                                    <?php
                                    $dokumenFields = [
                                        'ktp' => 'KTP',
                                        'kk' => 'Kartu Keluarga',
                                        'slip_gaji' => 'Slip Gaji',
                                        'foto_rumah' => 'Foto Rumah',
                                        'surat_keterangan_rumah' => 'Surat Keterangan Rumah',
                                        'rekening_listrik' => 'Rekening Listrik'
                                    ];

                                    foreach ($dokumenFields as $field => $label):
                                        if (!empty($pengajuan[$field])):
                                    ?>
                                            <div class="col-md-4 mb-2">
                                                <div
                                                    style="padding: 10px; background-color: var(--color-secondary); border-radius: 8px;">
                                                    <i class="fas fa-check-circle"
                                                        style="color: #16A34A; margin-right: 8px;"></i>
                                                    <strong><?php echo $label; ?></strong>
                                                </div>
                                            </div>
                                    <?php
                                        endif;
                                    endforeach;
                                    ?>
                                </div>
                            </div>

                            <?php if ($pengajuan['status'] == 'Ditolak'): ?>
                                <div class="alert alert-danger mt-3">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <div>
                                        <strong>Pengajuan Ditolak</strong>
                                        <p style="margin: 5px 0 0 0;">Mohon maaf, pengajuan Anda ditolak. Anda dapat mengajukan
                                            kembali dengan data yang lebih lengkap.</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleDetail(id) {
            const element = document.getElementById(id);
            if (element.style.display === 'none') {
                element.style.display = 'block';
            } else {
                element.style.display = 'none';
            }
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>