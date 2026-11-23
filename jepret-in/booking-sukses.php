<?php
// booking-sukses.php - Konfirmasi pemesanan + lanjut pembayaran via WhatsApp
require_once __DIR__ . '/data/cameras.php';
require_once __DIR__ . '/includes/bookings.php';

$kode = trim((string)($_GET['kode'] ?? ''));
$booking = $kode !== '' ? booking_find($kode) : null;

if (!$booking) {
    header('Location: katalog.php');
    exit;
}

$page_title = 'Booking ' . $booking['booking_code'] . ' - Jepret.in Surabaya';
$current_page = 'katalog';
$wa_number = '6283832104793';

$payment_method = (string)($booking['payment_method'] ?? 'cash');
$payment_label  = booking_payment_label($payment_method);
$camera_name    = (string)($booking['camera_name'] ?? '');
$addons_text    = !empty($booking['addon_labels']) ? implode(', ', (array)$booking['addon_labels']) : 'Hanya body + paket bawaan';

$wa_message = "Halo Admin Jepret.in Surabaya! Saya sudah booking online lewat website.\n\n"
    . "Kode Booking: " . $booking['booking_code'] . "\n"
    . "Nama: " . $booking['customer_name'] . "\n"
    . "WhatsApp: " . $booking['customer_phone'] . "\n"
    . "Kamera: " . $camera_name . "\n"
    . "Jadwal: " . date('d M Y', strtotime((string)$booking['start_date'])) . " s/d " . date('d M Y', strtotime((string)$booking['end_date'])) . " (" . (int)$booking['total_days'] . " hari)\n"
    . "Add-on: " . $addons_text . "\n"
    . "Pengambilan: " . $booking['pickup_method'] . "\n"
    . "Metode Pembayaran: " . $payment_label . "\n"
    . "Total: " . formatRupiah((int)$booking['total_price']) . "\n\n"
    . "Mohon konfirmasi ketersediaan unit dan instruksi pembayarannya ya. Terima kasih!";

$wa_link = 'https://wa.me/' . $wa_number . '?text=' . rawurlencode($wa_message);

include_once __DIR__ . '/includes/header.php';
?>

<section class="pt-10 pb-16 px-4 sm:px-6 lg:px-8 max-w-3xl mx-auto">

    <!-- Status -->
    <div class="text-center space-y-3 mb-8" data-aos="fade-down">
        <div class="w-14 h-14 mx-auto rounded-2xl bg-emerald-100 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-500/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
            <i class="ri-checkbox-circle-fill text-3xl"></i>
        </div>
        <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white font-heading">Booking Kamu Udah Kesimpan!</h1>
        <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 max-w-md mx-auto leading-relaxed">
            Sekarang tinggal lanjut konfirmasi ke WhatsApp admin biar unit kamu dikunci buat tanggal yang kamu pilih.
        </p>
        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-900 dark:bg-[#141b29] text-white text-xs border border-slate-800 dark:border-white/10">
            <span class="text-slate-400">Kode Booking</span>
            <span class="font-black tracking-wider"><?= htmlspecialchars($booking['booking_code']) ?></span>
        </div>
    </div>

    <!-- Rincian -->
    <div class="glass-panel rounded-3xl p-6 sm:p-7 space-y-4" data-aos="fade-up">
        <div class="flex items-center justify-between border-b border-slate-200 dark:border-white/10 pb-4">
            <h2 class="text-sm font-bold font-heading text-slate-900 dark:text-white">Rincian Pemesanan</h2>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[10px] font-bold border <?= booking_status_classes((string)$booking['status']) ?>">
                <?= htmlspecialchars(booking_status_label((string)$booking['status'])) ?>
            </span>
        </div>

        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-xs">
            <div>
                <dt class="text-[10px] uppercase tracking-wider text-slate-400 font-bold">Kamera</dt>
                <dd class="font-semibold text-slate-900 dark:text-white mt-0.5"><?= htmlspecialchars($camera_name) ?></dd>
            </div>
            <div>
                <dt class="text-[10px] uppercase tracking-wider text-slate-400 font-bold">Penyewa</dt>
                <dd class="font-semibold text-slate-900 dark:text-white mt-0.5"><?= htmlspecialchars((string)$booking['customer_name']) ?></dd>
            </div>
            <div>
                <dt class="text-[10px] uppercase tracking-wider text-slate-400 font-bold">Jadwal Sewa</dt>
                <dd class="font-semibold text-slate-900 dark:text-white mt-0.5">
                    <?= htmlspecialchars(date('d M Y', strtotime((string)$booking['start_date']))) ?>
                    &ndash;
                    <?= htmlspecialchars(date('d M Y', strtotime((string)$booking['end_date']))) ?>
                    (<?= (int)$booking['total_days'] ?> hari)
                </dd>
            </div>
            <div>
                <dt class="text-[10px] uppercase tracking-wider text-slate-400 font-bold">Add-on</dt>
                <dd class="font-semibold text-slate-900 dark:text-white mt-0.5"><?= htmlspecialchars($addons_text) ?></dd>
            </div>
            <div>
                <dt class="text-[10px] uppercase tracking-wider text-slate-400 font-bold">Pengambilan</dt>
                <dd class="font-semibold text-slate-900 dark:text-white mt-0.5"><?= htmlspecialchars((string)$booking['pickup_method']) ?></dd>
            </div>
            <div>
                <dt class="text-[10px] uppercase tracking-wider text-slate-400 font-bold">Metode Dipilih</dt>
                <dd class="font-semibold text-slate-900 dark:text-white mt-0.5"><?= htmlspecialchars($payment_label) ?></dd>
            </div>
            <div>
                <dt class="text-[10px] uppercase tracking-wider text-slate-400 font-bold">Status Pembayaran</dt>
                <dd class="mt-0.5">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border <?= booking_payment_status_classes((string)$booking['payment_status']) ?>">
                        <?= htmlspecialchars(booking_payment_status_label((string)$booking['payment_status'])) ?>
                    </span>
                    <span class="block text-[10px] text-slate-400 mt-1">Dikonfirmasi admin lewat WhatsApp</span>
                </dd>
            </div>
        </dl>

        <?php if (!empty($booking['discount'])): ?>
            <div class="flex items-center justify-between text-xs text-emerald-600 dark:text-emerald-400 font-semibold border-t border-slate-200 dark:border-white/10 pt-3">
                <span>Diskon durasi sewa</span>
                <span>- <?= formatRupiah((int)$booking['discount']) ?></span>
            </div>
        <?php endif; ?>

        <div class="flex items-baseline justify-between border-t border-slate-200 dark:border-white/10 pt-4">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-900 dark:text-white">Total Bayar</span>
            <span class="text-2xl font-black font-heading text-slate-900 dark:text-white"><?= formatRupiah((int)$booking['total_price']) ?></span>
        </div>
    </div>

    <!-- Cara pembayaran: hanya metode yang dipilih customer -->
    <div class="mt-6" data-aos="fade-up">
        <?php if ($payment_method === 'qris'): ?>
            <div class="glass-card rounded-2xl p-5 ring-2 ring-purple-500/40">
                <div class="flex items-center gap-2 mb-2">
                    <i class="ri-qr-code-line text-purple-600 dark:text-purple-400"></i>
                    <h3 class="text-xs font-bold font-heading text-slate-900 dark:text-white">Bayar dengan QRIS</h3>
                </div>
                <p class="text-[11px] text-slate-600 dark:text-slate-400 leading-relaxed">
                    Admin kirim gambar QRIS resmi lewat WhatsApp. Scan pakai mobile banking atau e-wallet, lalu kirim screenshot bukti transfernya ke chat admin.
                </p>
            </div>
        <?php else: ?>
            <div class="glass-card rounded-2xl p-5 ring-2 ring-purple-500/40">
                <div class="flex items-center gap-2 mb-2">
                    <i class="ri-money-dollar-circle-line text-purple-600 dark:text-purple-400"></i>
                    <h3 class="text-xs font-bold font-heading text-slate-900 dark:text-white">Bayar Cash di Studio</h3>
                </div>
                <p class="text-[11px] text-slate-600 dark:text-slate-400 leading-relaxed">
                    Bayar tunai saat ambil unit di studio Surabaya. Jangan lupa bawa KTP asli buat verifikasi ya.
                </p>
            </div>
        <?php endif; ?>
    </div>

    <!-- CTA WhatsApp -->
    <div class="mt-6 glass-panel rounded-3xl p-6 text-center space-y-3" data-aos="fade-up">
        <p class="text-xs text-slate-600 dark:text-slate-400">
            Kirim rincian booking ini ke admin lewat WhatsApp. Admin yang akan memverifikasi pembayaran
            dan menandainya lunas di sistem, jadi status di atas berubah setelah dikonfirmasi.
        </p>
        <a href="<?= htmlspecialchars($wa_link) ?>" target="_blank" rel="noopener noreferrer"
           class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-sm transition">
            <i class="ri-whatsapp-fill text-lg"></i>
            <span>Konfirmasi & Bayar via WhatsApp</span>
        </a>
        <div class="flex items-center justify-center gap-4 text-[11px] pt-1">
            <a href="katalog.php" class="text-slate-500 dark:text-slate-400 hover:text-purple-600 dark:hover:text-purple-400 transition flex items-center gap-1">
                <i class="ri-arrow-left-line"></i> Katalog kamera
            </a>
            <a href="index.php" class="text-slate-500 dark:text-slate-400 hover:text-purple-600 dark:hover:text-purple-400 transition flex items-center gap-1">
                <i class="ri-home-4-line"></i> Beranda
            </a>
        </div>
    </div>

    <p class="mt-6 text-center text-[10px] text-slate-400">
        Simpan kode booking <span class="font-bold"><?= htmlspecialchars($booking['booking_code']) ?></span> untuk cek status pesanan ke admin.
    </p>
</section>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
