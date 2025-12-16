<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once(__DIR__ . "/../functions/authentication.php");
require_once(__DIR__ . "/../functions/submission.php");
require_once(__DIR__ . "/../functions/program.php");
require_once(__DIR__ . "/../functions/saw.php");

if (!isLogged() || !isAdmin()) {
    header("Location: ../auth/login.php");
    exit;
}

$connection = getConnection();

// ✅ Get filter
$filter_program = $_GET['program'] ?? 'latest';

// ✅ Get CLOSED programs only (yang sudah ditutup)
$closedPrograms = getAllPrograms('Tutup');

// ✅ Determine selected program
$selectedProgram = null;
if ($filter_program == 'latest') {
    $selectedProgram = !empty($closedPrograms) ? $closedPrograms[0] : null;
} else {
    $selectedProgram = getProgramById(intval($filter_program));
}

// ✅ Get ranking untuk program yang dipilih
$rankingList = [];
$sawStats = ['total_peserta' => 0, 'skor_tertinggi' => 0, 'skor_terendah' => 0, 'rata_rata' => 0];

if ($selectedProgram) {
    $rankingList = getRankingByProgram($selectedProgram['id']);
    $sawStats = getSAWStatisticsByProgram($selectedProgram['id']);
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
    <title>Hasil Ranking - SIMBA DIY</title>
</head>

<body>
    <div class="dashboard-wrapper">
        <?php include('../templates/admin_sidebar.php'); ?>

        <div class="main-content" style="background-color: #f0f9ff;">
            <div class="page-header">
                <h1 class="page-title" style="color: #1e40af;">
                    <i class="fas fa-trophy" style="color: #2563eb;"></i> Hasil Ranking SAW
                </h1>
                <p class="page-subtitle">Hasil perhitungan penerima bantuan berdasarkan metode Simple Additive Weighting (SAW)</p>
            </div>

            <?php if (empty($closedPrograms)): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <strong>Belum Ada Program yang Ditutup</strong>
                        <p style="margin: 8px 0 0 0;">Ranking akan muncul setelah ada program yang ditutup dan perhitungan SAW selesai. <a href="manage_programs.php" style="color: #92400e; font-weight: 600;">Kelola program</a></p>
                    </div>
                </div>

                <div class="form-card">
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="empty-state-title">Ranking Belum Tersedia</div>
                        <div class="empty-state-description">
                            Sistem akan otomatis menghitung ranking saat admin menutup program bantuan.
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Program Filter -->
                <div class="form-card">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <label class="form-label">Pilih Program</label>
                            <select class="form-select" onchange="window.location.href='?program='+this.value">
                                <option value="latest" <?php echo $filter_program == 'latest' ? 'selected' : ''; ?>>Program Terbaru</option>
                                <?php foreach ($closedPrograms as $prog): ?>
                                    <option value="<?php echo $prog['id']; ?>"
                                        <?php echo ($selectedProgram && $selectedProgram['id'] == $prog['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($prog['nama_program']); ?>
                                        (<?php echo date('M Y', strtotime($prog['tanggal_mulai'])); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <?php if ($selectedProgram): ?>
                            <div class="col-md-6">
                                <div style="text-align: right;">
                                    <button onclick="window.print()" class="btn" style="background-color: #10b981; color: white;">
                                        <i class="fas fa-print"></i> Cetak Laporan
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($selectedProgram): ?>
                    <!-- Program Info Banner -->
                    <div class="form-card" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none;">
                        <div class="row align-items-center">
                            <div class="col-lg-8">
                                <div style="display: flex; align-items: start; gap: 20px;">
                                    <div style="width: 64px; height: 64px; background: rgba(255, 255, 255, 0.2); border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 28px; flex-shrink: 0;">
                                        <i class="fas fa-award"></i>
                                    </div>
                                    <div>
                                        <h3 style="font-size: 22px; font-weight: 700; margin-bottom: 8px;">
                                            <?php echo htmlspecialchars($selectedProgram['nama_program']); ?>
                                        </h3>
                                        <div style="display: flex; gap: 24px; flex-wrap: wrap; margin-top: 12px;">
                                            <div>
                                                <div style="font-size: 12px; opacity: 0.8; margin-bottom: 4px;">Periode Program</div>
                                                <div style="font-size: 14px; font-weight: 600;">
                                                    <?php echo date('d M Y', strtotime($selectedProgram['tanggal_mulai'])); ?> -
                                                    <?php echo date('d M Y', strtotime($selectedProgram['tanggal_selesai'])); ?>
                                                </div>
                                            </div>
                                            <div>
                                                <div style="font-size: 12px; opacity: 0.8; margin-bottom: 4px;">Kuota Program</div>
                                                <div style="font-size: 14px; font-weight: 600;"><?php echo $selectedProgram['kuota']; ?> Penerima</div>
                                            </div>
                                            <div>
                                                <div style="font-size: 12px; opacity: 0.8; margin-bottom: 4px;">Status</div>
                                                <div style="font-size: 14px; font-weight: 600;">
                                                    <i class="fas fa-lock"></i> Program Ditutup
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics -->
                    <?php if ($sawStats['total_peserta'] > 0): ?>
                        <div class="dashboard-cards">
                            <div class="dashboard-card" style="border-left: 4px solid #8b5cf6;">
                                <div class="card-icon" style="background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%); color: #5b21b6;">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="card-title">Total Penerima</div>
                                <div class="card-value" style="color: #5b21b6;"><?php echo min($sawStats['total_peserta'], $selectedProgram['kuota']); ?></div>
                                <div class="card-description">Dari <?php echo $sawStats['total_peserta']; ?> peserta</div>
                            </div>

                            <div class="dashboard-card" style="border-left: 4px solid #10b981;">
                                <div class="card-icon" style="background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); color: #065f46;">
                                    <i class="fas fa-arrow-up"></i>
                                </div>
                                <div class="card-title">Skor Tertinggi</div>
                                <div class="card-value" style="color: #065f46;"><?php echo number_format($sawStats['skor_tertinggi'], 4); ?></div>
                                <div class="card-description">Prioritas utama</div>
                            </div>

                            <div class="dashboard-card" style="border-left: 4px solid #3b82f6;">
                                <div class="card-icon" style="background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); color: #1e40af;">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <div class="card-title">Rata-rata Skor</div>
                                <div class="card-value" style="color: #1e40af;"><?php echo number_format($sawStats['rata_rata'], 4); ?></div>
                                <div class="card-description">Skor tengah</div>
                            </div>

                            <div class="dashboard-card" style="border-left: 4px solid #f59e0b;">
                                <div class="card-icon" style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); color: #92400e;">
                                    <i class="fas fa-arrow-down"></i>
                                </div>
                                <div class="card-title">Skor Terendah</div>
                                <div class="card-value" style="color: #92400e;"><?php echo number_format($sawStats['skor_terendah'], 4); ?></div>
                                <div class="card-description">Batas bawah</div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Ranking List -->
                    <div class="form-card">
                        <h2 class="form-section-title" style="color: #1e40af;">
                            <i class="fas fa-list-ol" style="color: #2563eb;"></i> Daftar Ranking Penerima
                            <span style="font-size: 14px; font-weight: normal; color: #6b7280; margin-left: 10px;">
                                (<?php echo count($rankingList); ?> peserta terverifikasi)
                            </span>
                        </h2>

                        <?php if (empty($rankingList)): ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <i class="fas fa-chart-bar"></i>
                                </div>
                                <div class="empty-state-title">Belum Ada Data Ranking</div>
                                <div class="empty-state-description">
                                    Belum ada pengajuan yang terverifikasi untuk program ini.
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- ✅ Tampilkan SEMUA ranking (transparan) -->
                            <div class="table-responsive">
                                <table class="table" id="rankingTable">
                                    <thead>
                                        <tr>
                                            <th>Peringkat</th>
                                            <th>Nama</th>
                                            <th>NIK</th>
                                            <th>No. HP</th>
                                            <th>Skor SAW</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($rankingList as $ranking):
                                            // ✅ Penerima = yang masuk kuota
                                            $isPenerima = $ranking['peringkat'] <= $selectedProgram['kuota'];
                                        ?>
                                            <tr style="<?php echo $isPenerima ? 'background-color: #f0fdf4;' : ''; ?>">
                                                <td>
                                                    <div style="display: flex; align-items: center; gap: 8px;">
                                                        <?php if ($ranking['peringkat'] <= 3): ?>
                                                            <i class="fas fa-trophy" style="color: #fbbf24; font-size: 18px;"></i>
                                                        <?php endif; ?>
                                                        <strong style="font-size: 18px; color: <?php echo $isPenerima ? '#10b981' : '#6b7280'; ?>;">
                                                            #<?php echo $ranking['peringkat']; ?>
                                                        </strong>
                                                        <?php if ($isPenerima): ?>
                                                            <span style="font-size: 10px; background-color: #10b981; color: white; padding: 2px 6px; border-radius: 6px; font-weight: 600;">
                                                                PENERIMA
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td><strong><?php echo htmlspecialchars($ranking['nama_lengkap']); ?></strong></td>
                                                <td><?php echo $ranking['nik']; ?></td>
                                                <td><?php echo $ranking['no_hp']; ?></td>
                                                <td>
                                                    <strong style="font-size: 16px; color: #2563eb;">
                                                        <?php echo number_format($ranking['skor_total'], 4); ?>
                                                    </strong>
                                                </td>
                                                <td>
                                                    <span class="status-badge status-terverifikasi">
                                                        Terverifikasi
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="manage_submissions.php?id=<?php echo $ranking['id']; ?>" class="btn btn-sm" style="background-color: #2563eb; color: white; padding: 6px 12px; border-radius: 6px; font-size: 13px;">
                                                        <i class="fas fa-eye"></i> Detail
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Legend -->
                            <div style="margin-top: 24px; padding: 16px; background-color: #f9fafb; border-radius: 12px; border-left: 4px solid #2563eb;">
                                <p style="font-size: 13px; color: #6b7280; margin: 0; line-height: 1.6;">
                                    <i class="fas fa-info-circle" style="color: #2563eb; margin-right: 6px;"></i>
                                    <strong>Keterangan:</strong> Peserta dengan latar hijau dan badge "PENERIMA" adalah yang masuk dalam kuota program (Top <?php echo $selectedProgram['kuota']; ?>).
                                    Sisanya adalah cadangan jika ada penerima yang mengundurkan diri. Semua data ditampilkan untuk transparansi.
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- SAW Method Info -->
            <div class="form-card">
                <h2 class="form-section-title" style="color: #1e40af;">
                    <i class="fas fa-calculator" style="color: #2563eb;"></i> Tentang Metode SAW
                </h2>

                <div style="background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); padding: 24px; border-radius: 12px; margin-bottom: 24px;">
                    <p style="line-height: 1.8; color: #1e40af; margin-bottom: 16px;">
                        <strong>Simple Additive Weighting (SAW)</strong> adalah metode penjumlahan terbobot yang digunakan untuk menentukan
                        penerima bantuan secara objektif dan transparan. Metode ini menilai setiap calon penerima berdasarkan beberapa
                        kriteria dengan bobot yang telah ditentukan.
                    </p>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <div style="padding: 16px; background-color: #fee2e2; border-radius: 12px; height: 100%;">
                            <h5 style="font-size: 14px; font-weight: 600; color: #dc2626; margin-bottom: 12px;">
                                <i class="fas fa-minus-circle"></i> Kriteria Cost (Semakin Kecil Semakin Baik)
                            </h5>
                            <ul style="font-size: 13px; color: #7f1d1d; line-height: 1.8; margin: 0; padding-left: 20px;">
                                <li>Penghasilan (30%)</li>
                                <li>Kepemilikan Rumah (15%)</li>
                                <li>Daya Listrik (10%)</li>
                                <li>Pengeluaran (20%)</li>
                            </ul>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div style="padding: 16px; background-color: #d1fae5; border-radius: 12px; height: 100%;">
                            <h5 style="font-size: 14px; font-weight: 600; color: #10b981; margin-bottom: 12px;">
                                <i class="fas fa-plus-circle"></i> Kriteria Benefit (Semakin Besar Semakin Baik)
                            </h5>
                            <ul style="font-size: 13px; color: #065f46; line-height: 1.8; margin: 0; padding-left: 20px;">
                                <li>Jumlah Anggota Keluarga (15%)</li>
                                <li>Anak Usia Sekolah (10%)</li>
                            </ul>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div style="padding: 16px; background-color: #fef3c7; border-radius: 12px; height: 100%;">
                            <h5 style="font-size: 14px; font-weight: 600; color: #f59e0b; margin-bottom: 12px;">
                                <i class="fas fa-info-circle"></i> Rumus Perhitungan
                            </h5>
                            <p style="font-size: 13px; color: #92400e; line-height: 1.8; margin: 0;">
                                <strong>Vi = Σ(wj × rij)</strong>
                                <br><br>
                                dimana:<br>
                                - Vi = skor akhir<br>
                                - wj = bobot kriteria<br>
                                - rij = nilai normalisasi
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>