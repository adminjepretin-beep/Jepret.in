<?php
// admin/logout.php - Keluar dari area admin
require_once __DIR__ . '/../includes/auth.php';

admin_start_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !admin_verify_csrf($_POST['csrf'] ?? null)) {
    header('Location: index.php');
    exit;
}

admin_logout();
header('Location: ../login.php?logout=1');
exit;
