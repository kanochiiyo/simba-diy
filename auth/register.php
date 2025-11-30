<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once(__DIR__ . "/../functions/authentication.php");

if (isLogged()) {
    header("Location:../index.php");
}

$error = '';
$success = false;

if (isset($_POST["register"])) {
    // Validasi form
    if (empty($_POST['name']) || empty($_POST['nik']) || empty($_POST['password']) || empty($_POST['confirmpassword'])) {
        $error = "Semua field harus diisi!";
    } elseif (strlen($_POST['password']) < 6) {
        $error = "Password minimal 6 karakter!";
    } elseif ($_POST['password'] !== $_POST['confirmpassword']) {
        $error = "Password dan konfirmasi password tidak sama!";
    } else {
        $result = register($_POST);
        if ($result) {
            $success = true;
            echo "<script>
                alert('Pendaftaran berhasil! Silakan login dengan akun Anda.');
                window.location.href = 'login.php';
            </script>";
        } else {
            $error = "Pendaftaran gagal. NIK mungkin sudah digunakan.";
        }
    }
}

$projectRoot = dirname(__DIR__);
include($projectRoot . '/templates/header.php');
?>

<div class="login-container">
    <div class="image-placeholder">
        <img src="../assets/images/register-illustration.svg" alt="Register Illustration"
            style="width: 100%; height: 100%; object-fit: contain;"
            onerror="this.parentElement.innerHTML='<h1 style=\'font-size: 8rem; color: var(--color-text); margin: 0;\'>SIMBA</h1>'">
    </div>

    <div class="form-container">
        <div class="form-header" style="text-align: center; margin-bottom: 30px;">
            <h2 style="font-size: 28px; font-weight: 700; color: var(--color-text); margin-bottom: 8px;">Daftar Akun
                Baru</h2>
            <p style="font-size: 14px; color: var(--color-text); opacity: 0.7;">Buat akun untuk mengajukan bantuan
                sosial</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger" style="margin-bottom: 20px; padding: 12px; border-radius: 8px;">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" id="registerForm">

            <div class="input-group">
                <label for="name"
                    style="display: block; font-size: 14px; font-weight: 600; color: var(--color-text); margin-bottom: 8px;">
                    </i> Nama Lengkap
                </label>
                <input type="text" name="name" id="name" placeholder="Masukkan nama lengkap" required autofocus>
                <small class="form-text">Nama sesuai dengan KTP</small>
            </div>

            <div class="input-group">
                <label for="nik"
                    style="display: block; font-size: 14px; font-weight: 600; color: var(--color-text); margin-bottom: 8px;">
                    </i> NIK
                </label>
                <input type="text" name="nik" id="nik" placeholder="Masukkan NIK" required maxlength="16"
                    pattern="\d{16}">
                <small class="form-text">Nomor Induk Kependudukan (16 digit)</small>
            </div>

            <div class="input-group password-wrapper">
                <label for="password"
                    style="display: block; font-size: 14px; font-weight: 600; color: var(--color-text); margin-bottom: 8px;">
                    </i> Password
                </label>
                <input type="password" name="password" id="password" placeholder="Buat password yang kuat" required
                    minlength="6">
                <i class="fa-solid fa-eye" id="togglePassword"></i>
                <small class="form-text">Minimal 6 karakter</small>
            </div>

            <div class="input-group password-wrapper">
                <label for="confirmpassword"
                    style="display: block; font-size: 14px; font-weight: 600; color: var(--color-text); margin-bottom: 8px;">
                    Konfirmasi Password
                </label>
                <input type="password" name="confirmpassword" id="confirmpassword" placeholder="Ketik ulang password" required
                    minlength="6">
                <i class="fa-solid fa-eye" id="toggleConfirmPassword"></i>
                <small class="form-text">Ketik ulang password yang sama</small>
            </div>

            <div style="margin-bottom: 20px;">
                <label
                    style="display: flex; align-items: flex-start; font-size: 13px; color: var(--color-text); cursor: pointer;">
                    <input type="checkbox" required style="margin-right: 8px; margin-top: 3px;">
                    <span>Saya menyetujui <a href="#" style="color: var(--color-primary); text-decoration: none;">Syarat
                            dan Ketentuan</a> yang berlaku</span>
                </label>
            </div>

            <button type="submit" class="auth-button" name="register" id="register">
                Daftar Sekarang
            </button>

        </form>

        <div class="divider" style="display: flex; align-items: center; margin: 25px 0; gap: 15px;">
            <div style="flex: 1; height: 1px; background-color: var(--color-stroke);"></div>
            <span style="font-size: 13px; color: var(--color-text); opacity: 0.6;">atau</span>
            <div style="flex: 1; height: 1px; background-color: var(--color-stroke);"></div>
        </div>

        <p class="register-link">
            Sudah punya akun? <a href="login.php">Masuk</a>
        </p>
    </div>
</div>
<?php
include($projectRoot . '/templates/footer.php');
?>