<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once(__DIR__ . "/../functions/authentication.php");
require_once(__DIR__ . "/../functions/submission.php");
require_once(__DIR__ . "/../functions/saw.php");

if (!isLogged() || isAdmin()) {
    header("Location: ../auth/login.php");
    exit;
}

$id_user = $_SESSION['id'];
$rankingList = getRanking();
$userRanking = getUserRanking($id_user);
$sawStats = getSAWStatistics();
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
    <title>Hasil Ranking - SIMBA DIY</title>
</head>

<body>
    <div class="dashboard-wrapper">
        <?php include('../templates/user_sidebar.php'); ?>

        <div class="main-content">
            <div class="page-header">
                <h1 class="page-title">Hasil Ranking</h1>
                <p class="page-subtitle">Hasil perhitungan penerima bantuan berdasarkan metode SAW (Simple Additive Weighting)</p>
            </div>

            <!-- User Ranking Alert -->
            <?php if ($userRanking): ?>
                <div class="alert alert-success">
                    <i class="fas fa-trophy"></i>
                    <div>
                        <strong>Selamat! Anda Masuk dalam Daftar Ranking</strong>
                        <p style="margin: 8px 0 0 0;">Anda berada di peringkat <strong>#<?php echo $userRanking['peringkat']; ?></strong> dari <?php echo $sawStats['total_peserta']; ?> peserta dengan skor <strong><?php echo number_format($userRanking['skor_total'], 4); ?></strong></p>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        <strong>Informasi Ranking</strong>
                        <p style="margin: 8px 0 0 0;">Data ranking Anda akan muncul setelah pengajuan diverifikasi dan proses perhitungan SAW selesai.</p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Statistik SAW -->
            <?php if ($sawStats['total_peserta'] > 0): ?>
                <div class="dashboard-cards">
                    <div class="dashboard-card">
                        <div class="card-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="card-title">Total Peserta</div>
                        <div class="card-value"><?php echo $sawStats['total_peserta']; ?></div>
                        <div class="card-description">Pengajuan terverifikasi</div>
                    </div>

                    <div class="dashboard-card">
                        <div class="card-icon">
                            <i class="fas fa-arrow-up"></i>
                        </div>
                        <div class="card-title">Skor Tertinggi</div>
                        <div class="card-value"><?php echo number_format($sawStats['skor_tertinggi'], 4); ?></div>
                        <div class="card-description">Skor maksimal peserta</div>
                    </div>

                    <div class="dashboard-card">
                        <div class="card-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="card-title">Rata-rata Skor</div>
                        <div class="card-value"><?php echo number_format($sawStats['rata_rata'], 4); ?></div>
                        <div class="card-description">Skor rata-rata peserta</div>
                    </div>

                    <div class="dashboard-card">
                        <div class="card-icon">
                            <i class="fas fa-arrow-down"></i>
                        </div>
                        <div class="card-title">Skor Terendah</div>
                        <div class="card-value"><?php echo number_format($sawStats['skor_terendah'], 4); ?></div>
                        <div class="card-description">Skor minimal peserta</div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Daftar Ranking -->
            <div class="form-card">
                <h2 class="form-section-title">
                    <i class="fas fa-list-ol"></i> Daftar Ranking Penerima Bantuan
                </h2>

                <?php if (empty($rankingList)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-inbox"></i>
                        </div>
                        <div class="empty-state-title">Belum Ada Data Ranking</div>
                        <div class="empty-state-description">
                            Data ranking akan muncul setelah proses verifikasi dan perhitungan SAW selesai dilakukan oleh admin.
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($rankingList as $ranking): ?>
                        <?php
                        $isCurrentUser = $ranking['nik'] == $_SESSION['nik'];
                        $cardStyle = $isCurrentUser ? 'border: 2px solid var(--color-primary); background: linear-gradient(135deg, var(--color-secondary) 0%, var(--color-background) 100%);' : '';
                        ?>
                        <div class="ranking-card" style="<?php echo $cardStyle; ?>">
                            <div class="ranking-header">
                                <div class="ranking-number" style="<?php echo $isCurrentUser ? 'background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-btn-bg) 100%); color: white;' : ''; ?>">
                                    <?php if ($ranking['peringkat'] <= 3): ?>
                                        <i class="fas fa-trophy"></i>
                                    <?php else: ?>
                                        #<?php echo $ranking['peringkat']; ?>
                                    <?php endif; ?>
                                </div>
                                <div class="ranking-info">
                                    <h3>
                                        <?php echo htmlspecialchars($ranking['nama_lengkap']); ?>
                                        <?php if ($isCurrentUser): ?>
                                            <span class="status-badge" style="background-color: #dbeafe; color: #1e40af; margin-left: 8px; font-size: 12px;">
                                                <i class="fas fa-user"></i> Anda
                                            </span>
                                        <?php endif; ?>
                                    </h3>
                                    <p>
                                        <i class="fas fa-id-card" style="margin-right: 6px; color: #9ca3af;"></i>
                                        NIK: <?php echo $ranking['nik']; ?>
                                    </p>
                                </div>
                                <div class="ranking-score">
                                    <div class="ranking-score-label">Skor SAW</div>
                                    <div class="ranking-score-value"><?php echo number_format($ranking['skor_total'], 4); ?></div>
                                    <?php if ($ranking['peringkat'] <= 3): ?>
                                        <div style="text-align: center; margin-top: 8px;">
                                            <span style="font-size: 12px; font-weight: 600; color: #10b981; background-color: #d1fae5; padding: 4px 12px; border-radius: 12px;">
                                                <i class="fas fa-check-circle"></i> Top 3
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Info Metode SAW -->
            <div class="form-card">
                <h2 class="form-section-title">
                    <i class="fas fa-calculator"></i> Tentang Metode SAW
                </h2>

                <div style="background: linear-gradient(135deg, var(--color-secondary) 0%, var(--color-background) 100%); padding: 24px; border-radius: 12px; margin-bottom: 24px;">
                    <p style="line-height: 1.8; color: var(--color-text); margin-bottom: 16px;">
                        <strong>Simple Additive Weighting (SAW)</strong> adalah metode penjumlahan terbobot yang
                        digunakan untuk menentukan penerima bantuan secara objektif dan transparan. Metode ini menilai setiap calon
                        penerima berdasarkan beberapa kriteria dengan bobot yang telah ditentukan.
                    </p>
                    <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background-color: rgba(255, 255, 255, 0.7); border-radius: 8px;">
                        <i class="fas fa-lightbulb" style="font-size: 24px; color: var(--color-primary);"></i>
                        <div style="font-size: 13px; color: #6b7280;">
                            Semakin tinggi skor SAW, semakin tinggi prioritas untuk menerima bantuan sosial.
                        </div>
                    </div>
                </div>

                <h4 style="font-size: 16px; font-weight: 600; margin-bottom: 16px; color: var(--color-text);">
                    <i class="fas fa-list-check" style="color: var(--color-primary); margin-right: 8px;"></i>
                    Kriteria Penilaian
                </h4>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div style="padding: 16px; background-color: #fff; border: 1px solid #e5e7eb; border-radius: 12px; height: 100%;">
                            <div style="display: flex; align-items: start; gap: 12px;">
                                <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #dc2626; font-size: 18px; flex-shrink: 0;">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                                <div>
                                    <h5 style="font-size: 14px; font-weight: 600; margin-bottom: 6px;">Penghasilan (25%)</h5>
                                    <p style="font-size: 13px; color: #6b7280; margin: 0; line-height: 1.6;">Semakin rendah penghasilan, semakin tinggi prioritas</p>
                                    <span style="font-size: 11px; background-color: #fee2e2; color: #dc2626; padding: 2px 8px; border-radius: 6px; display: inline-block; margin-top: 6px; font-weight: 600;">COST</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div style="padding: 16px; background-color: #fff; border: 1px solid #e5e7eb; border-radius: 12px; height: 100%;">
                            <div style="display: flex; align-items: start; gap: 12px;">
                                <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #dc2626; font-size: 18px; flex-shrink: 0;">
                                    <i class="fas fa-home"></i>
                                </div>
                                <div>
                                    <h5 style="font-size: 14px; font-weight: 600; margin-bottom: 6px;">Status Rumah (15%)</h5>
                                    <p style="font-size: 13px; color: #6b7280; margin: 0; line-height: 1.6;">Prioritas untuk yang tidak memiliki rumah sendiri</p>
                                    <span style="font-size: 11px; background-color: #fee2e2; color: #dc2626; padding: 2px 8px; border-radius: 6px; display: inline-block; margin-top: 6px; font-weight: 600;">COST</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div style="padding: 16px; background-color: #fff; border: 1px solid #e5e7eb; border-radius: 12px; height: 100%;">
                            <div style="display: flex; align-items: start; gap: 12px;">
                                <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #dc2626; font-size: 18px; flex-shrink: 0;">
                                    <i class="fas fa-bolt"></i>
                                </div>
                                <div>
                                    <h5 style="font-size: 14px; font-weight: 600; margin-bottom: 6px;">Daya Listrik (15%)</h5>
                                    <p style="font-size: 13px; color: #6b7280; margin: 0; line-height: 1.6;">Daya listrik rendah mendapat nilai lebih tinggi</p>
                                    <span style="font-size: 11px; background-color: #fee2e2; color: #dc2626; padding: 2px 8px; border-radius: 6px; display: inline-block; margin-top: 6px; font-weight: 600;">COST</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div style="padding: 16px; background-color: #fff; border: 1px solid #e5e7eb; border-radius: 12px; height: 100%;">
                            <div style="display: flex; align-items: start; gap: 12px;">
                                <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #dc2626; font-size: 18px; flex-shrink: 0;">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <div>
                                    <h5 style="font-size: 14px; font-weight: 600; margin-bottom: 6px;">Pengeluaran (20%)</h5>
                                    <p style="font-size: 13px; color: #6b7280; margin: 0; line-height: 1.6;">Pengeluaran tinggi mendapat nilai lebih tinggi</p>
                                    <span style="font-size: 11px; background-color: #fee2e2; color: #dc2626; padding: 2px 8px; border-radius: 6px; display: inline-block; margin-top: 6px; font-weight: 600;">COST</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div style="padding: 16px; background-color: #fff; border: 1px solid #e5e7eb; border-radius: 12px; height: 100%;">
                            <div style="display: flex; align-items: start; gap: 12px;">
                                <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #065f46; font-size: 18px; flex-shrink: 0;">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div>
                                    <h5 style="font-size: 14px; font-weight: 600; margin-bottom: 6px;">Jumlah Keluarga (15%)</h5>
                                    <p style="font-size: 13px; color: #6b7280; margin: 0; line-height: 1.6;">Semakin banyak anggota keluarga, semakin tinggi prioritas</p>
                                    <span style="font-size: 11px; background-color: #d1fae5; color: #065f46; padding: 2px 8px; border-radius: 6px; display: inline-block; margin-top: 6px; font-weight: 600;">BENEFIT</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div style="padding: 16px; background-color: #fff; border: 1px solid #e5e7eb; border-radius: 12px; height: 100%;">
                            <div style="display: flex; align-items: start; gap: 12px;">
                                <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #065f46; font-size: 18px; flex-shrink: 0;">
                                    <i class="fas fa-graduation-cap"></i>
                                </div>
                                <div>
                                    <h5 style="font-size: 14px; font-weight: 600; margin-bottom: 6px;">Anak Usia Sekolah (10%)</h5>
                                    <p style="font-size: 13px; color: #6b7280; margin: 0; line-height: 1.6;">Prioritas untuk keluarga dengan anak sekolah</p>
                                    <span style="font-size: 11px; background-color: #d1fae5; color: #065f46; padding: 2px 8px; border-radius: 6px; display: inline-block; margin-top: 6px; font-weight: 600;">BENEFIT</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="margin-top: 24px; padding: 16px; background-color: #f9fafb; border-radius: 12px; border-left: 4px solid var(--color-primary);">
                    <p style="font-size: 13px; color: #6b7280; margin: 0; line-height: 1.6;">
                        <i class="fas fa-info-circle" style="color: var(--color-primary); margin-right: 6px;"></i>
                        <strong>Catatan:</strong> Setiap kriteria memiliki bobot berbeda sesuai tingkat kepentingannya. Perhitungan dilakukan
                        secara otomatis oleh sistem untuk memastikan objektivitas dan transparansi.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>