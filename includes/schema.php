<?php
// includes/schema.php - Skema database & seed data produk

function schema_install(PDO $pdo, string $driver): void
{
    static $installed = false;
    if ($installed) {
        return;
    }
    $installed = true;

    $pk = $driver === 'mysql'
        ? 'INT AUTO_INCREMENT PRIMARY KEY'
        : 'INTEGER PRIMARY KEY AUTOINCREMENT';

    $engine = $driver === 'mysql'
        ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        : '';

    $pdo->exec("CREATE TABLE IF NOT EXISTS cameras (
        id $pk,
        slug VARCHAR(191) NOT NULL UNIQUE,
        name VARCHAR(191) NOT NULL,
        brand VARCHAR(80) NOT NULL,
        category VARCHAR(60) NOT NULL,
        tag VARCHAR(120) NOT NULL,
        daily_price INT NOT NULL,
        image VARCHAR(255) NOT NULL,
        sensor VARCHAR(200) NOT NULL,
        video VARCHAR(200) NOT NULL,
        iso VARCHAR(160) NOT NULL,
        mount VARCHAR(160) NOT NULL,
        weight VARCHAR(60) NOT NULL,
        stabilization VARCHAR(200) NOT NULL,
        description TEXT NOT NULL,
        includes TEXT NOT NULL,
        is_featured INT DEFAULT 0,
        is_available INT DEFAULT 1,
        stock INT NOT NULL DEFAULT 1,
        created_at VARCHAR(19) DEFAULT NULL
    )$engine");

        // Kolom rating kamera sudah tidak dipakai: rapikan database lama yang masih menyimpannya.
    foreach (['rating', 'review_count'] as $legacy_column) {
        if (schema_has_column($pdo, $driver, 'cameras', $legacy_column)) {
            try {
                $pdo->exec('ALTER TABLE cameras DROP COLUMN ' . $legacy_column);
            } catch (Throwable $e) {
                // Driver lama tanpa DROP COLUMN: biarkan kolomnya, aplikasi tidak memakainya lagi.
            }
        }
    }

    // Migrasi ringan: kolom stok untuk database yang dibuat sebelum fitur kelola kamera.
    if (!schema_has_column($pdo, $driver, 'cameras', 'stock')) {
        $pdo->exec('ALTER TABLE cameras ADD COLUMN stock INT NOT NULL DEFAULT 1');
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id $pk,
        name VARCHAR(191) NOT NULL,
        email VARCHAR(191) NOT NULL UNIQUE,
        phone VARCHAR(50) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        security_question VARCHAR(80) NOT NULL DEFAULT '',
        security_answer_hash VARCHAR(255) NOT NULL DEFAULT '',
        created_at VARCHAR(19) DEFAULT NULL,
        updated_at VARCHAR(19) DEFAULT NULL
    )$engine");

    // Migrasi ringan: database lama belum punya pertanyaan keamanan.
    foreach ([
        'security_question'    => "VARCHAR(80) NOT NULL DEFAULT ''",
        'security_answer_hash' => "VARCHAR(255) NOT NULL DEFAULT ''",
    ] as $column => $definition) {
        if (!schema_has_column($pdo, $driver, 'users', $column)) {
            $pdo->exec('ALTER TABLE users ADD COLUMN ' . $column . ' ' . $definition);
        }
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS customers (
        id $pk,
        name VARCHAR(191) NOT NULL,
        phone VARCHAR(50) NOT NULL UNIQUE,
        instagram VARCHAR(191) DEFAULT '',
        created_at VARCHAR(19) DEFAULT NULL,
        updated_at VARCHAR(19) DEFAULT NULL
    )$engine");

    $pdo->exec("CREATE TABLE IF NOT EXISTS bookings (
        id $pk,
        booking_code VARCHAR(60) NOT NULL UNIQUE,
        user_id INT DEFAULT NULL,
        customer_id INT DEFAULT NULL,
        camera_id INT DEFAULT NULL,
        customer_name VARCHAR(191) NOT NULL,
        customer_phone VARCHAR(50) NOT NULL,
        start_date VARCHAR(10) NOT NULL,
        end_date VARCHAR(10) NOT NULL,
        total_days INT NOT NULL DEFAULT 1,
        addon_keys TEXT DEFAULT NULL,
        addon_labels TEXT DEFAULT NULL,
        addon_per_day INT NOT NULL DEFAULT 0,
        camera_subtotal INT NOT NULL DEFAULT 0,
        addon_subtotal INT NOT NULL DEFAULT 0,
        discount INT NOT NULL DEFAULT 0,
        total_price INT NOT NULL DEFAULT 0,
        pickup_method VARCHAR(191) DEFAULT '',
        payment_method VARCHAR(20) DEFAULT 'cash',
        note TEXT DEFAULT NULL,
        status VARCHAR(20) DEFAULT 'pending',
        created_at VARCHAR(19) DEFAULT NULL,
        updated_at VARCHAR(19) DEFAULT NULL
    )$engine");

    // Migrasi ringan: database lama belum punya kolom user_id di bookings
    if (!schema_has_column($pdo, $driver, 'bookings', 'user_id')) {
        $pdo->exec('ALTER TABLE bookings ADD COLUMN user_id INT DEFAULT NULL');
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS payments (
        id $pk,
        booking_id INT NOT NULL,
        method VARCHAR(20) NOT NULL DEFAULT 'cash',
        amount INT NOT NULL DEFAULT 0,
        note VARCHAR(255) DEFAULT '',
        paid_at VARCHAR(19) DEFAULT NULL,
        created_at VARCHAR(19) DEFAULT NULL
    )$engine");

    // Token reset password: disimpan dalam bentuk hash, sekali pakai, kedaluwarsa 60 menit.
    $pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
        id $pk,
        user_id INT NOT NULL,
        token_hash VARCHAR(64) NOT NULL UNIQUE,
        expires_at INT NOT NULL,
        used_at INT DEFAULT NULL,
        ip VARCHAR(45) DEFAULT '',
        created_at VARCHAR(19) DEFAULT NULL
    )$engine");
    try {
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_password_resets_user ON password_resets (user_id)');
    } catch (Throwable $e) {
        // MySQL versi lama tidak kenal CREATE INDEX IF NOT EXISTS.
    }

    // Catatan percobaan login: rate limit per IP dan per akun (bukan per sesi).
    $pdo->exec("CREATE TABLE IF NOT EXISTS login_attempts (
        id $pk,
        ip VARCHAR(45) NOT NULL,
        identifier VARCHAR(191) NOT NULL,
        attempted_at INT NOT NULL
    )$engine");
    try {
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_login_attempts_window ON login_attempts (attempted_at)');
    } catch (Throwable $e) {
        // MySQL versi lama tidak kenal CREATE INDEX IF NOT EXISTS.
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS testimonials (
        id $pk,
        user_id INT NOT NULL,
        booking_id INT NOT NULL UNIQUE,
        name VARCHAR(191) NOT NULL,
        rating INT NOT NULL DEFAULT 5,
        message TEXT NOT NULL,
        role VARCHAR(191) NOT NULL DEFAULT '',
        created_at VARCHAR(19) DEFAULT NULL
    )$engine");

    // Kolom role (label penyewa) untuk instalasi lama.
    if (!schema_has_column($pdo, $driver, 'testimonials', 'role')) {
        try {
            $pdo->exec("ALTER TABLE testimonials ADD COLUMN role VARCHAR(191) NOT NULL DEFAULT ''");
        } catch (Throwable $e) {
            // Diabaikan: tabel mungkin baru dibuat lengkap dengan kolom ini.
        }
    }
}

/**
 * Isi tabel kamera dengan 10 unit bawaan kalau tabelnya masih kosong.
 */
function seed_product_data(PDO $pdo): void
{
    static $seeded = false;
    if ($seeded) {
        return;
    }
    $seeded = true;

    $count = (int)$pdo->query('SELECT COUNT(*) FROM cameras')->fetchColumn();
    if ($count > 0) {
        return;
    }

    $seed = require __DIR__ . '/../data/cameras_seed.php';
    if (!is_array($seed) || !$seed) {
        return;
    }

    $stmt = $pdo->prepare('INSERT INTO cameras
        (slug, name, brand, category, tag, daily_price, image, sensor, video, iso, mount, weight, stabilization, description, includes, is_featured, is_available, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');

    $now = date('Y-m-d H:i:s');

    foreach ($seed as $camera) {
        $stmt->execute([
            $camera['slug'],
            $camera['name'],
            $camera['brand'],
            $camera['category'],
            $camera['tag'],
            (int)$camera['daily_price'],
            $camera['image'],
            $camera['sensor'],
            $camera['video'],
            $camera['iso'],
            $camera['mount'],
            $camera['weight'],
            $camera['stabilization'],
            $camera['description'],
            json_encode($camera['includes'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            !empty($camera['is_featured']) ? 1 : 0,
            !empty($camera['is_available']) ? 1 : 0,
            $now,
        ]);
    }
}

/**
 * Isi testimoni contoh sekali, supaya beranda tidak kosong di instalasi baru.
 * booking_id memakai nilai contoh (900001+) karena testimoni ini tidak terikat
 * pesanan nyata, sementara kolomnya UNIQUE.
 */
function seed_testimonial_data(PDO $pdo): void
{
    static $seeded = false;
    if ($seeded) {
        return;
    }
    $seeded = true;

    try {
        if ((int)$pdo->query('SELECT COUNT(*) FROM testimonials')->fetchColumn() > 0) {
            return;
        }
    } catch (Throwable $e) {
        return;
    }

    $seed = require __DIR__ . '/../data/testimonials_seed.php';
    if (!is_array($seed) || !$seed) {
        return;
    }

    $stmt = $pdo->prepare('INSERT INTO testimonials
        (user_id, booking_id, name, rating, message, role, created_at)
        VALUES (0, ?, ?, ?, ?, ?, ?)');

    $now = date('Y-m-d H:i:s');
    $demo_booking = 900000;

    foreach ($seed as $row) {
        try {
            $stmt->execute([
                ++$demo_booking,
                (string)$row['name'],
                max(1, min(5, (int)($row['rating'] ?? 5))),
                (string)$row['message'],
                (string)($row['role'] ?? ''),
                $now,
            ]);
        } catch (Throwable $e) {
            // Baris contoh kemungkinan sudah ada; lanjut ke berikutnya.
        }
    }
}

/**
 * Cek apakah sebuah kolom sudah ada (MySQL / SQLite).
 */
function schema_has_column(PDO $pdo, string $driver, string $table, string $column): bool
{
    try {
        if ($driver === 'mysql') {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.columns
                WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?');
            $stmt->execute([$table, $column]);
            return (int)$stmt->fetchColumn() > 0;
        }

        foreach ($pdo->query('PRAGMA table_info(' . $table . ')')->fetchAll() as $col) {
            if (($col['name'] ?? '') === $column) {
                return true;
            }
        }
    } catch (Throwable $e) {
        return false;
    }

    return false;
}
