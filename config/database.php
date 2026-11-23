<?php
// config/database.php - Koneksi database & timezone Jepret.in
//
// Kredensial dibaca dari environment variable, dengan nilai default untuk
// development lokal (XAMPP/Laragon). Di hosting isi lewat .htaccess
// (SetEnv) atau panel hosting:
//
//   DB_DRIVER  auto | mysql | sqlite        (default: auto)
//     - auto   : coba MySQL; kalau gagal pakai SQLite dan catat peringatan
//                di error_log (mode development).
//     - mysql  : WAJIB MySQL. Kalau gagal, aplikasi berhenti dengan pesan
//                error yang jelas. PAKAI INI DI HOSTING, supaya data tidak
//                diam-diam tersimpan di SQLite sementara admin mengira
//                semuanya masuk MySQL.
//     - sqlite : paksa SQLite, tanpa mencoba MySQL.
//   DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS
//   APP_TIMEZONE  (default: Asia/Jakarta)
//
// Semua tanggal di aplikasi (kode booking JP-YYMMDD, jadwal sewa, created_at)
// memakai APP_TIMEZONE ini, bukan zona waktu bawaan server.

$app_timezone = getenv('APP_TIMEZONE');
if ($app_timezone === false || $app_timezone === '') {
    $app_timezone = $_SERVER['APP_TIMEZONE'] ?? 'Asia/Jakarta';
}
$app_timezone = (string)$app_timezone;
if (!in_array($app_timezone, timezone_identifiers_list(), true)) {
    $app_timezone = 'Asia/Jakarta';
}
date_default_timezone_set($app_timezone);

require_once __DIR__ . '/../includes/schema.php';

class Database
{
    private static ?PDO $pdo = null;
    private static string $driver = '';
    private static bool $resolved = false;
    private static string $mysql_error = '';
    private static array $config = [];
    private static array $local = [];

    /**
     * Setelan dari config/local.php (dipakai hosting tanpa env var).
     */
    private static function local(): array
    {
        if (self::$local) {
            return self::$local;
        }

        $file = __DIR__ . '/local.php';
        if (is_file($file)) {
            $data = require $file;
            if (is_array($data)) {
                self::$local = $data;
            }
        }

        return self::$local;
    }

    /**
     * Baca environment variable (getenv, lalu $_SERVER/$_ENV untuk SetEnv hosting).
     */
    private static function env(string $key, string $default = ''): string
    {
        $value = getenv($key);
        if ($value === false || $value === '') {
            $value = $_SERVER[$key] ?? $_ENV[$key] ?? '';
        }

        return ($value === '' || $value === null) ? $default : (string)$value;
    }

    private static function config(): array
    {
        if (self::$config) {
            return self::$config;
        }

        $db = (array)(self::local()['db'] ?? []);
        $pick = static function (string $key, string $env_key, string $default) use ($db): string {
            if (array_key_exists($key, $db) && $db[$key] !== null) {
                return (string)$db[$key];
            }
            return self::env($env_key, $default);
        };

        self::$config = [
            'mode'    => strtolower($pick('driver', 'DB_DRIVER', 'auto')),
            'host'    => $pick('host', 'DB_HOST', '127.0.0.1'),
            'port'    => $pick('port', 'DB_PORT', '3306'),
            'name'    => $pick('name', 'DB_NAME', 'jepret_in'),
            'user'    => $pick('user', 'DB_USER', 'root'),
            'pass'    => $pick('pass', 'DB_PASS', ''),
            'charset' => 'utf8mb4',
        ];

        return self::$config;
    }

    public static function getConnection(): ?PDO
    {
        if (self::$resolved) {
            return self::$pdo;
        }
        self::$resolved = true;

        $config = self::config();
        $mode = in_array($config['mode'], ['auto', 'mysql', 'sqlite'], true) ? $config['mode'] : 'auto';

        if ($mode !== 'sqlite') {
            $pdo = self::connectMysql($config);
            if ($pdo) {
                self::$pdo = $pdo;
                self::$driver = 'mysql';
            } elseif ($mode === 'mysql') {
                // Gagal keras: jangan pernah diam-diam pindah ke SQLite di produksi.
                self::abortMysqlUnavailable($config);
            }
        }

        if (!self::$pdo) {
            self::$pdo = self::connectSqlite();
            if (self::$pdo) {
                self::$driver = 'sqlite';
                if ($mode !== 'sqlite') {
                    error_log(
                        'Jepret.in: MySQL gagal (' . (self::$mysql_error !== '' ? self::$mysql_error : 'tanpa detail')
                        . '), aplikasi memakai SQLite. Data TIDAK masuk ke MySQL. '
                        . 'Set DB_DRIVER=mysql di hosting supaya error ini terlihat.'
                    );
                }
            }
        }

        if (self::$pdo) {
            schema_install(self::$pdo, self::$driver);
            seed_product_data(self::$pdo);
            seed_testimonial_data(self::$pdo);
        }

        return self::$pdo;
    }

    public static function driver(): string
    {
        self::getConnection();
        return self::$driver;
    }

    public static function isMysql(): bool
    {
        return self::driver() === 'mysql';
    }

    /**
     * Berhenti dengan pesan jelas saat DB_DRIVER=mysql tapi koneksi gagal.
     * Halaman publik tidak menampilkan path/stack trace; detailnya ke error_log.
     */
    private static function abortMysqlUnavailable(array $config): void
    {
        $message = self::mysql_failure_message($config);
        error_log('Jepret.in: ' . $message);

        if (PHP_SAPI === 'cli') {
            throw new RuntimeException($message);
        }

        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=UTF-8');
        }

        echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8">'
            . '<meta name="robots" content="noindex"><title>Database belum terhubung</title></head>'
            . '<body style="font-family:Arial,Helvetica,sans-serif;max-width:560px;margin:60px auto;padding:0 20px;color:#0f172a">'
            . '<h1 style="font-size:18px;margin:0 0 12px">Database belum bisa dihubungi</h1>'
            . '<p style="font-size:14px;line-height:1.6;margin:0 0 12px">Website sedang tidak bisa mengakses MySQL. '
            . 'Periksa setelan <code>DB_HOST</code>, <code>DB_PORT</code>, <code>DB_NAME</code>, <code>DB_USER</code>, '
            . 'dan <code>DB_PASS</code> di hosting, lalu muat ulang halaman ini.</p>'
            . '<p style="font-size:12px;color:#64748b;margin:0">Detail teknis sudah dicatat di error_log hosting.</p>'
            . '</body></html>';
        exit;
    }

    private static function mysql_failure_message(array $config): string
    {
        $reason = self::$mysql_error !== '' ? self::$mysql_error : 'tanpa detail';

        return 'Jepret.in tidak bisa terhubung ke MySQL (host ' . $config['host'] . ':' . $config['port']
            . ', database ' . $config['name'] . ', user ' . $config['user'] . '): ' . $reason
            . '. Perbaiki DB_HOST/DB_PORT/DB_NAME/DB_USER/DB_PASS, atau set DB_DRIVER=sqlite '
            . 'kalau memang ingin memakai SQLite.';
    }

    private static function connectMysql(array $config): ?PDO
    {
        self::$mysql_error = '';

        if (!in_array('mysql', PDO::getAvailableDrivers(), true)) {
            self::$mysql_error = 'ekstensi pdo_mysql tidak aktif';
            return null;
        }

        // Lewati cepat kalau port MySQL tidak terbuka, supaya halaman tidak lemot.
        $socket = @fsockopen($config['host'], (int)$config['port'], $errno, $errstr, 0.4);
        if (!$socket) {
            self::$mysql_error = 'port ' . $config['port'] . ' di ' . $config['host'] . ' tidak bisa dihubungi'
                . ($errstr !== '' ? ' (' . $errstr . ')' : '');
            return null;
        }
        fclose($socket);

        $dsn = 'mysql:host=' . $config['host'] . ';port=' . $config['port']
            . ';dbname=' . $config['name'] . ';charset=' . $config['charset'];

        try {
            $pdo = new PDO($dsn, $config['user'], $config['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_TIMEOUT            => 2,
            ]);
            return $pdo;
        } catch (PDOException $e) {
            // Pesan asli PDO berguna untuk admin (mis. "Access denied for user"),
            // tapi tidak pernah memuat password.
            self::$mysql_error = $e->getMessage();
            return null;
        }
    }

    private static function connectSqlite(): ?PDO
    {
        if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            return null;
        }

        $dir = dirname(__DIR__) . '/data';
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            return null;
        }

        try {
            $pdo = new PDO('sqlite:' . $dir . '/jepret.sqlite', null, null, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            $pdo->exec('PRAGMA foreign_keys = ON');
            $pdo->exec('PRAGMA busy_timeout = 5000');
            return $pdo;
        } catch (PDOException $e) {
            return null;
        }
    }
}
