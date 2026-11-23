<?php
// detail.php - Halaman Detail & Booking Kamera Jepret.in Surabaya (Default Light: White + Orange, Dark: Black + Purple)
require_once __DIR__ . '/data/cameras.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/bookings.php';

$camera_id = isset($_GET['id']) ? intval($_GET['id']) : 1;
$camera = getCameraById($camera_id);

if (!$camera) {
    header('Location: katalog.php');
    exit;
}

$page_title = $camera['name'] . ' - Sewa Kamera di Jepret.in Surabaya';
$current_page = 'katalog';
$wa_number = '6283832104793';
$current_user = auth_current_user();

// Ketersediaan asli dari database (bukan simulasi): dipakai kalender & validasi jadwal.
$calendar_first = new DateTimeImmutable('first day of this month');
$calendar_last  = new DateTimeImmutable('last day of next month');
$calendar_from  = $calendar_first->format('Y-m-d');
$calendar_to    = $calendar_last->format('Y-m-d');

$busy_ranges = bookings_busy_ranges((int)$camera['id'], $calendar_from, $calendar_to);
$busy_usage   = bookings_day_usage((int)$camera['id'], $calendar_from, $calendar_to);
$camera_stock = max(0, (int)($camera['stock'] ?? 1));
$camera_available = !empty($camera['is_available']);
$units_left_default = booking_units_left((int)$camera['id'], $camera_stock, date('Y-m-d'), date('Y-m-d', strtotime('+1 day')));
$today_key   = date('Y-m-d');

$calendar_weekdays = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
$calendar_month_names = [
    1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
];
$calendar_months = [];
for ($offset = 0; $offset < 2; $offset++) {
    $month = $calendar_first->modify('+' . $offset . ' month');
    $cells = [];
    for ($blank = 0; $blank < (int)$month->format('N') - 1; $blank++) {
        $cells[] = ['blank' => true];
    }
    for ($day = 1; $day <= (int)$month->format('t'); $day++) {
        $key = $month->setDate((int)$month->format('Y'), (int)$month->format('n'), $day)->format('Y-m-d');
        $cells[] = [
            'blank' => false,
            'day'   => $day,
            'key'   => $key,
            'used'  => (int)($busy_usage[$key] ?? 0),
            'state' => $key < $today_key
                ? 'past'
                : ((int)($busy_usage[$key] ?? 0) >= max(1, $camera_stock) ? 'booked' : ((int)($busy_usage[$key] ?? 0) > 0 ? 'partial' : 'free')),
            'today' => $key === $today_key,
        ];
    }
    $calendar_months[] = [
        'label' => $calendar_month_names[(int)$month->format('n')] . ' ' . $month->format('Y'),
        'cells' => $cells,
    ];
}



include_once __DIR__ . '/includes/header.php';

// Related cameras
$related_cameras = array_filter($cameras, fn($c) => $c['id'] != $camera['id'] && !empty($c['is_available']));
$related_cameras = array_slice($related_cameras, 0, 3);
?>

<!-- BREADCRUMBS -->
<section class="pt-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
    <nav class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
        <a href="index.php" class="hover:text-purple-600 dark:hover:text-purple-400 transition">Beranda</a>
        <i class="ri-arrow-right-s-line text-slate-400"></i>
        <a href="katalog.php" class="hover:text-purple-600 dark:hover:text-purple-400 transition">Katalog Kamera</a>
        <i class="ri-arrow-right-s-line text-slate-400"></i>
        <span class="text-slate-900 dark:text-white font-medium truncate"><?= htmlspecialchars($camera['name']) ?></span>
    </nav>
</section>

<?php if (!$camera_available): ?>
<section class="px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
    <div class="rounded-2xl border border-rose-200 dark:border-rose-500/30 bg-rose-50 dark:bg-rose-950/30 px-4 py-3 flex items-start gap-2.5">
        <i class="ri-eye-off-line text-rose-600 dark:text-rose-400 text-sm mt-0.5 shrink-0"></i>
        <p class="text-[11px] sm:text-xs text-rose-700 dark:text-rose-300 leading-relaxed">
            <span class="font-bold uppercase tracking-wider text-[10px] mr-1">Tidak Tersedia</span>
            Unit ini sedang dinonaktifkan admin, jadi belum bisa dipesan. Lihat kamera lain yang siap sewa di
            <a href="katalog.php" class="font-bold underline">katalog</a>.
        </p>
    </div>
</section>
<?php endif; ?>

<!-- MAIN CAMERA DETAIL SECTION -->
<section class="py-8 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- LEFT COLUMN: FOTO, DESKRIPSI, SPEK, KELENGKAPAN (7 COLS) -->
        <div class="lg:col-span-7 space-y-6">
            
            <!-- Main Hero Image Glass Card -->
            <div class="glass-panel rounded-3xl p-4 sm:p-6 relative overflow-hidden" data-aos="fade-right">
                <div class="relative aspect-[16/10] rounded-2xl overflow-hidden bg-slate-100 dark:bg-black border border-slate-200 dark:border-white/5">
                    <img src="<?= htmlspecialchars($camera['image']) ?>" alt="<?= htmlspecialchars($camera['name']) ?>" class="w-full h-full object-cover">
                    
                    <!-- Badges -->
                    <div class="absolute top-4 left-4 flex gap-2">
                        <span class="px-3 py-1 rounded-md text-xs font-bold uppercase tracking-wider bg-white/90 text-slate-900 border border-slate-200 dark:bg-[#0e131d]/90 dark:text-white dark:border-white/15 shadow-sm">
                            <?= htmlspecialchars($camera['brand']) ?>
                        </span>
                        <span class="px-3 py-1 rounded-md text-xs font-bold uppercase tracking-wider bg-purple-100 text-purple-700 border border-purple-200 dark:bg-purple-950/60 dark:text-purple-300 dark:border-purple-500/30">
                            <?= htmlspecialchars($camera['tag']) ?>
                        </span>
                    </div>

                    <div class="absolute bottom-4 left-4 right-4 flex items-center justify-between text-white">
                        <div class="flex items-center gap-1.5 text-xs bg-white/95 text-slate-900 border border-slate-200 dark:bg-[#0e131d]/90 dark:text-white dark:border-white/15 px-3 py-1.5 rounded-lg shadow-sm">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span>Sensor 100% Bersih Terkalibrasi</span>
                        </div>
                    </div>
                </div>

                <!-- Title & Overview -->
                <div class="pt-6 space-y-3">
                    <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 dark:text-white font-heading">
                        <?= htmlspecialchars($camera['name']) ?>
                    </h1>
                    <p class="text-slate-600 dark:text-slate-300 text-xs sm:text-sm leading-relaxed">
                        <?= htmlspecialchars($camera['description']) ?>
                    </p>
                </div>
            </div>

            <!-- BENTO GRID: SPESIFIKASI TEKNIS LENGKAP -->
            <div class="space-y-4" data-aos="fade-up">
                <h3 class="text-base font-bold text-slate-900 dark:text-white font-heading flex items-center gap-2">
                    <i class="ri-cpu-line text-purple-600 dark:text-purple-400"></i>
                    <span>Spesifikasi Utama Kamera Ini</span>
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="glass-card rounded-2xl p-4 flex items-start gap-3.5">
                        <div class="w-9 h-9 rounded-xl bg-purple-100 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center shrink-0">
                            <i class="ri-focus-2-line text-lg"></i>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Sensor & Resolusi</span>
                            <div class="text-xs font-bold text-slate-900 dark:text-white font-heading mt-0.5"><?= htmlspecialchars($camera['sensor']) ?></div>
                        </div>
                    </div>

                    <div class="glass-card rounded-2xl p-4 flex items-start gap-3.5">
                        <div class="w-9 h-9 rounded-xl bg-purple-100 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center shrink-0">
                            <i class="ri-video-line text-lg"></i>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Kualitas Video</span>
                            <div class="text-xs font-bold text-slate-900 dark:text-white font-heading mt-0.5"><?= htmlspecialchars($camera['video']) ?></div>
                        </div>
                    </div>

                    <div class="glass-card rounded-2xl p-4 flex items-start gap-3.5">
                        <div class="w-9 h-9 rounded-xl bg-purple-100 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center shrink-0">
                            <i class="ri-sun-line text-lg"></i>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Rentang ISO</span>
                            <div class="text-xs font-bold text-slate-900 dark:text-white font-heading mt-0.5"><?= htmlspecialchars($camera['iso']) ?></div>
                        </div>
                    </div>

                    <div class="glass-card rounded-2xl p-4 flex items-start gap-3.5">
                        <div class="w-9 h-9 rounded-xl bg-purple-100 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center shrink-0">
                            <i class="ri-disc-line text-lg"></i>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Tipe Dudukan Lensa</span>
                            <div class="text-xs font-bold text-slate-900 dark:text-white font-heading mt-0.5"><?= htmlspecialchars($camera['mount']) ?></div>
                        </div>
                    </div>

                    <div class="glass-card rounded-2xl p-4 flex items-start gap-3.5">
                        <div class="w-9 h-9 rounded-xl bg-purple-100 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center shrink-0">
                            <i class="ri-pulse-line text-lg"></i>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Peredam Goyang (IBIS)</span>
                            <div class="text-xs font-bold text-slate-900 dark:text-white font-heading mt-0.5"><?= htmlspecialchars($camera['stabilization']) ?></div>
                        </div>
                    </div>

                    <div class="glass-card rounded-2xl p-4 flex items-start gap-3.5">
                        <div class="w-9 h-9 rounded-xl bg-purple-100 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center shrink-0">
                            <i class="ri-scales-3-line text-lg"></i>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Bobot Bodi Kamera</span>
                            <div class="text-xs font-bold text-slate-900 dark:text-white font-heading mt-0.5"><?= htmlspecialchars($camera['weight']) ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BENTO: WHAT'S IN THE BOX -->
            <div class="glass-panel rounded-3xl p-6 space-y-4" data-aos="fade-up">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white font-heading flex items-center gap-2">
                        <i class="ri-inbox-archive-line text-purple-600 dark:text-purple-400"></i>
                        <span>Kelengkapan yang Kamu Dapet</span>
                    </h3>
                    <span class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold">Tinggal Bawa Hunting</span>
                </div>
                
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Kamu gak perlu sewa baterai atau memori terpisah, semua paket kamera di Jepret.in udah komplit:
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                    <?php foreach ($camera['includes'] as $inc): ?>
                        <div class="flex items-center gap-3 p-3 rounded-xl bg-slate-50 dark:bg-[#141b29] border border-slate-200 dark:border-white/5">
                            <i class="ri-checkbox-circle-line text-emerald-500 text-sm shrink-0"></i>
                            <span class="text-xs font-semibold text-slate-700 dark:text-slate-200"><?= htmlspecialchars($inc) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>

        <!-- RIGHT COLUMN: FORM BOOKING LENGKAP & CEK TANGGAL SEWA (5 COLS) -->
        <div class="lg:col-span-5 lg:sticky lg:top-24 space-y-6" id="booking-form" data-aos="fade-left">
            <div class="glass-panel rounded-3xl p-6 sm:p-7 shadow-lg space-y-5">
                
                <!-- Price Header -->
                <div class="flex items-baseline justify-between border-b border-slate-200 dark:border-white/10 pb-4">
                    <div>
                        <span class="text-[11px] uppercase tracking-wider text-slate-500 dark:text-slate-400 font-bold">Harga Sewa</span>
                        <div class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white font-heading mt-0.5">
                            <?= formatRupiah($camera['daily_price']) ?>
                            <span class="text-xs text-slate-500 dark:text-slate-400 font-normal">/ 24 jam</span>
                        </div>
                    </div>
                    <div class="text-right">
                        <?php if ($camera_available): ?>
                        <span id="availabilityBadge" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-semibold bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/30">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            <?= $camera_stock > 1 ? 'Sisa ' . $units_left_default . ' Unit' : 'Unit Siap Sewa' ?>
                        </span>
                        <?php else: ?>
                        <span id="availabilityBadge" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-semibold bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400 border border-rose-200 dark:border-rose-500/30">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                            Tidak Tersedia
                        </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- FORM BOOKING LENGKAP -->
                <?php if (!$camera_available): ?>
                    <div class="rounded-2xl border border-rose-200 dark:border-rose-500/30 bg-rose-50 dark:bg-rose-950/30 p-5 text-center space-y-3">
                        <div class="w-10 h-10 mx-auto rounded-xl bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                            <i class="ri-tools-line text-lg"></i>
                        </div>
                        <h3 class="text-sm font-bold font-heading text-slate-900 dark:text-white">Unit sedang tidak disewakan</h3>
                        <p class="text-[11px] text-slate-600 dark:text-slate-400 leading-relaxed">
                            Admin menonaktifkan unit ini untuk sementara. Pilih kamera lain yang siap sewa ya.
                        </p>
                        <a href="katalog.php" class="btn-theme-primary inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-[11px] font-bold">
                            <i class="ri-camera-lens-line"></i>
                            <span>Lihat kamera lain</span>
                        </a>
                    </div>
                <?php elseif (!$current_user): ?>
                    <div class="rounded-2xl border border-slate-200 dark:border-white/10 bg-slate-50 dark:bg-[#141b29] p-5 text-center space-y-3">
                        <div class="w-10 h-10 mx-auto rounded-xl bg-purple-100 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                            <i class="ri-lock-2-line text-lg"></i>
                        </div>
                        <h3 class="text-sm font-bold font-heading text-slate-900 dark:text-white">Masuk dulu buat booking</h3>
                        <p class="text-[11px] text-slate-600 dark:text-slate-400 leading-relaxed">
                            Booking sekarang pakai akun, jadi riwayat sewa dan status pembayaran kamu tersimpan rapi di dashboard akun.
                        </p>
                        <div class="flex items-center justify-center gap-2 pt-1">
                            <a href="login.php?next=<?= urlencode('detail.php?id=' . $camera['id'] . '#booking-form') ?>"
                               class="btn-theme-primary inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-[11px] font-bold">
                                <i class="ri-login-box-line"></i>
                                <span>Masuk</span>
                            </a>
                            <a href="daftar.php?next=<?= urlencode('detail.php?id=' . $camera['id'] . '#booking-form') ?>"
                               class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-[11px] font-bold border border-slate-200 dark:border-white/10 text-slate-600 dark:text-slate-300 hover:bg-white dark:hover:bg-white/5 transition">
                                <i class="ri-user-add-line"></i>
                                <span>Daftar</span>
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                <?php
                $booking_error_texts = [
                    1 => 'Data booking belum lengkap atau tanggalnya belum valid. Cek lagi ya.',
                    2 => 'Maaf, sistem belum bisa menyimpan pesanan. Kabari admin lewat WhatsApp ya.',
                    3 => 'Tanggal yang kamu pilih baru saja dibooking orang lain. Pilih tanggal lain ya.',
                    4 => 'Stok unit ini sedang habis, jadi belum bisa dibooking. Coba tanggal lain atau kamera lain ya.',
                    5 => 'Unit ini sedang tidak disewakan oleh admin, jadi belum bisa dipesan. Coba kamera lain ya.',
                    6 => 'Sesi form sudah kedaluwarsa. Muat ulang halaman, lalu isi formnya lagi ya.',
                ];
                $booking_error_code = (int)($_GET['booking_error'] ?? 0);
                $booking_error_text = $booking_error_texts[$booking_error_code] ?? $booking_error_texts[1];
                ?>
                <?php if (isset($_GET['booking_error'])): ?>
                    <div class="mb-4 flex items-start gap-2 rounded-xl border border-red-200 dark:border-red-500/30 bg-red-50 dark:bg-red-950/30 px-3 py-2.5 text-[11px] text-red-700 dark:text-red-300">
                        <i class="ri-error-warning-line text-sm shrink-0 mt-0.5"></i>
                        <span><?= htmlspecialchars($booking_error_text) ?></span>
                    </div>
                <?php endif; ?>

                <form id="rentalBookingForm" action="proses-booking.php" method="POST" class="space-y-4">
                    <input type="hidden" name="camera_id" value="<?= (int)$camera['id'] ?>">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(admin_csrf_token()) ?>">
                    
                    <!-- 1. PILIH TANGGAL -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">1. Pilih Jadwal Sewa</label>
                            <span class="text-[10px] text-purple-600 dark:text-purple-400 font-semibold">Wajib pilih tanggal</span>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <span class="text-[10px] text-slate-500 dark:text-slate-400 block mb-1">Mulai Sewa:</span>
                                <input type="date" id="startDate" name="start_date" required class="glass-input w-full rounded-xl px-3 py-2 text-xs" value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>">
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-500 dark:text-slate-400 block mb-1">Selesai Sewa:</span>
                                <input type="date" id="endDate" name="end_date" required class="glass-input w-full rounded-xl px-3 py-2 text-xs" value="<?= date('Y-m-d', strtotime('+1 day')) ?>" min="<?= date('Y-m-d') ?>">
                            </div>
                        </div>

                        <!-- Slot Availability Alert -->
                        <div id="availabilityAlert" class="mt-2.5 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 dark:bg-emerald-950/40 dark:border-emerald-500/30 dark:text-emerald-300 text-xs flex items-center justify-between">
                            <span class="flex items-center gap-2">
                                <i class="ri-checkbox-circle-line text-sm"></i>
                                <span id="availabilityText">Unit Tersedia! Siap disewa di Surabaya.</span>
                            </span>
                            <span class="flex items-center gap-1.5">
                                <span id="stockInfo" class="text-[10px] font-semibold text-slate-700 dark:text-slate-200 whitespace-nowrap"><?= $camera_available ? 'Sisa ' . $units_left_default . ' unit' : 'Tidak disewakan' ?></span>
                                <span id="durationBadge" class="font-bold text-slate-900 dark:text-white bg-slate-200/80 dark:bg-[#141b29] px-2 py-0.5 rounded text-[11px]">1 Hari</span>
                            </span>
                        </div>
                    </div>

                    <!-- KALENDER KETERSEDIAAN: tanggal yang sudah dibooking penyewa lain -->
                    <div class="rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-[#0b0e15] p-3.5">
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-2.5">
                            <span class="text-[11px] font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                                <i class="ri-calendar-2-line text-purple-600 dark:text-purple-400"></i>
                                <span>Kalender Ketersediaan</span>
                            </span>
                            <span class="flex items-center gap-3 text-[10px] text-slate-500 dark:text-slate-400">
                                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-sm bg-emerald-500"></span>Kosong</span>
                                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-sm bg-rose-400"></span>Penuh</span>
                                <?php if ($camera_stock > 1): ?>
                                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-sm bg-amber-400"></span>Sebagian</span>
                                <?php endif; ?>
                            </span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <?php foreach ($calendar_months as $month): ?>
                                <div>
                                    <div class="text-[10px] font-bold text-slate-700 dark:text-slate-300 text-center mb-1.5"><?= $month['label'] ?></div>
                                    <div class="grid grid-cols-7 gap-1 mb-1 text-[9px] font-bold text-slate-400 uppercase text-center">
                                        <?php foreach ($calendar_weekdays as $weekday): ?>
                                            <span><?= $weekday ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="grid grid-cols-7 gap-1">
                                        <?php foreach ($month['cells'] as $cell): ?>
                                            <?php if (!empty($cell['blank'])): ?>
                                                <span></span>
                                            <?php elseif ($cell['state'] === 'booked'): ?>
                                                <span title="Sudah dibooking" class="flex items-center justify-center h-6 rounded-md bg-rose-100 text-rose-500 dark:bg-rose-950/50 dark:text-rose-400 line-through font-semibold">
                                                    <?= $cell['day'] ?>
                                                </span>
                                            <?php elseif ($cell['state'] === 'partial'): ?>
                                                <button type="button" data-pick-date="<?= $cell['key'] ?>" title="Sisa <?= max(0, $camera_stock - (int)$cell['used']) ?> unit di tanggal ini"
                                                        class="flex items-center justify-center h-6 rounded-md bg-amber-50 text-amber-700 border border-amber-200 font-semibold transition hover:bg-purple-600 hover:text-white hover:border-purple-600 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-500/30 <?= !empty($cell['today']) ? 'ring-2 ring-purple-500' : '' ?>">
                                                    <?= $cell['day'] ?>
                                                </button>
                                            <?php elseif ($cell['state'] === 'past'): ?>
                                                <span class="flex items-center justify-center h-6 rounded-md text-slate-300 dark:text-slate-600">
                                                    <?= $cell['day'] ?>
                                                </span>
                                            <?php else: ?>
                                                <button type="button" data-pick-date="<?= $cell['key'] ?>" title="Pilih <?= $cell['key'] ?>"
                                                        class="flex items-center justify-center h-6 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200 font-semibold transition hover:bg-purple-600 hover:text-white hover:border-purple-600 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-500/30 <?= !empty($cell['today']) ? 'ring-2 ring-purple-500' : '' ?>">
                                                    <?= $cell['day'] ?>
                                                </button>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-2.5 leading-snug">
                            Klik tanggal kosong buat isi jadwal otomatis. Tanggal kembali unit dihitung sudah bebas buat penyewa berikutnya.
                        </p>
                    </div>

                    <!-- 2. DATA IDENTITAS PENYEWA -->
                    <div class="pt-2 border-t border-slate-200 dark:border-white/10 space-y-3">
                        <label class="block text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">2. Data Diri Penyewa</label>
                        
                        <div>
                            <span class="text-[11px] text-slate-600 dark:text-slate-300 block mb-1">Nama Lengkap:</span>
                            <input type="text" id="custName" name="customer_name" required readonly value="<?= htmlspecialchars((string)$current_user['name']) ?>" class="glass-input w-full rounded-xl px-3.5 py-2 text-xs bg-slate-100 dark:bg-[#141b29] cursor-not-allowed">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <span class="text-[11px] text-slate-600 dark:text-slate-300 block mb-1">Nomor WhatsApp:</span>
                                <input type="tel" id="custPhone" name="customer_phone" required readonly value="<?= htmlspecialchars((string)$current_user['phone']) ?>" class="glass-input w-full rounded-xl px-3.5 py-2 text-xs bg-slate-100 dark:bg-[#141b29] cursor-not-allowed">
                            </div>
                            <div>
                                <span class="text-[11px] text-slate-600 dark:text-slate-300 block mb-1">Akun Instagram / Medsos:</span>
                                <input type="text" id="custIg" name="customer_ig" required placeholder="@username_kamu" class="glass-input w-full rounded-xl px-3.5 py-2 text-xs">
                            </div>
                        </div>

                        <div>
                            <span class="text-[11px] text-slate-600 dark:text-slate-300 block mb-1">Rencana Keperluan Sewa:</span>
                            <select id="custPurpose" name="purpose" class="glass-input w-full rounded-xl px-3.5 py-2 text-xs">
                                <option value="Bikin Konten Medsos / TikTok / Reels">Bikin Konten Medsos / TikTok / Reels</option>
                                <option value="Foto / Video Wedding & Prewedding">Foto / Video Wedding & Prewedding</option>
                                <option value="Liputan Event / Wisuda">Liputan Event / Wisuda</option>
                                <option value="Tugas Kuliah / Project Film Indie">Tugas Kuliah / Project Film Indie</option>
                                <option value="Liburan / Traveling Luar Kota">Liburan / Traveling Luar Kota</option>
                                <option value="Syuting Komersial / Iklan Brand">Syuting Komersial / Iklan Brand</option>
                            </select>
                        </div>
                    </div>

                    <!-- 3. ADDONS & PENGAMBILAN -->
                    <div class="pt-2 border-t border-slate-200 dark:border-white/10 space-y-3">
                        <label class="block text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">3. Opsi Tambahan & Pengambilan</label>
                        
                        <div class="space-y-2">
                            <label class="flex items-center justify-between p-2 rounded-xl bg-slate-50 dark:bg-[#141b29] border border-slate-200 dark:border-white/5 cursor-pointer text-xs">
                                <span class="flex items-center gap-2">
                                    <input type="checkbox" id="addonLens" name="addons[]" value="lensa" data-price="150000" class="rounded text-purple-600 dark:text-purple-600 focus:ring-0">
                                    <span class="text-slate-700 dark:text-slate-200">+ Lensa Prime 35mm/50mm</span>
                                </span>
                                <span class="text-slate-500 dark:text-slate-300 text-[11px]">+Rp 150rb/hr</span>
                            </label>

                            <label class="flex items-center justify-between p-2 rounded-xl bg-slate-50 dark:bg-[#141b29] border border-slate-200 dark:border-white/5 cursor-pointer text-xs">
                                <span class="flex items-center gap-2">
                                    <input type="checkbox" id="addonGimbal" name="addons[]" value="gimbal" data-price="100000" class="rounded text-purple-600 dark:text-purple-600 focus:ring-0">
                                    <span class="text-slate-700 dark:text-slate-200">+ DJI RS 3 Pro Gimbal</span>
                                </span>
                                <span class="text-slate-500 dark:text-slate-300 text-[11px]">+Rp 100rb/hr</span>
                            </label>

                            <label class="flex items-center justify-between p-2 rounded-xl bg-slate-50 dark:bg-[#141b29] border border-slate-200 dark:border-white/5 cursor-pointer text-xs">
                                <span class="flex items-center gap-2">
                                    <input type="checkbox" id="addonMic" name="addons[]" value="mic" data-price="75000" class="rounded text-purple-600 dark:text-purple-600 focus:ring-0">
                                    <span class="text-slate-700 dark:text-slate-200">+ Wireless Mic DJI Mic 2</span>
                                </span>
                                <span class="text-slate-500 dark:text-slate-300 text-[11px]">+Rp 75rb/hr</span>
                            </label>
                        </div>

                        <div>
                            <span class="text-[11px] text-slate-600 dark:text-slate-300 block mb-1">Metode Pengambilan di Surabaya:</span>
                            <select id="pickupMethod" name="pickup_method" class="glass-input w-full rounded-xl px-3 py-2 text-xs">
                                <option value="Ambil Sendiri di Studio Surabaya (Gratis)">Ambil Sendiri di Studio Surabaya (Gratis)</option>
                                <option value="Kirim via Kurir Instan Gojek / Grab (Surabaya & Sidoarjo)">Kirim via Kurir Instan Gojek / Grab (Surabaya & Sidoarjo)</option>
                                <option value="Antar Jemput Dedicated Crew Jepret.in">Antar Jemput Crew Jepret.in Surabaya</option>
                            </select>
                        </div>
                    </div>

                    <!-- 4. METODE PEMBAYARAN -->
                    <div class="pt-2 border-t border-slate-200 dark:border-white/10 space-y-3">
                        <label class="block text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">4. Metode Pembayaran</label>

                        <div class="grid grid-cols-2 gap-2">
                            <label class="flex items-start gap-2 p-2.5 rounded-xl bg-slate-50 dark:bg-[#141b29] border border-slate-200 dark:border-white/5 cursor-pointer text-xs hover:border-purple-300 dark:hover:border-purple-500/40 transition">
                                <input type="radio" name="payment_method" value="cash" checked class="mt-0.5 text-purple-600 focus:ring-0">
                                <span>
                                    <span class="block font-semibold text-slate-800 dark:text-slate-200">Cash</span>
                                    <span class="block text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">Bayar di studio</span>
                                </span>
                            </label>

                            <label class="flex items-start gap-2 p-2.5 rounded-xl bg-slate-50 dark:bg-[#141b29] border border-slate-200 dark:border-white/5 cursor-pointer text-xs hover:border-purple-300 dark:hover:border-purple-500/40 transition">
                                <input type="radio" name="payment_method" value="qris" class="mt-0.5 text-purple-600 focus:ring-0">
                                <span>
                                    <span class="block font-semibold text-slate-800 dark:text-slate-200">QRIS</span>
                                    <span class="block text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">Scan via WhatsApp</span>
                                </span>
                            </label>
                        </div>

                        <div>
                            <span class="text-[11px] text-slate-600 dark:text-slate-300 block mb-1">Catatan Tambahan (opsional):</span>
                            <textarea id="bookingNote" name="note" rows="2" placeholder="Contoh: butuh lensa tambahan atau jam ambil unit" class="glass-input w-full rounded-xl px-3.5 py-2 text-xs resize-none"></textarea>
                        </div>
                    </div>

                    <!-- 5. TOTAL BIAYA & SUBMIT BOOKING -->
                    <div class="border-t border-slate-200 dark:border-white/10 pt-3 space-y-2">
                        <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                            <span>Sewa Kamera:</span>
                            <span id="summaryCamPrice" class="text-slate-800 dark:text-slate-200 font-semibold">Rp 0</span>
                        </div>
                        <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                            <span>Add-on Tambahan:</span>
                            <span id="summaryAddonPrice" class="text-slate-800 dark:text-slate-200 font-semibold">Rp 0</span>
                        </div>
                        <div id="discountRow" class="flex items-center justify-between text-xs text-emerald-600 dark:text-emerald-400 font-semibold hidden">
                            <span>Diskon Durasi:</span>
                            <span id="summaryDiscount">- Rp 0</span>
                        </div>
                        <div class="flex items-baseline justify-between pt-2 border-t border-slate-200 dark:border-white/5">
                            <span class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Total Estimasi:</span>
                            <div id="grandTotalDisplay" class="text-2xl font-black text-slate-900 dark:text-white font-heading">
                                Rp 0
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button type="submit" id="btnSubmitBooking" class="btn-theme-primary w-full py-3.5 px-6 rounded-xl text-xs font-bold flex items-center justify-center gap-2 shadow-sm">
                            <i class="ri-whatsapp-fill text-lg"></i>
                            <span id="btnSubmitText">Kirim Booking ke WhatsApp (+62 838-3210-4793)</span>
                        </button>
                        <p class="text-[10px] text-center text-slate-500 dark:text-slate-400 mt-2">
                            Admin Surabaya akan langsung mengecek data dan mengonfirmasi ketersediaan unit.
                        </p>
                    </div>

                </form>
                <?php endif; ?>

            </div>
        </div>

    </div>
</section>

<!-- SECTION: REKOMENDASI LAINNYA -->
<section class="py-14 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto border-t border-slate-200 dark:border-white/10 mt-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white font-heading">Pilihan Kamera Lainnya di Surabaya</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Kalau butuh opsi kamera lain yang pas dengan budget kamu</p>
        </div>
        <a href="katalog.php" class="text-xs text-purple-600 dark:text-purple-400 hover:underline flex items-center gap-1 font-semibold">
            <span>Buka Semua Katalog</span>
            <i class="ri-arrow-right-line"></i>
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <?php foreach ($related_cameras as $rel): ?>
            <div class="glass-card rounded-3xl overflow-hidden flex flex-col group" data-aos="fade-up">
                <div class="aspect-video relative bg-slate-100 dark:bg-black overflow-hidden border-b border-slate-200 dark:border-white/5">
                    <img src="<?= htmlspecialchars($rel['image']) ?>" alt="<?= htmlspecialchars($rel['name']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                    <span class="absolute top-3 left-3 px-2 py-0.5 rounded text-[10px] font-bold bg-white/90 text-slate-900 border border-slate-200 dark:bg-[#0e131d]/90 dark:text-white dark:border-white/10 shadow-xs">
                        <?= htmlspecialchars($rel['brand']) ?>
                    </span>
                    <span class="absolute bottom-3 right-3 text-slate-900 dark:text-white font-bold text-xs bg-white/95 dark:bg-[#0e131d]/90 px-2.5 py-1 rounded border border-slate-200 dark:border-white/10 font-heading shadow-xs">
                        <?= formatRupiah($rel['daily_price']) ?>/hr
                    </span>
                </div>
                <div class="p-5 flex-grow flex flex-col justify-between">
                    <div>
                        <h4 class="font-bold text-slate-900 dark:text-white text-sm font-heading hover:text-purple-600 dark:hover:text-purple-400 transition-colors">
                            <a href="detail.php?id=<?= $rel['id'] ?>"><?= htmlspecialchars($rel['name']) ?></a>
                        </h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 line-clamp-1"><?= htmlspecialchars($rel['tag']) ?></p>
                    </div>
                    <div class="pt-4 mt-3 border-t border-slate-100 dark:border-white/5 flex items-center justify-between">
                        <a href="detail.php?id=<?= $rel['id'] ?>" class="text-xs font-semibold text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white flex items-center gap-1">
                            <span>Lihat Detail</span>
                            <i class="ri-arrow-right-line text-purple-600 dark:text-purple-400"></i>
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- JAVASCRIPT VALIDASI TANGGAL & KETERSEDIAAN ASLI DARI DATABASE -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const dailyPrice = <?= (int)$camera['daily_price'] ?>;
    const cameraStock = <?= (int)$camera_stock ?>;
    const cameraAvailable = <?= $camera_available ? 'true' : 'false' ?>;

    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    const availabilityAlert = document.getElementById('availabilityAlert');
    const availabilityText = document.getElementById('availabilityText');
    const availabilityBadge = document.getElementById('availabilityBadge');
    const durationBadge = document.getElementById('durationBadge');
    const stockInfo = document.getElementById('stockInfo');
    const btnSubmitBooking = document.getElementById('btnSubmitBooking');
    const btnSubmitText = document.getElementById('btnSubmitText');

    const custName = document.getElementById('custName');
    const custPhone = document.getElementById('custPhone');
    const custIg = document.getElementById('custIg');
    const custPurpose = document.getElementById('custPurpose');
    const pickupMethod = document.getElementById('pickupMethod');

    const addonLens = document.getElementById('addonLens');
    const addonGimbal = document.getElementById('addonGimbal');
    const addonMic = document.getElementById('addonMic');

    const summaryCamPrice = document.getElementById('summaryCamPrice');
    const summaryAddonPrice = document.getElementById('summaryAddonPrice');
    const summaryDiscount = document.getElementById('summaryDiscount');
    const discountRow = document.getElementById('discountRow');
    const grandTotalDisplay = document.getElementById('grandTotalDisplay');

    // Rentang booking asli dari database (dipakai kalender & validasi jadwal).
    const bookedRanges = <?= json_encode($busy_ranges, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?: '[]' ?>;

    // Halaman detail tanpa form booking (belum login) tidak perlu logika jadwal.
    if (!startDateInput || !endDateInput) {
        return;
    }

    function occupiedUnits(startIso, endIso) {
        return bookedRanges.filter((range) => startIso < range.end && endIso > range.start).length;
    }

    // Klik tanggal kosong di kalender -> isi jadwal otomatis.
    document.querySelectorAll('[data-pick-date]').forEach(function (cell) {
        cell.addEventListener('click', function () {
            const picked = cell.dataset.pickDate;
            const next = new Date(picked + 'T00:00:00');
            next.setDate(next.getDate() + 1);
            const pad = (value) => String(value).padStart(2, '0');

            startDateInput.value = picked;
            endDateInput.min = picked;
            endDateInput.value = `${next.getFullYear()}-${pad(next.getMonth() + 1)}-${pad(next.getDate())}`;

            calculateTotal();
        });
    });
    

    function calculateDays() {
        const start = new Date(startDateInput.value);
        const end = new Date(endDateInput.value);
        const diffTime = end - start;
        let days = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        if (days < 1 || isNaN(days)) {
            days = 1;
        }
        return days;
    }

    function checkSlotAvailability() {
        const selectedStart = startDateInput.value;
        const selectedEnd = endDateInput.value;

        const used = occupiedUnits(selectedStart, selectedEnd);
        const left = Math.max(0, cameraStock - used);
        const isConflict = !cameraAvailable || cameraStock <= 0 || left <= 0;

        stockInfo.textContent = cameraStock > 0 ? `Sisa ${left} dari ${cameraStock} unit` : 'Stok unit sedang habis';

        const days = calculateDays();
        durationBadge.textContent = `${days} Hari (${days * 24} Jam)`;

        if (isConflict) {
            availabilityAlert.className = 'mt-2.5 p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 dark:bg-red-950/60 dark:border-red-500/40 dark:text-red-300 text-xs flex items-center justify-between';
            availabilityText.innerHTML = !cameraAvailable
                ? '<strong>Tidak disewakan.</strong> Unit ini sedang dinonaktifkan admin, jadi belum bisa dipesan.'
                : (cameraStock <= 0
                    ? '<strong>Stok habis.</strong> Unit ini sedang tidak tersedia untuk disewa.'
                    : `<strong>Penuh!</strong> Semua ${cameraStock} unit sudah dibooking di tanggal itu. Pilih tanggal lain ya!`);
            
            availabilityBadge.className = 'inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-semibold bg-red-100 text-red-700 dark:bg-red-950/60 dark:text-red-400 border border-red-200 dark:border-red-500/30';
            availabilityBadge.innerHTML = `<span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> ${!cameraAvailable ? 'Tidak Tersedia' : (cameraStock <= 0 ? 'Stok Habis' : 'Tanggal Ini Penuh')}`;

            btnSubmitBooking.disabled = true;
            btnSubmitBooking.className = 'w-full py-3.5 px-6 rounded-xl text-xs font-bold flex items-center justify-center gap-2 bg-slate-200 text-slate-400 dark:bg-slate-800 dark:text-slate-500 cursor-not-allowed';
            btnSubmitText.textContent = !cameraAvailable ? 'Tidak Tersedia' : (cameraStock <= 0 ? 'Stok Habis' : 'Unit Penuh (Pilih Tanggal Lain)');
            return false;
        } else {
            availabilityAlert.className = 'mt-2.5 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 dark:bg-emerald-950/40 dark:border-emerald-500/30 dark:text-emerald-300 text-xs flex items-center justify-between';
            availabilityText.textContent = cameraStock > 1
                ? `Unit tersedia. Sisa ${left} dari ${cameraStock} unit.`
                : 'Unit Tersedia! Siap disewa di Surabaya.';

            availabilityBadge.className = 'inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-semibold bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/30';
            availabilityBadge.innerHTML = `<span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> ${cameraStock > 1 ? `Sisa ${left} Unit` : 'Unit Siap Sewa'}`;

            btnSubmitBooking.disabled = false;
            btnSubmitBooking.className = 'btn-theme-primary w-full py-3.5 px-6 rounded-xl text-xs font-bold flex items-center justify-center gap-2 shadow-sm cursor-pointer';
            btnSubmitText.textContent = 'Kirim Booking ke WhatsApp (+62 838-3210-4793)';
            return true;
        }
    }

    function calculateTotal() {
        const isAvailable = checkSlotAvailability();
        const days = calculateDays();

        let addonTotalPerDay = 0;
        let activeAddons = [];

        if (addonLens.checked) {
            addonTotalPerDay += parseInt(addonLens.dataset.price || '0');
            activeAddons.push('Lensa Prime F/1.4');
        }
        if (addonGimbal.checked) {
            addonTotalPerDay += parseInt(addonGimbal.dataset.price || '0');
            activeAddons.push('DJI RS 3 Pro Gimbal');
        }
        if (addonMic.checked) {
            addonTotalPerDay += parseInt(addonMic.dataset.price || '0');
            activeAddons.push('DJI Mic 2 Wireless');
        }

        const camSubtotal = dailyPrice * days;
        const addonSubtotal = addonTotalPerDay * days;
        const subtotal = camSubtotal + addonSubtotal;

        let discountRate = 0;
        if (days >= 7) {
            discountRate = 0.20;
        } else if (days >= 3) {
            discountRate = 0.10;
        }

        const discountAmount = subtotal * discountRate;
        const grandTotal = subtotal - discountAmount;

        summaryCamPrice.textContent = 'Rp ' + camSubtotal.toLocaleString('id-ID');
        summaryAddonPrice.textContent = 'Rp ' + addonSubtotal.toLocaleString('id-ID');

        if (discountRate > 0) {
            discountRow.classList.remove('hidden');
            summaryDiscount.textContent = `- Rp ${discountAmount.toLocaleString('id-ID')} (${discountRate * 100}%)`;
        } else {
            discountRow.classList.add('hidden');
        }

        grandTotalDisplay.textContent = 'Rp ' + grandTotal.toLocaleString('id-ID');

        return {
            days,
            grandTotal,
            activeAddons,
            isAvailable
        };
    }

    startDateInput.addEventListener('change', function() {
        if (new Date(endDateInput.value) <= new Date(startDateInput.value)) {
            const nextDay = new Date(startDateInput.value);
            nextDay.setDate(nextDay.getDate() + 1);
            endDateInput.value = nextDay.toISOString().split('T')[0];
        }
        calculateTotal();
    });

    endDateInput.addEventListener('change', function() {
        if (new Date(endDateInput.value) <= new Date(startDateInput.value)) {
            alert('Tanggal selesai sewa harus setelah tanggal mulai ya!');
            const nextDay = new Date(startDateInput.value);
            nextDay.setDate(nextDay.getDate() + 1);
            endDateInput.value = nextDay.toISOString().split('T')[0];
        }
        calculateTotal();
    });

    addonLens.addEventListener('change', calculateTotal);
    addonGimbal.addEventListener('change', calculateTotal);
    addonMic.addEventListener('change', calculateTotal);

    document.getElementById('rentalBookingForm').addEventListener('submit', function(e) {
        const calculation = calculateTotal();

        if (!calculation.isAvailable) {
            e.preventDefault();
            alert('Mohon maaf, kamera sedang penuh pada tanggal yang Anda pilih. Silakan ganti tanggal sewa!');
            return;
        }

        if (!custName.value.trim() || !custPhone.value.trim() || !custIg.value.trim()) {
            e.preventDefault();
            alert('Mohon lengkapi nama, nomor WhatsApp, dan akun Instagram untuk verifikasi ya!');
            return;
        }

        if (!document.querySelector('input[name="payment_method"]:checked')) {
            e.preventDefault();
            alert('Pilih metode pembayaran dulu ya (Cash atau QRIS).');
            return;
        }

        // Data dikirim ke proses-booking.php lalu disimpan; setelah itu customer
        // diarahkan ke halaman konfirmasi + tombol WhatsApp.
        btnSubmitBooking.disabled = true;
        btnSubmitText.textContent = 'Menyimpan booking...';
    });

    calculateTotal();
});
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>


