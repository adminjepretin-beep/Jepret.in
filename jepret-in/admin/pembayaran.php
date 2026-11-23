<?php
// admin/pembayaran.php - Catat & pantau pembayaran pemesanan
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin_layout.php';
require_once __DIR__ . '/../includes/bookings.php';
require_once __DIR__ . '/../data/cameras.php';

require_admin_login();

$csrf = admin_csrf_token();
$notice = null;

// Simpan pembayaran baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_payment') {
    if (!admin_verify_csrf($_POST['csrf'] ?? null)) {
        $notice = ['type' => 'error', 'text' => 'Sesi form kedaluwarsa. Coba lagi.'];
    } else {
        $booking_id = (int)($_POST['booking_id'] ?? 0);
        $method     = (string)($_POST['method'] ?? '');
        $amount     = (int)preg_replace('/\D+/', '', (string)($_POST['amount'] ?? '0'));
        $note       = trim((string)($_POST['note'] ?? ''));
        $booking    = booking_by_id($booking_id);

        if (!$booking) {
            $notice = ['type' => 'error', 'text' => 'Pemesanan tidak ditemukan.'];
        } elseif ($amount <= 0) {
            $notice = ['type' => 'error', 'text' => 'Nominal pembayaran harus lebih dari 0.'];
        } elseif (bookings_add_payment($booking_id, $method, $amount, $note)) {
            header('Location: pembayaran.php?booking=' . $booking_id . '&saved=1');
            exit;
        } else {
            $notice = ['type' => 'error', 'text' => 'Pembayaran gagal dicatat.'];
        }
    }
}

$stats = bookings_stats();
$bookings = bookings_all();

$unpaid = array_values(array_filter($bookings, static fn($row) => $row['payment_status'] !== 'paid' && $row['status'] !== 'cancelled'));

$selected = null;
if (!empty($_GET['booking'])) {
    $selected = booking_by_id((int)$_GET['booking']);
}
if (!$selected && $unpaid) {
    $selected = $unpaid[0];
}

// Riwayat pembayaran terakhir
$pdo = Database::getConnection();
$recent_payments = [];
if ($pdo) {
    try {
        $recent_payments = $pdo->query('SELECT p.*, b.booking_code, b.customer_name
            FROM payments p
            LEFT JOIN bookings b ON b.id = p.booking_id
            ORDER BY p.id DESC
            LIMIT 20')->fetchAll();
    } catch (Throwable $e) {
        $recent_payments = [];
    }
}

admin_layout_start('Pembayaran', 'pembayaran', $csrf, 'Catat pembayaran cash/QRIS yang sudah dikonfirmasi lewat WhatsApp');
?>

<?php if (isset($_GET['saved'])): ?>
    <div class="rounded-xl border border-emerald-200 dark:border-emerald-500/30 bg-emerald-50 dark:bg-emerald-950/30 px-4 py-2.5 text-xs text-emerald-700 dark:text-emerald-300">
        Pembayaran berhasil dicatat.
    </div>
<?php endif; ?>

<?php if ($notice): ?>
    <div class="rounded-xl border border-red-200 dark:border-red-500/30 bg-red-50 dark:bg-red-950/30 px-4 py-2.5 text-xs text-red-700 dark:text-red-300">
        <?= htmlspecialchars($notice['text']) ?>
    </div>
<?php endif; ?>

<!-- Ringkasan -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <div class="glass-card rounded-2xl p-5">
        <div class="flex items-center justify-between">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Pembayaran Diterima</span>
            <i class="ri-wallet-3-line text-emerald-500"></i>
        </div>
        <div class="text-2xl font-black font-heading text-slate-900 dark:text-white mt-2"><?= formatRupiah((int)$stats['payments']) ?></div>
        <p class="text-[10px] text-slate-400 mt-1">Dari semua pembayaran yang dicatat</p>
    </div>
    <div class="glass-card rounded-2xl p-5">
        <div class="flex items-center justify-between">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Belum / DP</span>
            <i class="ri-time-line text-amber-500"></i>
        </div>
        <div class="text-2xl font-black font-heading text-slate-900 dark:text-white mt-2"><?= (int)$stats['unpaid'] + (int)$stats['partial'] ?></div>
        <p class="text-[10px] text-slate-400 mt-1">Pemesanan yang belum lunas</p>
    </div>
    <div class="glass-card rounded-2xl p-5">
        <div class="flex items-center justify-between">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Lunas</span>
            <i class="ri-checkbox-circle-line text-purple-500"></i>
        </div>
        <div class="text-2xl font-black font-heading text-slate-900 dark:text-white mt-2"><?= (int)$stats['paid'] ?></div>
        <p class="text-[10px] text-slate-400 mt-1">Pemesanan sudah dibayar penuh</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 items-start">

    <!-- Form catat pembayaran -->
    <div class="lg:col-span-1 glass-panel rounded-2xl p-5 space-y-4">
        <div>
            <h2 class="text-sm font-bold font-heading text-slate-900 dark:text-white">Catat Pembayaran</h2>
            <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">Isi setelah uang benar-benar diterima.</p>
        </div>

        <?php if (!$selected): ?>
            <p class="text-[11px] text-slate-500 dark:text-slate-400">Belum ada pemesanan yang perlu dibayar.</p>
        <?php else: ?>
            <form method="POST" action="pembayaran.php" class="space-y-3">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="action" value="add_payment">

                <div>
                    <label for="booking_id" class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">Pemesanan</label>
                    <select id="booking_id" name="booking_id" class="glass-input w-full rounded-xl px-3 py-2 text-[11px]" onchange="if(this.value){window.location='pembayaran.php?booking='+this.value;}">
                        <?php foreach ($unpaid as $row): ?>
                            <option value="<?= (int)$row['id'] ?>" <?= (int)$row['id'] === (int)$selected['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string)$row['booking_code']) ?> &middot; <?= htmlspecialchars((string)$row['customer_name']) ?>
                            </option>
                        <?php endforeach; ?>
                        <?php if ($selected['payment_status'] === 'paid'): ?>
                            <option value="<?= (int)$selected['id'] ?>" selected>
                                <?= htmlspecialchars((string)$selected['booking_code']) ?> &middot; <?= htmlspecialchars((string)$selected['customer_name']) ?> (lunas)
                            </option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="rounded-xl border border-slate-200 dark:border-white/10 bg-slate-50 dark:bg-[#10141d] p-3 space-y-1.5">
                    <div class="flex items-center justify-between text-[11px]">
                        <span class="text-slate-500 dark:text-slate-400">Total tagihan</span>
                        <span class="font-bold text-slate-900 dark:text-white"><?= formatRupiah((int)$selected['total_price']) ?></span>
                    </div>
                    <div class="flex items-center justify-between text-[11px]">
                        <span class="text-slate-500 dark:text-slate-400">Sudah dibayar</span>
                        <span class="font-semibold text-slate-700 dark:text-slate-200"><?= formatRupiah((int)$selected['paid_total']) ?></span>
                    </div>
                    <div class="flex items-center justify-between text-[11px]">
                        <span class="text-slate-500 dark:text-slate-400">Sisa</span>
                        <span class="font-bold text-amber-600 dark:text-amber-400"><?= formatRupiah((int)$selected['remaining']) ?></span>
                    </div>
                </div>

                <div>
                    <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">Metode Diterima</span>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="flex items-center gap-2 p-2 rounded-xl border border-slate-200 dark:border-white/10 cursor-pointer text-[11px] font-semibold">
                            <input type="radio" name="method" value="cash" checked class="text-purple-600 focus:ring-0">
                            <span>Cash</span>
                        </label>
                        <label class="flex items-center gap-2 p-2 rounded-xl border border-slate-200 dark:border-white/10 cursor-pointer text-[11px] font-semibold">
                            <input type="radio" name="method" value="qris" class="text-purple-600 focus:ring-0">
                            <span>QRIS</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label for="amount" class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">Nominal Diterima</label>
                    <input id="amount" name="amount" type="number" min="1" step="1000" required
                           value="<?= max(1, (int)$selected['remaining']) ?>"
                           class="glass-input w-full rounded-xl px-3 py-2 text-[11px]">
                </div>

                <div>
                    <label for="note" class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">Catatan / Bukti</label>
                    <input id="note" name="note" type="text" placeholder="Contoh: QRIS BCA 14 Nov, ref 1234"
                           class="glass-input w-full rounded-xl px-3 py-2 text-[11px]">
                </div>

                <button type="submit" class="btn-theme-primary w-full py-2.5 rounded-xl text-[11px] font-bold flex items-center justify-center gap-1.5">
                    <i class="ri-save-3-line"></i>
                    <span>Simpan Pembayaran</span>
                </button>
            </form>

            <?php if ($selected['payment_status'] === 'paid'): ?>
                <div class="text-[10px] text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5">
                    <i class="ri-checkbox-circle-line"></i>
                    <span>Pemesanan ini sudah lunas.</span>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Daftar belum lunas -->
    <div class="lg:col-span-2 glass-panel rounded-2xl overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 dark:border-white/10 flex items-center justify-between">
            <div>
                <h2 class="text-sm font-bold font-heading text-slate-900 dark:text-white">Menunggu Pembayaran</h2>
                <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">Cocokkan dengan bukti transfer / chat WhatsApp</p>
            </div>
            <span class="text-[11px] font-bold text-amber-600 dark:text-amber-400"><?= count($unpaid) ?> pesanan</span>
        </div>

        <?php if (!$unpaid): ?>
            <div class="px-5 py-12 text-center">
                <i class="ri-checkbox-circle-line text-3xl text-emerald-400"></i>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">Semua pemesanan sudah lunas.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs min-w-[640px]">
                    <thead class="bg-slate-50 dark:bg-[#141b29] text-slate-500 dark:text-slate-400">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Kode</th>
                            <th class="px-4 py-3 font-semibold">Penyewa</th>
                            <th class="px-4 py-3 font-semibold">Total</th>
                            <th class="px-4 py-3 font-semibold">Sudah Bayar</th>
                            <th class="px-4 py-3 font-semibold">Status</th>
                            <th class="px-4 py-3 font-semibold"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                        <?php foreach ($unpaid as $row): ?>
                            <tr class="hover:bg-slate-50 dark:hover:bg-white/5 transition">
                                <td class="px-4 py-3 font-bold text-slate-900 dark:text-white whitespace-nowrap"><?= htmlspecialchars((string)$row['booking_code']) ?></td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
                                    <div class="font-semibold text-slate-900 dark:text-white whitespace-nowrap"><?= htmlspecialchars((string)$row['customer_name']) ?></div>
                                    <div class="text-[10px] text-slate-400"><?= htmlspecialchars((string)$row['customer_phone']) ?></div>
                                </td>
                                <td class="px-4 py-3 font-semibold text-slate-900 dark:text-white whitespace-nowrap"><?= formatRupiah((int)$row['total_price']) ?></td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300 whitespace-nowrap">
                                    <?= formatRupiah((int)$row['paid_total']) ?>
                                    <div class="text-[10px] text-amber-600 dark:text-amber-400">sisa <?= formatRupiah((int)$row['remaining']) ?></div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border <?= booking_payment_status_classes((string)$row['payment_status']) ?>">
                                        <?= htmlspecialchars(booking_payment_status_label((string)$row['payment_status'])) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <a href="pembayaran.php?booking=<?= (int)$row['id'] ?>" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-slate-200 dark:border-white/10 text-[10px] font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-white/5 transition">
                                        Catat
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Riwayat pembayaran -->
<div class="glass-panel rounded-2xl overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-200 dark:border-white/10">
        <h2 class="text-sm font-bold font-heading text-slate-900 dark:text-white">Riwayat Pembayaran</h2>
        <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">20 transaksi terakhir yang dicatat admin</p>
    </div>

    <?php if (!$recent_payments): ?>
        <div class="px-5 py-10 text-center">
            <i class="ri-wallet-3-line text-3xl text-slate-300 dark:text-slate-600"></i>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">Belum ada pembayaran yang dicatat.</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs min-w-[560px]">
                <thead class="bg-slate-50 dark:bg-[#141b29] text-slate-500 dark:text-slate-400">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Tanggal</th>
                        <th class="px-4 py-3 font-semibold">Kode</th>
                        <th class="px-4 py-3 font-semibold">Penyewa</th>
                        <th class="px-4 py-3 font-semibold">Metode</th>
                        <th class="px-4 py-3 font-semibold">Nominal</th>
                        <th class="px-4 py-3 font-semibold">Catatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                    <?php foreach ($recent_payments as $payment): ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-white/5 transition">
                            <td class="px-4 py-3 text-slate-500 dark:text-slate-400 whitespace-nowrap"><?= htmlspecialchars(date('d M Y H:i', strtotime((string)$payment['paid_at']))) ?></td>
                            <td class="px-4 py-3 font-semibold text-slate-900 dark:text-white whitespace-nowrap"><?= htmlspecialchars((string)($payment['booking_code'] ?? '-')) ?></td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300"><?= htmlspecialchars((string)($payment['customer_name'] ?? '-')) ?></td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold <?= $payment['method'] === 'qris' ? 'text-purple-600 dark:text-purple-400' : 'text-emerald-600 dark:text-emerald-400' ?>">
                                    <i class="<?= $payment['method'] === 'qris' ? 'ri-qr-code-line' : 'ri-money-dollar-circle-line' ?>"></i>
                                    <?= htmlspecialchars(booking_payment_label((string)$payment['method'])) ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 font-bold text-slate-900 dark:text-white whitespace-nowrap"><?= formatRupiah((int)$payment['amount']) ?></td>
                            <td class="px-4 py-3 text-slate-500 dark:text-slate-400"><?= htmlspecialchars((string)($payment['note'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<p class="text-[11px] text-slate-400 flex items-center gap-1.5">
    <i class="ri-information-line"></i>
    <span>Status bayar dihitung dari nominal pembayaran yang dicatat di sini, bukan dari pilihan customer saat booking.</span>
</p>

<?php admin_layout_end(); ?>
