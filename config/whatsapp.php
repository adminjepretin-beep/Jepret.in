<?php
// config/whatsapp.php - Pengiriman WhatsApp untuk link reset password
//
// driver:
//   'log'    -> pesan tidak dikirim, ditulis ke data/whatsapp.log (development)
//   'fonnte' -> kirim lewat API Fonnte (https://fonnte.com) memakai token perangkat
//
// Isi lewat environment variable supaya token tidak ikut ter-commit:
//   WA_DRIVER=fonnte
//   WA_API_TOKEN=token-dari-dashboard-fonnte
//   WA_API_URL=https://api.fonnte.com/send   (opsional)
$env = static function (string $key, $default = '') {
    $value = getenv($key);
    if ($value === false || $value === '') {
        // Sebagian hosting mengirim SetEnv lewat $_SERVER, bukan getenv().
        $value = $_SERVER[$key] ?? '';
    }
    return ($value === false || $value === '') ? $default : $value;
};

$config = [
    'driver'  => strtolower($env('WA_DRIVER', 'log')),
    'token'   => $env('WA_API_TOKEN', ''),
    'api_url' => $env('WA_API_URL', 'https://api.fonnte.com/send'),
];

// Setelan hosting tanpa environment variable: config/local.php -> 'whatsapp'.
$local_file = __DIR__ . '/local.php';
if (is_file($local_file)) {
    $local_data = require $local_file;
    if (is_array($local_data) && is_array($local_data['whatsapp'] ?? null)) {
        $config = array_merge($config, $local_data['whatsapp']);
    }
}

// Pengaturan lokal untuk tes di komputer (file ini tidak ikut ter-commit):
// config/whatsapp.local.php  ->  <?php return ['driver' => 'fonnte', 'token' => 'xxxx'];
$local = __DIR__ . '/whatsapp.local.php';
if (is_file($local)) {
    $override = require $local;
    if (is_array($override)) {
        $config = array_merge($config, $override);
    }
}

return $config;
