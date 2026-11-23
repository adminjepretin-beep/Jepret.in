<?php
// config/mail.php - Konfigurasi pengiriman email (reset password, notifikasi)
//
// driver:
//   'log'  -> email tidak dikirim, ditulis ke data/mail.log (untuk development)
//   'smtp' -> kirim lewat server SMTP (hosting/cPanel, Gmail app password, dll)
//   'api'  -> kirim lewat API provider (Resend / Brevo) tanpa library tambahan
//
// Contoh SMTP hosting:
//   'driver' => 'smtp', 'smtp_host' => 'mail.domainkamu.com', 'smtp_port' => 465,
//   'smtp_secure' => 'ssl', 'smtp_user' => 'no-reply@domainkamu.com', 'smtp_pass' => '...'
//
// Contoh API Resend: 'driver' => 'api', 'api_provider' => 'resend', 'api_key' => 're_...'
// Contoh API Brevo : 'driver' => 'api', 'api_provider' => 'brevo',  'api_key' => 'xkeysib-...'
// Contoh EmailJS   : 'driver' => 'api', 'api_provider' => 'emailjs',
//                    'emailjs_service_id' => 'service_xxx', 'emailjs_template_id' => 'template_xxx',
//                    'emailjs_public_key' => '...', 'emailjs_private_key' => '...'

// Nilai sensitif bisa diisi lewat environment variable supaya tidak ikut ter-commit:
// MAIL_DRIVER, MAIL_FROM, MAIL_API_KEY, SMTP_HOST, SMTP_PORT, SMTP_SECURE, SMTP_USER, SMTP_PASS
$env = static function (string $key, $default = '') {
    $value = getenv($key);
    return ($value === false || $value === '') ? $default : $value;
};

return [
    'driver'      => $env('MAIL_DRIVER', 'log'),

    // Alamat pengirim. Untuk SMTP biasanya harus sama dengan smtp_user.
    'from_email'  => $env('MAIL_FROM', 'no-reply@jepretin.local'),
    'from_name'   => 'Jepret.in Surabaya',

    // URL website publik, dipakai untuk menyusun link reset password.
    // Kosongkan untuk menebak otomatis dari request (cukup untuk localhost).
    'app_url'     => $env('MAIL_APP_URL', ''),

    // Khusus driver 'smtp'
    'smtp_host'   => $env('SMTP_HOST', ''),
    'smtp_port'   => (int)$env('SMTP_PORT', 587),
    'smtp_secure' => $env('SMTP_SECURE', 'tls'), // 'tls' (587), 'ssl' (465), atau 'none'
    'smtp_user'   => $env('SMTP_USER', ''),
    'smtp_pass'   => $env('SMTP_PASS', ''),

    // Khusus driver 'api'
    'api_provider' => $env('MAIL_API_PROVIDER', 'resend'), // 'resend', 'brevo', atau 'emailjs'
    'api_key'      => $env('MAIL_API_KEY', ''),

    // Khusus EmailJS. Template email dibuat di dashboard EmailJS,
    // jadi isi email diatur di sana, bukan dari kode ini.
    'emailjs_service_id'   => $env('EMAILJS_SERVICE_ID', ''),
    'emailjs_template_id'  => $env('EMAILJS_TEMPLATE_ID', ''),
    'emailjs_public_key'   => $env('EMAILJS_PUBLIC_KEY', ''),
    'emailjs_private_key'  => $env('EMAILJS_PRIVATE_KEY', ''),
];
