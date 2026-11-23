<?php
// config/admin.php - Akun admin Jepret.in
//
// Kredensial admin TIDAK disimpan di dalam kode, supaya tidak ada password
// default yang bisa dipakai orang lain. Isi lewat environment variable:
//
//   ADMIN_USERNAME       (opsional, default: admin)
//   ADMIN_PASSWORD_HASH  (WAJIB, hasil password_hash(), bukan password asli)
//
// Cara bikin hash:
//   php -r "echo password_hash('PasswordBaruKamu', PASSWORD_DEFAULT), PHP_EOL;"
//
// Lalu isi hasilnya (yang diawali $2y$...) ke ADMIN_PASSWORD_HASH:
//   - hosting cPanel : .htaccess  ->  SetEnv ADMIN_PASSWORD_HASH '$2y$10$....'
//   - development    : jalankan server dengan env var tersebut, misalnya
//                      ADMIN_PASSWORD_HASH='$2y$10$....' php -S localhost:8000
//
// Kalau ADMIN_PASSWORD_HASH kosong, login admin DIMATIKAN (bukan memakai
// password bawaan). Website publik tetap jalan normal.

$jepret_env = static function (string $key, string $default = ''): string {
    $value = getenv($key);
    if ($value === false || $value === '') {
        $value = $_SERVER[$key] ?? $_ENV[$key] ?? '';
    }

    return ($value === '' || $value === null) ? $default : (string)$value;
};

$jepret_local = [];
$jepret_local_file = __DIR__ . '/local.php';
if (is_file($jepret_local_file)) {
    $jepret_local_data = require $jepret_local_file;
    if (is_array($jepret_local_data)) {
        $jepret_local = (array)($jepret_local_data['admin'] ?? []);
    }
}

$jepret_admin_user = trim((string)($jepret_local['username'] ?? $jepret_env('ADMIN_USERNAME', 'admin')));
$jepret_admin_hash = trim((string)($jepret_local['password_hash'] ?? $jepret_env('ADMIN_PASSWORD_HASH')));

$jepret_admin_configured = false;
if ($jepret_admin_hash !== '') {
    $jepret_hash_info = password_get_info($jepret_admin_hash);
    $jepret_admin_configured = ($jepret_hash_info['algo'] ?? null) !== null;
    if (!$jepret_admin_configured) {
        // Isinya bukan hash yang valid — jangan dipakai, jangan bocorkan nilainya.
        error_log('Jepret.in: ADMIN_PASSWORD_HASH diisi tapi bukan hash password yang valid. Login admin dimatikan sampai diperbaiki.');
        $jepret_admin_hash = '';
    }
}

unset($jepret_env, $jepret_hash_info, $jepret_local, $jepret_local_data, $jepret_local_file);

return [
    'username'      => $jepret_admin_user !== '' ? $jepret_admin_user : 'admin',
    'password_hash' => $jepret_admin_hash,
    'configured'    => $jepret_admin_configured,
];
