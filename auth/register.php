<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once(__DIR__ . "/../functions/authentication.php");


if (isset($_POST["register"])) {
    $result = register($_POST);
    if ($result) {
        echo "<script>
    alert('Sign up berhasil.');
    document.location.href = 'login.php';
    </script>";
    }
}

if (isLogged()) {
    header("Location:../index.php");
}

$projectRoot = dirname(__DIR__);
include($projectRoot . '/templates/header.php');
?>

<div class="login-container">
    <div class="image-placeholder">
        <h1>IMG</h1>
    </div>

    <div class="form-container">

        <form method="POST">

            <div class="input-group">
                <input type="text" name="name" placeholder="Nama Lengkap" required>
            </div>

            <div class="input-group">
                <input type="text" name="username" placeholder="Username" required>
            </div>

            <div class="input-group password-wrapper">
                <input type="password" name="password" id="password" placeholder="Password" required>
                <i class="fa-solid fa-eye" id="togglePassword"></i>
            </div>

            <div class="input-group password-wrapper">
                <input type="password" name="confirmpassword" id="confirmpassword" placeholder="Konfirmasi Password"
                    required>
                <i class="fa-solid fa-eye" id="toggleConfirmPassword"></i>
            </div>

            <button type="submit" class="auth-button" name="register" id="register">Daftar</button>

        </form>

        <p class="register-link">
            Sudah punya akun? <a href="login.php">Masuk</a>
        </p>
    </div>
</div>

<?php
include($projectRoot . '/templates/footer.php');
?>