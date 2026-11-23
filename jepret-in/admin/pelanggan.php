<?php
// admin/pelanggan.php - Data pelanggan hasil pemesanan website
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin_layout.php';
require_once __DIR__ . '/../includes/bookings.php';
require_once __DIR__ . '/../data/cameras.php';

require_admin_login();

$csrf = admin_csrf_token();
$customers = bookings_customers();
$total_spend = array_sum(array_column($customers, 'total_spend'));

admin_layout_start('Pelanggan', 'pelanggan', $csrf, 'Data penyewa dari nomor WhatsApp yang dipakai saat booking');
?>

<div class="flex flex-wrap items-center justify-between gap-3">
    <div class="flex items-center gap-2 text-[11px]">
        <span class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-white/10 bg-white dark:bg-[#10141d] text-slate-600 dark:text-slate-300 font-bold">
            <?= count($customers) ?> pelanggan
        </span>
        <span class="px-3 py-1.5 rounded-lg border border-emerald-200 dark:border-emerald-500/30 bg-emerald-50 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-300 font-bold">
            <?= formatRupiah((int)$total_spend) ?> nilai sewa
        </span>
    </div>
</div>

<div class="glass-panel rounded-2xl overflow-hidden">
    <?php if (empty($customers)): ?>
        <div class="px-5 py-14 text-center">
            <i class="ri-team-line text-3xl text-slate-300 dark:text-slate-600"></i>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">
                Belum ada data pelanggan. Data akan muncul setelah ada pemesanan dari website.
            </p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs min-w-[820px]">
                <thead class="bg-slate-50 dark:bg-[#141b29] text-slate-500 dark:text-slate-400">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Pelanggan</th>
                        <th class="px-4 py-3 font-semibold">Kontak</th>
                        <th class="px-4 py-3 font-semibold">Pesanan</th>
                        <th class="px-4 py-3 font-semibold">Nilai Sewa</th>
                        <th class="px-4 py-3 font-semibold">Booking Terakhir</th>
                        <th class="px-4 py-3 font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                    <?php foreach ($customers as $customer): ?>
                        <?php $wa_digits = preg_replace('/\D+/', '', $customer['phone']) ?? ''; ?>
                        <tr class="align-top hover:bg-slate-50 dark:hover:bg-white/5 transition">
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-900 dark:text-white"><?= htmlspecialchars($customer['name']) ?></div>
                                <?php if ($customer['instagram'] !== ''): ?>
                                    <div class="text-[10px] text-slate-400 mt-0.5"><?= htmlspecialchars($customer['instagram']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
                                <a href="https://wa.me/<?= htmlspecialchars($wa_digits) ?>" target="_blank" rel="noopener"
                                   class="inline-flex items-center gap-1 text-emerald-600 dark:text-emerald-400 hover:underline">
                                    <i class="ri-whatsapp-line"></i><?= htmlspecialchars($customer['phone']) ?>
                                </a>
                            </td>
                            <td class="px-4 py-3 font-bold text-slate-900 dark:text-white"><?= (int)$customer['orders'] ?>x</td>
                            <td class="px-4 py-3 font-semibold text-slate-900 dark:text-white whitespace-nowrap"><?= formatRupiah((int)$customer['total_spend']) ?></td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300 whitespace-nowrap">
                                <?= $customer['last_order'] !== '' ? htmlspecialchars(date('d M Y', strtotime($customer['last_order']))) : '-' ?>
                                <div class="text-[10px] text-slate-400 mt-0.5"><?= htmlspecialchars(implode(', ', $customer['codes'])) ?></div>
                            </td>
                            <td class="px-4 py-3">
                                <?php if ($wa_digits !== ''): ?>
                                    <a href="https://wa.me/<?= htmlspecialchars($wa_digits) ?>?text=<?= rawurlencode('Halo ' . $customer['name'] . ', ini admin Jepret.in Surabaya.') ?>"
                                       target="_blank" rel="noopener"
                                       class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-emerald-200 dark:border-emerald-500/30 text-emerald-700 dark:text-emerald-300 text-[10px] font-bold hover:bg-emerald-50 dark:hover:bg-emerald-950/30 transition">
                                        <i class="ri-chat-3-line"></i> Chat
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<p class="text-[11px] text-slate-400 flex items-center gap-1.5">
    <i class="ri-information-line"></i>
    <span>Data pelanggan hanya mencakup penyewa yang booking lewat website. Booking lewat WhatsApp langsung belum tercatat di sini.</span>
</p>

<?php admin_layout_end(); ?>
