<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once(__DIR__ . "/../functions/authentication.php");
// require_once (__DIR__ . "/functions/functions.php");

if (isLogged()) {
    if (isStaff()) {
        header("Location:../staff/index.php");
    } elseif (isAdmin()) {
        header("Location:../admin/index.php");
    } else {
        header("Location:../user/index.php");
    }
}

if (isset($_POST["login"])) {
    $result = loginAttempt($_POST);
    if ($result) {
        if (isStaff()) {
            header("Location:../staff/index.php");
        } elseif (isAdmin()) {
            header("Location:../admin/index.php");
        } else {
            header("Location:../user/index.php");
        }
    }
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
                <input type="text" name="username" placeholder="Username" required>
            </div>

            <div class="input-group password-wrapper">
                <input type="password" name="password" id="password" placeholder="Password" required>
                <i class="fa-solid fa-eye" id="togglePassword"></i>
            </div>

            <button type="submit" class="auth-button" name="login" id="login">Masuk</button>

        </form>

        <p class="register-link">
            Belum punya akun? <a href="register.php">Daftar</a>
        </p>
    </div>
</div>


<?php
include($projectRoot . '/templates/footer.php');
?>