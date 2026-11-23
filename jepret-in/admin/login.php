<?php
// admin/login.php - Diarahkan ke halaman login terpadu di root
require_once __DIR__ . '/../includes/auth.php';

if (admin_is_logged_in()) {
    header('Location: index.php');
    exit;
}

header('Location: ../login.php?next=' . urlencode('admin/index.php'));
exit;
