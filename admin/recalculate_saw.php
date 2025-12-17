<?php

/**
 * ============================================
 * TOOL RECALCULATE SAW - ADMIN ONLY
 * File: admin/recalculate_saw.php
 * * Gunakan ini untuk recalculate SAW secara manual
 * jika terjadi error atau data tidak konsisten
 * ============================================
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once(__DIR__ . "/../functions/authentication.php");
require_once(__DIR__ . "/../functions/connection.php");
require_once(__DIR__ . "/../functions/saw.php");
require_once(__DIR__ . "/../functions/program.php");

// ✅ SECURITY: Hanya admin yang bisa akses
if (!isLogged() || !isAdmin()) {
    die("Access Denied - Admin Only");
}

$connection = getConnection();
$results = [];

// ✅ Handle recalculation request
if (isset($_POST['recalculate'])) {
    $program_id = isset($_POST['program_id']) ? intval($_POST['program_id']) : null;

    if ($program_id) {
        // Recalculate untuk program spesifik
        $program = getProgramById($program_id);

        if ($program) {
            $results[] = "🔄 Memproses program: " . htmlspecialchars($program['nama_program']);

            // Hapus data lama
            $deleteQuery = "DELETE tn FROM total_nilai tn 
                           JOIN pengajuan p ON tn.id_pengajuan = p.id 
                           WHERE p.id_program = $program_id";
            $connection->query($deleteQuery);
            $results[] = "🗑️ Data SAW lama dihapus";

            // Hitung ulang (Fungsi ini harus sudah support filter duplikat user)
            $sawResult = calculateSAW($program_id);

            if ($sawResult) {
                // Hitung jumlah data yang diproses
                $countQuery = "SELECT COUNT(*) as total FROM total_nilai tn 
                              JOIN pengajuan p ON tn.id_pengajuan = p.id 
                              WHERE p.id_program = $program_id";
                $countResult = $connection->query($countQuery);
                $count = $countResult->fetch_assoc()['total'];

                $results[] = "✅ Berhasil! $count user telah dihitung dan diranking";
            } else {
                $results[] = "❌ Gagal menghitung SAW - Tidak ada data terverifikasi atau error sistem";
            }
        } else {
            $results[] = "❌ Program tidak ditemukan";
        }
    } else {
        $results[] = "❌ ID Program tidak valid";
    }
}

// ✅ Get all programs
$programs = getAllPrograms();

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <title>Recalculate SAW - SIMBA DIY</title>
    <style>
        .result-box {
            background: #f9fafb;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            line-height: 1.8;
        }

        .result-box div {
            margin-bottom: 8px;
        }
    </style>
</head>

<body>
    <div class="dashboard-wrapper">
        <?php include('../templates/admin_sidebar.php'); ?>

        <div class="main-content" style="background-color: #f0f9ff;">
            <div class="page-header">
                <h1 class="page-title" style="color: #dc2626;">
                    <i class="fas fa-tools" style="color: #ef4444;"></i> Recalculate SAW (ADMIN TOOL)
                </h1>
                <p class="page-subtitle">Tool untuk menghitung ulang ranking SAW secara manual</p>
            </div>

            <div class="alert" style="background-color: #fee2e2; border-color: #fecaca; color: #991b1b;">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <strong>⚠️ PERINGATAN - TOOL ADMIN</strong>
                    <p style="margin: 8px 0 0 0;">
                        Tool ini akan menghitung ulang ranking SAW. Gunakan hanya jika:
                    </p>
                    <ul style="margin: 8px 0 0 20px; padding: 0;">
                        <li>Data ranking tidak konsisten</li>
                        <li>Terjadi duplikasi data</li>
                        <li>Setelah membersihkan database manual</li>
                    </ul>
                </div>
            </div>

            <?php if (!empty($results)): ?>
                <div class="form-card">
                    <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 16px;">
                        <i class="fas fa-clipboard-check" style="color: #10b981;"></i> Hasil Perhitungan
                    </h3>
                    <div class="result-box">
                        <?php foreach ($results as $msg): ?>
                            <div><?php echo $msg; ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="form-card">
                <h2 class="form-section-title" style="color: #1e40af;">
                    <i class="fas fa-calculator" style="color: #2563eb;"></i> Pilih Program untuk Recalculate
                </h2>

                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Pilih Program <span class="required">*</span></label>
                        <select name="program_id" class="form-select" required>
                            <option value="">-- Pilih Program --</option>
                            <?php foreach ($programs as $prog): ?>
                                <option value="<?php echo $prog['id']; ?>">
                                    <?php echo htmlspecialchars($prog['nama_program']); ?>
                                    (Status: <?php echo $prog['status']; ?>)
                                    - <?php echo date('M Y', strtotime($prog['tanggal_mulai'])); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text">Pilih program yang ingin dihitung ulang ranking SAW-nya</small>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <div>
                            <strong>Informasi Proses:</strong>
                            <ol style="margin: 8px 0 0 20px; padding: 0;">
                                <li>Data SAW lama untuk program ini akan dihapus</li>
                                <li>Sistem akan mengambil semua pengajuan <strong>Terverifikasi</strong></li>
                                <li>Duplikasi user akan difilter (ambil pengajuan terbaru)</li>
                                <li>Ranking baru akan disimpan ke database</li>
                            </ol>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" name="recalculate" class="btn btn-primary"
                            onclick="return confirm('Yakin ingin recalculate SAW untuk program ini?\n\nData ranking lama akan diganti dengan hasil perhitungan baru.')">
                            <i class="fas fa-sync"></i> Recalculate SAW
                        </button>
                        <a href="ranking.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali ke Ranking
                        </a>
                    </div>
                </form>
            </div>

            <div class="form-card">
                <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 16px;">
                    <i class="fas fa-bug" style="color: #ef4444;"></i> Debug Information (Cek Sinkronisasi Data)
                </h3>

                <?php
                // Show current SAW data per program
                foreach ($programs as $prog) {
                    $progId = $prog['id'];

                    // ✅ UPDATED QUERY: Menghitung Unique USER, bukan Unique Submission ID
                    // Agar kalau 1 user punya 2 pengajuan (1 diterima, 1 duplikat), tetap dianggap SINKRON.
                    $query = "SELECT 
                                COUNT(DISTINCT p.id_user) as total_user_verified,
                                COUNT(DISTINCT CASE WHEN tn.id IS NOT NULL THEN p.id_user END) as total_user_ranked
                              FROM pengajuan p
                              LEFT JOIN total_nilai tn ON p.id = tn.id_pengajuan
                              WHERE p.id_program = $progId AND p.status = 'Terverifikasi'";

                    $result = $connection->query($query);
                    $debugData = $result->fetch_assoc();

                    // Cek sinkronisasi berdasarkan USER
                    $isSync = $debugData['total_user_verified'] == $debugData['total_user_ranked'];
                    ?>
                    <div style="background: #f9fafb; padding: 16px; border-radius: 8px; margin-bottom: 12px;">
                        <div style="font-size: 14px; font-weight: 600; margin-bottom: 8px;">
                            <?php echo htmlspecialchars($prog['nama_program']); ?>
                            <span style="font-size: 12px; font-weight: normal; color: #6b7280; margin-left: 8px;">
                                (Status: <?php echo $prog['status']; ?>)
                            </span>
                        </div>
                        <div style="font-size: 13px; color: #6b7280;">
                            User Terverifikasi: <strong><?php echo $debugData['total_user_verified']; ?></strong> Orang |
                            User Diranking: <strong><?php echo $debugData['total_user_ranked']; ?></strong> Orang
                            <?php if (!$isSync): ?>
                                <span style="color: #dc2626; font-weight: 600; margin-left: 8px;">
                                    ⚠️ TIDAK SINKRON! (Ada user terverifikasi yang belum diranking)
                                </span>
                            <?php else: ?>
                                <span style="color: #10b981; font-weight: 600; margin-left: 8px;">
                                    ✓ OK (Semua user sudah dapat nilai)
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>