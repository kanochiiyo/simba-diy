<?php
// mengambil fungsi koneksi dari connection
require_once(__DIR__ . "/connection.php");

function register($formData)
{
  $connection = getConnection();

  $name = mysqli_real_escape_string($connection, trim($formData["name"]));
  $nik = mysqli_real_escape_string($connection, trim($formData["nik"]));
  $password = mysqli_real_escape_string($connection, $formData["password"]);
  $confirmpassword = mysqli_real_escape_string($connection, $formData["confirmpassword"]);

  // cek udah ada yg make belom niknya
  $result = $connection->query("SELECT nik FROM user WHERE nik = '$nik'");
  if ($result->fetch_assoc()) {
    echo "<script>
    alert('Login gagal. NIK tidak tersedia.');
    </script>";
    return false;
  }

  // kalo password & confirm nggak sama
  if ($password != $confirmpassword) {
    echo "<script>
    alert('Login gagal. Password salah!');
    </script>";
    return false;
  }

  // enkripsi password pake password hash
  $password = password_hash($password, PASSWORD_DEFAULT);

  $connection->query("INSERT INTO user (nama, nik, password, role) VALUES ('$name', '$nik', '$password', 'user')");

  return ($connection->affected_rows) ? true : false;
}

function loginAttempt($formData)
{
  // ob_start();
  $connection = getConnection();

  $nik = mysqli_real_escape_string($connection, trim($formData["nik"]));
  $password = $formData["password"];

  $result = $connection->query("SELECT * FROM user WHERE nik='$nik'");

  // kalo nik gk ditemuin gaiso login
  if ($result->num_rows !== 1) {
    $messageError = 'Login gagal. NIK tidak ditemukan.';
    echo "<script>alert('" . addslashes($messageError) . "');</script>";
    ob_end_flush();
    return false;
  }

  $userData = $result->fetch_object();

  // password salah gaiso login juga
  if (!password_verify($password, $userData->password)) {
    $message = 'Login gagal. Password salah.';
    echo "<script>alert('" . addslashes($message) . "');</script>";
    ob_end_flush();
    return false;
  }

  $_SESSION['id'] = $userData->id;
  $_SESSION['nik'] = $userData->nik;
  $_SESSION['login'] = true;

  return true;
}


function isLogged()
{
  if (isset($_SESSION['login'])) {
    return true;
  }
  return false;
}

function isAdmin()
{
  $connection = getConnection();

  if (isset($_SESSION['nik'])) {
    $nik = $_SESSION['nik'];

    $result = $connection->query("SELECT * FROM user WHERE nik = '$nik'");

    $userData = $result->fetch_object();

    if ($userData->role === "admin") {
      return true;
    }
  }
  return false;
}


function logout(): void
{
  session_destroy();
}
