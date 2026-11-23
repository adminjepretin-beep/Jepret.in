<?php
// lupa-password.php - Lupa password: cocokkan nomor WhatsApp + email, lalu link reset dikirim ke WhatsApp
require_once __DIR__ . '/includes/auth.php';

admin_start_session();

if (auth_current_user()) {
    header('Location: akun.php');
    exit;
}

$error = '';
$success = '';
$phone_value = '';
$email_value = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone_value = trim((string)($_POST['phone'] ?? ''));
    $email_value = trim((string)($_POST['email'] ?? ''));
    $ip = login_client_ip();

    if (!admin_verify_csrf($_POST['csrf'] ?? null)) {
        $error = 'Sesi form sudah kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } elseif (strlen(auth_normalize_phone($phone_value)) < 8) {
        $error = 'Masukkan nomor WhatsApp yang kamu pakai saat daftar, contoh: 08123456789.';
    } elseif (!filter_var($email_value, FILTER_VALIDATE_EMAIL)) {
        $error = 'Masukkan email yang kamu pakai saat daftar, contoh: nama@email.com.';
    } elseif (password_reset_throttled($ip, $phone_value)) {
        $error = 'Terlalu banyak percobaan dari jaringan ini. Coba lagi dalam 10 menit.';
    } else {
        $result = auth_request_password_reset_whatsapp($phone_value, $email_value);

        if ($result['ok']) {
            $success = 'Link reset sudah dikirim ke WhatsApp ' . $result['phone'] . '. Buka WhatsApp, lalu ketuk link di pesan itu.';
            $phone_value = '';
            $email_value = '';
        } else {
            // Catat hanya percobaan yang gagal, supaya permintaan yang benar tidak ikut terkunci.
            password_reset_log_attempt($ip, $phone_value);
            $error = $result['error'];
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
    <title>Lupa Password - Jepret.in</title>

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
                <i class="ri-shield-keyhole-line text-purple-600 dark:text-purple-400"></i>
                <h1 class="text-lg font-extrabold font-heading text-slate-900 dark:text-white">Lupa Password</h1>
            </div>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed mb-5">
                Isi nomor WhatsApp dan email yang kamu pakai saat daftar. Kalau cocok, link untuk membuat password baru dikirim ke WhatsApp kamu.
            </p>

            <?php if ($error !== ''): ?>
                <div class="mb-4 flex items-start gap-2 rounded-xl border border-red-200 dark:border-red-500/30 bg-red-50 dark:bg-red-950/30 px-3 py-2.5 text-[11px] text-red-700 dark:text-red-300">
                    <i class="ri-error-warning-line text-sm shrink-0 mt-0.5"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($success !== ''): ?>
                <div class="mb-4 flex items-start gap-2 rounded-xl border border-emerald-200 dark:border-emerald-500/30 bg-emerald-50 dark:bg-emerald-950/30 px-3 py-2.5 text-[11px] text-emerald-700 dark:text-emerald-300">
                    <i class="ri-whatsapp-line text-sm shrink-0 mt-0.5"></i>
                    <span><?= htmlspecialchars($success) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="lupa-password.php" class="space-y-3">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">

                <div>
                    <label for="email" class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">Email Akun</label>
                    <div class="relative">
                        <i class="ri-mail-line text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 text-sm pointer-events-none"></i>
                        <input id="email" name="email" type="email" required autocomplete="email"
                               value="<?= htmlspecialchars($email_value) ?>"
                               class="glass-input w-full pl-9 pr-3 py-2.5 rounded-xl text-sm"
                               placeholder="nama@email.com">
                    </div>
                </div>

                <div>
                    <label for="phone" class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">No. WhatsApp Akun</label>
                    <div class="relative">
                        <i class="ri-whatsapp-line text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 text-sm pointer-events-none"></i>
                        <input id="phone" name="phone" type="tel" inputmode="numeric" required autocomplete="tel"
                               value="<?= htmlspecialchars($phone_value) ?>"
                               class="glass-input w-full pl-9 pr-3 py-2.5 rounded-xl text-sm"
                               placeholder="08123456789">
                    </div>
                </div>

                <button type="submit" class="btn-theme-primary w-full py-3 rounded-xl text-sm font-bold flex items-center justify-center gap-2 shadow-sm">
                    <i class="ri-whatsapp-line text-base"></i>
                    <span>Kirim Link Reset ke WhatsApp</span>
                </button>
            </form>

            <p class="mt-4 text-[10px] text-slate-500 dark:text-slate-400 leading-relaxed">
                Nomor atau email tidak cocok? Coba nomor lain yang mungkin kamu pakai saat daftar. Kalau masih tidak bisa, hubungi admin lewat WhatsApp untuk dibuatkan link reset.
            </p>

            <p class="mt-3 text-center text-[11px] text-slate-500 dark:text-slate-400">
                Ingat password kamu?
                <a href="login.php" class="font-bold text-purple-600 dark:text-purple-400 hover:underline">Masuk di sini</a>
            </p>
        </div>

        <a href="index.php" class="mt-5 flex items-center justify-center gap-1.5 text-[11px] font-semibold text-slate-500 dark:text-slate-400 hover:text-purple-600 dark:hover:text-purple-400 transition">
            <i class="ri-arrow-left-line"></i>
            <span>Balik ke website</span>
        </a>
    </div>

</body>
</html>
