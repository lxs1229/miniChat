<?php
session_start();

$input = $_POST['password'] ?? '';
$adminPwd = getenv("ADMIN_PASSWORD");

if ($input === $adminPwd) {
    $_SESSION['is_admin'] = true;
    header("Location: admin.php");
    exit;
}

$_SESSION['admin_error'] = "Mot de passe incorrect.";
header("Location: admin_login.php");
exit;
