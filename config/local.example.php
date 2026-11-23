<?php
// config/local.php - Setelan khusus satu server (TIDAK ikut Git).
//
// Salin file ini menjadi config/local.php di hosting, lalu isi nilainya.
// Dipakai kalau hosting tidak mendukung environment variable
// (misalnya InfinityFree: SetEnv/.htaccess tidak terbaca PHP).
//
// Nilai di sini menang atas environment variable.

return [
    'db' => [
        'driver' => 'mysql',                          // 'mysql' | 'auto' | 'sqlite'
        'host'   => 'sqlXXX.infinityfree.com',
        'port'   => '3306',
        'name'   => 'if0_XXXXXXXX_jepret',
        'user'   => 'if0_XXXXXXXX',
        'pass'   => 'password-database',
    ],

    'admin' => [
        'username'      => 'admin',
        // Hasil: php -r "echo password_hash('PasswordKamu', PASSWORD_DEFAULT);"
        'password_hash' => '$2y$10$GANTI_DENGAN_HASH_ASLI',
    ],

    'whatsapp' => [
        'driver' => 'fonnte',
        'token'  => 'token-dari-dashboard-fonnte',
    ],
];
