<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once(__DIR__ . "/../functions/authentication.php");
require_once(__DIR__ . "/../functions/connection.php");

if (!isLogged() || isAdmin()) {
    header("Location: ../auth/login.php");
    exit;
}

$id_user = $_SESSION['id'];
$connection = getConnection();

// Fetch user data
$query = "SELECT * FROM user WHERE id = " . intval($id_user);
$result = $connection->query($query);
$userData = $result->fetch_assoc();

$success = '';
$error = '';

// Handle profile update
if (isset($_POST['update_profile'])) {
    $nama = mysqli_real_escape_string($connection, trim($_POST['nama']));

    $updateQuery = "UPDATE user SET nama = '$nama' WHERE id = " . intval($id_user);

    if ($connection->query($updateQuery)) {
        $success = 'Profil berhasil diperbarui!';
        // Refresh user data
        $result = $connection->query($query);
        $userData = $result->fetch_assoc();
    } else {
        $error = 'Gagal memperbarui profil: ' . $connection->error;
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (password_verify($current_password, $userData['password'])) {
        if ($new_password === $confirm_password) {
            if (strlen($new_password) >= 6) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $updateQuery = "UPDATE user SET password = '$hashed_password' WHERE id = " . intval($id_user);

                if ($connection->query($updateQuery)) {
                    $success = 'Password berhasil diubah!';
                    // Refresh user data
                    $result = $connection->query($query);
                    $userData = $result->fetch_assoc();
                } else {
                    $error = 'Gagal mengubah password: ' . $connection->error;
                }
            } else {
                $error = 'Password baru minimal 6 karakter!';
            }
        } else {
            $error = 'Konfirmasi password tidak cocok!';
        }
    } else {
        $error = 'Password lama tidak sesuai!';
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
    <title>Profil Saya - SIMBA DIY</title>
</head>

<body>
    <div class="dashboard-wrapper">
        <?php include('../templates/user_sidebar.php'); ?>

        <div class="main-content">
            <div class="page-header">
                <h1 class="page-title">Profil Saya</h1>
                <p class="page-subtitle">Kelola informasi akun dan keamanan Anda</p>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <div><?php echo htmlspecialchars($success); ?></div>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <div><?php echo htmlspecialchars($error); ?></div>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- Profile Card - FIXED WITH SAFE ACCESS -->
                <div class="col-lg-4">
                    <div class="form-card">
                        <div style="text-align: center;">
                            <div style="width: 120px; height: 120px; background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-btn-bg) 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; color: white; font-size: 48px; font-weight: 700; box-shadow: 0 8px 24px rgba(255, 142, 123, 0.3);">
                                <?php echo strtoupper(substr($userData['nama'], 0, 1)); ?>
                            </div>
                            <h3 style="font-size: 22px; font-weight: 600; margin-bottom: 8px; color: var(--color-text);">
                                <?php echo htmlspecialchars($userData['nama']); ?>
                            </h3>
                            <p style="font-size: 14px; color: #6b7280; margin-bottom: 20px;">
                                <i class="fas fa-id-card" style="margin-right: 6px;"></i>
                                <?php echo htmlspecialchars($userData['nik']); ?>
                            </p>

                            <div style="background: linear-gradient(135deg, var(--color-secondary) 0%, var(--color-background) 100%); padding: 16px; border-radius: 12px; margin-bottom: 20px;">
                                <div style="font-size: 12px; color: #6b7280; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Role</div>
                                <div style="font-size: 16px; font-weight: 600; color: var(--color-text);">
                                    <i class="fas fa-user" style="color: var(--color-primary); margin-right: 6px;"></i>
                                    <?php

                                    // Fetch user data
                                    $query = "SELECT * FROM user WHERE id = " . intval($id_user);
                                    $result = $connection->query($query);
                                    $userData = $result->fetch_assoc();
                                    if ($userData['role'] == "user") {
                                        echo "Pengguna";
                                    } else {
                                        echo ucfirst($userData['role']);
                                    }
                                    ?>
                                </div>
                            </div>

                            <div style="background-color: #f9fafb; padding: 16px; border-radius: 12px;">
                                <div style="font-size: 12px; color: #6b7280; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Bergabung Sejak</div>
                                <div style="font-size: 14px; font-weight: 600; color: var(--color-text);">
                                    <i class="fas fa-calendar" style="color: var(--color-primary); margin-right: 6px;"></i>
                                    <?php
                                    // Format date dengan aman
                                    $timestamp = strtotime($userData['created_at']);
                                    if ($timestamp === false || $timestamp <= 0) {
                                        echo "Tidak tersedia";
                                    } else {
                                        echo date('d F Y', $timestamp);
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Info Card -->
                    <div class="form-card">
                        <h4 style="font-size: 16px; font-weight: 600; margin-bottom: 16px; color: var(--color-text);">
                            <i class="fas fa-info-circle" style="color: var(--color-primary); margin-right: 8px;"></i>
                            Informasi
                        </h4>
                        <div style="font-size: 13px; color: #6b7280; line-height: 1.8;">
                            <p style="margin-bottom: 12px;">
                                <i class="fas fa-shield-alt" style="color: var(--color-primary); margin-right: 6px;"></i>
                                Data Anda terlindungi dengan enkripsi
                            </p>
                            <p style="margin-bottom: 12px;">
                                <i class="fas fa-lock" style="color: var(--color-primary); margin-right: 6px;"></i>
                                Ubah password secara berkala untuk keamanan
                            </p>
                            <p style="margin: 0;">
                                <i class="fas fa-question-circle" style="color: var(--color-primary); margin-right: 6px;"></i>
                                Hubungi admin jika ada masalah
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Forms -->
                <div class="col-lg-8">
                    <!-- Update Profile Form -->
                    <div class="form-card">
                        <h2 class="form-section-title">
                            <i class="fas fa-user-edit"></i> Informasi Profil
                        </h2>

                        <form method="POST">
                            <div class="form-group">
                                <label class="form-label">Nama Lengkap <span class="required">*</span></label>
                                <input type="text" name="nama" class="form-control" value="<?php echo htmlspecialchars($userData['nama']); ?>" required>
                                <small class="form-text">Nama akan ditampilkan di seluruh sistem</small>
                            </div>

                            <div class="form-group">
                                <label class="form-label">NIK</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($userData['nik']); ?>" disabled>
                                <small class="form-text">NIK tidak dapat diubah</small>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Role</label>
                                <input type="text" class="form-control" value="<?php echo ucfirst($userData['role']); ?>" disabled>
                                <small class="form-text">Role akun Anda</small>
                            </div>

                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fas fa-save"></i> Simpan Perubahan
                            </button>
                        </form>
                    </div>

                    <!-- Change Password Form -->
                    <div class="form-card">
                        <h2 class="form-section-title">
                            <i class="fas fa-key"></i> Ubah Password
                        </h2>

                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <div>
                                <strong>Perhatian!</strong>
                                <p style="margin: 8px 0 0 0;">Pastikan password baru Anda kuat dan tidak mudah ditebak. Minimal 6 karakter.</p>
                            </div>
                        </div>

                        <form method="POST">
                            <div class="form-group">
                                <label class="form-label">Password Lama <span class="required">*</span></label>
                                <div class="password-wrapper">
                                    <input type="password" name="current_password" id="current_password" class="form-control" required>
                                    <i class="fa-solid fa-eye" onclick="togglePassword('current_password')"></i>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Password Baru <span class="required">*</span></label>
                                <div class="password-wrapper">
                                    <input type="password" name="new_password" id="new_password" class="form-control" minlength="6" required>
                                    <i class="fa-solid fa-eye" onclick="togglePassword('new_password')"></i>
                                </div>
                                <small class="form-text">Minimal 6 karakter</small>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Konfirmasi Password Baru <span class="required">*</span></label>
                                <div class="password-wrapper">
                                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" minlength="6" required>
                                    <i class="fa-solid fa-eye" onclick="togglePassword('confirm_password')"></i>
                                </div>
                                <small class="form-text">Ketik ulang password baru</small>
                            </div>

                            <button type="submit" name="change_password" class="btn btn-primary">
                                <i class="fas fa-key"></i> Ubah Password
                            </button>
                        </form>
                    </div>

                    <!-- Danger Zone -->
                    <div class="form-card" style="border: 2px solid #fee2e2;">
                        <h2 class="form-section-title" style="color: #dc2626;">
                            <i class="fas fa-exclamation-triangle"></i> Zona Berbahaya
                        </h2>

                        <div style="background-color: #fee2e2; padding: 20px; border-radius: 12px;">
                            <h4 style="font-size: 16px; font-weight: 600; margin-bottom: 12px; color: #991b1b;">
                                <i class="fas fa-trash-alt" style="margin-right: 8px;"></i>
                                Hapus Akun
                            </h4>
                            <p style="font-size: 14px; color: #7f1d1d; margin-bottom: 16px; line-height: 1.6;">
                                Menghapus akun akan menghapus semua data pengajuan Anda secara permanen. Tindakan ini tidak dapat dibatalkan.
                            </p>
                            <button class="btn" style="background-color: #dc2626; color: white;" onclick="alert('Fitur hapus akun sedang dalam pengembangan. Silakan hubungi admin untuk menghapus akun.')">
                                <i class="fas fa-trash-alt"></i> Hapus Akun Saya
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = field.nextElementSibling;

            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>