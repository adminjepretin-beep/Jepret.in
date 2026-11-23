<?php
// daftar.php - Registrasi akun pengguna Jepret.in
require_once __DIR__ . '/includes/auth.php';

admin_start_session();

if (auth_current_user()) {
    header('Location: akun.php');
    exit;
}

$next = auth_safe_next($_GET['next'] ?? null, 'akun.php');
$errors = [];
$values = ['name' => '', 'email' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $next = auth_safe_next($_POST['next'] ?? null, 'akun.php');

    $values['name']  = trim((string)($_POST['name'] ?? ''));
    $values['email'] = trim((string)($_POST['email'] ?? ''));
    $values['phone'] = trim((string)($_POST['phone'] ?? ''));
    $password        = (string)($_POST['password'] ?? '');
    $password_confirm = (string)($_POST['password_confirm'] ?? '');

    if (!admin_verify_csrf($_POST['csrf'] ?? null)) {
        $errors[] = 'Sesi form sudah kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    }

    if (mb_strlen($values['name']) < 2) {
        $errors[] = 'Nama minimal 2 karakter.';
    }
    if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email belum benar.';
    }
    $phone_digits = auth_normalize_phone($values['phone']);
    if (strlen($phone_digits) < 8) {
        $errors[] = 'Nomor WhatsApp minimal 8 angka.';
    }
    $values['phone'] = $phone_digits;
    if (strlen($password) < 8) {
        $errors[] = 'Password minimal 8 karakter.';
    }
    if ($password !== $password_confirm) {
        $errors[] = 'Konfirmasi password belum sama.';
    }

    if (!$errors) {
        $pdo = Database::getConnection();
        if (!$pdo) {
            $errors[] = 'Database belum siap. Coba lagi sebentar.';
        } else {
            try {
                $check = $pdo->prepare('SELECT id FROM users WHERE email = ? OR phone = ? LIMIT 1');
                $check->execute([$values['email'], $phone_digits]);
                if ($check->fetchColumn()) {
                    $errors[] = 'Email atau nomor WhatsApp itu sudah terdaftar. Coba masuk saja.';
                } else {
                    $now = date('Y-m-d H:i:s');
                    $insert = $pdo->prepare('INSERT INTO users
                        (name, email, phone, password_hash, created_at, updated_at)
                        VALUES (?, ?, ?, ?, ?, ?)');
                    $insert->execute([
                        $values['name'],
                        $values['email'],
                        $phone_digits,
                        password_hash($password, PASSWORD_DEFAULT),
                        $now,
                        $now,
                    ]);

                    $new_user_id = (int)$pdo->lastInsertId();

                    auth_login_user($new_user_id);
                    header('Location: ' . $next);
                    exit;
                }
            } catch (Throwable $e) {
                $errors[] = 'Pendaftaran gagal diproses. Coba lagi.';
            }
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
    <title>Daftar Akun - Jepret.in</title>

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

    <div class="w-full max-w-md">

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
                <i class="ri-user-add-line text-purple-600 dark:text-purple-400"></i>
                <h1 class="text-lg font-extrabold font-heading text-slate-900 dark:text-white">Daftar Akun</h1>
            </div>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed mb-5">
                Akun dipakai buat booking dan menyimpan riwayat sewa kamu di Jepret.in.
            </p>

            <?php if ($errors): ?>
                <div class="mb-4 rounded-xl border border-red-200 dark:border-red-500/30 bg-red-50 dark:bg-red-950/30 px-3 py-2.5 text-[11px] text-red-700 dark:text-red-300 space-y-1">
                    <?php foreach ($errors as $message): ?>
                        <div class="flex items-start gap-2">
                            <i class="ri-error-warning-line text-sm shrink-0 mt-0.5"></i>
                            <span><?= htmlspecialchars($message) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="daftar.php" class="space-y-3">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="next" value="<?= htmlspecialchars($next) ?>">

                <div>
                    <label for="name" class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">Nama Lengkap</label>
                    <input id="name" name="name" type="text" required value="<?= htmlspecialchars($values['name']) ?>"
                           class="glass-input w-full px-3.5 py-2.5 rounded-xl text-sm" placeholder="Budi Pratama">
                </div>

                <div>
                    <label for="email" class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">Email</label>
                    <input id="email" name="email" type="email" required autocomplete="email" value="<?= htmlspecialchars($values['email']) ?>"
                           class="glass-input w-full px-3.5 py-2.5 rounded-xl text-sm" placeholder="nama@email.com">
                </div>

                <div>
                    <label for="phone" class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">Nomor WhatsApp</label>
                    <input id="phone" name="phone" type="tel" required value="<?= htmlspecialchars($values['phone']) ?>"
                           class="glass-input w-full px-3.5 py-2.5 rounded-xl text-sm" placeholder="081234567890">
                    <p class="text-[10px] text-slate-400 mt-1">Link reset password nanti dikirim ke nomor ini.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="password" class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">Password</label>
                        <input id="password" name="password" type="password" required minlength="8" autocomplete="new-password"
                               class="glass-input w-full px-3.5 py-2.5 rounded-xl text-sm" placeholder="Min. 8 karakter">
                    </div>
                    <div>
                        <label for="password_confirm" class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">Ulangi Password</label>
                        <input id="password_confirm" name="password_confirm" type="password" required minlength="8" autocomplete="new-password"
                               class="glass-input w-full px-3.5 py-2.5 rounded-xl text-sm" placeholder="Ulangi password">
                    </div>
                </div>

                <button type="submit" class="btn-theme-primary w-full py-3 rounded-xl text-sm font-bold flex items-center justify-center gap-2 shadow-sm">
                    <i class="ri-user-add-line text-base"></i>
                    <span>Daftar & Masuk</span>
                </button>
            </form>

            <p class="mt-4 text-center text-[11px] text-slate-500 dark:text-slate-400">
                Sudah punya akun?
                <a href="login.php<?= $next !== 'akun.php' ? '?next=' . urlencode($next) : '' ?>" class="font-bold text-purple-600 dark:text-purple-400 hover:underline">Masuk di sini</a>
            </p>
        </div>

        <a href="index.php" class="mt-5 flex items-center justify-center gap-1.5 text-[11px] font-semibold text-slate-500 dark:text-slate-400 hover:text-purple-600 dark:hover:text-purple-400 transition">
            <i class="ri-arrow-left-line"></i>
            <span>Balik ke website</span>
        </a>
    </div>

</body>
</html>
