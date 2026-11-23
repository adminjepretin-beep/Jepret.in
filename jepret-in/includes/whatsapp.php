<?php
// includes/whatsapp.php - Kirim pesan WhatsApp tanpa library tambahan

function whatsapp_config(): array
{
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/../config/whatsapp.php';
    }
    return $config;
}

/**
 * Nomor tujuan dalam format internasional tanpa tanda plus (62812...).
 */
function whatsapp_target(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone) ?? '';

    if (str_starts_with($digits, '0')) {
        return '62' . substr($digits, 1);
    }
    if (str_starts_with($digits, '8')) {
        return '62' . $digits;
    }

    return $digits;
}

/**
 * Mode development: pesan tidak dikirim, ditulis ke data/whatsapp.log.
 */
function whatsapp_send_log(string $target, string $message): array
{
    $dir = __DIR__ . '/../data';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    $entry = str_repeat('=', 70) . "\n"
        . 'Waktu  : ' . date('Y-m-d H:i:s') . "\n"
        . 'Tujuan : ' . $target . "\n"
        . str_repeat('-', 70) . "\n"
        . $message . "\n\n";

    if (@file_put_contents($dir . '/whatsapp.log', $entry, FILE_APPEND | LOCK_EX) === false) {
        return ['ok' => false, 'error' => 'Folder data tidak bisa ditulis untuk whatsapp.log.', 'driver' => 'log'];
    }

    return ['ok' => true, 'error' => '', 'driver' => 'log'];
}

/**
 * Kirim lewat API Fonnte. Tanpa library tambahan (cURL atau allow_url_fopen).
 */
function whatsapp_send_fonnte(string $target, string $message, array $config): array
{
    $token = trim((string)($config['token'] ?? ''));
    $url   = trim((string)($config['api_url'] ?? 'https://api.fonnte.com/send'));

    if ($token === '') {
        return ['ok' => false, 'error' => 'WA_API_TOKEN belum diisi (lihat config/whatsapp.php).', 'driver' => 'fonnte'];
    }

    $payload = http_build_query([
        'target'  => $target,
        'message' => $message,
    ]);

    if (function_exists('curl_init')) {
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Authorization: ' . $token],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
        ]);
        $response = curl_exec($curl);
        $status   = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($curl);
        curl_close($curl);

        if ($response === false || $status < 200 || $status >= 300) {
            $detail = $curlErr !== '' ? $curlErr : trim((string)$response);
            return ['ok' => false, 'error' => 'Fonnte gagal (HTTP ' . $status . '): ' . $detail, 'driver' => 'fonnte'];
        }

        return whatsapp_fonnte_result((string)$response);
    }

    // Fallback tanpa cURL (allow_url_fopen).
    $context = stream_context_create([
        'http' => [
            'method'        => 'POST',
            'header'        => "Authorization: " . $token . "\r\nContent-Type: application/x-www-form-urlencoded\r\n",
            'content'       => $payload,
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
        return ['ok' => false, 'error' => 'Fonnte gagal (HTTP ' . $status . ').', 'driver' => 'fonnte'];
    }

    return whatsapp_fonnte_result((string)$response);
}

/**
 * Fonnte menjawab JSON. Kalau ada status=false, anggap gagal.
 */
function whatsapp_fonnte_result(string $response): array
{
    $data = json_decode($response, true);

    if (is_array($data) && array_key_exists('status', $data) && $data['status'] === false) {
        $reason = (string)($data['reason'] ?? $data['detail'] ?? 'ditolak');
        return ['ok' => false, 'error' => 'Fonnte menolak pesan: ' . $reason, 'driver' => 'fonnte'];
    }

    return ['ok' => true, 'error' => '', 'driver' => 'fonnte'];
}

/**
 * Kirim satu pesan WhatsApp. Mengembalikan ['ok' => bool, 'error' => string, 'driver' => string].
 */
function app_send_whatsapp(string $phone, string $message): array
{
    $target = whatsapp_target($phone);
    if (strlen($target) < 10 || strlen($target) > 15) {
        return ['ok' => false, 'error' => 'Nomor WhatsApp tujuan tidak valid.', 'driver' => ''];
    }

    $config = whatsapp_config();
    $driver = strtolower((string)($config['driver'] ?? 'log'));

    if ($driver === 'fonnte') {
        return whatsapp_send_fonnte($target, $message, $config);
    }

    return whatsapp_send_log($target, $message);
}
