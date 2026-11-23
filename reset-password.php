<?php
// reset-password.php - Buat password baru setelah pertanyaan keamanan terjawab
require_once __DIR__ . '/includes/auth.php';

admin_start_session();

$token = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));
$reset = auth_password_reset_token($token);
$error = '';
$done = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!admin_verify_csrf($_POST['csrf'] ?? null)) {
        $error = 'Sesi form sudah kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } elseif (!$reset) {
        $error = 'Link reset tidak valid atau sudah kedaluwarsa. Minta link baru ya.';
    } else {
        $result = auth_reset_password(
            $token,
            (string)($_POST['new_password'] ?? ''),
            (string)($_POST['confirm_password'] ?? '')
        );

        if ($result['ok']) {
            $done = true;
        } else {
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
    <title>Reset Password - Jepret.in</title>

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
                <h1 class="text-lg font-extrabold font-heading text-slate-900 dark:text-white">Buat Password Baru</h1>
            </div>

            <?php if ($done): ?>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">
                    Password akun kamu sudah diganti. Silakan masuk memakai password baru.
                </p>

                <div class="mt-4 flex items-start gap-2 rounded-xl border border-emerald-200 dark:border-emerald-500/30 bg-emerald-50 dark:bg-emerald-950/30 px-3 py-2.5 text-[11px] text-emerald-700 dark:text-emerald-300">
                    <i class="ri-checkbox-circle-line text-sm shrink-0 mt-0.5"></i>
                    <span>Sesi reset ini sudah dipakai dan tidak bisa digunakan lagi.</span>
                </div>

                <a href="login.php" class="mt-5 inline-flex items-center justify-center gap-1.5 w-full py-3 rounded-xl btn-theme-primary text-sm font-bold">
                    <i class="ri-login-box-line"></i>
                    <span>Masuk Sekarang</span>
                </a>
            <?php elseif (!$reset): ?>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">
                    Sesi reset ini tidak valid, sudah dipakai, atau sudah lewat 60 menit.
                </p>

                <div class="mt-4 flex items-start gap-2 rounded-xl border border-red-200 dark:border-red-500/30 bg-red-50 dark:bg-red-950/30 px-3 py-2.5 text-[11px] text-red-700 dark:text-red-300">
                    <i class="ri-error-warning-line text-sm shrink-0 mt-0.5"></i>
                    <span>Ulangi proses dari halaman lupa password.</span>
                </div>

                <a href="lupa-password.php" class="mt-5 inline-flex items-center justify-center gap-1.5 w-full py-3 rounded-xl btn-theme-primary text-sm font-bold">
                    <i class="ri-refresh-line"></i>
                    <span>Ulangi dari Awal</span>
                </a>
            <?php else: ?>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed mb-5">
                    Masukkan password baru minimal 8 karakter, lalu ulangi untuk memastikan tidak ada typo.
                </p>

                <?php if ($error !== ''): ?>
                    <div class="mb-4 flex items-start gap-2 rounded-xl border border-red-200 dark:border-red-500/30 bg-red-50 dark:bg-red-950/30 px-3 py-2.5 text-[11px] text-red-700 dark:text-red-300">
                        <i class="ri-error-warning-line text-sm shrink-0 mt-0.5"></i>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="reset-password.php" class="space-y-3">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                    <div>
                        <label for="newPassword" class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">Password Baru</label>
                        <div class="relative">
                            <i class="ri-lock-line text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 text-sm pointer-events-none"></i>
                            <input id="newPassword" name="new_password" type="password" required minlength="8" maxlength="72"
                                   autocomplete="new-password"
                                   class="glass-input w-full pl-9 pr-3 py-2.5 rounded-xl text-sm"
                                   placeholder="Minimal 8 karakter">
                        </div>
                    </div>

                    <div>
                        <label for="confirmPassword" class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">Ulangi Password Baru</label>
                        <div class="relative">
                            <i class="ri-lock-2-line text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 text-sm pointer-events-none"></i>
                            <input id="confirmPassword" name="confirm_password" type="password" required minlength="8" maxlength="72"
                                   autocomplete="new-password"
                                   class="glass-input w-full pl-9 pr-3 py-2.5 rounded-xl text-sm">
                        </div>
                    </div>

                    <button type="submit" class="btn-theme-primary w-full py-3 rounded-xl text-sm font-bold flex items-center justify-center gap-2 shadow-sm">
                        <i class="ri-save-3-line text-base"></i>
                        <span>Simpan Password Baru</span>
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <a href="index.php" class="mt-5 flex items-center justify-center gap-1.5 text-[11px] font-semibold text-slate-500 dark:text-slate-400 hover:text-purple-600 dark:hover:text-purple-400 transition">
            <i class="ri-arrow-left-line"></i>
            <span>Balik ke website</span>
        </a>
    </div>

</body>
</html>
