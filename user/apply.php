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
$success = false;
$error = null;

// Cek apakah sudah ada pengajuan yang sedang diproses
$currentPengajuan = getPengajuanStatus($id_user);
if ($currentPengajuan && $currentPengajuan['status'] != 'Ditolak') {
    header("Location: submission_status.php");
    exit;
}

// Proses form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_pengajuan'])) {
    // Validasi
    if (!validateNIK($_POST['nik'])) {
        $error = "NIK harus 16 digit angka.";
    } elseif (!validatePhoneNumber($_POST['no_hp'])) {
        $error = "Nomor HP tidak valid.";
    } else {
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <title>Ajukan Bantuan - SIMBA DIY</title>
    <style>
        .custom-file-upload {
            border: 2px dashed #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background-color: #f9fafb;
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

            <!-- Progress Steps -->
            <div class="form-card">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div style="text-align: center; padding: 16px; background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-btn-bg) 100%); border-radius: 12px; color: white;">
                            <i class="fas fa-user" style="font-size: 32px; margin-bottom: 8px;"></i>
                            <div style="font-size: 13px; font-weight: 600;">1. Data Diri</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div style="text-align: center; padding: 16px; background-color: #f3f4f6; border-radius: 12px; color: #6b7280;">
                            <i class="fas fa-money-bill-wave" style="font-size: 32px; margin-bottom: 8px;"></i>
                            <div style="font-size: 13px; font-weight: 600;">2. Data Ekonomi</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div style="text-align: center; padding: 16px; background-color: #f3f4f6; border-radius: 12px; color: #6b7280;">
                            <i class="fas fa-users" style="font-size: 32px; margin-bottom: 8px;"></i>
                            <div style="font-size: 13px; font-weight: 600;">3. Data Keluarga</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div style="text-align: center; padding: 16px; background-color: #f3f4f6; border-radius: 12px; color: #6b7280;">
                            <i class="fas fa-file-upload" style="font-size: 32px; margin-bottom: 8px;"></i>
                            <div style="font-size: 13px; font-weight: 600;">4. Upload Dokumen</div>
                        </div>
                    </div>
                </div>
            </div>

            <form method="POST" enctype="multipart/form-data" id="pengajuanForm">
                <!-- Data Diri -->
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
                        <input type="text" name="no_hp" class="form-control" required pattern="[0-9]{10,15}"
                            placeholder="Contoh: 081234567890">
                        <small class="form-text">Nomor HP yang dapat dihubungi</small>
                    </div>
                </div>

                <!-- Data Ekonomi -->
                <div class="form-card">
                    <h2 class="form-section-title">
                        <i class="fas fa-money-bill-wave"></i> Data Ekonomi
                    </h2>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Penghasilan per Bulan <span class="required">*</span></label>
                                <input type="number" name="gaji" class="form-control" required min="0"
                                    placeholder="Contoh: 2000000">
                                <small class="form-text">Dalam Rupiah</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Pengeluaran per Bulan <span class="required">*</span></label>
                                <input type="number" name="pengeluaran" class="form-control" required min="0"
                                    placeholder="Contoh: 1500000">
                                <small class="form-text">Dalam Rupiah</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Status Kepemilikan Rumah <span class="required">*</span></label>
                                <select name="status_rumah" class="form-select" required>
                                    <option value="">Pilih Status Rumah</option>
                                    <option value="Milik Sendiri">Milik Sendiri</option>
                                    <option value="Sewa">Sewa</option>
                                    <option value="Menumpang">Menumpang</option>
                                    <option value="Lainnya">Lainnya</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Daya Listrik <span class="required">*</span></label>
                                <select name="daya_listrik" class="form-select" required>
                                    <option value="">Pilih Daya Listrik</option>
                                    <option value="450">450 VA</option>
                                    <option value="900">900 VA</option>
                                    <option value="1300">1300 VA</option>
                                    <option value="2200">2200 VA</option>
                                    <option value="3500">3500 VA atau lebih</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Data Keluarga -->
                <div class="form-card">
                    <h2 class="form-section-title">
                        <i class="fas fa-users"></i> Data Keluarga
                    </h2>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Jumlah Anggota Keluarga <span class="required">*</span></label>
                                <input type="number" name="jml_keluarga" class="form-control" required min="1"
                                    placeholder="Contoh: 4">
                                <small class="form-text">Termasuk Anda</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Jumlah Anak Usia Sekolah <span class="required">*</span></label>
                                <input type="number" name="jml_anak_sekolah" class="form-control" required min="0"
                                    placeholder="Contoh: 2">
                                <small class="form-text">Usia 5-18 tahun</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Upload Dokumen -->
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
                        <!-- KTP -->
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
                                    <div style="font-size: 12px; margin-top: 4px;">JPG, PNG, atau PDF (Max. 2MB)</div>
                                </div>
                                <input type="file" name="ktp" id="ktp" accept="image/*,application/pdf" required
                                    style="display: none;" onchange="handleFileSelect(this, 'ktp')">
                                <div class="file-info" id="ktp-info"></div>
                            </label>
                        </div>

                        <!-- KK -->
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
                                    <div style="font-size: 12px; margin-top: 4px;">JPG, PNG, atau PDF (Max. 2MB)</div>
                                </div>
                                <input type="file" name="kk" id="kk" accept="image/*,application/pdf" required
                                    style="display: none;" onchange="handleFileSelect(this, 'kk')">
                                <div class="file-info" id="kk-info"></div>
                            </label>
                        </div>

                        <!-- Slip Gaji -->
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
                                    <div style="font-size: 12px; margin-top: 4px;">JPG, PNG, atau PDF (Max. 2MB)</div>
                                </div>
                                <input type="file" name="slip_gaji" id="slip_gaji" accept="image/*,application/pdf"
                                    style="display: none;" onchange="handleFileSelect(this, 'slip_gaji')">
                                <div class="file-info" id="slip_gaji-info"></div>
                            </label>
                        </div>

                        <!-- Foto Rumah -->
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

                        <!-- Surat Keterangan Rumah -->
                        <div class="col-md-6">
                            <label class="form-label">
                                <i class="fas fa-file-alt" style="color: #ef4444; margin-right: 6px;"></i>
                                Surat Keterangan Rumah <span class="optional-badge">OPSIONAL</span>
                            </label>
                            <label for="surat_keterangan_rumah" class="custom-file-upload" id="surat_keterangan_rumah-label">
                                <div class="file-upload-icon">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                </div>
                                <div class="file-upload-text">
                                    <strong>Klik untuk upload Surat Keterangan</strong>
                                    <div style="font-size: 12px; margin-top: 4px;">JPG, PNG, atau PDF (Max. 2MB)</div>
                                </div>
                                <input type="file" name="surat_keterangan_rumah" id="surat_keterangan_rumah"
                                    accept="image/*,application/pdf" style="display: none;"
                                    onchange="handleFileSelect(this, 'surat_keterangan_rumah')">
                                <div class="file-info" id="surat_keterangan_rumah-info"></div>
                            </label>
                        </div>

                        <!-- Rekening Listrik -->
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
                                    <div style="font-size: 12px; margin-top: 4px;">JPG, PNG, atau PDF (Max. 2MB)</div>
                                </div>
                                <input type="file" name="rekening_listrik" id="rekening_listrik"
                                    accept="image/*,application/pdf" style="display: none;"
                                    onchange="handleFileSelect(this, 'rekening_listrik')">
                                <div class="file-info" id="rekening_listrik-info"></div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="form-card">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div>
                            <strong>Perhatian!</strong>
                            <p style="margin: 5px 0 0 0;">Pastikan semua data yang Anda masukkan sudah benar. Data akan
                                diverifikasi oleh petugas kami.</p>
                        </div>
                    </div>

                    <div class="d-flex gap-3 justify-content-end">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Batal
                        </a>
                        <button type="submit" name="submit_pengajuan" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Kirim Pengajuan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function handleFileSelect(input, fieldId) {
            const file = input.files[0];
            const label = document.getElementById(fieldId + '-label');
            const info = document.getElementById(fieldId + '-info');

            if (file) {
                const fileSize = (file.size / 1024 / 1024).toFixed(2); // Convert to MB

                if (fileSize > 2) {
                    alert('Ukuran file terlalu besar! Maksimal 2 MB');
                    input.value = '';
                    label.classList.remove('has-file');
                    info.classList.remove('show');
                    return;
                }

                // Show file info
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

                // Update icon
                const icon = label.querySelector('.file-upload-icon i');
                icon.className = 'fas fa-check-circle';
                icon.style.color = '#10b981';
            }
        }

        function removeFile(fieldId) {
            const input = document.getElementById(fieldId);
            const label = document.getElementById(fieldId + '-label');
            const info = document.getElementById(fieldId + '-info');

            input.value = '';
            label.classList.remove('has-file');
            info.classList.remove('show');
            info.innerHTML = '';

            // Reset icon
            const icon = label.querySelector('.file-upload-icon i');
            icon.className = 'fas fa-cloud-upload-alt';
            icon.style.color = 'var(--color-primary)';
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>