<?php
// admin/index.php - Dashboard admin Jepret.in (data dari database)
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin_layout.php';
require_once __DIR__ . '/../includes/bookings.php';
require_once __DIR__ . '/../data/cameras.php';

require_admin_login();

$csrf = admin_csrf_token();

$stats     = bookings_stats();
$customers = bookings_customers();

$total_cameras = count($cameras);
$ready_cameras = count(array_filter($cameras, static fn($c) => !empty($c['is_available'])));

$bookings = bookings_all();
$today_bookings = 0;
foreach ($bookings as $row) {
    if (substr((string)($row['created_at'] ?? ''), 0, 10) === date('Y-m-d')) {
        $today_bookings++;
    }
}

// Rentang grafik: 7 / 14 / 30 / 90 hari, atau semua riwayat
$range_options = ['7' => '7 hari', '14' => '14 hari', '30' => '30 hari', '90' => '90 hari', 'all' => 'Semua'];
$range = (string)($_GET['range'] ?? '14');
if (!isset($range_options[$range])) {
    $range = '14';
}
$chart_days = $range === 'all' ? 0 : (int)$range;
$series = bookings_series($chart_days);

$chart_values = array_column($series, 'count');
$chart_total  = array_sum($chart_values);
$chart_max    = max(1, max($chart_values));
$chart_n      = count($chart_values);
$chart_avg    = $chart_n > 0 ? $chart_total / $chart_n : 0;

$chart_w = 600;
$chart_h = 180;
$pad_x = 12;
$pad_top = 16;
$pad_bottom = 16;

$chart_points = [];
foreach ($chart_values as $i => $value) {
    $x = $pad_x + ($chart_n > 1 ? $i * (($chart_w - $pad_x * 2) / ($chart_n - 1)) : 0);
    $y = $chart_h - $pad_bottom - ($value / $chart_max) * ($chart_h - $pad_top - $pad_bottom);
    $chart_points[] = [round($x, 1), round($y, 1)];
}

$line_path = '';
foreach ($chart_points as $i => $point) {
    $line_path .= ($i === 0 ? 'M' : ' L') . $point[0] . ' ' . $point[1];
}
$baseline_y = $chart_h - $pad_bottom;
$area_path = $line_path
    . ' L ' . end($chart_points)[0] . ' ' . $baseline_y
    . ' L ' . $chart_points[0][0] . ' ' . $baseline_y . ' Z';

// Label sumbu X: maksimal 7 label supaya tidak berdesakan
$label_step = max(1, (int)ceil($chart_n / 7));

// Rekap pembayaran per metode (dari tabel payments)
$method_summary = ['cash' => ['count' => 0, 'amount' => 0], 'qris' => ['count' => 0, 'amount' => 0]];
$pdo = Database::getConnection();
if ($pdo) {
    try {
        foreach ($pdo->query('SELECT method, COUNT(*) AS jumlah, SUM(amount) AS total FROM payments GROUP BY method')->fetchAll() as $row) {
            $key = (string)$row['method'];
            if (isset($method_summary[$key])) {
                $method_summary[$key] = ['count' => (int)$row['jumlah'], 'amount' => (int)$row['total']];
            }
        }
    } catch (Throwable $e) {
        // biarkan nol
    }
}
$payment_total_count = max(1, $method_summary['cash']['count'] + $method_summary['qris']['count']);

$status_dot = [
    'pending'   => 'bg-amber-500',
    'confirmed' => 'bg-blue-500',
    'active'    => 'bg-purple-500',
    'completed' => 'bg-emerald-500',
    'cancelled' => 'bg-slate-400',
];

$recent = array_slice($bookings, 0, 5);

admin_layout_start('Dashboard', 'dashboard', $csrf, 'Ringkasan bisnis & aktivitas pemesanan');
?>

<!-- KPI -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="glass-card rounded-2xl p-5">
        <div class="flex items-center justify-between">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Pemesanan</span>
            <span class="w-8 h-8 rounded-lg bg-purple-50 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                <i class="ri-file-list-3-line text-sm"></i>
            </span>
        </div>
        <div class="text-3xl font-black font-heading text-slate-900 dark:text-white mt-3"><?= (int)$stats['total'] ?></div>
        <div class="mt-1 flex items-center gap-1.5 text-[10px] font-semibold text-emerald-600 dark:text-emerald-400">
            <i class="ri-arrow-up-line"></i>
            <span><?= $today_bookings ?> masuk hari ini</span>
        </div>
    </div>

    <div class="glass-card rounded-2xl p-5">
        <div class="flex items-center justify-between">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Menunggu Konfirmasi</span>
            <span class="w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                <i class="ri-time-line text-sm"></i>
            </span>
        </div>
        <div class="text-3xl font-black font-heading text-slate-900 dark:text-white mt-3"><?= (int)$stats['pending'] ?></div>
        <div class="mt-1 text-[10px] font-semibold text-slate-400">Perlu dicek admin</div>
    </div>

    <div class="glass-card rounded-2xl p-5">
        <div class="flex items-center justify-between">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Pembayaran Diterima</span>
            <span class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                <i class="ri-wallet-3-line text-sm"></i>
            </span>
        </div>
        <div class="text-2xl font-black font-heading text-slate-900 dark:text-white mt-3"><?= formatRupiah((int)$stats['payments']) ?></div>
        <div class="mt-1 text-[10px] font-semibold text-amber-600 dark:text-amber-400">
            <?= (int)$stats['unpaid'] + (int)$stats['partial'] ?> pesanan belum lunas
        </div>
    </div>

    <div class="glass-card rounded-2xl p-5">
        <div class="flex items-center justify-between">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Pelanggan</span>
            <span class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-[#141b29] text-slate-600 dark:text-slate-300 flex items-center justify-center">
                <i class="ri-team-line text-sm"></i>
            </span>
        </div>
        <div class="text-3xl font-black font-heading text-slate-900 dark:text-white mt-3"><?= count($customers) ?></div>
        <div class="mt-1 text-[10px] font-semibold text-slate-400"><?= $ready_cameras ?>/<?= $total_cameras ?> unit siap sewa</div>
    </div>
</div>

<!-- GRAFIK + RINGKASAN -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 glass-panel rounded-2xl p-5 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-sm font-bold font-heading text-slate-900 dark:text-white">Riwayat Pemesanan</h2>
                <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">
                    <?= $range === 'all' ? 'Seluruh riwayat pemesanan' : 'Pemesanan masuk ' . $range . ' hari terakhir' ?>
                </p>
            </div>
            <div class="text-right">
                <div class="text-xl font-black font-heading text-slate-900 dark:text-white"><?= (int)$chart_total ?></div>
                <div class="text-[10px] text-slate-400">rata-rata <?= number_format($chart_avg, 1, ',', '.') ?>/hari</div>
            </div>
        </div>

        <!-- Pilihan rentang -->
        <div class="mt-4 flex items-center gap-1.5 overflow-x-auto">
            <?php foreach ($range_options as $key => $label): ?>
                <a href="index.php?range=<?= urlencode($key) ?>"
                   class="px-2.5 py-1.5 rounded-lg text-[10px] font-bold whitespace-nowrap border transition
                          <?= $range === $key
                                ? 'bg-slate-900 text-white border-slate-900 dark:bg-white dark:text-slate-900 dark:border-white'
                                : 'bg-white dark:bg-[#10141d] text-slate-600 dark:text-slate-300 border-slate-200 dark:border-white/10 hover:border-slate-300' ?>">
                    <?= htmlspecialchars($label) ?>
                </a>
            <?php endforeach; ?>
            <a href="pemesanan.php" class="ml-auto px-2.5 py-1.5 rounded-lg text-[10px] font-bold whitespace-nowrap text-purple-600 dark:text-purple-400 hover:underline flex items-center gap-1">
                Riwayat lengkap <i class="ri-arrow-right-line"></i>
            </a>
        </div>

        <div class="mt-4">
            <svg viewBox="0 0 <?= $chart_w ?> <?= $chart_h ?>" class="w-full h-40 sm:h-44" preserveAspectRatio="none" role="img" aria-label="Grafik pemesanan">
                <?php for ($g = 0; $g <= 3; $g++): ?>
                    <?php $gy = $pad_top + ($g / 3) * ($chart_h - $pad_top - $pad_bottom); ?>
                    <line x1="<?= $pad_x ?>" y1="<?= round($gy, 1) ?>" x2="<?= $chart_w - $pad_x ?>" y2="<?= round($gy, 1) ?>"
                          stroke="currentColor" stroke-width="1" class="text-slate-200 dark:text-white/10" />
                <?php endfor; ?>

                <path d="<?= $area_path ?>" fill="#7c3aed" fill-opacity="0.14" />
                <path d="<?= $line_path ?>" fill="none" stroke="#7c3aed" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" />

                <?php if ($chart_n <= 31): ?>
                    <?php foreach ($chart_points as $i => $point): ?>
                        <circle cx="<?= $point[0] ?>" cy="<?= $point[1] ?>" r="<?= $i === count($chart_points) - 1 ? 4 : 2.5 ?>"
                                fill="<?= $i === count($chart_points) - 1 ? '#7c3aed' : '#ffffff' ?>"
                                stroke="#7c3aed" stroke-width="1.8" />
                    <?php endforeach; ?>
                <?php endif; ?>
            </svg>

            <div class="flex items-center justify-between mt-2 text-[9px] text-slate-400">
                <?php foreach ($series as $i => $day): ?>
                    <?php if ($i % $label_step === 0 || $i === $chart_n - 1): ?>
                        <span><?= htmlspecialchars($day['label']) ?></span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($chart_total === 0): ?>
            <p class="mt-3 text-[10px] text-slate-400 flex items-center gap-1.5">
                <i class="ri-information-line"></i>
                <span>Belum ada pemesanan pada rentang ini.</span>
            </p>
        <?php endif; ?>
    </div>

    <div class="glass-panel rounded-2xl p-5 sm:p-6 space-y-5">
        <div>
            <h2 class="text-sm font-bold font-heading text-slate-900 dark:text-white">Pembayaran</h2>
            <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">Dari pembayaran yang dicatat admin</p>
        </div>

        <?php foreach (['cash' => ['label' => 'Cash', 'color' => 'bg-emerald-500', 'icon' => 'ri-money-dollar-circle-line text-emerald-500'], 'qris' => ['label' => 'QRIS', 'color' => 'bg-purple-500', 'icon' => 'ri-qr-code-line text-purple-500']] as $key => $meta): ?>
            <div class="-mt-2">
                <div class="flex items-center justify-between text-[11px] font-semibold">
                    <span class="flex items-center gap-1.5 text-slate-700 dark:text-slate-200">
                        <i class="<?= $meta['icon'] ?>"></i> <?= $meta['label'] ?>
                    </span>
                    <span class="text-slate-500 dark:text-slate-400"><?= (int)$method_summary[$key]['count'] ?>x</span>
                </div>
                <div class="mt-1.5 h-2 rounded-full bg-slate-100 dark:bg-white/5 overflow-hidden">
                    <div class="h-full rounded-full <?= $meta['color'] ?>" style="width: <?= round($method_summary[$key]['count'] / $payment_total_count * 100) ?>%"></div>
                </div>
                <div class="mt-1 text-[10px] text-slate-400"><?= formatRupiah($method_summary[$key]['amount']) ?></div>
            </div>
        <?php endforeach; ?>

        <div class="pt-4 border-t border-slate-200 dark:border-white/10 space-y-2.5">
            <h3 class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Status Pemesanan</h3>
            <?php foreach (BOOKING_STATUSES as $key => $label): ?>
                <div class="flex items-center justify-between text-[11px]">
                    <span class="flex items-center gap-2 text-slate-600 dark:text-slate-300">
                        <span class="w-1.5 h-1.5 rounded-full <?= $status_dot[$key] ?? 'bg-slate-400' ?>"></span>
                        <?= htmlspecialchars($label) ?>
                    </span>
                    <span class="font-bold text-slate-900 dark:text-white"><?= (int)($stats[$key] ?? 0) ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <a href="pembayaran.php" class="flex items-center justify-center gap-1.5 px-3 py-2.5 rounded-xl text-[11px] font-bold btn-theme-secondary">
            <i class="ri-add-circle-line"></i>
            <span>Catat Pembayaran</span>
        </a>
    </div>
</div>

<!-- PEMESANAN TERBARU -->
<div class="glass-panel rounded-2xl overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-200 dark:border-white/10 flex items-center justify-between">
        <div>
            <h2 class="text-sm font-bold font-heading text-slate-900 dark:text-white">Pemesanan Terbaru</h2>
            <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">5 booking terakhir yang masuk</p>
        </div>
        <a href="pemesanan.php" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-[11px] font-bold border border-slate-200 dark:border-white/10 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-white/5 transition">
            <span>Kelola</span>
            <i class="ri-arrow-right-line"></i>
        </a>
    </div>

    <?php if (!$recent): ?>
        <div class="px-5 py-12 text-center">
            <i class="ri-calendar-2-line text-3xl text-slate-300 dark:text-slate-600"></i>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">Belum ada pemesanan masuk.</p>
            <a href="../katalog.php" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 mt-3 text-[11px] font-bold text-purple-600 dark:text-purple-400 hover:underline">
                Buka katalog <i class="ri-arrow-right-line"></i>
            </a>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs min-w-[760px]">
                <thead class="bg-slate-50 dark:bg-[#141b29] text-slate-500 dark:text-slate-400">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Kode</th>
                        <th class="px-5 py-3 font-semibold">Penyewa</th>
                        <th class="px-5 py-3 font-semibold">Kamera</th>
                        <th class="px-5 py-3 font-semibold">Jadwal</th>
                        <th class="px-5 py-3 font-semibold">Total</th>
                        <th class="px-5 py-3 font-semibold">Bayar</th>
                        <th class="px-5 py-3 font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                    <?php foreach ($recent as $row): ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-white/5 transition">
                            <td class="px-5 py-3 font-bold text-slate-900 dark:text-white whitespace-nowrap"><?= htmlspecialchars((string)$row['booking_code']) ?></td>
                            <td class="px-5 py-3 text-slate-600 dark:text-slate-300 whitespace-nowrap"><?= htmlspecialchars((string)$row['customer_name']) ?></td>
                            <td class="px-5 py-3 text-slate-600 dark:text-slate-300"><?= htmlspecialchars((string)$row['camera_name']) ?></td>
                            <td class="px-5 py-3 text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                <?= htmlspecialchars(date('d M', strtotime((string)$row['start_date']))) ?>
                                &ndash;
                                <?= htmlspecialchars(date('d M', strtotime((string)$row['end_date']))) ?>
                            </td>
                            <td class="px-5 py-3 font-semibold text-slate-900 dark:text-white whitespace-nowrap"><?= formatRupiah((int)$row['total_price']) ?></td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border <?= booking_payment_status_classes((string)$row['payment_status']) ?>">
                                    <?= htmlspecialchars(booking_payment_status_label((string)$row['payment_status'])) ?>
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border <?= booking_status_classes((string)$row['status']) ?>">
                                    <?= htmlspecialchars(booking_status_label((string)$row['status'])) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- KATALOG -->
<div class="glass-panel rounded-2xl overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-200 dark:border-white/10 flex items-center justify-between">
        <div>
            <h2 class="text-sm font-bold font-heading text-slate-900 dark:text-white">Katalog Kamera</h2>
            <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">Data produk dari tabel <span class="font-semibold">cameras</span></p>
        </div>
        <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400"><?= $total_cameras ?> unit</span>
    </div>

    <div class="overflow-x-auto max-h-80 admin-scroll">
        <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 dark:bg-[#141b29] text-slate-500 dark:text-slate-400 sticky top-0">
                <tr>
                    <th class="px-5 py-3 font-semibold">Kamera</th>
                    <th class="px-5 py-3 font-semibold">Merk</th>
                    <th class="px-5 py-3 font-semibold">Harga / 24 jam</th>
                    <th class="px-5 py-3 font-semibold">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                <?php foreach ($cameras as $camera): ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-white/5 transition">
                        <td class="px-5 py-3 font-semibold text-slate-900 dark:text-white"><?= htmlspecialchars($camera['name']) ?></td>
                        <td class="px-5 py-3 text-slate-600 dark:text-slate-300"><?= htmlspecialchars($camera['brand']) ?></td>
                        <td class="px-5 py-3 text-slate-600 dark:text-slate-300"><?= formatRupiah($camera['daily_price']) ?></td>
                        <td class="px-5 py-3">
                            <?php if (!empty($camera['is_available'])): ?>
                                <span class="inline-flex items-center gap-1.5 text-[10px] font-bold text-emerald-600 dark:text-emerald-400">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Ready
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-1.5 text-[10px] font-bold text-slate-500 dark:text-slate-400">
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Disewa
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php admin_layout_end(); ?>
