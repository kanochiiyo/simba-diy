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
$success = false;
$error = null;

$activeProgram = getActiveProgram();

if (!$activeProgram) {
    $error = "Tidak ada program bantuan yang aktif saat ini. Silakan coba lagi nanti.";
}

if ($activeProgram && hasReceivedInLast3Periods($id_user)) {
    $error = "Anda tidak dapat mendaftar karena sudah menerima bantuan dalam 3 periode terakhir. Silakan tunggu hingga periode berikutnya.";
    $activeProgram = null;
}

// ✅ FIX: Cek apakah sudah ada pengajuan UNTUK PROGRAM AKTIF INI
if ($activeProgram) {
    $currentPengajuan = getPengajuanStatus($id_user, $activeProgram['id']); // ← TAMBAHKAN PARAMETER
    if ($currentPengajuan && $currentPengajuan['status'] != 'Ditolak') {
        header("Location: submission_status.php");
        exit;
    }
}

// Proses form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_pengajuan'])) {
    if (!$activeProgram) {
        $error = "Tidak ada program aktif untuk pengajuan.";
    } elseif (!validateNIK($_POST['nik'])) {
        $error = "NIK harus 16 digit angka.";
    } elseif (!validatePhoneNumber($_POST['no_hp'])) {
        $error = "Nomor HP tidak valid.";
    } else {
        $_POST['id_program'] = $activeProgram['id'];
        $result = createPengajuan($_POST, $_FILES);
        if ($result) {
            $success = true;
            echo "<script>
                alert('Pengajuan berhasil dibuat! Data Anda akan segera diverifikasi.');
                window.location.href = 'submission_status.php';
            </script>";
        } else {
            $error = "Terjadi kesalahan saat menyimpan pengajuan.";
        }
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
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <title>Ajukan Bantuan - SIMBA DIY</title>
    <style>
        /* Style untuk Multi-step form */
        .step-content {
            display: none;
            /* Sembunyikan semua step secara default */
            animation: fadeIn 0.5s;
        }

        .step-content.active {
            display: block;
            /* Tampilkan hanya yang ada class active */
        }

        /* Style Indikator Progress */
        .step-indicator {
            text-align: center;
            padding: 16px;
            border-radius: 12px;
            transition: all 0.3s ease;
            background-color: #f3f4f6;
            color: #6b7280;
            height: 100%;
            cursor: default;
        }

        .step-indicator.active {
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-btn-bg) 100%);
            color: white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .step-indicator i {
            font-size: 32px;
            margin-bottom: 8px;
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .custom-file-upload {
            border: 2px dashed #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background-color: #f9fafb;
            display: block;
        }

        .custom-file-upload:hover {
            border-color: var(--color-primary);
            background-color: var(--color-background);
        }

        .custom-file-upload.has-file {
            border-color: var(--color-primary);
            background: linear-gradient(135deg, var(--color-secondary) 0%, var(--color-background) 100%);
        }

        .file-upload-icon {
            font-size: 32px;
            color: var(--color-primary);
            margin-bottom: 12px;
        }

        .file-upload-text {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 8px;
        }

        .file-upload-text strong {
            color: var(--color-text);
            font-weight: 600;
        }

        .file-info {
            display: none;
            margin-top: 12px;
            padding: 12px;
            background-color: white;
            border-radius: 8px;
            border: 1px solid var(--color-primary);
        }

        .file-info.show {
            display: block;
        }

        .file-name {
            font-size: 14px;
            font-weight: 600;
            color: var(--color-text);
            margin-bottom: 4px;
            word-break: break-all;
        }

        .file-size {
            font-size: 12px;
            color: #6b7280;
        }

        .remove-file {
            background: none;
            border: none;
            color: #ef4444;
            font-size: 20px;
            cursor: pointer;
            padding: 0;
            margin-left: 8px;
            transition: color 0.3s ease;
        }

        .remove-file:hover {
            color: #dc2626;
        }

        .required-badge {
            display: inline-block;
            background-color: #fee2e2;
            color: #dc2626;
            font-size: 11px;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 6px;
            margin-left: 8px;
        }

        .optional-badge {
            display: inline-block;
            background-color: #f3f4f6;
            color: #6b7280;
            font-size: 11px;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 6px;
            margin-left: 8px;
        }
    </style>
</head>

<body>
    <div class="dashboard-wrapper">
        <?php include('../templates/user_sidebar.php'); ?>

        <div class="main-content">
            <div class="page-header">
                <h1 class="page-title">Ajukan Bantuan</h1>
                <p class="page-subtitle">Lengkapi formulir di bawah ini untuk mengajukan bantuan sosial</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <div><?php echo $error; ?></div>
                </div>
            <?php endif; ?>

            <?php if ($activeProgram): ?>
                <div class="form-card"
                    style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none; color: white;">
                    <div class="row align-items-center">
                        <div class="col-lg-8">
                            <div style="display: flex; align-items: start; gap: 20px;">
                                <div
                                    style="width: 64px; height: 64px; background: rgba(255, 255, 255, 0.2); border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 28px; flex-shrink: 0;">
                                    <i class="fas fa-clipboard-check"></i>
                                </div>
                                <div>
                                    <h3 style="font-size: 22px; font-weight: 700; margin-bottom: 8px;">
                                        <?php echo htmlspecialchars($activeProgram['nama_program']); ?>
                                    </h3>
                                    <?php if ($activeProgram['deskripsi']): ?>
                                        <p style="font-size: 14px; opacity: 0.9; margin-bottom: 12px;">
                                            <?php echo htmlspecialchars($activeProgram['deskripsi']); ?>
                                        </p>
                                    <?php endif; ?>
                                    <div style="display: flex; gap: 24px; flex-wrap: wrap; margin-top: 12px;">
                                        <div>
                                            <div style="font-size: 12px; opacity: 0.8; margin-bottom: 4px;">Periode Program
                                            </div>
                                            <div style="font-size: 14px; font-weight: 600;">
                                                <?php echo date('d M Y', strtotime($activeProgram['tanggal_mulai'])); ?> -
                                                <?php echo date('d M Y', strtotime($activeProgram['tanggal_selesai'])); ?>
                                            </div>
                                        </div>
                                        <div>
                                            <div style="font-size: 12px; opacity: 0.8; margin-bottom: 4px;">Kuota Penerima
                                            </div>
                                            <div style="font-size: 14px; font-weight: 600;">
                                                <?php echo $activeProgram['kuota']; ?> Orang
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4" style="text-align: right;">
                            <div
                                style="background: rgba(255, 255, 255, 0.2); padding: 16px; border-radius: 12px; display: inline-block;">
                                <div style="font-size: 12px; opacity: 0.8; margin-bottom: 4px;">Status Program</div>
                                <div style="font-size: 18px; font-weight: 700;">
                                    <i class="fas fa-check-circle"></i> AKTIF
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!$activeProgram): ?>
                <div class="form-card">
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="empty-state-title">Tidak Ada Program Aktif</div>
                        <div class="empty-state-description">
                            Saat ini belum ada program bantuan yang dibuka. Silakan kembali lagi nanti atau hubungi admin
                            untuk informasi lebih lanjut.
                        </div>
                        <a href="index.php" class="btn btn-primary" style="margin-top: 24px;">
                            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                        </a>
                    </div>
                </div>
            <?php else: ?>

                <div class="form-card mb-4">
                    <div class="row g-3">
                        <div class="col-md-3 col-6">
                            <div class="step-indicator active" id="indicator-1">
                                <i class="fas fa-user"></i>
                                <div style="font-size: 13px; font-weight: 600;">1. Data Diri</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="step-indicator" id="indicator-2">
                                <i class="fas fa-money-bill-wave"></i>
                                <div style="font-size: 13px; font-weight: 600;">2. Ekonomi</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="step-indicator" id="indicator-3">
                                <i class="fas fa-users"></i>
                                <div style="font-size: 13px; font-weight: 600;">3. Keluarga</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="step-indicator" id="indicator-4">
                                <i class="fas fa-file-upload"></i>
                                <div style="font-size: 13px; font-weight: 600;">4. Dokumen</div>
                            </div>
                        </div>
                    </div>
                </div>

                <form method="POST" enctype="multipart/form-data" id="pengajuanForm">

                    <div class="step-content active" id="step-1">
                        <div class="form-card">
                            <h2 class="form-section-title">
                                <i class="fas fa-user"></i> Data Diri
                            </h2>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">NIK <span class="required">*</span></label>
                                        <input type="text" name="nik" class="form-control" required maxlength="16"
                                            pattern="[0-9]{16}" placeholder="Masukkan 16 digit NIK">
                                        <small class="form-text">NIK harus 16 digit angka</small>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Nomor KK <span class="required">*</span></label>
                                        <input type="text" name="no_kk" class="form-control" required maxlength="16"
                                            pattern="[0-9]{16}" placeholder="Masukkan 16 digit Nomor KK">
                                        <small class="form-text">Nomor KK harus 16 digit angka</small>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Nama Lengkap <span class="required">*</span></label>
                                <input type="text" name="nama_lengkap" class="form-control" required
                                    placeholder="Masukkan nama lengkap sesuai KTP">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Alamat Lengkap <span class="required">*</span></label>
                                <textarea name="alamat" class="form-control" rows="3" required
                                    placeholder="Masukkan alamat lengkap sesuai KTP"></textarea>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Nomor HP <span class="required">*</span></label>
                                <input type="text" name="no_hp" class="form-control" required pattern="[0-9]{10,13}"
                                    autofocus placeholder="Contoh: 081234567890">
                                <small class="form-text">Nomor HP yang dapat dihubungi</small>
                            </div>

                            <div class="d-flex justify-content-end mt-4">
                                <button type="button" class="btn btn-primary" onclick="nextStep(2)">
                                    Lanjut <i class="fas fa-arrow-right ms-2"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="step-content" id="step-2">
                        <div class="form-card">
                            <h2 class="form-section-title">
                                <i class="fas fa-money-bill-wave"></i> Data Ekonomi
                            </h2>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Penghasilan per Bulan <span
                                                class="required">*</span></label>
                                        <select name="gaji" class="form-select" required>
                                            <option value="">Pilih Range Penghasilan</option>
                                            <option value="500000">
                                                < Rp 1.000.000</option>
                                            <option value="1500000">Rp 1.000.000 - Rp 1.999.000</option>
                                            <option value="2750000">Rp 2.000.000 - Rp 3.500.000</option>
                                            <option value="4000000">> Rp 3.500.000</option>
                                        </select>
                                        <small class="form-text">Pilih range penghasilan bulanan keluarga</small>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Pengeluaran per Bulan <span
                                                class="required">*</span></label>
                                        <select name="pengeluaran" class="form-select" required>
                                            <option value="">Pilih Range Pengeluaran</option>
                                            <option value="500000">
                                                < Rp 1.000.000</option>
                                            <option value="1500000">Rp 1.000.000 - Rp 1.999.000</option>
                                            <option value="2750000">Rp 2.000.000 - Rp 3.500.000</option>
                                            <option value="4000000">> Rp 3.500.000</option>
                                        </select>
                                        <small class="form-text">Pilih range pengeluaran bulanan keluarga</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Kepemilikan Rumah <span class="required">*</span></label>
                                        <select name="status_rumah" class="form-select" required>
                                            <option value="">Pilih Status Kepemilikan</option>
                                            <option value="Sewa">Sewa</option>
                                            <option value="Keluarga">Keluarga</option>
                                            <option value="Pribadi">Pribadi</option>
                                        </select>
                                        <small class="form-text">Status kepemilikan tempat tinggal</small>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Kelistrikan <span class="required">*</span></label>
                                        <select name="daya_listrik" class="form-select" required>
                                            <option value="">Pilih Status Kelistrikan</option>
                                            <option value="Menumpang">Menumpang</option>
                                            <option value="Pribadi 450 Watt">Pribadi 450 Watt</option>
                                            <option value="Pribadi 900 Watt">Pribadi 900 Watt</option>
                                            <option value="Pribadi 1200 Watt">Pribadi 1200 Watt</option>
                                            <option value="Pribadi > 1200 Watt">Pribadi > 1200 Watt</option>
                                        </select>
                                        <small class="form-text">Status dan daya listrik rumah</small>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between mt-4">
                                <button type="button" class="btn btn-secondary" onclick="prevStep(1)">
                                    <i class="fas fa-arrow-left me-2"></i> Kembali
                                </button>
                                <button type="button" class="btn btn-primary" onclick="nextStep(3)">
                                    Lanjut <i class="fas fa-arrow-right ms-2"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="step-content" id="step-3">
                        <div class="form-card">
                            <h2 class="form-section-title">
                                <i class="fas fa-users"></i> Data Keluarga
                            </h2>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Jumlah Anggota Keluarga <span
                                                class="required">*</span></label>
                                        <select name="jml_keluarga" class="form-select" required>
                                            <option value="">Pilih Jumlah Anggota</option>
                                            <option value="1">1 - 2 orang</option>
                                            <option value="3">3 orang</option>
                                            <option value="4">4 orang</option>
                                            <option value="5">5 orang</option>
                                            <option value="6">> 5 orang</option>
                                        </select>
                                        <small class="form-text">Termasuk Anda dalam satu KK</small>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Keberadaan Anak Usia Sekolah <span
                                                class="required">*</span></label>
                                        <select name="jml_anak_sekolah" class="form-select" required>
                                            <option value="">Pilih Jumlah Anak Sekolah</option>
                                            <option value="0">Tidak ada</option>
                                            <option value="1">1 orang</option>
                                            <option value="2">2 orang</option>
                                            <option value="3">3 orang</option>
                                            <option value="4">> 3 orang</option>
                                        </select>
                                        <small class="form-text">Anak usia 5-18 tahun yang masih sekolah</small>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between mt-4">
                                <button type="button" class="btn btn-secondary" onclick="prevStep(2)">
                                    <i class="fas fa-arrow-left me-2"></i> Kembali
                                </button>
                                <button type="button" class="btn btn-primary" onclick="nextStep(4)">
                                    Lanjut <i class="fas fa-arrow-right ms-2"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="step-content" id="step-4">
                        <div class="form-card">
                            <h2 class="form-section-title">
                                <i class="fas fa-file-upload"></i> Dokumen Pendukung
                            </h2>

                            <div class="alert alert-info mb-4">
                                <i class="fas fa-info-circle"></i>
                                <div>
                                    <strong>Ketentuan Upload Dokumen:</strong>
                                    <ul style="margin: 10px 0 0 0; padding-left: 20px;">
                                        <li>Format file: JPG, PNG, atau PDF</li>
                                        <li>Ukuran maksimal: 2 MB per file</li>
                                        <li>Pastikan foto/scan dokumen jelas dan terbaca</li>
                                        <li>Dokumen bertanda <span class="required-badge">WAJIB</span> harus diupload</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label class="form-label">
                                        <i class="fas fa-id-card" style="color: #3b82f6; margin-right: 6px;"></i>
                                        KTP <span class="required-badge">WAJIB</span>
                                    </label>
                                    <label for="ktp" class="custom-file-upload" id="ktp-label">
                                        <div class="file-upload-icon">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                        </div>
                                        <div class="file-upload-text">
                                            <strong>Klik untuk upload KTP</strong>
                                            <div style="font-size: 12px; margin-top: 4px;">JPG, PNG, atau PDF (Max. 2MB)
                                            </div>
                                        </div>
                                        <input type="file" name="ktp" id="ktp" accept="image/*,application/pdf" required
                                            style="display: none;" onchange="handleFileSelect(this, 'ktp')">
                                        <div class="file-info" id="ktp-info"></div>
                                    </label>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">
                                        <i class="fas fa-users" style="color: #8b5cf6; margin-right: 6px;"></i>
                                        Kartu Keluarga <span class="required-badge">WAJIB</span>
                                    </label>
                                    <label for="kk" class="custom-file-upload" id="kk-label">
                                        <div class="file-upload-icon">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                        </div>
                                        <div class="file-upload-text">
                                            <strong>Klik untuk upload Kartu Keluarga</strong>
                                            <div style="font-size: 12px; margin-top: 4px;">JPG, PNG, atau PDF (Max. 2MB)
                                            </div>
                                        </div>
                                        <input type="file" name="kk" id="kk" accept="image/*,application/pdf" required
                                            style="display: none;" onchange="handleFileSelect(this, 'kk')">
                                        <div class="file-info" id="kk-info"></div>
                                    </label>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">
                                        <i class="fas fa-money-check" style="color: #10b981; margin-right: 6px;"></i>
                                        Slip Gaji / Bukti Penghasilan <span class="optional-badge">OPSIONAL</span>
                                    </label>
                                    <label for="slip_gaji" class="custom-file-upload" id="slip_gaji-label">
                                        <div class="file-upload-icon">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                        </div>
                                        <div class="file-upload-text">
                                            <strong>Klik untuk upload Slip Gaji</strong>
                                            <div style="font-size: 12px; margin-top: 4px;">JPG, PNG, atau PDF (Max. 2MB)
                                            </div>
                                        </div>
                                        <input type="file" name="slip_gaji" id="slip_gaji" accept="image/*,application/pdf"
                                            style="display: none;" onchange="handleFileSelect(this, 'slip_gaji')">
                                        <div class="file-info" id="slip_gaji-info"></div>
                                    </label>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">
                                        <i class="fas fa-home" style="color: #f59e0b; margin-right: 6px;"></i>
                                        Foto Rumah <span class="optional-badge">OPSIONAL</span>
                                    </label>
                                    <label for="foto_rumah" class="custom-file-upload" id="foto_rumah-label">
                                        <div class="file-upload-icon">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                        </div>
                                        <div class="file-upload-text">
                                            <strong>Klik untuk upload Foto Rumah</strong>
                                            <div style="font-size: 12px; margin-top: 4px;">JPG atau PNG (Max. 2MB)</div>
                                        </div>
                                        <input type="file" name="foto_rumah" id="foto_rumah" accept="image/*"
                                            style="display: none;" onchange="handleFileSelect(this, 'foto_rumah')">
                                        <div class="file-info" id="foto_rumah-info"></div>
                                    </label>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">
                                        <i class="fas fa-file-alt" style="color: #ef4444; margin-right: 6px;"></i>
                                        Surat Keterangan Rumah <span class="optional-badge">OPSIONAL</span>
                                    </label>
                                    <label for="surat_keterangan_rumah" class="custom-file-upload"
                                        id="surat_keterangan_rumah-label">
                                        <div class="file-upload-icon">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                        </div>
                                        <div class="file-upload-text">
                                            <strong>Klik untuk upload Surat Keterangan</strong>
                                            <div style="font-size: 12px; margin-top: 4px;">JPG, PNG, atau PDF (Max. 2MB)
                                            </div>
                                        </div>
                                        <input type="file" name="surat_keterangan_rumah" id="surat_keterangan_rumah"
                                            accept="image/*,application/pdf" style="display: none;"
                                            onchange="handleFileSelect(this, 'surat_keterangan_rumah')">
                                        <div class="file-info" id="surat_keterangan_rumah-info"></div>
                                    </label>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">
                                        <i class="fas fa-bolt" style="color: #06b6d4; margin-right: 6px;"></i>
                                        Rekening Listrik <span class="optional-badge">OPSIONAL</span>
                                    </label>
                                    <label for="rekening_listrik" class="custom-file-upload" id="rekening_listrik-label">
                                        <div class="file-upload-icon">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                        </div>
                                        <div class="file-upload-text">
                                            <strong>Klik untuk upload Rekening Listrik</strong>
                                            <div style="font-size: 12px; margin-top: 4px;">JPG, PNG, atau PDF (Max. 2MB)
                                            </div>
                                        </div>
                                        <input type="file" name="rekening_listrik" id="rekening_listrik"
                                            accept="image/*,application/pdf" style="display: none;"
                                            onchange="handleFileSelect(this, 'rekening_listrik')">
                                        <div class="file-info" id="rekening_listrik-info"></div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="form-card mt-3">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <div>
                                    <strong>Perhatian!</strong>
                                    <p style="margin: 5px 0 0 0;">Pastikan semua data yang Anda masukkan sudah benar sebelum
                                        dikirim.</p>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between gap-3">
                                <button type="button" class="btn btn-secondary" onclick="prevStep(3)">
                                    <i class="fas fa-arrow-left me-2"></i> Kembali
                                </button>

                                <div class="d-flex gap-2">
                                    <a href="index.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i> Batal
                                    </a>
                                    <button type="submit" name="submit_pengajuan" class="btn btn-primary">
                                        <i class="fas fa-paper-plane me-2"></i> Kirim Pengajuan
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function handleFileSelect(input, fieldId) {
            const file = input.files[0];
            const label = document.getElementById(fieldId + '-label');
            const info = document.getElementById(fieldId + '-info');

            if (file) {
                const fileSize = (file.size / 1024 / 1024).toFixed(2);

                if (fileSize > 2) {
                    alert('Ukuran file terlalu besar! Maksimal 2 MB');
                    input.value = '';
                    label.classList.remove('has-file');
                    info.classList.remove('show');
                    return;
                }

                label.classList.add('has-file');
                info.innerHTML = `
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-check-circle" style="color: #10b981; font-size: 20px;"></i>
                            <div>
                                <div class="file-name">${file.name}</div>
                                <div class="file-size">${fileSize} MB</div>
                            </div>
                        </div>
                        <button type="button" class="remove-file" onclick="removeFile('${fieldId}')">
                            <i class="fas fa-times-circle"></i>
                        </button>
                    </div>
                `;
                info.classList.add('show');

                const icon = label.querySelector('.file-upload-icon i');
                icon.className = 'fas fa-check-circle';
                icon.style.color = '#10b981';
            }
        }

        // --- LOGIKA STEP WIZARD ---
        let currentStep = 1;
        const totalSteps = 4;

        function showStep(step) {
            // 1. Sembunyikan semua konten step
            document.querySelectorAll('.step-content').forEach(el => el.classList.remove('active'));

            // 2. Tampilkan konten step yang dituju
            document.getElementById('step-' + step).classList.add('active');

            // 3. Update indikator di atas
            for (let i = 1; i <= totalSteps; i++) {
                const indicator = document.getElementById('indicator-' + i);
                if (i === step) {
                    indicator.classList.add('active'); // Highlight step sekarang
                } else {
                    indicator.classList.remove('active'); // Matikan highlight yang lain
                }
            }

            // Scroll ke paling atas form agar user melihat judul step baru
            window.scrollTo({
                top: 100,
                behavior: 'smooth'
            });
        }

        function nextStep(targetStep) {
            // Validasi input pada step saat ini sebelum lanjut
            if (!validateCurrentStep(currentStep)) {
                return; // Stop jika tidak valid
            }

            currentStep = targetStep;
            showStep(currentStep);
        }

        function prevStep(targetStep) {
            // Langsung pindah tanpa validasi
            currentStep = targetStep;
            showStep(currentStep);
        }

        function validateCurrentStep(step) {
            const currentStepDiv = document.getElementById('step-' + step);
            const inputs = currentStepDiv.querySelectorAll('input[required], select[required], textarea[required]');
            let isValid = true;

            inputs.forEach(input => {
                if (!input.value) {
                    isValid = false;
                    input.classList.add('is-invalid'); // Tambah border merah bootstrap

                    // Hapus merah saat user mulai mengetik/memilih
                    input.addEventListener('input', function () {
                        this.classList.remove('is-invalid');
                    });
                } else {
                    // Khusus validasi panjang NIK/KK
                    if ((input.name === 'nik' || input.name === 'no_kk') && input.value.length !== 16) {
                        alert(input.name.toUpperCase() + ' harus 16 digit!');
                        isValid = false;
                        input.classList.add('is-invalid');
                    }
                }
            });

            if (!isValid) {
                alert('Mohon lengkapi semua data wajib pada tahap ini sebelum melanjutkan.');
            }

            return isValid;
        }

        function removeFile(fieldId) {
            const input = document.getElementById(fieldId);
            const label = document.getElementById(fieldId + '-label');
            const info = document.getElementById(fieldId + '-info');

            input.value = '';
            label.classList.remove('has-file');
            info.classList.remove('show');
            info.innerHTML = '';

            const icon = label.querySelector('.file-upload-icon i');
            icon.className = 'fas fa-cloud-upload-alt';
            icon.style.color = 'var(--color-primary)';
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>