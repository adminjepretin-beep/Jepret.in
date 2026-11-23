<?php
// proses-booking.php - Simpan pemesanan dari form detail kamera
require_once __DIR__ . '/data/cameras.php';
require_once __DIR__ . '/includes/bookings.php';
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: katalog.php');
    exit;
}

$camera_id = (int)($_POST['camera_id'] ?? 0);
$camera = getCameraById($camera_id);

if (!$camera) {
    header('Location: katalog.php');
    exit;
}

// Form booking wajib bawa token CSRF dari detail.php (form lain juga begitu).
if (!admin_verify_csrf($_POST['csrf'] ?? null)) {
    header('Location: detail.php?id=' . $camera_id . '&booking_error=6#booking-form');
    exit;
}

// Booking wajib pakai akun pengguna, supaya riwayatnya tersimpan di dashboard.
$user = auth_current_user();
if (!$user) {
    header('Location: login.php?next=' . urlencode('detail.php?id=' . $camera_id . '#booking-form'));
    exit;
}

function booking_parse_date(string $value): ?DateTimeImmutable
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    if (!$date || $date->format('Y-m-d') !== $value) {
        return null;
    }
    return $date;
}

$start_date = trim((string)($_POST['start_date'] ?? ''));
$end_date   = trim((string)($_POST['end_date'] ?? ''));
$name       = (string)$user['name'];
$phone      = (string)$user['phone'];
$instagram  = trim((string)($_POST['customer_ig'] ?? ''));
$purpose    = trim((string)($_POST['purpose'] ?? ''));
$pickup     = trim((string)($_POST['pickup_method'] ?? ''));
$payment    = trim((string)($_POST['payment_method'] ?? 'cash'));
$note       = trim((string)($_POST['note'] ?? ''));

$start_obj = booking_parse_date($start_date);
$end_obj   = booking_parse_date($end_date);
$today     = new DateTimeImmutable('today');

$is_valid = true;

if (!$start_obj || !$end_obj) {
    $is_valid = false;
} elseif ($start_obj < $today || $end_obj <= $start_obj) {
    $is_valid = false;
}

if (mb_strlen($name) < 2 || preg_replace('/\D+/', '', $phone) === '' || strlen(preg_replace('/\D+/', '', $phone)) < 8) {
    $is_valid = false;
}

if (!isset(BOOKING_PAYMENT_METHODS[$payment])) {
    $is_valid = false;
}

if ($pickup === '') {
    $pickup = 'Ambil Sendiri di Studio Surabaya (Gratis)';
}

if (!$is_valid) {
    header('Location: detail.php?id=' . $camera_id . '&booking_error=1#booking-form');
    exit;
}

$days = (int)$start_obj->diff($end_obj)->days;
$addon_keys = array_values(array_intersect(
    array_keys(booking_addon_options()),
    array_map('strval', (array)($_POST['addons'] ?? []))
));

$calculation = booking_calculate((int)$camera['daily_price'], $days, $addon_keys);

// Unit yang dinonaktifkan admin tidak boleh dipesan, walau stoknya masih ada.
if (empty($camera['is_available'])) {
    header('Location: detail.php?id=' . $camera_id . '&booking_error=5#booking-form');
    exit;
}

// Cegah double booking: unit harus benar-benar tersedia di database, bukan cuma di browser.
// Stok 0 = unit tidak bisa dibooking; stok N = maksimal N booking di tanggal yang sama.
$stock = (int)($camera['stock'] ?? 1);

if ($stock <= 0) {
    header('Location: detail.php?id=' . $camera_id . '&booking_error=4#booking-form');
    exit;
}

$reservation = booking_reserve([
    'user_id'         => (int)$user['id'],
    'camera_id'       => (int)$camera['id'],
    'camera_name'     => (string)$camera['name'],
    'camera_slug'     => (string)($camera['slug'] ?? ''),
    'daily_price'     => (int)$camera['daily_price'],
    'customer_name'   => $name,
    'customer_phone'  => $phone,
    'customer_ig'     => $instagram,
    'purpose'         => $purpose,
    'start_date'      => $start_date,
    'end_date'        => $end_date,
    'total_days'      => $calculation['days'],
    'addon_keys'      => $calculation['addon_keys'],
    'addon_labels'    => $calculation['addon_labels'],
    'addon_per_day'   => $calculation['addon_per_day'],
    'camera_subtotal' => $calculation['camera_subtotal'],
    'addon_subtotal'  => $calculation['addon_subtotal'],
    'discount'        => $calculation['discount'],
    'total_price'     => $calculation['total'],
    'pickup_method'   => $pickup,
    'payment_method'  => $payment,
    'note'            => $note,
], $stock);

// Tanggal sudah diambil orang lain di antara cek awal dan penyimpanan.
if (($reservation['error'] ?? '') === 'conflict') {
    header('Location: detail.php?id=' . $camera_id . '&booking_error=3#booking-form');
    exit;
}

$booking = $reservation['booking'] ?? [];

// Penyimpanan gagal (folder data tidak bisa ditulis)
if (!$booking) {
    header('Location: detail.php?id=' . $camera_id . '&booking_error=2#booking-form');
    exit;
}

header('Location: booking-sukses.php?kode=' . urlencode($booking['booking_code']));
exit;
