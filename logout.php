<?php
// logout.php - Keluar dari akun pengguna
require_once __DIR__ . '/includes/auth.php';

admin_start_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !admin_verify_csrf($_POST['csrf'] ?? null)) {
    header('Location: akun.php');
    exit;
}

auth_logout();
header('Location: index.php');
exit;
