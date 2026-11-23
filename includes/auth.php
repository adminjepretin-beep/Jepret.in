<?php
// includes/auth.php - Session & autentikasi Jepret.in
//
// Satu halaman login untuk admin & pengguna:
// - Admin   : akun dari config/admin.php (lihat require_admin_login)
// - Pengguna: akun dari tabel `users` (lihat auth_current_user)

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/whatsapp.php';

const ADMIN_MAX_ATTEMPTS = 5;   // maksimal percobaan gagal
const ADMIN_LOCK_SECONDS = 600; // dalam 10 menit

function admin_config(): array
{
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/../config/admin.php';
    }
    return $config;
}

function admin_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    // Jangan pernah ubah nama cookie setelah output terkirim (bikin warning di halaman).
    if (headers_sent()) {
        return;
    }

    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_name('JEPRETSESSION');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => $https,
    ]);
    session_start();
}

function admin_is_logged_in(): bool
{
    admin_start_session();
    return !empty($_SESSION['admin_logged_in']);
}

function admin_username(): string
{
    return (string)($_SESSION['admin_username'] ?? 'admin');
}

function admin_failed_attempts(): array
{
    $fails = $_SESSION['admin_fails'] ?? [];
    $fails = array_values(array_filter($fails, static function ($time) {
        return $time > time() - ADMIN_LOCK_SECONDS;
    }));
    $_SESSION['admin_fails'] = $fails;
    return $fails;
}

function admin_is_locked_out(): bool
{
    return count(admin_failed_attempts()) >= ADMIN_MAX_ATTEMPTS;
}

function admin_lockout_remaining(): int
{
    $fails = admin_failed_attempts();
    if (!$fails) {
        return 0;
    }
    return max(0, (min($fails) + ADMIN_LOCK_SECONDS) - time());
}

/* =========================================================
 * Rate limit login: per IP dan per akun (tersimpan di database)
 * ========================================================= */

const LOGIN_MAX_ATTEMPTS = 5;     // maksimal gagal dalam jendela waktu
const LOGIN_WINDOW_SECONDS = 600; // 10 menit

function login_client_ip(): string
{
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    return $ip !== '' ? substr($ip, 0, 45) : 'unknown';
}

/**
 * Kunci akun memakai identitas yang dinormalisasi (email/username/no. WA).
 */
function login_identifier_key(string $identity): string
{
    $identity = mb_strtolower(trim($identity));
    return $identity !== '' ? substr($identity, 0, 191) : '-';
}

function login_attempts_count(string $ip, string $identifier): int
{
    $pdo = Database::getConnection();
    if (!$pdo) {
        return 0;
    }

    try {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM login_attempts
                               WHERE attempted_at > ? AND (ip = ? OR identifier = ?)');
        $stmt->execute([time() - LOGIN_WINDOW_SECONDS, $ip, $identifier]);
        return (int)$stmt->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

function login_throttled(string $ip, string $identifier): bool
{
    return admin_is_locked_out() || login_attempts_count($ip, $identifier) >= LOGIN_MAX_ATTEMPTS;
}

function login_lockout_remaining(string $ip, string $identifier): int
{
    $db_left = 0;
    $pdo = Database::getConnection();

    if ($pdo) {
        try {
            $stmt = $pdo->prepare('SELECT MIN(attempted_at) FROM login_attempts
                                   WHERE attempted_at > ? AND (ip = ? OR identifier = ?)');
            $stmt->execute([time() - LOGIN_WINDOW_SECONDS, $ip, $identifier]);
            $oldest = (int)$stmt->fetchColumn();
            if ($oldest > 0) {
                $db_left = max(0, ($oldest + LOGIN_WINDOW_SECONDS) - time());
            }
        } catch (Throwable $e) {
            $db_left = 0;
        }
    }

    return max(admin_lockout_remaining(), $db_left);
}

/**
 * Catat satu percobaan gagal (plus bersihkan catatan lama).
 */
function login_throttle_record(string $ip, string $identifier): void
{
    $pdo = Database::getConnection();
    if (!$pdo) {
        return;
    }

    try {
        $cleanup = $pdo->prepare('DELETE FROM login_attempts WHERE attempted_at < ?');
        $cleanup->execute([time() - (LOGIN_WINDOW_SECONDS * 6)]);

        $stmt = $pdo->prepare('INSERT INTO login_attempts (ip, identifier, attempted_at) VALUES (?, ?, ?)');
        $stmt->execute([$ip, $identifier, time()]);
    } catch (Throwable $e) {
        // Rate limit di database tidak boleh memblokir login normal.
    }
}

/**
 * Login berhasil: hapus catatan gagal untuk IP/akun ini.
 */
function login_throttle_clear(string $ip, string $identifier): void
{
    $pdo = Database::getConnection();
    if (!$pdo) {
        return;
    }

    try {
        $stmt = $pdo->prepare('DELETE FROM login_attempts WHERE ip = ? OR identifier = ?');
        $stmt->execute([$ip, $identifier]);
    } catch (Throwable $e) {
        // abaikan
    }
}

function admin_csrf_token(): string
{
    admin_start_session();
    if (empty($_SESSION['admin_csrf'])) {
        $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['admin_csrf'];
}

function admin_verify_csrf(?string $token): bool
{
    admin_start_session();
    if (empty($_SESSION['admin_csrf']) || !is_string($token) || $token === '') {
        return false;
    }
    return hash_equals($_SESSION['admin_csrf'], $token);
}

function admin_attempt_login(string $username, string $password): bool
{
    admin_start_session();
    $config = admin_config();

    if (empty($config['configured'])) {
        error_log('Jepret.in: login admin ditolak karena ADMIN_PASSWORD_HASH belum diisi (lihat config/admin.php).');
        return false;
    }

    $username_ok = hash_equals((string)$config['username'], $username);
    $password_ok = password_verify($password, (string)$config['password_hash']);

    if ($username_ok && $password_ok) {
        session_regenerate_id(true);
        unset($_SESSION['user_id']);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username']  = (string)$config['username'];
        $_SESSION['admin_login_at']  = time();
        $_SESSION['admin_fails']     = [];
        unset($_SESSION['admin_csrf']);
        return true;
    }

    $_SESSION['admin_fails'][] = time();
    usleep(400000); // pelan-pelanin percobaan beruntun
    return false;
}

function admin_logout(): void
{
    admin_start_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 42000,
            'path'     => $params['path'],
            'domain'   => $params['domain'],
            'secure'   => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
    }
    session_destroy();
}

/**
 * Gerbang utama halaman admin.
 * Belum login -> dilempar ke login.php. Sudah login -> halaman tidak di-cache.
 */
function require_admin_login(): void
{
    if (!admin_is_logged_in()) {
        header('Location: ../login.php?next=' . urlencode('admin/index.php'));
        exit;
    }

    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: no-referrer');
}

/* =========================================================
 * Autentikasi pengguna (customer)
 * ========================================================= */

function auth_current_user(): ?array
{
    admin_start_session();

    $user_id = (int)($_SESSION['user_id'] ?? 0);
    if ($user_id <= 0) {
        return null;
    }

    $pdo = Database::getConnection();
    if (!$pdo) {
        return null;
    }

    try {
        $stmt = $pdo->prepare('SELECT id, name, email, phone, created_at FROM users WHERE id = ?');
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
    } catch (Throwable $e) {
        return null;
    }

    return $user ?: null;
}

function auth_login_user(int $user_id): void
{
    admin_start_session();
    session_regenerate_id(true);
    unset($_SESSION['admin_logged_in'], $_SESSION['admin_username']);
    $_SESSION['user_id'] = $user_id;
    $_SESSION['admin_fails'] = [];
    unset($_SESSION['admin_csrf']);
}

function auth_logout(): void
{
    admin_logout();
}

/**
 * Pastikan tujuan redirect tetap lokal (tidak bisa dipakai open redirect).
 */
function auth_safe_next(?string $next, string $fallback): string
{
    $next = trim((string)$next);

    if ($next === '' || $next[0] === '/' || str_contains($next, '\\') || preg_match('#^[a-z][a-z0-9+.-]*:#i', $next)) {
        return $fallback;
    }

    return $next;
}

/**
 * Ubah profil pengguna: nama, email, nomor WhatsApp.
 *
 * @return array{ok: bool, error: string}
 */
function auth_update_profile(int $user_id, array $input): array
{
    $pdo = Database::getConnection();
    if (!$pdo) {
        return ['ok' => false, 'error' => 'Database tidak bisa dihubungi. Coba lagi ya.'];
    }

    $name  = trim((string)($input['name'] ?? ''));
    $email = trim((string)($input['email'] ?? ''));
    $phone = auth_normalize_phone((string)($input['phone'] ?? ''));

    if (mb_strlen($name) < 2) {
        return ['ok' => false, 'error' => 'Nama minimal 2 karakter.'];
    }
    if (mb_strlen($name) > 191) {
        return ['ok' => false, 'error' => 'Nama terlalu panjang (maksimal 191 karakter).'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 191) {
        return ['ok' => false, 'error' => 'Format email belum benar, contoh: nama@email.com.'];
    }
    if (strlen($phone) < 8 || strlen($phone) > 15) {
        return ['ok' => false, 'error' => 'Nomor WhatsApp harus berupa 8-15 digit angka.'];
    }

    try {
        $check = $pdo->prepare('SELECT email, phone FROM users WHERE (LOWER(email) = LOWER(?) OR phone = ?) AND id <> ?');
        $check->execute([$email, $phone, $user_id]);
        foreach ($check->fetchAll() as $row) {
            if (strcasecmp((string)$row['email'], $email) === 0) {
                return ['ok' => false, 'error' => 'Email itu sudah dipakai akun lain.'];
            }
            if ((string)$row['phone'] === $phone) {
                return ['ok' => false, 'error' => 'Nomor WhatsApp itu sudah dipakai akun lain.'];
            }
        }

        $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, phone = ?, updated_at = ? WHERE id = ?');
        $stmt->execute([$name, $email, $phone, date('Y-m-d H:i:s'), $user_id]);
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'Email atau nomor WhatsApp itu sudah dipakai akun lain.'];
    }

    return ['ok' => true, 'error' => ''];
}

/**
 * Ganti password pengguna yang sedang login (wajib tahu password lama).
 *
 * @return array{ok: bool, error: string}
 */
function auth_change_password(int $user_id, string $current, string $new, string $confirm): array
{
    $pdo = Database::getConnection();
    if (!$pdo) {
        return ['ok' => false, 'error' => 'Database tidak bisa dihubungi. Coba lagi ya.'];
    }

    if ($current === '') {
        return ['ok' => false, 'error' => 'Masukkan password lama kamu dulu.'];
    }
    if (strlen($new) < 8) {
        return ['ok' => false, 'error' => 'Password baru minimal 8 karakter.'];
    }
    if (strlen($new) > 72) {
        return ['ok' => false, 'error' => 'Password baru maksimal 72 karakter.'];
    }
    if ($new !== $confirm) {
        return ['ok' => false, 'error' => 'Konfirmasi password baru belum sama.'];
    }
    if ($new === $current) {
        return ['ok' => false, 'error' => 'Password baru harus beda dari password sekarang.'];
    }

    try {
        $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
        $stmt->execute([$user_id]);
        $hash = (string)$stmt->fetchColumn();

        if ($hash === '' || !password_verify($current, $hash)) {
            return ['ok' => false, 'error' => 'Password lama tidak cocok.'];
        }

        $update = $pdo->prepare('UPDATE users SET password_hash = ?, updated_at = ? WHERE id = ?');
        $update->execute([password_hash($new, PASSWORD_DEFAULT), date('Y-m-d H:i:s'), $user_id]);
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'Password gagal disimpan. Coba lagi ya.'];
    }

    return ['ok' => true, 'error' => ''];
}

/* =========================================================
 * Reset password lewat nomor WhatsApp (link dikirim ke WA)
 * ========================================================= */

/**
 * Nomor WhatsApp disimpan & dicocokkan dalam bentuk digit saja,
 * dengan awalan 62 diubah jadi 0. Contoh: +62 812-3456 -> 08123456.
 */
function auth_normalize_phone(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone) ?? '';
    if (str_starts_with($digits, '62')) {
        $digits = '0' . substr($digits, 2);
    }

    return $digits;
}

/**
 * Cari akun berdasarkan nomor WhatsApp DAN email yang dipakai saat daftar.
 * Dua-duanya wajib cocok dengan akun yang sama.
 */
function auth_find_user_by_phone(string $phone, string $email): ?array
{
    $digits = auth_normalize_phone($phone);
    $email = mb_strtolower(trim($email));

    if (strlen($digits) < 8 || strlen($digits) > 15) {
        return null;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return null;
    }

    $pdo = Database::getConnection();
    if (!$pdo) {
        return null;
    }

    try {
        $stmt = $pdo->prepare('SELECT id, name, email, phone
                               FROM users WHERE phone = ? LIMIT 1');
        $stmt->execute([$digits]);
        $user = $stmt->fetch();
    } catch (Throwable $e) {
        return null;
    }

    if (!$user || strcasecmp((string)$user['email'], $email) !== 0) {
        return null;
    }

    return $user;
}

function auth_mask_phone(string $phone): string
{
    $digits = auth_normalize_phone($phone);
    if (strlen($digits) < 8) {
        return $digits;
    }

    return substr($digits, 0, 4) . str_repeat('*', max(2, strlen($digits) - 7)) . substr($digits, -3);
}

/**
 * Cocokkan nomor WhatsApp + email, lalu kirim link reset ke WhatsApp pemilik akun.
 * Link tidak pernah ditampilkan di halaman, supaya hanya pemilik nomor yang bisa memakainya.
 *
 * @return array{ok: bool, error: string, phone: string}
 */
function auth_request_password_reset_whatsapp(string $phone, string $email): array
{
    $fail = [
        'ok'    => false,
        'error' => 'Nomor WhatsApp dan email tidak cocok dengan akun mana pun. Cek lagi data yang dipakai saat daftar.',
        'phone' => '',
    ];

    if (strlen(auth_normalize_phone($phone)) < 8) {
        return $fail;
    }
    if (!filter_var(trim($email), FILTER_VALIDATE_EMAIL)) {
        return $fail;
    }

    $user = auth_find_user_by_phone($phone, $email);
    if (!$user) {
        return $fail;
    }

    $token = auth_create_password_reset_token((int)$user['id']);
    if ($token === null) {
        return ['ok' => false, 'error' => 'Gagal membuat link reset. Coba lagi ya.', 'phone' => ''];
    }

    $link = app_base_url() . '/reset-password.php?token=' . rawurlencode($token);
    $name = trim((string)($user['name'] ?? '')) !== '' ? trim((string)$user['name']) : 'Kak';

    $message = 'Halo ' . $name . ', ini link untuk mengatur ulang password akun Jepret.in kamu:' . "\n"
        . $link . "\n\n"
        . 'Link berlaku 60 menit dan hanya bisa dipakai sekali. Kalau kamu tidak meminta reset, abaikan pesan ini.';

    $result = app_send_whatsapp((string)$user['phone'], $message);
    if (empty($result['ok'])) {
        error_log('Jepret.in: gagal kirim link reset WhatsApp (' . ($result['driver'] ?? '') . '): ' . ($result['error'] ?? ''));
        return [
            'ok'    => false,
            'error' => 'Link reset gagal dikirim ke WhatsApp. Coba lagi sebentar atau hubungi admin.',
            'phone' => '',
        ];
    }

    password_reset_clear_attempts(login_client_ip(), $phone);

    return ['ok' => true, 'error' => '', 'phone' => auth_mask_phone($phone)];
}

/* =========================================================
 * Reset password lewat token (dari link WhatsApp atau tombol
 * "Buat Link Reset" di panel admin)
 * ========================================================= */

const PASSWORD_RESET_TTL = 3600; // link reset berlaku 60 menit

function password_reset_identifier(string $phone): string
{
    return 'pwreset:' . auth_normalize_phone($phone);
}

function password_reset_throttled(string $ip, string $phone): bool
{
    $pdo = Database::getConnection();
    if (!$pdo) {
        return false;
    }

    try {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM login_attempts
                               WHERE attempted_at > ?
                                 AND (identifier = ? OR (ip = ? AND identifier LIKE ?))');
        $stmt->execute([time() - LOGIN_WINDOW_SECONDS, password_reset_identifier($phone), $ip, 'pwreset:%']);
        return (int)$stmt->fetchColumn() >= LOGIN_MAX_ATTEMPTS;
    } catch (Throwable $e) {
        return false;
    }
}

function password_reset_log_attempt(string $ip, string $phone): void
{
    $pdo = Database::getConnection();
    if (!$pdo) {
        return;
    }

    try {
        $pdo->prepare('INSERT INTO login_attempts (ip, identifier, attempted_at) VALUES (?, ?, ?)')
            ->execute([$ip, password_reset_identifier($phone), time()]);
    } catch (Throwable $e) {
        // rate limit tidak boleh mengganggu alur normal
    }
}

function password_reset_clear_attempts(string $ip, string $phone): void
{
    $pdo = Database::getConnection();
    if (!$pdo) {
        return;
    }

    try {
        $pdo->prepare('DELETE FROM login_attempts WHERE identifier = ? OR (ip = ? AND identifier LIKE ?)')
            ->execute([password_reset_identifier($phone), $ip, 'pwreset:%']);
    } catch (Throwable $e) {
        // abaikan
    }
}

/**
 * Buat token reset password untuk satu akun.
 * Dipakai alur link WhatsApp maupun tombol "Buat Link Reset" di panel admin.
 *
 * @return string|null token mentah (hanya ditampilkan sekali), atau null kalau gagal
 */
function auth_create_password_reset_token(int $user_id): ?string
{
    if ($user_id <= 0) {
        return null;
    }

    $pdo = Database::getConnection();
    if (!$pdo) {
        return null;
    }

    $token = bin2hex(random_bytes(32));
    $now = time();

    try {
        // Token lama yang belum dipakai dimatikan: hanya link terbaru yang berlaku.
        $pdo->prepare('DELETE FROM password_resets WHERE user_id = ? AND used_at IS NULL')->execute([$user_id]);
        $pdo->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at, ip, created_at) VALUES (?, ?, ?, ?, ?)')
            ->execute([
                $user_id,
                hash('sha256', $token),
                $now + PASSWORD_RESET_TTL,
                login_client_ip(),
                date('Y-m-d H:i:s', $now),
            ]);
    } catch (Throwable $e) {
        error_log('Jepret.in: gagal menyimpan token reset password: ' . $e->getMessage());
        return null;
    }

    return $token;
}

/**
 * Cari baris token reset yang masih berlaku, atau null.
 */
function auth_password_reset_token(string $token): ?array
{
    $token = trim($token);
    if ($token === '' || strlen($token) < 32) {
        return null;
    }

    $pdo = Database::getConnection();
    if (!$pdo) {
        return null;
    }

    try {
        $stmt = $pdo->prepare('SELECT id, user_id, expires_at, used_at FROM password_resets WHERE token_hash = ? LIMIT 1');
        $stmt->execute([hash('sha256', $token)]);
        $row = $stmt->fetch();
    } catch (Throwable $e) {
        return null;
    }

    if (!$row || !empty($row['used_at']) || (int)$row['expires_at'] <= time()) {
        return null;
    }

    return $row;
}

/**
 * Simpan password baru dari link reset (sekali pakai).
 *
 * @return array{ok: bool, error: string}
 */
function auth_reset_password(string $token, string $new, string $confirm): array
{
    $row = auth_password_reset_token($token);
    if (!$row) {
        return ['ok' => false, 'error' => 'Link reset tidak valid atau sudah kedaluwarsa. Minta link baru ya.'];
    }
    if (strlen($new) < 8) {
        return ['ok' => false, 'error' => 'Password baru minimal 8 karakter.'];
    }
    if (strlen($new) > 72) {
        return ['ok' => false, 'error' => 'Password baru maksimal 72 karakter.'];
    }
    if ($new !== $confirm) {
        return ['ok' => false, 'error' => 'Konfirmasi password belum sama.'];
    }

    $pdo = Database::getConnection();
    if (!$pdo) {
        return ['ok' => false, 'error' => 'Database tidak bisa dihubungi. Coba lagi ya.'];
    }

    try {
        $pdo->beginTransaction();
        $pdo->prepare('UPDATE users SET password_hash = ?, updated_at = ? WHERE id = ?')
            ->execute([password_hash($new, PASSWORD_DEFAULT), date('Y-m-d H:i:s'), (int)$row['user_id']]);
        $pdo->prepare('UPDATE password_resets SET used_at = ? WHERE id = ?')->execute([time(), (int)$row['id']]);
        $pdo->prepare('DELETE FROM password_resets WHERE user_id = ? AND used_at IS NULL')->execute([(int)$row['user_id']]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'error' => 'Password gagal disimpan. Coba lagi ya.'];
    }

    try {
        $stmt = $pdo->prepare('SELECT phone FROM users WHERE id = ?');
        $stmt->execute([(int)$row['user_id']]);
        $phone = (string)$stmt->fetchColumn();
        if ($phone !== '') {
            password_reset_clear_attempts(login_client_ip(), $phone);
        }
    } catch (Throwable $e) {
        // pembersihan rate limit bukan hal kritis
    }

    return ['ok' => true, 'error' => ''];
}

/**
 * Halaman yang butuh login pengguna. Belum login -> dilempar ke login.php.
 */
function auth_require_user(string $next = ''): array
{
    $user = auth_current_user();

    if (!$user) {
        $target = $next !== '' ? $next : basename($_SERVER['PHP_SELF'] ?? 'akun.php');
        header('Location: login.php?next=' . urlencode($target));
        exit;
    }

    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');

    return $user;
}
