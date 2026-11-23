<?php
// login.php - Login terpadu: pengguna (customer) & admin
require_once __DIR__ . '/includes/auth.php';

admin_start_session();

if (auth_current_user()) {
    header('Location: akun.php');
    exit;
}
if (admin_is_logged_in()) {
    header('Location: admin/index.php');
    exit;
}

$next = auth_safe_next($_GET['next'] ?? null, 'akun.php');
$error = '';
$identity_value = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $next = auth_safe_next($_POST['next'] ?? null, 'akun.php');
    $identity = trim((string)($_POST['identity'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $identity_value = $identity;

    // Rate limit per IP + per akun, bukan cuma per sesi: hapus cookie tidak mereset jatah.
    $login_ip  = login_client_ip();
    $login_key = login_identifier_key($identity);

    if (!admin_verify_csrf($_POST['csrf'] ?? null)) {
        $error = 'Sesi form sudah kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } elseif (login_throttled($login_ip, $login_key)) {
        $error = 'Terlalu banyak percobaan gagal. Coba lagi dalam ' . login_lockout_remaining($login_ip, $login_key) . ' detik.';
    } else {

        // 1) Admin (kredensial dari environment variable, lihat config/admin.php)
        $config = admin_config();
        if (!empty($config['configured']) && $identity !== '' && hash_equals((string)$config['username'], $identity) && password_verify($password, (string)$config['password_hash'])) {
            login_throttle_clear($login_ip, $login_key);
            session_regenerate_id(true);
            unset($_SESSION['user_id']);
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username']  = (string)$config['username'];
            $_SESSION['admin_fails']     = [];
            unset($_SESSION['admin_csrf']);

            $target = $next !== 'akun.php' ? $next : 'admin/index.php';
            header('Location: ' . $target);
            exit;
        }

        if (empty($config['configured']) && $identity !== '' && hash_equals((string)$config['username'], $identity)) {
            error_log('Jepret.in: login admin ditolak karena ADMIN_PASSWORD_HASH belum diisi (lihat config/admin.php).');
        }

        // 2) Pengguna (tabel users)
        $pdo = Database::getConnection();
        $account = null;
        if ($pdo && $identity !== '') {
            try {
                $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? OR phone = ? LIMIT 1');
                $stmt->execute([$identity, $identity]);
                $account = $stmt->fetch();
            } catch (Throwable $e) {
                $account = null;
            }
        }

        if ($account && password_verify($password, (string)$account['password_hash'])) {
            login_throttle_clear($login_ip, $login_key);
            auth_login_user((int)$account['id']);
            header('Location: ' . $next);
            exit;
        }

        $_SESSION['admin_fails'][] = time();
        login_throttle_record($login_ip, $login_key);
        usleep(400000);

        if (login_throttled($login_ip, $login_key)) {
            $error = 'Terlalu banyak percobaan gagal. Login dikunci sementara ' . login_lockout_remaining($login_ip, $login_key) . ' detik.';
        } else {
            $error = 'Email/no. WhatsApp atau password salah.';
        }
    }
}

$csrf = admin_csrf_token();
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Masuk - Jepret.in</title>

    <link rel="icon" type="image/png" sizes="512x512" href="assets/images/favicon.png">

    <script>
        (function() {
            if (localStorage.theme === 'dark') {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        heading: ['"Outfit"', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet" />
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-[#f8fafc] text-slate-900 dark:bg-[#090a0f] dark:text-slate-100 antialiased min-h-screen flex items-center justify-center px-4 py-10">

    <div class="w-full max-w-sm">

        <a href="index.php" class="flex items-center justify-center gap-3 mb-6">
            <div class="w-11 h-11 rounded-xl overflow-hidden border border-slate-200 dark:border-white/15 bg-black shrink-0">
                <img src="assets/images/logo.jpg" alt="Jepret.in Logo" class="w-full h-full object-cover">
            </div>
            <span class="font-heading font-extrabold text-2xl tracking-tight text-slate-900 dark:text-white">
                Jepret<span class="text-purple-600 dark:text-purple-400">.in</span>
            </span>
        </a>

        <div class="glass-card rounded-3xl p-7">
            <div class="flex items-center gap-2 mb-1">
                <i class="ri-user-3-line text-purple-600 dark:text-purple-400"></i>
                <h1 class="text-lg font-extrabold font-heading text-slate-900 dark:text-white">Masuk</h1>
            </div>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed mb-5">
                Satu pintu untuk pengguna dan admin. Setelah masuk, riwayat sewa kamu tersimpan di dashboard akun.
            </p>

            <?php if (isset($_GET['logout'])): ?>
                <div class="mb-4 flex items-start gap-2 rounded-xl border border-emerald-200 dark:border-emerald-500/30 bg-emerald-50 dark:bg-emerald-950/30 px-3 py-2.5 text-[11px] text-emerald-700 dark:text-emerald-300">
                    <i class="ri-checkbox-circle-line text-sm shrink-0 mt-0.5"></i>
                    <span>Kamu sudah keluar. Sampai jumpa lagi!</span>
                </div>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <div class="mb-4 flex items-start gap-2 rounded-xl border border-red-200 dark:border-red-500/30 bg-red-50 dark:bg-red-950/30 px-3 py-2.5 text-[11px] text-red-700 dark:text-red-300">
                    <i class="ri-error-warning-line text-sm shrink-0 mt-0.5"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php" class="space-y-3">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="next" value="<?= htmlspecialchars($next) ?>">

                <div>
                    <label for="identity" class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">Email / No. WhatsApp</label>
                    <div class="relative">
                        <i class="ri-user-3-line text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 text-sm pointer-events-none"></i>
                        <input id="identity" name="identity" type="text" required autocomplete="username"
                               value="<?= htmlspecialchars($identity_value) ?>"
                               class="glass-input w-full pl-9 pr-3 py-2.5 rounded-xl text-sm"
                               placeholder="nama@email.com atau 0812...">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">Password</label>
                    <div class="relative">
                        <i class="ri-lock-line text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 text-sm pointer-events-none"></i>
                        <input id="password" name="password" type="password" required autocomplete="current-password"
                               class="glass-input w-full pl-9 pr-3 py-2.5 rounded-xl text-sm"
                               placeholder="&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;">
                    </div>
                </div>

                <button type="submit" class="btn-theme-primary w-full py-3 rounded-xl text-sm font-bold flex items-center justify-center gap-2 shadow-sm">
                    <i class="ri-login-box-line text-base"></i>
                    <span>Masuk</span>
                </button>
            </form>

            <p class="mt-3 text-center text-[11px]">
                <a href="lupa-password.php" class="font-semibold text-slate-500 dark:text-slate-400 hover:text-purple-600 dark:hover:text-purple-400 transition">Lupa password?</a>
            </p>

            <p class="mt-3 text-center text-[11px] text-slate-500 dark:text-slate-400">
                Belum punya akun?
                <a href="daftar.php<?= $next !== 'akun.php' ? '?next=' . urlencode($next) : '' ?>" class="font-bold text-purple-600 dark:text-purple-400 hover:underline">Daftar gratis</a>
            </p>
        </div>

        <a href="index.php" class="mt-5 flex items-center justify-center gap-1.5 text-[11px] font-semibold text-slate-500 dark:text-slate-400 hover:text-purple-600 dark:hover:text-purple-400 transition">
            <i class="ri-arrow-left-line"></i>
            <span>Balik ke website</span>
        </a>
    </div>

</body>
</html>
