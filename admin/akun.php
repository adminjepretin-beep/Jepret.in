<?php
// admin/akun.php - Akun pengguna terdaftar + kirim link reset password
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin_layout.php';
require_once __DIR__ . '/../includes/bookings.php';
require_once __DIR__ . '/../data/cameras.php';

require_admin_login();

$csrf = admin_csrf_token();
$notice = null;

$pdo = Database::getConnection();
$users = [];
if ($pdo) {
    try {
        $users = $pdo->query('SELECT u.id, u.name, u.email, u.phone, u.created_at,
                                     (SELECT COUNT(*) FROM bookings b WHERE b.user_id = u.id) AS orders
                              FROM users u
                              ORDER BY u.id DESC')->fetchAll();
    } catch (Throwable $e) {
        $users = [];
    }
}

// Buat link reset password. Tidak perlu email: link dikirim admin lewat WhatsApp.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset_link') {
    if (!admin_verify_csrf($_POST['csrf'] ?? null)) {
        $notice = ['type' => 'error', 'text' => 'Sesi form kedaluwarsa. Coba lagi.'];
    } else {
        $user_id = (int)($_POST['user_id'] ?? 0);
        $target = null;

        foreach ($users as $row) {
            if ((int)$row['id'] === $user_id) {
                $target = $row;
                break;
            }
        }

        if (!$target) {
            $notice = ['type' => 'error', 'text' => 'Akun tidak ditemukan.'];
        } else {
            $token = auth_create_password_reset_token($user_id);
            if ($token === null) {
                $notice = ['type' => 'error', 'text' => 'Link reset gagal dibuat. Coba lagi.'];
            } else {
                $_SESSION['admin_reset_link'] = [
                    'name'  => (string)$target['name'],
                    'phone' => (string)$target['phone'],
                    'url'   => app_base_url() . '/reset-password.php?token=' . rawurlencode($token),
                ];
                header('Location: akun.php?reset=1');
                exit;
            }
        }
    }
}

// Link hanya ditampilkan sekali (tidak disimpan di halaman).
$reset_link = null;
if (!empty($_SESSION['admin_reset_link'])) {
    $reset_link = $_SESSION['admin_reset_link'];
    unset($_SESSION['admin_reset_link']);
}

$wa_phone = '';
$wa_text = '';
if ($reset_link) {
    $wa_phone = preg_replace('/\D+/', '', (string)$reset_link['phone']);
    if (str_starts_with($wa_phone, '0')) {
        $wa_phone = '62' . substr($wa_phone, 1);
    } elseif (str_starts_with($wa_phone, '8')) {
        $wa_phone = '62' . $wa_phone;
    }

    $wa_text = 'Halo ' . $reset_link['name'] . ', ini link untuk mengatur ulang password akun Jepret.in kamu: '
        . $reset_link['url'] . ' (berlaku 60 menit dan hanya bisa dipakai sekali).';
}

admin_layout_start('Akun Pengguna', 'akun', $csrf, 'Akun login penyewa + kirim link reset password lewat WhatsApp');
?>

<?php if ($notice): ?>
    <div class="flex items-start gap-2 rounded-xl border px-3 py-2.5 text-[11px] <?= $notice['type'] === 'error' ? 'border-red-200 dark:border-red-500/30 bg-red-50 dark:bg-red-950/30 text-red-600 dark:text-red-300' : 'border-emerald-200 dark:border-emerald-500/30 bg-emerald-50 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-300' ?>">
        <i class="ri-error-warning-line text-sm shrink-0 mt-0.5"></i>
        <span><?= htmlspecialchars($notice['text']) ?></span>
    </div>
<?php endif; ?>

<?php if ($reset_link): ?>
    <div class="glass-panel rounded-2xl p-5 sm:p-6 space-y-3">
        <div class="flex items-start gap-3">
            <div class="w-9 h-9 rounded-xl bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                <i class="ri-link"></i>
            </div>
            <div>
                <h2 class="text-sm font-bold font-heading text-slate-900 dark:text-white">Link reset untuk <?= htmlspecialchars((string)$reset_link['name']) ?></h2>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 leading-relaxed">
                    Kirim link ini ke penyewa lewat WhatsApp. Berlaku 60 menit, sekali pakai, dan hanya ditampilkan sekali di halaman ini.
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <input id="resetLinkInput" type="text" readonly value="<?= htmlspecialchars((string)$reset_link['url']) ?>"
                   class="glass-input flex-1 min-w-[260px] rounded-xl px-3 py-2 text-[11px] font-mono">
            <button type="button" id="copyResetLink"
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border border-slate-200 dark:border-white/10 text-[11px] font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-white/5 transition">
                <i class="ri-file-copy-line"></i>
                <span id="copyResetLabel">Salin</span>
            </button>
            <?php if ($wa_phone !== ''): ?>
                <a href="https://wa.me/<?= htmlspecialchars($wa_phone) ?>?text=<?= rawurlencode($wa_text) ?>"
                   target="_blank" rel="noopener noreferrer"
                   class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-[11px] font-bold transition">
                    <i class="ri-whatsapp-fill"></i>
                    <span>Kirim via WhatsApp</span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <script>
        (function () {
            var input = document.getElementById('resetLinkInput');
            var button = document.getElementById('copyResetLink');
            var label = document.getElementById('copyResetLabel');
            if (!input || !button) return;

            button.addEventListener('click', function () {
                var done = function () {
                    if (label) {
                        label.textContent = 'Tersalin';
                        setTimeout(function () { label.textContent = 'Salin'; }, 1800);
                    }
                };

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(input.value).then(done, function () {
                        input.select();
                        document.execCommand('copy');
                        done();
                    });
                } else {
                    input.select();
                    document.execCommand('copy');
                    done();
                }
            });
        })();
    </script>
<?php endif; ?>

<div class="flex flex-wrap items-center justify-between gap-3">
    <div class="flex items-center gap-2 text-[11px]">
        <span class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-white/10 bg-white dark:bg-[#10141d] text-slate-600 dark:text-slate-300 font-bold">
            <?= count($users) ?> akun terdaftar
        </span>
    </div>
</div>

<div class="glass-panel rounded-2xl overflow-hidden">
    <?php if (empty($users)): ?>
        <div class="px-5 py-14 text-center">
            <i class="ri-user-settings-line text-3xl text-slate-300 dark:text-slate-600"></i>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">
                Belum ada akun pengguna. Akun muncul setelah ada yang mendaftar lewat halaman daftar.
            </p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs min-w-[820px]">
                <thead class="bg-slate-50 dark:bg-[#141b29] text-slate-500 dark:text-slate-400">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Nama</th>
                        <th class="px-4 py-3 font-semibold">Email</th>
                        <th class="px-4 py-3 font-semibold">WhatsApp</th>
                        <th class="px-4 py-3 font-semibold">Pesanan</th>
                        <th class="px-4 py-3 font-semibold">Terdaftar</th>
                        <th class="px-4 py-3 font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                    <?php foreach ($users as $row): ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-white/5 transition">
                            <td class="px-4 py-3">
                                <div class="font-bold text-slate-900 dark:text-white"><?= htmlspecialchars((string)$row['name']) ?></div>
                            </td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300"><?= htmlspecialchars((string)$row['email']) ?></td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300 whitespace-nowrap"><?= htmlspecialchars((string)$row['phone']) ?></td>
                            <td class="px-4 py-3 font-bold text-slate-900 dark:text-white"><?= (int)$row['orders'] ?>x</td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300 whitespace-nowrap">
                                <?= $row['created_at'] ? htmlspecialchars(date('d M Y', strtotime((string)$row['created_at']))) : '-' ?>
                            </td>
                            <td class="px-4 py-3">
                                <form method="POST" action="akun.php" class="inline">
                                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                                    <input type="hidden" name="action" value="reset_link">
                                    <input type="hidden" name="user_id" value="<?= (int)$row['id'] ?>">
                                    <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 dark:border-white/10 text-[10px] font-bold text-slate-600 dark:text-slate-300 hover:bg-white dark:hover:bg-white/5 transition">
                                        <i class="ri-lock-unlock-line"></i>
                                        <span>Buat Link Reset</span>
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

<p class="text-[10px] text-slate-400 dark:text-slate-500 flex items-start gap-1.5">
    <i class="ri-information-line"></i>
    <span>Link reset hanya untuk akun yang mendaftar lewat website. Penyewa yang booking lewat WhatsApp langsung belum punya akun login.</span>
</p>

<?php admin_layout_end(); ?>
