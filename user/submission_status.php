<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once(__DIR__ . "/../functions/authentication.php");
require_once(__DIR__ . "/../functions/submission.php");

function formatGajiLabel($nilai)
{
    if ($nilai < 1000000) return '< Rp 1.000.000';
    if ($nilai < 2000000) return 'Rp 1.000.000 - Rp 1.999.000';
    if ($nilai < 3500000) return 'Rp 2.000.000 - Rp 3.500.000';
    return '> Rp 3.500.000';
}

function formatJumlahKeluargaLabel($nilai)
{
    if ($nilai <= 2) return '1 - 2 orang';
    if ($nilai == 3) return '3 orang';
    if ($nilai == 4) return '4 orang';
    if ($nilai == 5) return '5 orang';
    return '> 5 orang';
}

function formatAnakSekolahLabel($nilai)
{
    if ($nilai == 0) return 'Tidak ada';
    if ($nilai <= 3) return $nilai . ' orang';
    return '> 3 orang';
}

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
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <title>Status Pengajuan - SIMBA DIY</title>
</head>

<body>
    <div class="dashboard-wrapper">
        <?php include('../templates/user_sidebar.php'); ?>

        <div class="main-content">
            <div class="page-header">
                <h1 class="page-title">Status Pengajuan</h1>
                <p class="page-subtitle">Pantau perkembangan pengajuan bantuan sosial Anda</p>
            </div>

            <?php if (empty($pengajuanList)): ?>
                <div class="form-card">
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="empty-state-title">Belum Ada Pengajuan</div>
                        <div class="empty-state-description">
                            Anda belum memiliki riwayat pengajuan bantuan sosial. Buat pengajuan baru untuk memulai proses verifikasi.
                        </div>
                        <a href="apply.php" class="btn btn-primary" style="margin-top: 24px;">
                            <i class="fas fa-plus-circle"></i> Buat Pengajuan Baru
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Info Alert -->
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        <strong>Informasi Status Pengajuan</strong>
                        <p style="margin: 8px 0 0 0;">Total pengajuan Anda: <strong><?php echo count($pengajuanList); ?></strong>. Klik "Lihat Detail" untuk informasi lengkap setiap pengajuan.</p>
                    </div>
                </div>

                <?php foreach ($pengajuanList as $pengajuan): ?>
                    <div class="form-card">
                        <div class="row align-items-center">
                            <div class="col-lg-9">
                                <div style="display: flex; align-items: start; gap: 20px;">
                                    <!-- Icon -->
                                    <div style="width: 64px; height: 64px; background: linear-gradient(135deg, var(--color-secondary) 0%, var(--color-background) 100%); border-radius: 14px; display: flex; align-items: center; justify-content: center; color: var(--color-primary); font-size: 28px; flex-shrink: 0;">
                                        <i class="fas fa-file-invoice"></i>
                                    </div>

                                    <!-- Content -->
                                    <div style="flex: 1;">
                                        <h3 style="font-size: 20px; font-weight: 600; margin-bottom: 12px; color: var(--color-text);">
                                            Pengajuan #<?php echo $pengajuan['id']; ?>
                                        </h3>

                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <div style="font-size: 12px; color: #6b7280; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">NIK</div>
                                                <div style="font-size: 15px; font-weight: 600; color: var(--color-text);">
                                                    <i class="fas fa-id-card" style="color: #9ca3af; margin-right: 6px;"></i>
                                                    <?php echo htmlspecialchars($pengajuan['nik']); ?>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div style="font-size: 12px; color: #6b7280; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Nama Lengkap</div>
                                                <div style="font-size: 15px; font-weight: 600; color: var(--color-text);">
                                                    <i class="fas fa-user" style="color: #9ca3af; margin-right: 6px;"></i>
                                                    <?php echo htmlspecialchars($pengajuan['nama_lengkap']); ?>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div style="font-size: 12px; color: #6b7280; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Tanggal Dibuat</div>
                                                <div style="font-size: 15px; font-weight: 600; color: var(--color-text);">
                                                    <i class="fas fa-calendar" style="color: #9ca3af; margin-right: 6px;"></i>
                                                    <?php echo date('d F Y, H:i', strtotime($pengajuan['tanggal_dibuat'])); ?> WIB
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div style="font-size: 12px; color: #6b7280; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Status</div>
                                                <div>
                                                    <span class="status-badge status-<?php echo strtolower(str_replace(' ', '', $pengajuan['status'])); ?>">
                                                        <?php echo $pengajuan['status']; ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-3" style="text-align: right;">
                                <button class="btn btn-primary w-100" onclick="toggleDetail('detail-<?php echo $pengajuan['id']; ?>', this)">
                                    <i class="fas fa-eye"></i> Lihat Detail
                                </button>
                            </div>
                        </div>

                        <!-- Detail Pengajuan (Hidden by default) -->
                        <div id="detail-<?php echo $pengajuan['id']; ?>" class="mt-4 pt-4" style="display: none; border-top: 2px solid #f3f4f6;">

                            <!-- Data Ekonomi & Keluarga (2 kolom sejajar) -->
                            <div class="row g-4 mb-4">

                                <!-- Card Data Ekonomi -->
                                <div class="col-lg-6">
                                    <div style="background: linear-gradient(135deg, var(--color-secondary) 0%, var(--color-background) 100%); padding: 20px; border-radius: 12px; height: 100%;">
                                        <h5 style="font-size: 16px; font-weight: 600; margin-bottom: 16px; color: var(--color-text); display: flex; align-items: center; gap: 8px;">
                                            <i class="fas fa-money-bill-wave" style="color: var(--color-primary);"></i>
                                            Data Ekonomi
                                        </h5>

                                        <div style="background-color: rgba(255, 255, 255, 0.7); padding: 16px; border-radius: 10px; height: 180px;">
                                            <div class="row g-3">
                                                <div class="col-6">
                                                    <div style="font-size: 12px; color: #6b7280; margin-bottom: 4px;">Penghasilan</div>
                                                    <div style="font-size: 14px; font-weight: 600;"><?php echo formatGajiLabel($pengajuan['gaji']); ?></div>
                                                </div>
                                                <div class="col-6">
                                                    <div style="font-size: 12px; color: #6b7280; margin-bottom: 4px;">Pengeluaran</div>
                                                    <div style="font-size: 14px; font-weight: 600;"><?php echo formatGajiLabel($pengajuan['pengeluaran']); ?></div>
                                                </div>
                                            </div>

                                            <div class="row g-3 mt-1">
                                                <div class="col-6">
                                                    <div style="font-size: 12px; color: #6b7280; margin-bottom: 4px;">Status Rumah</div>
                                                    <div style="font-size: 14px; font-weight: 600;"><?php echo $pengajuan['status_rumah']; ?></div>
                                                </div>
                                                <div class="col-6">
                                                    <div style="font-size: 12px; color: #6b7280; margin-bottom: 4px;">Daya Listrik</div>
                                                    <div style="font-size: 14px; font-weight: 600;"><?php echo $pengajuan['daya_listrik']; ?> VA</div>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>

                                <!-- Card Data Keluarga -->
                                <div class="col-lg-6">
                                    <div style="background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); padding: 20px; border-radius: 12px; height: 100%;">
                                        <h5 style="font-size: 16px; font-weight: 600; margin-bottom: 16px; color: #1e40af; display: flex; align-items: center; gap: 8px;">
                                            <i class="fas fa-users"></i>
                                            Data Keluarga
                                        </h5>

                                        <div style="background-color: rgba(255, 255, 255, 0.7); padding: 16px; border-radius: 10px;">
                                            <div class="row g-3">
                                                <div class="col-6">
                                                    <div style="font-size: 12px; color: #6b7280;">Jumlah Anggota Keluarga</div>
                                                    <div style="font-size: 14px; font-weight: 600;"><?php echo formatJumlahKeluargaLabel($pengajuan['jml_keluarga']); ?></div>
                                                </div>
                                                <div class="col-6">
                                                    <div style="font-size: 12px; color: #6b7280;">Jumlah Anak Sekolah</div>
                                                    <div style="font-size: 14px; font-weight: 600;"><?php echo formatAnakSekolahLabel($pengajuan['jml_anak_sekolah']); ?></div>
                                                </div>

                                                <div class="col-12">
                                                    <div style="font-size: 12px; color: #6b7280;">Nomor KK</div>
                                                    <div style="font-size: 14px; font-weight: 600;"><?php echo $pengajuan['no_kk']; ?></div>
                                                </div>

                                                <div class="col-12">
                                                    <div style="font-size: 12px; color: #6b7280;">Nomor HP</div>
                                                    <div style="font-size: 14px; font-weight: 600;"><?php echo $pengajuan['no_hp']; ?></div>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                            </div> <!-- END row -->
                        </div>


                        <!-- Alamat -->
                        <div style="background-color: #f9fafb; padding: 16px; border-radius: 12px; margin-top:20px; margin-bottom: 20px;">
                            <h5 style="font-size: 14px; font-weight: 600; margin-bottom: 8px; color: var(--color-text);">
                                <i class="fas fa-map-marker-alt" style="color: var(--color-primary); margin-right: 6px;"></i>
                                Alamat Lengkap
                            </h5>
                            <p style="font-size: 14px; color: #6b7280; margin: 0; line-height: 1.6;"><?php echo nl2br(htmlspecialchars($pengajuan['alamat'])); ?></p>
                        </div>

                        <!-- Dokumen -->
                        <h5 style="font-size: 16px; font-weight: 600; margin-bottom: 16px; color: var(--color-text);">
                            <i class="fas fa-paperclip" style="color: var(--color-primary); margin-right: 8px;"></i>
                            Dokumen yang Diupload
                        </h5>
                        <div class="row g-3">
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
                                if (!empty($pengajuan[$field])):
                            ?>
                                    <div class="col-md-6 col-lg-4">
                                        <div style="padding: 14px; background-color: #fff; border: 1px solid #e5e7eb; border-radius: 10px; display: flex; align-items: center; gap: 12px;">
                                            <div style="width: 40px; height: 40px; background-color: <?php echo $info['color']; ?>20; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: <?php echo $info['color']; ?>; font-size: 18px;">
                                                <i class="fas <?php echo $info['icon']; ?>"></i>
                                            </div>
                                            <div style="flex: 1; min-width: 0;">
                                                <div style="font-size: 13px; font-weight: 600; color: var(--color-text); margin-bottom: 2px;"><?php echo $info['label']; ?></div>
                                                <div style="font-size: 11px; color: #10b981;">
                                                    <i class="fas fa-check-circle"></i> Tersedia
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php
                                else:
                                ?>
                                    <div class="col-md-6 col-lg-4">
                                        <div style="padding: 14px; background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; display: flex; align-items: center; gap: 12px; opacity: 0.6;">
                                            <div style="width: 40px; height: 40px; background-color: #e5e7eb; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #9ca3af; font-size: 18px;">
                                                <i class="fas <?php echo $info['icon']; ?>"></i>
                                            </div>
                                            <div style="flex: 1; min-width: 0;">
                                                <div style="font-size: 13px; font-weight: 600; color: #6b7280; margin-bottom: 2px;"><?php echo $info['label']; ?></div>
                                                <div style="font-size: 11px; color: #9ca3af;">
                                                    <i class="fas fa-times-circle"></i> Tidak tersedia
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                            <?php
                                endif;
                            endforeach;
                            ?>
                        </div>

                        <?php if ($pengajuan['status'] == 'Ditolak'): ?>
                            <div class="alert alert-danger mt-4">
                                <i class="fas fa-exclamation-circle"></i>
                                <div>
                                    <strong>Pengajuan Ditolak</strong>
                                    <p style="margin: 8px 0 0 0;">Mohon maaf, pengajuan Anda ditolak. Anda dapat mengajukan kembali dengan data yang lebih lengkap dan akurat.</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleDetail(id, button) {
            const element = document.getElementById(id);
            const icon = button.querySelector('i');

            if (element.style.display === 'none' || element.style.display === '') {
                element.style.display = 'block';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
                button.innerHTML = '<i class="fas fa-eye-slash"></i> Sembunyikan';
            } else {
                element.style.display = 'none';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
                button.innerHTML = '<i class="fas fa-eye"></i> Lihat Detail';
            }
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>