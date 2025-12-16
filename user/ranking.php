<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once(__DIR__ . "/../functions/authentication.php");
require_once(__DIR__ . "/../functions/submission.php");
require_once(__DIR__ . "/../functions/program.php");
require_once(__DIR__ . "/../functions/saw.php");

if (!isLogged() || isAdmin()) {
    header("Location: ../auth/login.php");
    exit;
}

$id_user = $_SESSION['id'];
$connection = getConnection();

// ✅ Get program yang USER pernah ikuti DAN sudah ditutup
$query = "SELECT DISTINCT pb.* FROM program_bantuan pb
          JOIN pengajuan p ON pb.id = p.id_program
          WHERE p.id_user = " . intval($id_user) . "
          AND pb.status = 'Tutup'
          ORDER BY pb.tanggal_selesai DESC";

$result = $connection->query($query);
$userPrograms = $result->fetch_all(MYSQLI_ASSOC);

// ✅ Filter program
$filter_program = $_GET['program'] ?? 'latest';

$selectedProgram = null;
if ($filter_program == 'latest') {
    $selectedProgram = !empty($userPrograms) ? $userPrograms[0] : null;
} else {
    // Cek apakah user pernah ikut program ini
    foreach ($userPrograms as $prog) {
        if ($prog['id'] == intval($filter_program)) {
            $selectedProgram = $prog;
            break;
        }
    }
}

// ✅ Get ranking dan user ranking
$rankingList = [];
$sawStats = ['total_peserta' => 0, 'skor_tertinggi' => 0, 'skor_terendah' => 0, 'rata_rata' => 0];
$userRanking = null;

if ($selectedProgram) {
    // Ambil semua ranking
    $rankingList = getRankingByProgram($selectedProgram['id']);

    // Ambil statistik
    $sawStats = getSAWStatisticsByProgram($selectedProgram['id']);

    // ✅ FIX: Gunakan getUserRanking dari submission.php
    $userRanking = getUserRanking($id_user, $selectedProgram['id']);

    // ✅ FIX: Hitung is_penerima manual karena fungsi getUserRanking tidak mengembalikannya
    if ($userRanking) {
        // Pastikan kuota diambil dari program jika tidak ada di array userRanking
        $kuota = $selectedProgram['kuota'];
        $userRanking['is_penerima'] = $userRanking['peringkat'] <= $kuota;
    }
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
        <?php include('../templates/user_sidebar.php'); ?>

        <div class="main-content">
            <div class="page-header">
                <h1 class="page-title">Hasil Ranking</h1>
                <p class="page-subtitle">Hasil perhitungan penerima bantuan berdasarkan metode SAW (Simple Additive Weighting)</p>
            </div>

            <?php if (empty($userPrograms)): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <strong>Belum Ada Ranking Tersedia</strong>
                        <p style="margin: 8px 0 0 0;">Anda belum pernah mengikuti program yang sudah ditutup, atau program yang Anda ikuti masih dalam proses.</p>
                    </div>
                </div>

                <div class="form-card">
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <div class="empty-state-title">Ranking Belum Tersedia</div>
                        <div class="empty-state-description">
                            Ranking akan muncul setelah program yang Anda ikuti ditutup dan perhitungan SAW selesai.
                        </div>
                        <a href="index.php" class="btn btn-primary" style="margin-top: 24px;">
                            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="form-card">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <label class="form-label">Pilih Program</label>
                            <select class="form-select" onchange="window.location.href='?program='+this.value">
                                <option value="latest" <?php echo $filter_program == 'latest' ? 'selected' : ''; ?>>Program Terbaru</option>
                                <?php foreach ($userPrograms as $prog): ?>
                                    <option value="<?php echo $prog['id']; ?>"
                                        <?php echo ($selectedProgram && $selectedProgram['id'] == $prog['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($prog['nama_program']); ?>
                                        (<?php echo date('M Y', strtotime($prog['tanggal_mulai'])); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text">Hanya menampilkan program yang pernah Anda ikuti</small>
                        </div>
                    </div>
                </div>

                <?php if ($selectedProgram): ?>
                    <div class="form-card" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none;">
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

                    <?php if ($userRanking): ?>
                        <div class="alert <?php echo $userRanking['is_penerima'] ? 'alert-success' : 'alert-info'; ?>">
                            <i class="fas fa-<?php echo $userRanking['is_penerima'] ? 'check-circle' : 'info-circle'; ?>"></i>
                            <div>
                                <strong><?php echo $userRanking['is_penerima'] ? 'Selamat! Anda adalah Penerima Bantuan' : 'Anda Masuk dalam Daftar Ranking'; ?></strong>
                                <p style="margin: 8px 0 0 0;">
                                    Anda berada di peringkat <strong>#<?php echo $userRanking['peringkat']; ?></strong> dari <?php echo $sawStats['total_peserta']; ?> peserta
                                    dengan skor <strong><?php echo number_format($userRanking['skor_total'], 4); ?></strong>
                                    <?php if ($userRanking['is_penerima']): ?>
                                        <br><strong>Anda masuk dalam <?php echo $selectedProgram['kuota']; ?> penerima bantuan!</strong>
                                    <?php else: ?>
                                        <br>Anda berada dalam daftar cadangan (Kuota: <?php echo $selectedProgram['kuota']; ?>).
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <div>
                                <strong>Data Ranking Tidak Ditemukan</strong>
                                <p style="margin: 8px 0 0 0;">Pengajuan Anda mungkin belum diverifikasi atau tidak memenuhi kriteria.</p>
                            </div>
                        </div>
                    <?php endif; ?>

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

                    <div class="form-card">
                        <h2 class="form-section-title">
                            <i class="fas fa-list-ol"></i> Daftar Ranking Program
                            <span style="font-size: 14px; font-weight: normal; color: #6b7280; margin-left: 10px;">
                                (<?php echo count($rankingList); ?> peserta)
                            </span>
                        </h2>

                        <?php if (empty($rankingList)): ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <i class="fas fa-inbox"></i>
                                </div>
                                <div class="empty-state-title">Belum Ada Data Ranking</div>
                                <div class="empty-state-description">
                                    Belum ada pengajuan yang terverifikasi untuk program ini.
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table" id="rankingTable">
                                    <thead>
                                        <tr>
                                            <th>Peringkat</th>
                                            <th>Nama</th>
                                            <th>NIK</th>
                                            <th>Skor SAW</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($rankingList as $ranking):
                                            $isCurrentUser = isset($_SESSION['nik']) && $ranking['nik'] == $_SESSION['nik'];
                                            $isPenerima = $ranking['peringkat'] <= $selectedProgram['kuota'];
                                            $rowStyle = $isCurrentUser ? 'background-color: #eff6ff; border-left: 4px solid #2563eb;' : '';
                                            if ($isPenerima && !$isCurrentUser) {
                                                $rowStyle = 'background-color: #f0fdf4;';
                                            }
                                        ?>
                                            <tr style="<?php echo $rowStyle; ?>">
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
                                                <td>
                                                    <strong><?php echo htmlspecialchars($ranking['nama_lengkap']); ?></strong>
                                                    <?php if ($isCurrentUser): ?>
                                                        <span class="badge bg-primary ms-2" style="font-size: 10px;">ANDA</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo $ranking['nik']; ?></td>
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
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div style="margin-top: 24px; padding: 16px; background-color: #f9fafb; border-radius: 12px; border-left: 4px solid #2563eb;">
                                <p style="font-size: 13px; color: #6b7280; margin: 0; line-height: 1.6;">
                                    <i class="fas fa-info-circle" style="color: #2563eb; margin-right: 6px;"></i>
                                    <strong>Keterangan:</strong> Peserta dengan latar hijau dan badge "PENERIMA" adalah yang masuk dalam kuota program (Top <?php echo $selectedProgram['kuota']; ?>).
                                    Sisanya adalah cadangan. Semua data ditampilkan untuk transparansi.
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="form-card">
                <h2 class="form-section-title">
                    <i class="fas fa-calculator"></i> Tentang Metode SAW
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
                                <i class="fas fa-minus-circle"></i> Kriteria Cost
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
                                <i class="fas fa-plus-circle"></i> Kriteria Benefit
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
                                Skor akhir dihitung berdasarkan penjumlahan bobot dikali nilai normalisasi setiap kriteria.
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