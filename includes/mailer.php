<?php
// includes/mailer.php - Pengiriman email sederhana (log / SMTP / API) tanpa library tambahan

function mail_config(): array
{
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/../config/mail.php';
    }
    return $config;
}

/**
 * URL dasar website, dipakai untuk link di dalam email.
 */
function app_base_url(): string
{
    $config = mail_config();
    $configured = trim((string)($config['app_url'] ?? ''));
    if ($configured !== '') {
        return rtrim($configured, '/');
    }

    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $host  = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');

    // Base URL harus menunjuk ke root proyek, bukan folder skrip yang sedang dibuka.
    // Contoh: dari admin/akun.php link tetap harus ke /reset-password.php.
    $base = '';
    $doc_root = str_replace('\\', '/', rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/'));
    $app_root = str_replace('\\', '/', rtrim(dirname(__DIR__), '/'));

    if ($doc_root !== '' && str_starts_with($app_root, $doc_root)) {
        $base = substr($app_root, strlen($doc_root));
    } elseif (empty($_SERVER['DOCUMENT_ROOT'])) {
        // CLI atau server tanpa DOCUMENT_ROOT: pakai alamat relatif apa adanya.
        $base = '';
    }

    return ($https ? 'https://' : 'http://') . $host . rtrim($base, '/');
}

/**
 * Kirim email HTML. Mengembalikan ['ok' => bool, 'error' => string, 'driver' => string].
 */
function app_send_mail(string $to, string $subject, string $html, array $vars = []): array
{
    $to = trim($to);
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Alamat email tujuan tidak valid.', 'driver' => ''];
    }

    $config = mail_config();
    $driver = strtolower((string)($config['driver'] ?? 'log'));

    if ($driver === 'smtp') {
        return mail_send_smtp($to, $subject, $html, $config);
    }
    if ($driver === 'api') {
        return mail_send_api($to, $subject, $html, $config, $vars);
    }

    return mail_send_log($to, $subject, $html, $config);
}

/**
 * Mode development: tulis email ke data/mail.log supaya alurnya tetap bisa diuji.
 */
function mail_send_log(string $to, string $subject, string $html, array $config): array
{
    $dir = __DIR__ . '/../data';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    $path = $dir . '/mail.log';
    $entry = str_repeat('=', 70) . "\n"
        . 'Waktu  : ' . date('Y-m-d H:i:s') . "\n"
        . 'Ke     : ' . $to . "\n"
        . 'Subjek : ' . $subject . "\n"
        . 'Dari   : ' . (string)($config['from_email'] ?? '') . "\n"
        . str_repeat('-', 70) . "\n"
        . $html . "\n\n";

    if (@file_put_contents($path, $entry, FILE_APPEND | LOCK_EX) === false) {
        return ['ok' => false, 'error' => 'Folder data tidak bisa ditulis untuk mail.log.', 'driver' => 'log'];
    }

    return ['ok' => true, 'error' => '', 'driver' => 'log'];
}

function mail_build_headers(string $to, string $subject, array $config): string
{
    $from_email = (string)($config['from_email'] ?? 'no-reply@localhost');
    $from_name  = (string)($config['from_name'] ?? 'Jepret.in');
    $encoded_name = '=?UTF-8?B?' . base64_encode($from_name) . '?=';

    return 'Date: ' . date('r') . "\r\n"
        . 'From: ' . $encoded_name . ' <' . $from_email . ">\r\n"
        . 'To: <' . $to . ">\r\n"
        . 'Subject: =?UTF-8?B?' . base64_encode($subject) . "?=\r\n"
        . "MIME-Version: 1.0\r\n"
        . "Content-Type: text/html; charset=UTF-8\r\n"
        . "Content-Transfer-Encoding: base64\r\n";
}

function mail_send_smtp(string $to, string $subject, string $html, array $config): array
{
    $host   = trim((string)($config['smtp_host'] ?? ''));
    $port   = (int)($config['smtp_port'] ?? 587);
    $user   = (string)($config['smtp_user'] ?? '');
    $pass   = (string)($config['smtp_pass'] ?? '');
    $secure = strtolower((string)($config['smtp_secure'] ?? 'tls'));
    $from   = (string)($config['from_email'] ?? $user);

    if ($host === '' || $port <= 0) {
        return ['ok' => false, 'error' => 'smtp_host / smtp_port belum diisi di config/mail.php.', 'driver' => 'smtp'];
    }

    $remote = ($secure === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
    $socket = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT);
    if (!$socket) {
        return ['ok' => false, 'error' => 'Gagal terhubung ke SMTP: ' . ($errstr !== '' ? $errstr : 'unknown'), 'driver' => 'smtp'];
    }

    stream_set_timeout($socket, 15);

    $read = static function () use ($socket): string {
        $response = '';
        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') {
                break;
            }
        }
        return $response;
    };

    $expected = static function (string $response, string $codes): bool {
        $code = substr(trim($response), 0, 3);
        return in_array($code, explode(' ', $codes), true);
    };

    $command = static function (string $line, string $codes) use ($socket, $read, $expected): bool {
        fwrite($socket, $line . "\r\n");
        return $expected($read(), $codes);
    };

    $banner = $read();
    if (!$expected($banner, '220')) {
        fclose($socket);
        return ['ok' => false, 'error' => 'SMTP menolak koneksi: ' . trim($banner), 'driver' => 'smtp'];
    }

    $ehloHost = 'localhost';
    $command('EHLO ' . $ehloHost, '250');

    if ($secure === 'tls') {
        if (!$command('STARTTLS', '220')) {
            fclose($socket);
            return ['ok' => false, 'error' => 'Server SMTP tidak menerima STARTTLS.', 'driver' => 'smtp'];
        }
        if (!@stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($socket);
            return ['ok' => false, 'error' => 'Gagal memulai enkripsi TLS.', 'driver' => 'smtp'];
        }
        $command('EHLO ' . $ehloHost, '250');
    }

    if ($user !== '') {
        if (!$command('AUTH LOGIN', '334')) {
            fclose($socket);
            return ['ok' => false, 'error' => 'Server SMTP tidak menerima AUTH LOGIN.', 'driver' => 'smtp'];
        }
        if (!$command(base64_encode($user), '334')) {
            fclose($socket);
            return ['ok' => false, 'error' => 'Username SMTP ditolak.', 'driver' => 'smtp'];
        }
        if (!$command(base64_encode($pass), '235')) {
            fclose($socket);
            return ['ok' => false, 'error' => 'Password SMTP ditolak.', 'driver' => 'smtp'];
        }
    }

    if (!$command('MAIL FROM:<' . $from . '>', '250')) {
        fclose($socket);
        return ['ok' => false, 'error' => 'Alamat pengirim ditolak SMTP.', 'driver' => 'smtp'];
    }
    if (!$command('RCPT TO:<' . $to . '>', '250 251')) {
        fclose($socket);
        return ['ok' => false, 'error' => 'Alamat tujuan ditolak SMTP.', 'driver' => 'smtp'];
    }
    if (!$command('DATA', '354')) {
        fclose($socket);
        return ['ok' => false, 'error' => 'SMTP tidak siap menerima isi email.', 'driver' => 'smtp'];
    }

    $message = mail_build_headers($to, $subject, $config)
        . "\r\n"
        . chunk_split(base64_encode($html), 76, "\r\n")
        . "\r\n.\r\n";

    fwrite($socket, $message);
    $sent = $expected($read(), '250');
    fwrite($socket, "QUIT\r\n");
    fclose($socket);

    if (!$sent) {
        return ['ok' => false, 'error' => 'SMTP menolak pengiriman email.', 'driver' => 'smtp'];
    }

    return ['ok' => true, 'error' => '', 'driver' => 'smtp'];
}

function mail_send_api(string $to, string $subject, string $html, array $config, array $vars = []): array
{
    $provider = strtolower((string)($config['api_provider'] ?? 'resend'));
    $key      = trim((string)($config['api_key'] ?? ''));
    $from     = (string)($config['from_email'] ?? '');
    $fromName = (string)($config['from_name'] ?? 'Jepret.in');

    if ($provider === 'emailjs') {
        return mail_send_emailjs($to, $subject, $html, $config, $vars);
    }

    if ($key === '' || $from === '') {
        return ['ok' => false, 'error' => 'api_key / from_email belum diisi di config/mail.php.', 'driver' => 'api'];
    }

    if ($provider === 'brevo') {
        $url = 'https://api.brevo.com/v3/smtp/email';
        $headers = ['api-key: ' . $key, 'Content-Type: application/json', 'Accept: application/json'];
        $payload = [
            'sender'      => ['email' => $from, 'name' => $fromName],
            'to'          => [['email' => $to]],
            'subject'     => $subject,
            'htmlContent' => $html,
        ];
    } else {
        $url = 'https://api.resend.com/emails';
        $headers = ['Authorization: Bearer ' . $key, 'Content-Type: application/json'];
        $payload = [
            'from'    => $fromName . ' <' . $from . '>',
            'to'      => [$to],
            'subject' => $subject,
            'html'    => $html,
        ];
    }

    $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if (function_exists('curl_init')) {
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
        ]);
        $response = curl_exec($curl);
        $status   = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($curl);
        curl_close($curl);

        if ($response === false || $status < 200 || $status >= 300) {
            return [
                'ok'     => false,
                'error'  => 'API email gagal (HTTP ' . $status . '): ' . ($curlErr !== '' ? $curlErr : (string)$response),
                'driver' => 'api',
            ];
        }

        return ['ok' => true, 'error' => '', 'driver' => 'api'];
    }

    // Fallback tanpa cURL (allow_url_fopen).
    $context = stream_context_create([
        'http' => [
            'method'        => 'POST',
            'header'        => implode("\r\n", $headers),
            'content'       => $body,
            'timeout'       => 15,
            'ignore_errors' => true,
        ],
    ]);

    $response = @file_get_contents($url, false, $context);
    $status = 0;
    if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
        $status = (int)$m[1];
    }

    if ($response === false || $status < 200 || $status >= 300) {
        return ['ok' => false, 'error' => 'API email gagal (HTTP ' . $status . ').', 'driver' => 'api'];
    }

    return ['ok' => true, 'error' => '', 'driver' => 'api'];
}

/**
 * Kirim lewat EmailJS (https://www.emailjs.com/docs/rest-api/send/).
 * Isi email diatur oleh template di dashboard EmailJS, jadi yang dikirim
 * dari sini hanya variabel-variabelnya ({{to_email}}, {{reset_link}}, dst).
 */
function mail_send_emailjs(string $to, string $subject, string $html, array $config, array $vars = []): array
{
    $service  = trim((string)($config['emailjs_service_id'] ?? ''));
    $template = trim((string)($config['emailjs_template_id'] ?? ''));
    $public   = trim((string)($config['emailjs_public_key'] ?? ''));
    $private  = trim((string)($config['emailjs_private_key'] ?? ''));

    if ($service === '' || $template === '' || $public === '') {
        return [
            'ok'     => false,
            'error'  => 'EMAILJS_SERVICE_ID / EMAILJS_TEMPLATE_ID / EMAILJS_PUBLIC_KEY belum diisi.',
            'driver' => 'emailjs',
        ];
    }

    $params = array_merge([
        'to_email' => $to,
        'to_name'  => '',
        'subject'  => $subject,
        'message'  => $html,
    ], $vars);

    $payload = [
        'service_id'      => $service,
        'template_id'     => $template,
        'user_id'         => $public,
        'template_params' => $params,
    ];
    if ($private !== '') {
        $payload['accessToken'] = $private;
    }

    $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($body === false) {
        return ['ok' => false, 'error' => 'Gagal menyusun data EmailJS.', 'driver' => 'emailjs'];
    }

    $url = 'https://api.emailjs.com/api/v1.0/email/send';

    if (function_exists('curl_init')) {
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
        ]);
        $response = curl_exec($curl);
        $status   = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($curl);
        curl_close($curl);

        if ($response === false || $status < 200 || $status >= 300) {
            $detail = $curlErr !== '' ? $curlErr : trim((string)$response);
            return [
                'ok'     => false,
                'error'  => 'EmailJS gagal (HTTP ' . $status . '): ' . $detail,
                'driver' => 'emailjs',
            ];
        }

        return ['ok' => true, 'error' => '', 'driver' => 'emailjs'];
    }

    // Fallback tanpa cURL (allow_url_fopen).
    $context = stream_context_create([
        'http' => [
            'method'        => 'POST',
            'header'        => 'Content-Type: application/json',
            'content'       => $body,
            'timeout'       => 15,
            'ignore_errors' => true,
        ],
    ]);

    $response = @file_get_contents($url, false, $context);
    $status = 0;
    if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
        $status = (int)$m[1];
    }

    if ($response === false || $status < 200 || $status >= 300) {
        return [
            'ok'     => false,
            'error'  => 'EmailJS gagal (HTTP ' . $status . '): ' . trim((string)$response),
            'driver' => 'emailjs',
        ];
    }

    return ['ok' => true, 'error' => '', 'driver' => 'emailjs'];
}
