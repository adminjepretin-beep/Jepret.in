<?php
// akun.php - Dashboard pengguna (customer) dengan sidebar + topbar seperti admin panel
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/bookings.php';
require_once __DIR__ . '/includes/testimonials.php';
require_once __DIR__ . '/data/cameras.php';
require_once __DIR__ . '/includes/user_layout.php';

$user = auth_require_user('akun.php');

$page_title = 'Dashboard Akun - Jepret.in';
$current_page = 'akun';

$bookings = bookings_all(['user_id' => (int)$user['id']]);
$csrf = admin_csrf_token();

$my_testimonials = testimonials_by_user((int)$user['id']);
$testimonial_error = '';
$testimonial_saved = isset($_GET['testimoni']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'testimonial') {
    $booking_id = (int)($_POST['booking_id'] ?? 0);
    $rating = max(1, min(5, (int)($_POST['rating'] ?? 5)));
    $message = trim((string)($_POST['message'] ?? ''));

    $target_booking = null;
    foreach ($bookings as $row) {
        if ((int)$row['id'] === $booking_id) {
            $target_booking = $row;
            break;
        }
    }

    if (!admin_verify_csrf($_POST['csrf'] ?? null)) {
        $testimonial_error = 'Sesi kamu kedaluwarsa. Coba kirim ulang testimoni ya.';
    } elseif (!$target_booking) {
        $testimonial_error = 'Pesanan yang mau kamu beri testimoni tidak ditemukan.';
    } elseif ($target_booking['status'] !== 'completed') {
        $testimonial_error = 'Testimoni bisa ditulis setelah pesanan kamu berstatus Selesai.';
    } elseif (mb_strlen($message) < 10) {
        $testimonial_error = 'Tulis testimoni minimal 10 karakter ya.';
    } elseif (testimonial_create((int)$user['id'], $booking_id, (string)$user['name'], $rating, $message)) {
        header('Location: akun.php?testimoni=1#testimoni');
        exit;
    } else {
        $testimonial_error = 'Pesanan ini sudah pernah kamu beri testimoni.';
    }

    $my_testimonials = testimonials_by_user((int)$user['id']);
}

// ---- Ubah profil ----
$profile_error = '';
$password_error = '';
$profile_saved = isset($_GET['profil']);
$password_saved = isset($_GET['password']);
$profile_values = [
    'name'  => (string)$user['name'],
    'email' => (string)$user['email'],
    'phone' => (string)$user['phone'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'profile') {
    $profile_values = [
        'name'  => trim((string)($_POST['name'] ?? '')),
        'email' => trim((string)($_POST['email'] ?? '')),
        'phone' => trim((string)($_POST['phone'] ?? '')),
    ];

    if (!admin_verify_csrf($_POST['csrf'] ?? null)) {
        $profile_error = 'Sesi kamu kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } else {
        $result = auth_update_profile((int)$user['id'], $_POST);
        if ($result['ok']) {
            header('Location: akun.php?profil=1#profil');
            exit;
        }
        $profile_error = $result['error'];
    }
}

// ---- Ganti password ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'password') {
    if (!admin_verify_csrf($_POST['csrf'] ?? null)) {
        $password_error = 'Sesi kamu kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } else {
        $result = auth_change_password(
            (int)$user['id'],
            (string)($_POST['current_password'] ?? ''),
            (string)($_POST['new_password'] ?? ''),
            (string)($_POST['confirm_password'] ?? '')
        );

        if ($result['ok']) {
            session_regenerate_id(true);
            header('Location: akun.php?password=1#keamanan');
            exit;
        }
        $password_error = $result['error'];
    }
}

$total_orders = count($bookings);
$total_spend = 0;
$active_orders = 0;
$cash_count = 0;
$qris_count = 0;
foreach ($bookings as $row) {
    if ($row['status'] !== 'cancelled') {
        $total_spend += (int)$row['total_price'];
    }
    if (in_array($row['status'], ['pending', 'confirmed', 'active'], true)) {
        $active_orders++;
    }
    if (($row['payment_method'] ?? 'cash') === 'qris') {
        $qris_count++;
    } else {
        $cash_count++;
    }
}

user_layout_start('Dashboard Akun', 'dashboard', $user, $csrf, 'Halo, ' . (string)$user['name']);
?>

    <!-- Kartu sambutan -->
    <div class="rounded-2xl border border-slate-200 dark:border-white/10 bg-white dark:bg-[#0b0e15] p-5 sm:p-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-purple-600 text-white flex items-center justify-center font-heading font-black text-lg shrink-0">
                    <?= htmlspecialchars(strtoupper(mb_substr((string)$user['name'], 0, 1))) ?>
                </div>
                <div>
                    <div class="text-sm font-bold font-heading text-slate-900 dark:text-white">Hai, <?= htmlspecialchars((string)$user['name']) ?></div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                        <?= htmlspecialchars((string)$user['email']) ?> &middot; <?= htmlspecialchars((string)$user['phone']) ?>
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="katalog.php" class="btn-theme-primary inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-xs font-bold">
                    <i class="ri-camera-3-line"></i>
                    <span>Sewa Kamera</span>
                </a>
                <form method="POST" action="logout.php">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                    <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-2.5 rounded-xl text-xs font-bold border border-slate-200 dark:border-white/10 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-white/5 transition">
                        <i class="ri-logout-box-r-line"></i>
                        <span>Keluar</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Statistik -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="rounded-2xl border border-slate-200 dark:border-white/10 bg-white dark:bg-[#0b0e15] p-5">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Pesanan</span>
                <i class="ri-file-list-3-line text-purple-500"></i>
            </div>
            <div class="text-2xl font-black font-heading text-slate-900 dark:text-white mt-2"><?= (int)$total_orders ?></div>
            <p class="text-[10px] text-slate-400 mt-1"><?= (int)$active_orders ?> masih berjalan</p>
        </div>

        <div class="rounded-2xl border border-slate-200 dark:border-white/10 bg-white dark:bg-[#0b0e15] p-5">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Nilai Sewa</span>
                <i class="ri-wallet-3-line text-emerald-500"></i>
            </div>
            <div class="text-2xl font-black font-heading text-slate-900 dark:text-white mt-2"><?= formatRupiah($total_spend) ?></div>
            <p class="text-[10px] text-slate-400 mt-1">Tanpa pesanan batal</p>
        </div>

        <div class="rounded-2xl border border-slate-200 dark:border-white/10 bg-white dark:bg-[#0b0e15] p-5">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Metode Favorit</span>
                <i class="ri-bank-card-line text-purple-500"></i>
            </div>
            <div class="text-2xl font-black font-heading text-slate-900 dark:text-white mt-2"><?= $qris_count >= $cash_count ? 'QRIS' : 'Cash' ?></div>
            <p class="text-[10px] text-slate-400 mt-1">Cash <?= $cash_count ?>x &middot; QRIS <?= $qris_count ?>x</p>
        </div>
    </div>

    <!-- Profil & keamanan akun -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div id="profil" class="user-scroll-target rounded-2xl border border-slate-200 dark:border-white/10 bg-white dark:bg-[#0b0e15] p-5 sm:p-6">
            <div class="flex items-center gap-2">
                <i class="ri-user-settings-line text-purple-500"></i>
                <h2 class="text-sm font-bold font-heading text-slate-900 dark:text-white">Ubah Profil</h2>
            </div>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                Nama, email, dan nomor WhatsApp dipakai untuk login dan konfirmasi booking.
            </p>

            <?php if ($profile_saved): ?>
                <div class="mt-4 flex items-start gap-2 rounded-xl border border-emerald-200 dark:border-emerald-500/30 bg-emerald-50 dark:bg-emerald-950/30 px-3 py-2.5 text-[11px] text-emerald-700 dark:text-emerald-300">
                    <i class="ri-checkbox-circle-line text-sm shrink-0 mt-0.5"></i>
                    <span>Profil kamu sudah diperbarui.</span>
                </div>
            <?php endif; ?>

            <?php if ($profile_error !== ''): ?>
                <div class="mt-4 flex items-start gap-2 rounded-xl border border-red-200 dark:border-red-500/30 bg-red-50 dark:bg-red-950/30 px-3 py-2.5 text-[11px] text-red-600 dark:text-red-300">
                    <i class="ri-error-warning-line text-sm shrink-0 mt-0.5"></i>
                    <span><?= htmlspecialchars($profile_error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="akun.php#profil" class="mt-4 space-y-3">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="action" value="profile">

                <div>
                    <label for="profileName" class="block text-[10px] font-semibold text-slate-600 dark:text-slate-300 mb-1">Nama Lengkap</label>
                    <input id="profileName" name="name" type="text" required minlength="2" maxlength="191"
                           value="<?= htmlspecialchars($profile_values['name']) ?>"
                           class="glass-input w-full rounded-xl px-3 py-2 text-[11px]">
                </div>

                <div>
                    <label for="profileEmail" class="block text-[10px] font-semibold text-slate-600 dark:text-slate-300 mb-1">Email</label>
                    <input id="profileEmail" name="email" type="email" required maxlength="191"
                           value="<?= htmlspecialchars($profile_values['email']) ?>"
                           class="glass-input w-full rounded-xl px-3 py-2 text-[11px]">
                </div>

                <div>
                    <label for="profilePhone" class="block text-[10px] font-semibold text-slate-600 dark:text-slate-300 mb-1">Nomor WhatsApp</label>
                    <input id="profilePhone" name="phone" type="tel" required inputmode="numeric" pattern="[0-9]{8,15}" maxlength="15"
                           value="<?= htmlspecialchars($profile_values['phone']) ?>"
                           class="glass-input w-full rounded-xl px-3 py-2 text-[11px]">
                    <p class="text-[10px] text-slate-400 mt-1">Angka saja, tanpa spasi atau tanda hubung.</p>
                </div>

                <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl btn-theme-primary text-[11px] font-bold">
                    <i class="ri-save-3-line"></i>
                    <span>Simpan Profil</span>
                </button>
            </form>
        </div>

        <div id="keamanan" class="user-scroll-target rounded-2xl border border-slate-200 dark:border-white/10 bg-white dark:bg-[#0b0e15] p-5 sm:p-6">
            <div class="flex items-center gap-2">
                <i class="ri-lock-password-line text-purple-500"></i>
                <h2 class="text-sm font-bold font-heading text-slate-900 dark:text-white">Ganti Password</h2>
            </div>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                Masukkan password lama dulu, lalu password baru minimal 8 karakter. Email dan nomor WhatsApp di atas tetap dipakai untuk login.
            </p>

            <?php if ($password_saved): ?>
                <div class="mt-4 flex items-start gap-2 rounded-xl border border-emerald-200 dark:border-emerald-500/30 bg-emerald-50 dark:bg-emerald-950/30 px-3 py-2.5 text-[11px] text-emerald-700 dark:text-emerald-300">
                    <i class="ri-checkbox-circle-line text-sm shrink-0 mt-0.5"></i>
                    <span>Password kamu sudah diganti. Pakai password baru saat login berikutnya.</span>
                </div>
            <?php endif; ?>

            <?php if ($password_error !== ''): ?>
                <div class="mt-4 flex items-start gap-2 rounded-xl border border-red-200 dark:border-red-500/30 bg-red-50 dark:bg-red-950/30 px-3 py-2.5 text-[11px] text-red-600 dark:text-red-300">
                    <i class="ri-error-warning-line text-sm shrink-0 mt-0.5"></i>
                    <span><?= htmlspecialchars($password_error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="akun.php#keamanan" class="mt-4 space-y-3">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="action" value="password">

                <div>
                    <label for="currentPassword" class="block text-[10px] font-semibold text-slate-600 dark:text-slate-300 mb-1">Password Sekarang</label>
                    <input id="currentPassword" name="current_password" type="password" required autocomplete="current-password"
                           class="glass-input w-full rounded-xl px-3 py-2 text-[11px]">
                </div>

                <div>
                    <label for="newPassword" class="block text-[10px] font-semibold text-slate-600 dark:text-slate-300 mb-1">Password Baru</label>
                    <input id="newPassword" name="new_password" type="password" required minlength="8" maxlength="72" autocomplete="new-password"
                           class="glass-input w-full rounded-xl px-3 py-2 text-[11px]" placeholder="Minimal 8 karakter">
                </div>

                <div>
                    <label for="confirmPassword" class="block text-[10px] font-semibold text-slate-600 dark:text-slate-300 mb-1">Ulangi Password Baru</label>
                    <input id="confirmPassword" name="confirm_password" type="password" required minlength="8" maxlength="72" autocomplete="new-password"
                           class="glass-input w-full rounded-xl px-3 py-2 text-[11px]">
                </div>

                <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl btn-theme-primary text-[11px] font-bold">
                    <i class="ri-shield-keyhole-line"></i>
                    <span>Ganti Password</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Riwayat pemesanan -->
    <div id="riwayat" class="user-scroll-target rounded-2xl border border-slate-200 dark:border-white/10 bg-white dark:bg-[#0b0e15] overflow-hidden">
        <div class="px-5 sm:px-6 py-4 border-b border-slate-200 dark:border-white/10 flex items-center justify-between">
            <div>
                <h2 class="text-sm font-bold font-heading text-slate-900 dark:text-white">Riwayat Pemesanan</h2>
                <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">Semua booking yang kamu buat dengan akun ini</p>
            </div>
            <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400"><?= (int)$total_orders ?> pesanan</span>
        </div>

        <?php if (!$bookings): ?>
            <div class="px-5 py-14 text-center">
                <i class="ri-calendar-2-line text-3xl text-slate-300 dark:text-slate-600"></i>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">Belum ada pemesanan. Yuk pilih kamera dulu.</p>
                <a href="katalog.php" class="inline-flex items-center gap-1.5 mt-4 px-4 py-2.5 rounded-xl btn-theme-primary text-[11px] font-bold">
                    <i class="ri-camera-3-line"></i>
                    <span>Lihat Katalog</span>
                </a>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs min-w-[760px]">
                    <thead class="bg-slate-50 dark:bg-[#141b29] text-slate-500 dark:text-slate-400">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Kode</th>
                            <th class="px-5 py-3 font-semibold">Kamera</th>
                            <th class="px-5 py-3 font-semibold">Jadwal</th>
                            <th class="px-5 py-3 font-semibold">Total</th>
                            <th class="px-5 py-3 font-semibold">Status</th>
                            <th class="px-5 py-3 font-semibold">Pembayaran</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                        <?php foreach ($bookings as $row): ?>
                            <tr class="hover:bg-slate-50 dark:hover:bg-white/5 transition align-top">
                                <td class="px-5 py-3 whitespace-nowrap">
                                    <div class="font-bold text-slate-900 dark:text-white"><?= htmlspecialchars((string)$row['booking_code']) ?></div>
                                    <div class="text-[10px] text-slate-400"><?= htmlspecialchars(date('d M Y', strtotime((string)$row['created_at']))) ?></div>
                                </td>
                                <td class="px-5 py-3 text-slate-600 dark:text-slate-300">
                                    <?= htmlspecialchars((string)$row['camera_name']) ?>
                                    <?php if (!empty($row['addon_labels'])): ?>
                                        <div class="text-[10px] text-slate-400 mt-0.5">+ <?= htmlspecialchars(implode(', ', (array)$row['addon_labels'])) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-3 text-slate-600 dark:text-slate-300 whitespace-nowrap">
                                    <?= htmlspecialchars(date('d M', strtotime((string)$row['start_date']))) ?>
                                    &ndash;
                                    <?= htmlspecialchars(date('d M Y', strtotime((string)$row['end_date']))) ?>
                                    <div class="text-[10px] text-slate-400"><?= (int)$row['total_days'] ?> hari</div>
                                </td>
                                <td class="px-5 py-3 font-semibold text-slate-900 dark:text-white whitespace-nowrap"><?= formatRupiah((int)$row['total_price']) ?></td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border <?= booking_status_classes((string)$row['status']) ?>">
                                        <?= htmlspecialchars(booking_status_label((string)$row['status'])) ?>
                                    </span>
                                </td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border <?= booking_payment_status_classes((string)$row['payment_status']) ?>">
                                        <?= htmlspecialchars(booking_payment_status_label((string)$row['payment_status'])) ?>
                                    </span>
                                    <?php if ((int)$row['remaining'] > 0): ?>
                                        <div class="text-[10px] text-amber-600 dark:text-amber-400 mt-1">Sisa <?= formatRupiah((int)$row['remaining']) ?></div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bantuan -->
    <div class="rounded-2xl border border-slate-200 dark:border-white/10 bg-white dark:bg-[#0b0e15] p-5 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-start gap-3">
            <i class="ri-whatsapp-line text-emerald-500 text-lg"></i>
            <div>
                <div class="text-xs font-bold text-slate-900 dark:text-white">Butuh bantuan atau konfirmasi pembayaran?</div>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Chat admin, sebutkan kode booking kamu biar cepat dicek.</p>
            </div>
        </div>
        <a href="https://wa.me/6283832104793?text=<?= rawurlencode('Halo Admin Jepret.in, saya mau tanya soal booking saya.') ?>"
           target="_blank" rel="noopener noreferrer"
           class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-[11px] font-bold transition">
            <i class="ri-whatsapp-fill"></i>
            <span>Chat Admin</span>
        </a>
    </div>

    <!-- Testimoni pengguna -->
    <div id="testimoni" class="user-scroll-target rounded-2xl border border-slate-200 dark:border-white/10 bg-white dark:bg-[#0b0e15] p-5 sm:p-6">
		<div class="flex flex-wrap items-start justify-between gap-3">
			<div>
				<h2 class="text-sm font-bold font-heading text-slate-900 dark:text-white">Cerita Kamu Selama Sewa</h2>
				<p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 max-w-xl leading-relaxed">
					Pesanan yang sudah selesai bisa kamu kasih rating dan testimoni. Ceritamu tampil di beranda buat bantu kreator lain yang mau sewa.
				</p>
			</div>
		</div>

		<?php if ($testimonial_saved): ?>
			<div class="mt-4 flex items-start gap-2 rounded-xl border border-emerald-200 dark:border-emerald-500/30 bg-emerald-50 dark:bg-emerald-950/30 px-3 py-2.5 text-[11px] text-emerald-700 dark:text-emerald-300">
				<i class="ri-checkbox-circle-line text-sm shrink-0 mt-0.5"></i>
				<span>Makasih! Testimoni kamu sudah tampil di beranda.</span>
			</div>
		<?php endif; ?>

		<?php if ($testimonial_error !== ''): ?>
			<div class="mt-4 flex items-start gap-2 rounded-xl border border-red-200 dark:border-red-500/30 bg-red-50 dark:bg-red-950/30 px-3 py-2.5 text-[11px] text-red-600 dark:text-red-300">
				<i class="ri-error-warning-line text-sm shrink-0 mt-0.5"></i>
				<span><?= htmlspecialchars($testimonial_error) ?></span>
			</div>
		<?php endif; ?>

		<?php $completed_bookings = array_values(array_filter($bookings, fn($row) => $row['status'] === 'completed')); ?>

		<?php if (empty($completed_bookings)): ?>
			<div class="mt-5 rounded-2xl border border-dashed border-slate-200 dark:border-white/10 px-4 py-6 text-center">
				<i class="ri-quill-pen-line text-2xl text-slate-300 dark:text-slate-600"></i>
				<p class="text-[11px] text-slate-500 dark:text-slate-400 mt-2">
					Belum ada pesanan berstatus <span class="font-semibold text-slate-700 dark:text-slate-300">Selesai</span>. Setelah kamera dikembalikan dan pesanan ditutup admin, form testimoni muncul di sini.
				</p>
			</div>
		<?php else: ?>
			<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mt-5">
				<?php foreach ($completed_bookings as $row): ?>
					<?php $existing = $my_testimonials[(int)$row['id']] ?? null; ?>
					<div class="rounded-2xl border border-slate-200 dark:border-white/10 bg-slate-50 dark:bg-[#10141d] p-4">
						<div class="flex flex-wrap items-start justify-between gap-2">
							<div>
								<div class="text-xs font-bold text-slate-900 dark:text-white"><?= htmlspecialchars((string)$row['camera_name']) ?></div>
								<div class="text-[10px] text-slate-400 mt-0.5">
									<?= htmlspecialchars((string)$row['booking_code']) ?> &middot;
									<?= htmlspecialchars(date('d M Y', strtotime((string)$row['start_date']))) ?>
								</div>
							</div>
							<span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border <?= booking_status_classes('completed') ?>">Selesai</span>
						</div>

						<?php if ($existing): ?>
							<div class="mt-3 flex text-amber-500 gap-1 text-xs">
								<?php for ($star = 1; $star <= 5; $star++): ?>
									<i class="<?= $star <= (int)$existing['rating'] ? 'ri-star-fill' : 'ri-star-line' ?>"></i>
								<?php endfor; ?>
							</div>
							<p class="mt-2 text-[11px] text-slate-600 dark:text-slate-300 leading-relaxed italic">
								"<?= htmlspecialchars((string)$existing['message']) ?>"
							</p>
							<p class="mt-2 text-[10px] text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
								<i class="ri-check-line"></i> Testimoni sudah tayang di beranda
							</p>
						<?php else: ?>
							<form method="POST" action="akun.php#testimoni" class="mt-3 space-y-2.5">
								<input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
								<input type="hidden" name="action" value="testimonial">
								<input type="hidden" name="booking_id" value="<?= (int)$row['id'] ?>">
								<div>
									<label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-300 mb-1">Rating</label>
									<select name="rating" class="glass-input w-full rounded-xl px-3 py-2 text-[11px]">
										<option value="5">5 bintang - Mantap banget</option>
										<option value="4">4 bintang - Bagus</option>
										<option value="3">3 bintang - Cukup</option>
										<option value="2">2 bintang - Kurang</option>
										<option value="1">1 bintang - Kecewa</option>
									</select>
								</div>
								<div>
									<label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-300 mb-1">Testimoni</label>
									<textarea name="message" rows="3" maxlength="600" required minlength="10"
										placeholder="Gimana kondisi kameranya? Proses sewa & serah-terimanya gimana?"
										class="glass-input w-full rounded-xl px-3 py-2 text-[11px] resize-none placeholder:text-slate-400"></textarea>
								</div>
								<button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl btn-theme-primary text-[11px] font-bold">
									<i class="ri-send-plane-line"></i>
									<span>Kirim Testimoni</span>
								</button>
							</form>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
    </div>

<?php user_layout_end(); ?>
