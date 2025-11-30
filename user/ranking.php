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
$rankingList = getRanking();
$userRanking = getUserRanking($id_user);
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
    <title>Hasil Ranking - SIMBA DIY</title>
</head>

<body>
    <div class="dashboard-wrapper">
        <?php include('../templates/user_sidebar.php'); ?>

        <div class="main-content">
            <div class="page-header">
                <h1 class="page-title">Hasil Ranking</h1>
                <p class="page-subtitle">Hasil perhitungan penerima bantuan berdasarkan metode SAW</p>
            </div>

            <?php if ($userRanking): ?>
                <div class="alert alert-success">
                    <i class="fas fa-trophy"></i>
                    <div>
                        <strong>Selamat!</strong>
                        <p style="margin: 5px 0 0 0;">Anda berada di peringkat <strong>#<?php echo $userRanking['peringkat']; ?></strong>
                            dengan skor <strong><?php echo number_format($userRanking['skor_total'], 4); ?></strong></p>
                    </div>
                </div>
            <?php endif; ?>

            <div class="form-card">
                <h2 class="form-section-title">
                    <i class="fas fa-list-ol"></i> Daftar Ranking Penerima Bantuan
                </h2>

                <?php if (empty($rankingList)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <div>
                            <strong>Belum Ada Data Ranking</strong>
                            <p style="margin: 5px 0 0 0;">Data ranking akan muncul setelah proses verifikasi dan perhitungan SAW selesai.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($rankingList as $ranking): ?>
                        <div class="ranking-card">
                            <div class="ranking-header">
                                <div class="ranking-number">
                                    #<?php echo $ranking['peringkat']; ?>
                                </div>
                                <div class="ranking-info">
                                    <h3><?php echo htmlspecialchars($ranking['nama_lengkap']); ?></h3>
                                    <p>NIK: <?php echo $ranking['nik']; ?></p>
                                </div>
                                <div class="ranking-score">
                                    <div class="ranking-score-label">Skor SAW</div>
                                    <div class="ranking-score-value"><?php echo number_format($ranking['skor_total'], 4); ?></div>
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
                <p style="line-height: 1.8; color: var(--color-text); opacity: 0.8;">
                    <strong>Simple Additive Weighting (SAW)</strong> adalah metode penjumlahan terbobot yang
                    digunakan untuk menentukan penerima bantuan secara objektif. Metode ini menilai setiap calon
                    penerima berdasarkan beberapa kriteria:
                </p>
                <ul style="line-height: 2; color: var(--color-text); opacity: 0.8;">
                    <li><strong>Penghasilan</strong> - Semakin rendah penghasilan, semakin tinggi nilai</li>
                    <li><strong>Status Rumah</strong> - Prioritas untuk yang tidak memiliki rumah sendiri</li>
                    <li><strong>Daya Listrik</strong> - Daya listrik yang lebih rendah mendapat nilai lebih tinggi</li>
                    <li><strong>Pengeluaran</strong> - Pengeluaran yang tinggi mendapat nilai lebih tinggi</li>
                    <li><strong>Jumlah Keluarga</strong> - Semakin banyak anggota keluarga, semakin tinggi nilai</li>
                    <li><strong>Anak Usia Sekolah</strong> - Prioritas untuk keluarga dengan anak sekolah</li>
                </ul>
                <p style="line-height: 1.8; color: var(--color-text); opacity: 0.8;">
                    Setiap kriteria memiliki bobot yang berbeda sesuai tingkat kepentingannya. Perhitungan dilakukan
                    secara otomatis oleh sistem untuk memastikan objektivitas dan transparansi.
                </p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>