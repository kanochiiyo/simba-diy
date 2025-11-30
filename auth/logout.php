<?php
session_start();
require_once(__DIR__ . "/../functions/authentication.php");

logout();
header("Location: ../index.php");
exit;
