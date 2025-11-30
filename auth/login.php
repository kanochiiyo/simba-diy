<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once(__DIR__ . "/../functions/authentication.php");

if (isLogged()) {
    if (isAdmin()) {
        header("Location:../admin/index.php");
    } else {
        header("Location:../user/index.php");
    }
}

$error = '';

if (isset($_POST["login"])) {
    $result = loginAttempt($_POST);
    if ($result) {
        if (isAdmin()) {
            header("Location:../admin/index.php");
        } else {
            header("Location:../user/index.php");
        }
    } else {
        $error = 'NIK atau password salah!';
    }
}

$projectRoot = dirname(__DIR__);
include($projectRoot . '/templates/header.php');
?>

<div class="login-container">
    <div class="image-placeholder">
        <img src="../assets/images/login-illustration.svg" alt="Login Illustration"
            style="width: 100%; height: 100%; object-fit: contain;"
            onerror="this.parentElement.innerHTML='<h1 style=\'font-size: 8rem; color: var(--color-text); margin: 0;\'>SIMBA</h1>'">
    </div>

    <div class="form-container">
        <div class="form-header" style="text-align: center; margin-bottom: 30px;">
            <h2 style="font-size: 28px; font-weight: 700; color: var(--color-text); margin-bottom: 8px;">Selamat
                Datang Kembali!</h2>
            <p style="font-size: 14px; color: var(--color-text); opacity: 0.7;">Masuk ke akun Anda untuk melanjutkan
            </p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger" style="margin-bottom: 20px; padding: 12px; border-radius: 8px;">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" id="loginForm">

            <div class="input-group">
                <label for="nik" style="display: block; font-size: 14px; font-weight: 600; color: var(--color-text); margin-bottom: 8px;">
                    <i class="fas fa-user"></i> NIK
                </label>
                <input type="text" name="nik" id="nik" placeholder="Masukkan NIK" required autofocus>
            </div>

            <div class="input-group password-wrapper">
                <label for="password" style="display: block; font-size: 14px; font-weight: 600; color: var(--color-text); margin-bottom: 8px;">
                    Password
                </label>
                <input type="password" name="password" id="password" placeholder="Masukkan password" required>
               
            </div>

            <div class="form-options" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <label style="display: flex; align-items: center; font-size: 14px; color: var(--color-text); cursor: pointer;">
                    <input type="checkbox" style="margin-right: 8px;">
                    <span>Ingat Saya</span>
                </label>
            </div>

            <button type="submit" class="auth-button" name="login" id="login">
                Masuk
            </button>

        </form>

        <div class="divider" style="display: flex; align-items: center; margin: 25px 0; gap: 15px;">
            <div style="flex: 1; height: 1px; background-color: var(--color-stroke);"></div>
            <span style="font-size: 13px; color: var(--color-text); opacity: 0.6;">atau</span>
            <div style="flex: 1; height: 1px; background-color: var(--color-stroke);"></div>
        </div>

        <p class="register-link">
            Belum punya akun? <a href="register.php">Daftar Sekarang</a>
        </p>

        <p style="text-align: center; margin-top: 15px; font-size: 13px; color: var(--color-text); opacity: 0.6;">
            <a href="../index.php" style="color: var(--color-primary); text-decoration: none;">
                <i class="fas fa-arrow-left"></i> Kembali ke Beranda
            </a>
        </p>
    </div>
</div>

<?php
include($projectRoot . '/templates/footer.php');
?>