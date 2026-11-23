<?php
// admin/pemesanan.php - Daftar & pengelolaan status pemesanan
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin_layout.php';
require_once __DIR__ . '/../includes/bookings.php';
require_once __DIR__ . '/../data/cameras.php';

require_admin_login();

$csrf = admin_csrf_token();

// Ubah status pemesanan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    if (!admin_verify_csrf($_POST['csrf'] ?? null)) {
        $notice = ['type' => 'error', 'text' => 'Sesi form kedaluwarsa. Coba lagi.'];
    } else {
        $id = (int)($_POST['booking_id'] ?? 0);
        $status = (string)($_POST['status'] ?? '');

        if (bookings_update_status($id, $status)) {
            header('Location: pemesanan.php?updated=1' . (isset($_POST['filter']) && $_POST['filter'] !== 'all' ? '&status=' . urlencode((string)$_POST['filter']) : ''));
            exit;
        }

        $notice = ['type' => 'error', 'text' => 'Status gagal diubah.'];
    }
}

$filter = (string)($_GET['status'] ?? 'all');
if ($filter !== 'all' && !isset(BOOKING_STATUSES[$filter])) {
    $filter = 'all';
}

$stats = bookings_stats();
$rows = bookings_all();
usort($rows, static fn($a, $b) => strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? '')));

if ($filter !== 'all') {
    $rows = array_values(array_filter($rows, static fn($row) => ($row['status'] ?? 'pending') === $filter));
}

admin_layout_start('Pemesanan', 'pemesanan', $csrf, 'Kelola booking, status, dan metode pembayaran');
?>

<div class="flex flex-wrap items-center justify-between gap-3">
    <div class="flex items-center gap-2 text-[11px]">
        <span class="px-3 py-1.5 rounded-lg border border-amber-200 dark:border-amber-500/30 bg-amber-50 dark:bg-amber-950/30 text-amber-700 dark:text-amber-300 font-bold">
            <?= (int)$stats['pending'] ?> menunggu
        </span>
        <span class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-white/10 bg-white dark:bg-[#10141d] text-slate-600 dark:text-slate-300 font-bold">
            <?= (int)$stats['total'] ?> total
        </span>
    </div>
</div>

<?php if (isset($_GET['updated'])): ?>
    <div class="rounded-xl border border-emerald-200 dark:border-emerald-500/30 bg-emerald-50 dark:bg-emerald-950/30 px-4 py-2.5 text-xs text-emerald-700 dark:text-emerald-300">
        Status pemesanan berhasil diperbarui.
    </div>
<?php endif; ?>

<?php if (!empty($notice)): ?>
    <div class="rounded-xl border border-red-200 dark:border-red-500/30 bg-red-50 dark:bg-red-950/30 px-4 py-2.5 text-xs text-red-700 dark:text-red-300">
        <?= htmlspecialchars($notice['text']) ?>
    </div>
<?php endif; ?>

<!-- Filter status -->
<div class="flex items-center gap-1.5 overflow-x-auto pb-1">
    <?php
    $filters = ['all' => 'Semua'] + BOOKING_STATUSES;
    foreach ($filters as $key => $label):
        $is_active = $filter === $key;
        $count = $key === 'all' ? $stats['total'] : ($stats[$key] ?? 0);
    ?>
        <a href="pemesanan.php<?= $key === 'all' ? '' : '?status=' . urlencode($key) ?>"
           class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-[11px] font-bold whitespace-nowrap border transition
                  <?= $is_active
                        ? 'bg-slate-900 text-white border-slate-900 dark:bg-white dark:text-slate-900 dark:border-white'
                        : 'bg-white dark:bg-[#10141d] text-slate-600 dark:text-slate-300 border-slate-200 dark:border-white/10 hover:border-slate-300' ?>">
            <?= htmlspecialchars($label) ?>
            <span class="text-[10px] opacity-70"><?= (int)$count ?></span>
        </a>
    <?php endforeach; ?>
</div>

<div class="glass-panel rounded-2xl overflow-hidden">
    <?php if (empty($rows)): ?>
        <div class="px-5 py-14 text-center">
            <i class="ri-calendar-2-line text-3xl text-slate-300 dark:text-slate-600"></i>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">
                Belum ada pemesanan<?= $filter === 'all' ? '' : ' dengan status ini' ?>. Coba buat booking dari halaman detail kamera.
            </p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs min-w-[900px]">
                <thead class="bg-slate-50 dark:bg-[#141b29] text-slate-500 dark:text-slate-400">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Kode / Masuk</th>
                        <th class="px-4 py-3 font-semibold">Penyewa</th>
                        <th class="px-4 py-3 font-semibold">Kamera</th>
                        <th class="px-4 py-3 font-semibold">Jadwal</th>
                        <th class="px-4 py-3 font-semibold">Total</th>
                        <th class="px-4 py-3 font-semibold">Bayar</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                    <?php foreach ($rows as $row): ?>
                        <tr class="align-top hover:bg-slate-50 dark:hover:bg-white/5 transition">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="font-bold text-slate-900 dark:text-white"><?= htmlspecialchars((string)$row['booking_code']) ?></div>
                                <div class="text-[10px] text-slate-400 mt-0.5"><?= htmlspecialchars(date('d M Y H:i', strtotime((string)($row['created_at'] ?? 'now')))) ?></div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-900 dark:text-white whitespace-nowrap"><?= htmlspecialchars((string)$row['customer_name']) ?></div>
                                <a href="https://wa.me/<?= htmlspecialchars(preg_replace('/\D+/', '', (string)$row['customer_phone']) ?? '') ?>" target="_blank" rel="noopener"
                                   class="text-[10px] text-emerald-600 dark:text-emerald-400 hover:underline inline-flex items-center gap-1 mt-0.5">
                                    <i class="ri-whatsapp-line"></i><?= htmlspecialchars((string)$row['customer_phone']) ?>
                                </a>
                                <?php if (!empty($row['customer_ig'])): ?>
                                    <div class="text-[10px] text-slate-400 mt-0.5"><?= htmlspecialchars((string)$row['customer_ig']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
                                <?= htmlspecialchars((string)$row['camera_name']) ?>
                                <?php if (!empty($row['addon_labels'])): ?>
                                    <div class="text-[10px] text-slate-400 mt-0.5">+ <?= htmlspecialchars(implode(', ', (array)$row['addon_labels'])) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300 whitespace-nowrap">
                                <?= htmlspecialchars(date('d M', strtotime((string)$row['start_date']))) ?>
                                &ndash;
                                <?= htmlspecialchars(date('d M Y', strtotime((string)$row['end_date']))) ?>
                                <div class="text-[10px] text-slate-400"><?= (int)$row['total_days'] ?> hari</div>
                            </td>
                            <td class="px-4 py-3 font-semibold text-slate-900 dark:text-white whitespace-nowrap">
                                <?= formatRupiah((int)$row['total_price']) ?>
                                <?php if (!empty($row['discount'])): ?>
                                    <div class="text-[10px] text-emerald-600 dark:text-emerald-400 font-normal">diskon <?= formatRupiah((int)$row['discount']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border <?= booking_payment_status_classes((string)$row['payment_status']) ?>">
                                    <?= htmlspecialchars(booking_payment_status_label((string)$row['payment_status'])) ?>
                                </span>
                                <div class="text-[10px] font-semibold text-slate-700 dark:text-slate-200 mt-1 whitespace-nowrap">
                                    <?= formatRupiah((int)$row['paid_total']) ?> / <?= formatRupiah((int)$row['total_price']) ?>
                                </div>
                                <?php if ((int)$row['remaining'] > 0): ?>
                                    <div class="text-[10px] text-amber-600 dark:text-amber-400">Sisa <?= formatRupiah((int)$row['remaining']) ?></div>
                                <?php endif; ?>
                                <div class="text-[10px] text-slate-400 mt-0.5">Pilihan: <?= htmlspecialchars(booking_payment_label((string)($row['payment_method'] ?? 'cash'))) ?></div>
                                <a href="pembayaran.php?booking=<?= (int)$row['id'] ?>" class="mt-1 inline-flex items-center gap-1 text-[10px] font-bold text-purple-600 dark:text-purple-400 hover:underline">
                                    <i class="ri-add-circle-line"></i> Catat bayar
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border <?= booking_status_classes((string)$row['status']) ?>">
                                    <?= htmlspecialchars(booking_status_label((string)$row['status'])) ?>
                                </span>

                                <form method="POST" action="pemesanan.php" class="mt-2 flex items-center gap-1.5">
                                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="booking_id" value="<?= (int)$row['id'] ?>">
                                    <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                                    <select name="status" class="glass-input rounded-lg px-2 py-1 text-[10px]">
                                        <?php foreach (BOOKING_STATUSES as $key => $label): ?>
                                            <option value="<?= htmlspecialchars($key) ?>" <?= $key === ($row['status'] ?? '') ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="px-2 py-1 rounded-lg bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-[10px] font-bold hover:opacity-90 transition">
                                        Simpan
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php admin_layout_end(); ?>
