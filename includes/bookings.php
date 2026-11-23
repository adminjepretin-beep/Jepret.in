<?php
// includes/bookings.php - Data pemesanan, pelanggan, dan pembayaran (database)

require_once __DIR__ . '/../config/database.php';

const BOOKING_STATUSES = [
    'pending'   => 'Menunggu Konfirmasi',
    'confirmed' => 'Dikonfirmasi',
    'active'    => 'Sedang Disewa',
    'completed' => 'Selesai',
    'cancelled' => 'Dibatalkan',
];

// Status yang masih mengunci tanggal unit. Hanya 'cancelled' yang melepas tanggal.
const BOOKING_BLOCKING_STATUSES = ['pending', 'confirmed', 'active', 'completed'];

const BOOKING_PAYMENT_METHODS = [
    'cash' => 'Cash di Studio',
    'qris' => 'QRIS',
];

function booking_addon_options(): array
{
    return [
        'lensa'  => ['label' => 'Lensa Prime 35mm/50mm', 'price' => 150000],
        'gimbal' => ['label' => 'DJI RS 3 Pro Gimbal', 'price' => 100000],
        'mic'    => ['label' => 'Wireless Mic DJI Mic 2', 'price' => 75000],
    ];
}

/**
 * Hitung biaya sewa di server (angka dari browser tidak dipercaya).
 */
function booking_calculate(int $daily_price, int $days, array $addon_keys): array
{
    $days = max(1, $days);
    $options = booking_addon_options();
    $addon_labels = [];
    $addon_per_day = 0;

    foreach ($addon_keys as $key) {
        if (isset($options[$key])) {
            $addon_labels[] = $options[$key]['label'];
            $addon_per_day += (int)$options[$key]['price'];
        }
    }

    $camera_subtotal = $daily_price * $days;
    $addon_subtotal  = $addon_per_day * $days;
    $subtotal        = $camera_subtotal + $addon_subtotal;

    $rate = 0;
    if ($days >= 7) {
        $rate = 0.20;
    } elseif ($days >= 3) {
        $rate = 0.10;
    }
    $discount = (int)round($subtotal * $rate);

    return [
        'days'            => $days,
        'addon_keys'      => array_values($addon_keys),
        'addon_labels'    => $addon_labels,
        'addon_per_day'   => $addon_per_day,
        'camera_subtotal' => $camera_subtotal,
        'addon_subtotal'  => $addon_subtotal,
        'discount_rate'   => $rate,
        'discount'        => $discount,
        'total'           => $subtotal - $discount,
        'discount_text'   => $rate > 0 ? (string)($rate * 100) . '%' : '',
    ];
}

function booking_generate_code(): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $suffix = '';
    for ($i = 0; $i < 4; $i++) {
        $suffix .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }
    return 'JP-' . date('ymd') . '-' . $suffix;
}

/**
 * Lengkapi baris booking dengan status pembayaran.
 */
/**
 * Booking aktif pada satu kamera yang bentrok dengan rentang tanggal ini.
 *
 * Sewa dihitung setengah terbuka [start, end): `end_date` adalah hari unit
 * kembali, jadi tanggal itu sudah bebas untuk booking berikutnya.
 *
 * @return array<int, array<string, mixed>>
 */
function bookings_overlapping(int $camera_id, string $start_date, string $end_date, ?int $ignore_booking_id = null): array
{
    $pdo = Database::getConnection();
    if (!$pdo) {
        return [];
    }

    $placeholders = implode(', ', array_fill(0, count(BOOKING_BLOCKING_STATUSES), '?'));
    $sql = "SELECT id, booking_code, start_date, end_date
            FROM bookings
            WHERE camera_id = ?
              AND status IN ($placeholders)
              AND start_date < ?
              AND end_date > ?";

    $params = array_merge([$camera_id], BOOKING_BLOCKING_STATUSES, [$end_date, $start_date]);

    if ($ignore_booking_id !== null && $ignore_booking_id > 0) {
        $sql .= ' AND id <> ?';
        $params[] = $ignore_booking_id;
    }

    $sql .= ' ORDER BY start_date ASC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

/**
 * Apakah unit benar-benar kosong pada rentang tanggal tersebut?
 * Kalau database tidak bisa dicek, jawabannya "tidak tersedia" (fail-closed)
 * supaya unit yang sama tidak bisa dibooking dua kali.
 */
function booking_is_available(int $camera_id, string $start_date, string $end_date, int $stock = 1, ?int $ignore_booking_id = null): bool
{
    if ($start_date === '' || $end_date === '' || $start_date >= $end_date) {
        return false;
    }

    $stock = max(0, $stock);
    if ($stock === 0) {
        return false;
    }

    try {
        return count(bookings_overlapping($camera_id, $start_date, $end_date, $ignore_booking_id)) < $stock;
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * Sisa unit yang masih bisa dibooking pada rentang tanggal tertentu.
 */
function booking_units_left(int $camera_id, int $stock, string $start_date, string $end_date): int
{
    $stock = max(0, $stock);

    if ($start_date === '' || $end_date === '' || $start_date >= $end_date) {
        return $stock;
    }

    try {
        $used = count(bookings_overlapping($camera_id, $start_date, $end_date));
    } catch (Throwable $e) {
        return 0;
    }

    return max(0, $stock - $used);
}

/**
 * Rentang tanggal yang sudah terisi, untuk kalender ketersediaan.
 * Sengaja tanpa data pribadi penyewa (nama/kode booking tidak ikut).
 *
 * @return array<int, array{start: string, end: string}>
 */
function bookings_busy_ranges(int $camera_id, string $from_date, string $to_date): array
{
    try {
        $rows = bookings_overlapping($camera_id, $from_date, $to_date);
    } catch (Throwable $e) {
        return [];
    }

    $ranges = [];
    foreach ($rows as $row) {
        $ranges[] = [
            'start' => (string)$row['start_date'],
            'end'   => (string)$row['end_date'],
        ];
    }

    return $ranges;
}

/**
 * Jumlah booking aktif per hari: dasar hitungan sisa unit di kalender.
 *
 * @return array<string, int>
 */
function bookings_day_usage(int $camera_id, string $from_date, string $to_date): array
{
    $usage = [];

    foreach (bookings_busy_ranges($camera_id, $from_date, $to_date) as $range) {
        try {
            $day = new DateTimeImmutable($range['start']);
            $end = new DateTimeImmutable($range['end']);
        } catch (Throwable $e) {
            continue;
        }

        // Batasi perulangan supaya data tanggal yang aneh tidak bikin halaman hang.
        $guard = 0;
        while ($day < $end && $guard < 400) {
            $key = $day->format('Y-m-d');
            if ($key >= $from_date && $key <= $to_date) {
                $usage[$key] = ($usage[$key] ?? 0) + 1;
            }
            $day = $day->modify('+1 day');
            $guard++;
        }
    }

    ksort($usage);

    return $usage;
}

function booking_hydrate(array $row): array
{
    $total = (int)($row['total_price'] ?? 0);
    $paid  = (int)($row['paid_total'] ?? 0);

    $row['total_price'] = $total;
    $row['paid_total']  = $paid;
    $row['remaining']   = max(0, $total - $paid);

    $row['addon_labels'] = json_decode((string)($row['addon_labels'] ?? '[]'), true);
    if (!is_array($row['addon_labels'])) {
        $row['addon_labels'] = [];
    }
    $row['addon_keys'] = json_decode((string)($row['addon_keys'] ?? '[]'), true);
    if (!is_array($row['addon_keys'])) {
        $row['addon_keys'] = [];
    }

    if ($paid <= 0) {
        $row['payment_status'] = 'unpaid';
    } elseif ($paid < $total) {
        $row['payment_status'] = 'partial';
    } else {
        $row['payment_status'] = 'paid';
    }

    return $row;
}

/**
 * Ambil pemesanan beserta total pembayaran yang sudah dicatat.
 *
 * @param array $filters status, from (YYYY-MM-DD), to (YYYY-MM-DD), limit, customer_id
 */
function bookings_all(array $filters = []): array
{
    $pdo = Database::getConnection();
    if (!$pdo) {
        return [];
    }

    $sql = 'SELECT b.*, c.name AS camera_name, c.slug AS camera_slug,
                   COALESCE(p.paid_total, 0) AS paid_total
            FROM bookings b
            LEFT JOIN cameras c ON c.id = b.camera_id
            LEFT JOIN (
                SELECT booking_id, SUM(amount) AS paid_total
                FROM payments
                GROUP BY booking_id
            ) p ON p.booking_id = b.id';
    $where = [];
    $params = [];

    if (!empty($filters['status']) && isset(BOOKING_STATUSES[$filters['status']])) {
        $where[] = 'b.status = ?';
        $params[] = $filters['status'];
    }
    if (!empty($filters['from'])) {
        $where[] = 'substr(b.created_at, 1, 10) >= ?';
        $params[] = $filters['from'];
    }
    if (!empty($filters['to'])) {
        $where[] = 'substr(b.created_at, 1, 10) <= ?';
        $params[] = $filters['to'];
    }
    if (!empty($filters['customer_id'])) {
        $where[] = 'b.customer_id = ?';
        $params[] = (int)$filters['customer_id'];
    }
    if (!empty($filters['user_id'])) {
        $where[] = 'b.user_id = ?';
        $params[] = (int)$filters['user_id'];
    }

    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY b.created_at DESC, b.id DESC';

    if (!empty($filters['limit'])) {
        $sql .= ' LIMIT ' . (int)$filters['limit'];
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }

    return array_map('booking_hydrate', $rows);
}

/**
 * Ambil satu booking langsung lewat query (bukan menarik semua baris).
 */
function booking_fetch_one(string $where, array $params): ?array
{
    $pdo = Database::getConnection();
    if (!$pdo) {
        return null;
    }

    $sql = 'SELECT b.*, c.name AS camera_name, c.slug AS camera_slug,
                   COALESCE(p.paid_total, 0) AS paid_total
            FROM bookings b
            LEFT JOIN cameras c ON c.id = b.camera_id
            LEFT JOIN (
                SELECT booking_id, SUM(amount) AS paid_total
                FROM payments
                GROUP BY booking_id
            ) p ON p.booking_id = b.id
            WHERE ' . $where . '
            LIMIT 1';

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
    } catch (Throwable $e) {
        return null;
    }

    return $row ? booking_hydrate($row) : null;
}

function booking_find(string $code): ?array
{
    if ($code === '') {
        return null;
    }

    return booking_fetch_one('b.booking_code = ?', [$code]);
}

function booking_by_id(int $id): ?array
{
    if ($id <= 0) {
        return null;
    }

    return booking_fetch_one('b.id = ?', [$id]);
}

/**
 * Cari pelanggan dari nomor WhatsApp, buat kalau belum ada.
 */
function customer_upsert(string $name, string $phone, string $instagram): ?int
{
    $pdo = Database::getConnection();
    if (!$pdo) {
        return null;
    }

    $phone = trim($phone);
    $now = date('Y-m-d H:i:s');

    try {
        $stmt = $pdo->prepare('SELECT id FROM customers WHERE phone = ?');
        $stmt->execute([$phone]);
        $existing = $stmt->fetchColumn();

        if ($existing) {
            $update = $pdo->prepare('UPDATE customers SET name = ?, instagram = ?, updated_at = ? WHERE id = ?');
            $update->execute([$name, $instagram, $now, (int)$existing]);
            return (int)$existing;
        }

        $insert = $pdo->prepare('INSERT INTO customers (name, phone, instagram, created_at, updated_at) VALUES (?, ?, ?, ?, ?)');
        $insert->execute([$name, $phone, $instagram, $now, $now]);
        return (int)$pdo->lastInsertId();
    } catch (Throwable $e) {
        return null;
    }
}

/**
 * Simpan pemesanan baru ke database. Mengembalikan data booking, atau [] kalau gagal.
 */
function bookings_create(array $row): array
{
    $pdo = Database::getConnection();
    if (!$pdo) {
        return [];
    }

    $customer_id = customer_upsert(
        (string)$row['customer_name'],
        (string)$row['customer_phone'],
        (string)($row['customer_ig'] ?? '')
    );

    $code = booking_generate_code();
    $now = date('Y-m-d H:i:s');

    try {
        $stmt = $pdo->prepare('INSERT INTO bookings
            (booking_code, user_id, customer_id, camera_id, customer_name, customer_phone, start_date, end_date, total_days,
             addon_keys, addon_labels, addon_per_day, camera_subtotal, addon_subtotal, discount, total_price,
             pickup_method, payment_method, note, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');

        $stmt->execute([
            $code,
            !empty($row['user_id']) ? (int)$row['user_id'] : null,
            $customer_id,
            (int)($row['camera_id'] ?? 0),
            (string)$row['customer_name'],
            (string)$row['customer_phone'],
            (string)$row['start_date'],
            (string)$row['end_date'],
            (int)$row['total_days'],
            json_encode($row['addon_keys'] ?? [], JSON_UNESCAPED_UNICODE),
            json_encode($row['addon_labels'] ?? [], JSON_UNESCAPED_UNICODE),
            (int)($row['addon_per_day'] ?? 0),
            (int)($row['camera_subtotal'] ?? 0),
            (int)($row['addon_subtotal'] ?? 0),
            (int)($row['discount'] ?? 0),
            (int)($row['total_price'] ?? 0),
            (string)($row['pickup_method'] ?? ''),
            (string)($row['payment_method'] ?? 'cash'),
            (string)($row['note'] ?? ''),
            'pending',
            $now,
            $now,
        ]);
    } catch (Throwable $e) {
        return [];
    }

    $id = (int)$pdo->lastInsertId();

    return booking_by_id($id) ?? [];
}

/**
 * Simpan booking hanya kalau kapasitas unit masih ada.
 *
 * Pengecekan dan penyimpanan berada dalam satu transaksi tulis, supaya dua
 * request yang datang bersamaan tidak lolos dari cek yang sama (double booking).
 *
 * @return array{booking: array, error: string} error: '' | 'conflict' | 'store'
 */
function booking_reserve(array $row, int $stock): array
{
    $pdo = Database::getConnection();
    if (!$pdo) {
        return ['booking' => [], 'error' => 'store'];
    }

    $stock = max(0, $stock);
    if ($stock === 0) {
        return ['booking' => [], 'error' => 'conflict'];
    }

    $driver = (string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    // Kalau pemanggil sudah punya transaksi, jangan tutup transaksi miliknya.
    $owns_transaction = !$pdo->inTransaction();

    $rollback = static function () use ($pdo, $driver, $owns_transaction): void {
        if (!$owns_transaction) {
            return;
        }
        try {
            if ($driver === 'sqlite') {
                $pdo->exec('ROLLBACK');
            } else {
                $pdo->rollBack();
            }
        } catch (Throwable $e) {
            // transaksi sudah berakhir
        }
    };

    try {
        if ($owns_transaction) {
            if ($driver === 'sqlite') {
                // BEGIN IMMEDIATE langsung mengambil kunci tulis SQLite.
                $pdo->exec('BEGIN IMMEDIATE');
            } else {
                $pdo->beginTransaction();
            }
        }

        // MySQL: kunci baris kamera supaya transaksi lain menunggu giliran.
        if ($driver !== 'sqlite') {
            $lock = $pdo->prepare('SELECT id FROM cameras WHERE id = ? FOR UPDATE');
            $lock->execute([(int)($row['camera_id'] ?? 0)]);
        }

        $used = count(bookings_overlapping(
            (int)($row['camera_id'] ?? 0),
            (string)($row['start_date'] ?? ''),
            (string)($row['end_date'] ?? '')
        ));

        if ($used >= $stock) {
            $rollback();
            return ['booking' => [], 'error' => 'conflict'];
        }

        $booking = bookings_create($row);
        if (!$booking) {
            $rollback();
            return ['booking' => [], 'error' => 'store'];
        }

        if ($owns_transaction) {
            if ($driver === 'sqlite') {
                $pdo->exec('COMMIT');
            } else {
                $pdo->commit();
            }
        }

        return ['booking' => $booking, 'error' => ''];
    } catch (Throwable $e) {
        $rollback();
        return ['booking' => [], 'error' => 'store'];
    }
}

function bookings_update_status(int $id, string $status): bool
{
    if (!isset(BOOKING_STATUSES[$status])) {
        return false;
    }

    $pdo = Database::getConnection();
    if (!$pdo) {
        return false;
    }

    try {
        $stmt = $pdo->prepare('UPDATE bookings SET status = ?, updated_at = ? WHERE id = ?');
        return $stmt->execute([$status, date('Y-m-d H:i:s'), $id]);
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * Catat pembayaran yang benar-benar diterima admin (cash atau QRIS).
 */
function bookings_add_payment(int $booking_id, string $method, int $amount, string $note = ''): bool
{
    if (!isset(BOOKING_PAYMENT_METHODS[$method]) || $amount <= 0) {
        return false;
    }

    $pdo = Database::getConnection();
    if (!$pdo) {
        return false;
    }

    $now = date('Y-m-d H:i:s');

    try {
        $stmt = $pdo->prepare('INSERT INTO payments (booking_id, method, amount, note, paid_at, created_at) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$booking_id, $method, $amount, $note, $now, $now]);

        $update = $pdo->prepare('UPDATE bookings SET payment_method = ?, updated_at = ? WHERE id = ?');
        $update->execute([$method, $now, $booking_id]);

        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function booking_payments(int $booking_id): array
{
    $pdo = Database::getConnection();
    if (!$pdo) {
        return [];
    }

    try {
        $stmt = $pdo->prepare('SELECT * FROM payments WHERE booking_id = ? ORDER BY id DESC');
        $stmt->execute([$booking_id]);
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

/**
 * Data pelanggan + rekap pemesanan mereka.
 */
function bookings_customers(): array
{
    $pdo = Database::getConnection();
    if (!$pdo) {
        return [];
    }

    try {
        $rows = $pdo->query("SELECT c.id, c.name, c.phone, c.instagram, c.created_at,
                COUNT(b.id) AS orders,
                COALESCE(SUM(CASE WHEN b.status <> 'cancelled' THEN b.total_price ELSE 0 END), 0) AS total_spend,
                MAX(b.created_at) AS last_order
            FROM customers c
            LEFT JOIN bookings b ON b.customer_id = c.id
            GROUP BY c.id, c.name, c.phone, c.instagram, c.created_at
            ORDER BY last_order DESC")->fetchAll();
    } catch (Throwable $e) {
        return [];
    }

    $codes = [];
    try {
        foreach ($pdo->query('SELECT customer_id, booking_code FROM bookings ORDER BY id DESC')->fetchAll() as $row) {
            $codes[(int)$row['customer_id']][] = (string)$row['booking_code'];
        }
    } catch (Throwable $e) {
        $codes = [];
    }

    $customers = [];
    foreach ($rows as $row) {
        $customers[] = [
            'id'          => (int)$row['id'],
            'name'        => (string)$row['name'],
            'phone'       => (string)$row['phone'],
            'instagram'   => (string)($row['instagram'] ?? ''),
            'orders'      => (int)$row['orders'],
            'total_spend' => (int)$row['total_spend'],
            'last_order'  => (string)($row['last_order'] ?? ''),
            'codes'       => $codes[(int)$row['id']] ?? [],
        ];
    }

    return $customers;
}

function bookings_stats(): array
{
    $stats = [
        'total'        => 0,
        'pending'      => 0,
        'confirmed'    => 0,
        'active'       => 0,
        'completed'    => 0,
        'cancelled'    => 0,
        'booked_value' => 0,
        'payments'     => 0,
        'unpaid'       => 0,
        'partial'      => 0,
        'paid'         => 0,
        'customers'    => 0,
    ];

    foreach (bookings_all() as $row) {
        $stats['total']++;
        $status = (string)($row['status'] ?? 'pending');
        if (isset($stats[$status])) {
            $stats[$status]++;
        }
        if ($status !== 'cancelled') {
            $stats['booked_value'] += (int)$row['total_price'];
            $stats[$row['payment_status']]++;
        }
        $stats['payments'] += (int)$row['paid_total'];
    }

    $stats['customers'] = count(bookings_customers());

    return $stats;
}

/**
 * Rangkaian data harian untuk grafik.
 *
 * @param int $days 0 berarti "semua riwayat"
 */
function bookings_series(int $days): array
{
    $rows = bookings_all();

    if ($days <= 0) {
        $earliest = null;
        foreach ($rows as $row) {
            $created = substr((string)($row['created_at'] ?? ''), 0, 10);
            if ($created !== '' && ($earliest === null || $created < $earliest)) {
                $earliest = $created;
            }
        }
        $start = $earliest ?? date('Y-m-d');
        $span = (int)(new DateTimeImmutable(substr($start, 0, 10)))
            ->diff(new DateTimeImmutable('today'))->days + 1;
        $days = max(1, min(365, $span));
    }

    $series = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $key = date('Y-m-d', strtotime("-$i days"));
        $series[$key] = [
            'label' => date('d/m', strtotime($key)),
            'count' => 0,
            'value' => 0,
        ];
    }

    foreach ($rows as $row) {
        $key = substr((string)($row['created_at'] ?? ''), 0, 10);
        if (isset($series[$key])) {
            $series[$key]['count']++;
            $series[$key]['value'] += (int)$row['total_price'];
        }
    }

    return array_values($series);
}

function booking_status_label(string $status): string
{
    return BOOKING_STATUSES[$status] ?? $status;
}

function booking_status_classes(string $status): string
{
    switch ($status) {
        case 'confirmed':
            return 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-500/30';
        case 'active':
            return 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-950/40 dark:text-purple-300 dark:border-purple-500/30';
        case 'completed':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-500/30';
        case 'cancelled':
            return 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800/60 dark:text-slate-300 dark:border-white/10';
        default:
            return 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-500/30';
    }
}

function booking_payment_label(string $method): string
{
    return BOOKING_PAYMENT_METHODS[$method] ?? $method;
}

function booking_payment_status_label(string $status): string
{
    switch ($status) {
        case 'paid':
            return 'Lunas';
        case 'partial':
            return 'DP / Sebagian';
        default:
            return 'Belum Dibayar';
    }
}

function booking_payment_status_classes(string $status): string
{
    switch ($status) {
        case 'paid':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-500/30';
        case 'partial':
            return 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-500/30';
        default:
            return 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800/60 dark:text-slate-300 dark:border-white/10';
    }
}
