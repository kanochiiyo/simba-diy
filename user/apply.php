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
    header("Location: status.php");
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
                window.location.href = 'status.php';
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
    <link rel="stylesheet" href="../css/dashboard.css">
    <title>Ajukan Bantuan - SIMBA DIY</title>
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

            <form method="POST" enctype="multipart/form-data">
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
                                <label class="form-label">Status Kepemilikan Rumah <span
                                        class="required">*</span></label>
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
                                <label class="form-label">Jumlah Anggota Keluarga <span
                                        class="required">*</span></label>
                                <input type="number" name="jml_keluarga" class="form-control" required min="1"
                                    placeholder="Contoh: 4">
                                <small class="form-text">Termasuk Anda</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Jumlah Anak Usia Sekolah <span
                                        class="required">*</span></label>
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
                            </ul>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">KTP <span class="required">*</span></label>
                            <div class="file-upload">
                                <input type="file" name="ktp" id="ktp" accept="image/*,application/pdf" required
                                    onchange="previewFile(this, 'ktp-preview')">
                                <label for="ktp" class="file-upload-label">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <div class="file-upload-text">
                                        <strong>Upload KTP</strong>
                                        <small>Klik untuk memilih file</small>
                                    </div>
                                </label>
                                <div id="ktp-preview" class="file-preview" style="display: none;"></div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Kartu Keluarga <span class="required">*</span></label>
                            <div class="file-upload">
                                <input type="file" name="kk" id="kk" accept="image/*,application/pdf" required
                                    onchange="previewFile(this, 'kk-preview')">
                                <label for="kk" class="file-upload-label">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <div class="file-upload-text">
                                        <strong>Upload Kartu Keluarga</strong>
                                        <small>Klik untuk memilih file</small>
                                    </div>
                                </label>
                                <div id="kk-preview" class="file-preview" style="display: none;"></div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Slip Gaji / Bukti Penghasilan</label>
                            <div class="file-upload">
                                <input type="file" name="slip_gaji" id="slip_gaji" accept="image/*,application/pdf"
                                    onchange="previewFile(this, 'slip_gaji-preview')">
                                <label for="slip_gaji" class="file-upload-label">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <div class="file-upload-text">
                                        <strong>Upload Slip Gaji</strong>
                                        <small>Opsional</small>
                                    </div>
                                </label>
                                <div id="slip_gaji-preview" class="file-preview" style="display: none;"></div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Foto Rumah</label>
                            <div class="file-upload">
                                <input type="file" name="foto_rumah" id="foto_rumah" accept="image/*"
                                    onchange="previewFile(this, 'foto_rumah-preview')">
                                <label for="foto_rumah" class="file-upload-label">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <div class="file-upload-text">
                                        <strong>Upload Foto Rumah</strong>
                                        <small>Opsional</small>
                                    </div>
                                </label>
                                <div id="foto_rumah-preview" class="file-preview" style="display: none;"></div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Surat Keterangan Rumah</label>
                            <div class="file-upload">
                                <input type="file" name="surat_keterangan_rumah" id="surat_keterangan_rumah"
                                    accept="image/*,application/pdf"
                                    onchange="previewFile(this, 'surat_keterangan_rumah-preview')">
                                <label for="surat_keterangan_rumah" class="file-upload-label">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <div class="file-upload-text">
                                        <strong>Upload Surat Keterangan</strong>
                                        <small>Opsional</small>
                                    </div>
                                </label>
                                <div id="surat_keterangan_rumah-preview" class="file-preview" style="display: none;">
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Rekening Listrik</label>
                            <div class="file-upload">
                                <input type="file" name="rekening_listrik" id="rekening_listrik"
                                    accept="image/*,application/pdf"
                                    onchange="previewFile(this, 'rekening_listrik-preview')">
                                <label for="rekening_listrik" class="file-upload-label">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <div class="file-upload-text">
                                        <strong>Upload Rekening Listrik</strong>
                                        <small>Opsional</small>
                                    </div>
                                </label>
                                <div id="rekening_listrik-preview" class="file-preview" style="display: none;"></div>
                            </div>
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
        function previewFile(input, previewId) {
            const preview = document.getElementById(previewId);
            const file = input.files[0];

            if (file) {
                const fileName = file.name;
                const fileSize = (file.size / 1024 / 1024).toFixed(2); // Convert to MB

                if (fileSize > 2) {
                    alert('Ukuran file terlalu besar! Maksimal 2 MB');
                    input.value = '';
                    preview.style.display = 'none';
                    return;
                }

                preview.innerHTML = `
                <div class="file-preview-info">
                    <div class="file-preview-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <span class="file-preview-name">${fileName} (${fileSize} MB)</span>
                </div>
                <button type="button" class="file-preview-remove" onclick="removeFile('${input.id}', '${previewId}')">
                    <i class="fas fa-times"></i>
                </button>
            `;
                preview.style.display = 'flex';
            }
        }

        function removeFile(inputId, previewId) {
            document.getElementById(inputId).value = '';
            document.getElementById(previewId).style.display = 'none';
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>