<?php
$projectRoot = dirname(__DIR__);
include($projectRoot . '/templates/header.php');
?>

<div class="login-container">
    <div class="image-placeholder">
        <h1>IMG</h1>
    </div>

    <div class="form-container">

        <form action="" method="POST">

            <div class="input-group">
                <input type="text" name="nama" placeholder="Nama Lengkap" required>
            </div>

            <div class="input-group">
                <input type="text" name="username" placeholder="Username" required>
            </div>

            <div class="input-group password-wrapper">
                <input type="password" name="password" id="password" placeholder="Password" required>
                <i class="fa-solid fa-eye" id="togglePassword"></i>
            </div>

            <div class="input-group password-wrapper">
                <input type="confirmPassword" name="confirmPassword" id="confirmPassword" placeholder="Konfirmasi Password" required>
                <i class="fa-solid fa-eye" id="togglePassword"></i>
            </div>

            <button type="submit" class="auth-button">Daftar</button>

        </form>

        <p class="register-link">
            Sudah punya akun? <a href="login.php">Masuk</a>
        </p>
    </div>
</div>

<?php
include($projectRoot . '/templates/footer.php');
?>