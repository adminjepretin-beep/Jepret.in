<?php
// data/cameras.php - Data kamera Jepret.in dibaca dari database
//
// Tabel `cameras` otomatis dibuat dan di-seed dari data/cameras_seed.php
// saat database pertama kali dipakai (lihat includes/schema.php).

require_once __DIR__ . '/../config/database.php';

$cameras = [];

$pdo = Database::getConnection();
if ($pdo) {
    try {
        $rows = $pdo->query('SELECT * FROM cameras ORDER BY id ASC')->fetchAll();

        foreach ($rows as $camera) {
            $includes = json_decode((string)$camera['includes'], true);
            if (!is_array($includes)) {
                $includes = array_values(array_filter(array_map('trim', explode("\n", (string)$camera['includes']))));
            }

            $camera['includes'] = $includes;
            $camera['id'] = (int)$camera['id'];
            $camera['daily_price'] = (int)$camera['daily_price'];
            $camera['is_featured'] = (bool)$camera['is_featured'];
            $camera['is_available'] = (bool)$camera['is_available'];
            $camera['stock'] = (int)($camera['stock'] ?? 1);

            $cameras[] = $camera;
        }
    } catch (Throwable $e) {
        $cameras = [];
    }
}

// Helper Functions
function formatRupiah($nominal)
{
    return 'Rp ' . number_format((int)$nominal, 0, ',', '.');
}

function getCameraById($id)
{
    global $cameras;
    foreach ($cameras as $cam) {
        if ($cam['id'] == $id) {
            return $cam;
        }
    }
    return null;
}

/**
 * Kamera yang aktif (tidak dinonaktifkan admin).
 */
function activeCameras(array $cameras): array
{
    return array_values(array_filter($cameras, static fn($cam) => !empty($cam['is_available'])));
}

/**
 * Jumlah model kamera yang aktif.
 */
function activeCameraCount(array $cameras): int
{
    return count(activeCameras($cameras));
}

/**
 * Total unit fisik yang aktif (menjumlahkan stok tiap model aktif).
 */
function activeCameraStock(array $cameras): int
{
    return array_sum(array_map(static fn($cam) => (int)($cam['stock'] ?? 0), activeCameras($cameras)));
}

function getCameraBySlug($slug)
{
    global $cameras;
    foreach ($cameras as $cam) {
        if ($cam['slug'] === $slug) {
            return $cam;
        }
    }
    return null;
}
