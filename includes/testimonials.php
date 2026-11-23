<?php
// includes/testimonials.php - Testimoni dari pelanggan yang sudah selesai sewa

require_once __DIR__ . '/../config/database.php';

function testimonial_create(int $user_id, int $booking_id, string $name, int $rating, string $message): bool
{
    $rating = max(1, min(5, $rating));
    $message = trim($message);

    if ($user_id <= 0 || $booking_id <= 0 || mb_strlen($message) < 10) {
        return false;
    }

    $pdo = Database::getConnection();
    if (!$pdo) {
        return false;
    }

    try {
        $check = $pdo->prepare('SELECT id FROM testimonials WHERE booking_id = ? LIMIT 1');
        $check->execute([$booking_id]);
        if ($check->fetchColumn()) {
            return false;
        }

        $stmt = $pdo->prepare('INSERT INTO testimonials (user_id, booking_id, name, rating, message, created_at)
            VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$user_id, $booking_id, $name, $rating, mb_substr($message, 0, 600), date('Y-m-d H:i:s')]);

        return true;
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * Label kecil di bawah nama penyewa: pakai role kalau ada,
 * kalau tidak tampilkan nama kamera yang disewa.
 */
function testimonial_label(array $row): string
{
    $role = trim((string)($row['role'] ?? ''));
    if ($role !== '') {
        return $role;
    }

    $camera = trim((string)($row['camera_name'] ?? ''));

    return $camera !== '' ? 'Penyewa ' . $camera : 'Penyewa Jepret.in';
}

/**
 * Testimoni terbaru untuk ditampilkan di beranda.
 */
function testimonials_all(int $limit = 6): array
{
    $pdo = Database::getConnection();
    if (!$pdo) {
        return [];
    }

    try {
        $stmt = $pdo->prepare('SELECT t.*, c.name AS camera_name
            FROM testimonials t
            LEFT JOIN bookings b ON b.id = t.booking_id
            LEFT JOIN cameras c ON c.id = b.camera_id
            ORDER BY t.id DESC
            LIMIT ' . max(1, $limit));
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

/**
 * Rata-rata rating + jumlah testimoni yang masuk.
 *
 * @return array{average: float, count: int}
 */
function testimonials_rating_stats(): array
{
    $pdo = Database::getConnection();
    if (!$pdo) {
        return ['average' => 0.0, 'count' => 0];
    }

    try {
        $row = $pdo->query('SELECT COUNT(*) AS total, AVG(rating) AS average FROM testimonials')->fetch();
    } catch (Throwable $e) {
        return ['average' => 0.0, 'count' => 0];
    }

    return [
        'average' => (float)($row['average'] ?? 0),
        'count'   => (int)($row['total'] ?? 0),
    ];
}

/**
 * Testimoni milik satu pengguna, di-index berdasarkan booking_id.
 */
function testimonials_by_user(int $user_id): array
{
    $pdo = Database::getConnection();
    if (!$pdo) {
        return [];
    }

    $result = [];
    try {
        $stmt = $pdo->prepare('SELECT * FROM testimonials WHERE user_id = ? ORDER BY id DESC');
        $stmt->execute([$user_id]);
        foreach ($stmt->fetchAll() as $row) {
            $result[(int)$row['booking_id']] = $row;
        }
    } catch (Throwable $e) {
        return [];
    }

    return $result;
}
