<?php
// includes/user_layout.php - Kerangka dashboard pengguna (sidebar kiri + topbar), samain gaya admin panel

require_once __DIR__ . '/auth.php';

function user_nav_items(): array
{
    return [
        'dashboard' => ['label' => 'Dashboard', 'href' => 'akun.php', 'icon' => 'ri-dashboard-line'],
        'riwayat'   => ['label' => 'Riwayat Sewa', 'href' => 'akun.php#riwayat', 'icon' => 'ri-calendar-check-line'],
        'testimoni' => ['label' => 'Testimoni', 'href' => 'akun.php#testimoni', 'icon' => 'ri-quill-pen-line'],
        'profil'    => ['label' => 'Profil', 'href' => 'akun.php#profil', 'icon' => 'ri-user-settings-line'],
        'keamanan'  => ['label' => 'Password', 'href' => 'akun.php#keamanan', 'icon' => 'ri-lock-password-line'],
    ];
}

function user_layout_start(string $title, string $active, array $user, string $csrf, string $subtitle = ''): void
{
    $initial = strtoupper(mb_substr((string)$user['name'], 0, 1));
    ?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <title><?= htmlspecialchars($title) ?> - Jepret.in</title>

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

    <style>
        body { background-color: #f6f7fb; }
        .dark body { background-color: #080a10; }
        .user-scroll::-webkit-scrollbar { width: 5px; height: 5px; }
        .user-scroll::-webkit-scrollbar-thumb { background: rgba(100, 116, 139, .35); border-radius: 999px; }
        .user-nav-link { transition: background-color .15s ease, color .15s ease; }
        .user-sidebar-shadow { box-shadow: 1px 0 0 0 rgba(15, 23, 42, .06); }
        .dark .user-sidebar-shadow { box-shadow: 1px 0 0 0 rgba(255, 255, 255, .06); }

        /* Tema baru ditumpuk di atas tema lama, lalu dibuka dengan clip-path lingkaran. */
        ::view-transition-old(root),
        ::view-transition-new(root) {
            animation: none;
            mix-blend-mode: normal;
        }
        ::view-transition-old(root) { z-index: 1; }
        ::view-transition-new(root) { z-index: 2; }
        .user-scroll-target { scroll-margin-top: 5rem; }
    </style>

    <!-- Navigasi instan: halaman di-prerender saat link di-hover -->
    <script type="speculationrules">
    {
        "prerender": [{
            "where": {
                "and": [
                    { "href_matches": "/*" },
                    { "not": { "href_matches": "*/logout.php*" } },
                    { "not": { "href_matches": "*/login.php*" } },
                    { "not": { "selector_matches": "[data-no-prerender]" } }
                ]
            },
            "eagerness": "moderate"
        }]
    }
    </script>
</head>
<body class="text-slate-900 dark:text-slate-100 antialiased min-h-screen font-sans">

    <!-- BACKDROP MOBILE -->
    <div id="userSidebarBackdrop" class="fixed inset-0 z-40 hidden bg-slate-900/50 backdrop-blur-[2px] lg:hidden"></div>

    <!-- SIDEBAR -->
    <aside id="userSidebar"
           class="fixed inset-y-0 left-0 z-50 w-64 bg-white dark:bg-[#0b0e15] border-r border-slate-200 dark:border-white/10 user-sidebar-shadow
                  flex flex-col -translate-x-full lg:translate-x-0 transition-transform duration-200 ease-out">

        <!-- Brand -->
        <div class="h-16 px-5 flex items-center gap-3 border-b border-slate-200 dark:border-white/10 shrink-0">
            <a href="index.php" class="w-9 h-9 rounded-xl overflow-hidden border border-slate-200 dark:border-white/15 bg-black shrink-0">
                <img src="assets/images/logo.jpg" alt="Jepret.in Logo" class="w-full h-full object-cover">
            </a>
            <div class="leading-tight">
                <a href="index.php" class="font-heading font-extrabold text-base text-slate-900 dark:text-white">Jepret<span class="text-purple-600 dark:text-purple-400">.in</span></a>
                <div class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Akun Saya</div>
            </div>
            <button id="userSidebarClose" type="button" class="ml-auto lg:hidden w-8 h-8 rounded-lg text-slate-400 hover:bg-slate-100 dark:hover:bg-white/5 flex items-center justify-center">
                <i class="ri-close-line text-lg"></i>
            </button>
        </div>

        <!-- Nav -->
        <div class="flex-1 overflow-y-auto user-scroll px-3 py-5">
            <div class="px-2 mb-2 text-[10px] font-bold uppercase tracking-wider text-slate-400">Menu Utama</div>
            <nav class="space-y-1">
                <?php foreach (user_nav_items() as $key => $item): ?>
                    <?php $is_active = $key === $active; ?>
                    <a href="<?= htmlspecialchars($item['href']) ?>"
                       class="user-nav-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold
                              <?= $is_active
                                    ? 'bg-purple-600 text-white shadow-sm'
                                    : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-white/5' ?>">
                        <i class="<?= htmlspecialchars($item['icon']) ?> text-base <?= $is_active ? '' : 'text-slate-400' ?>"></i>
                        <span><?= htmlspecialchars($item['label']) ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="px-2 mt-7 mb-2 text-[10px] font-bold uppercase tracking-wider text-slate-400">Website</div>
            <nav class="space-y-1">
                <a href="index.php"
                   class="user-nav-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-white/5">
                    <i class="ri-global-line text-base text-slate-400"></i>
                    <span>Beranda</span>
                    <i class="ri-external-link-line text-[11px] ml-auto text-slate-400"></i>
                </a>
                <a href="katalog.php"
                   class="user-nav-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-white/5">
                    <i class="ri-camera-3-line text-base text-slate-400"></i>
                    <span>Katalog Kamera</span>
                    <i class="ri-external-link-line text-[11px] ml-auto text-slate-400"></i>
                </a>
            </nav>

            <!-- Info box -->
            <div class="mt-6 mx-1 rounded-2xl border border-slate-200 dark:border-white/10 bg-slate-50 dark:bg-[#10141d] p-4">
                <div class="flex items-center gap-2 text-[11px] font-bold text-slate-700 dark:text-slate-200">
                    <i class="ri-customer-service-2-line text-purple-600 dark:text-purple-400"></i>
                    <span>Butuh Bantuan?</span>
                </div>
                <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1.5 leading-relaxed">
                    Chat admin dan sebutkan kode booking kamu biar cepat dicek.
                </p>
                <a href="https://wa.me/6283832104793?text=<?= rawurlencode('Halo Admin Jepret.in, saya mau tanya soal booking saya.') ?>"
                   target="_blank" rel="noopener noreferrer"
                   class="mt-3 inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-[10px] font-bold transition">
                    <i class="ri-whatsapp-fill"></i>
                    <span>Chat Admin</span>
                </a>
            </div>
        </div>

        <!-- User -->
        <div class="border-t border-slate-200 dark:border-white/10 p-3 shrink-0">
            <div class="flex items-center gap-3 px-2 py-2">
                <div class="w-9 h-9 rounded-full bg-purple-600 text-white flex items-center justify-center font-heading font-bold text-sm shrink-0">
                    <?= htmlspecialchars($initial) ?>
                </div>
                <div class="leading-tight min-w-0">
                    <div class="text-xs font-bold text-slate-900 dark:text-white truncate"><?= htmlspecialchars((string)$user['name']) ?></div>
                    <div class="text-[10px] text-slate-400 truncate">Penyewa</div>
                </div>
                <form method="POST" action="logout.php" class="ml-auto">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                    <button type="submit" title="Keluar"
                            class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-950/30 transition">
                        <i class="ri-logout-box-r-line text-base"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- CONTENT -->
    <div class="lg:pl-64 min-h-screen flex flex-col">

        <!-- TOPBAR -->
        <header class="sticky top-0 z-30 h-16 px-4 sm:px-6 lg:px-8 bg-white/85 dark:bg-[#0b0e15]/85 backdrop-blur border-b border-slate-200 dark:border-white/10 flex items-center gap-3">
            <button id="userMenuBtn" type="button" class="lg:hidden w-9 h-9 rounded-xl border border-slate-200 dark:border-white/10 text-slate-600 dark:text-slate-300 flex items-center justify-center">
                <i class="ri-menu-line text-lg"></i>
            </button>

            <div class="min-w-0">
                <h1 class="text-sm sm:text-base font-bold font-heading text-slate-900 dark:text-white leading-tight truncate"><?= htmlspecialchars($title) ?></h1>
                <?php if ($subtitle !== ''): ?>
                    <p class="text-[10px] sm:text-[11px] text-slate-500 dark:text-slate-400 truncate"><?= htmlspecialchars($subtitle) ?></p>
                <?php endif; ?>
            </div>

            <div class="ml-auto flex items-center gap-2">
                <button id="userThemeToggle" type="button" aria-label="Ganti tema"
                        class="w-9 h-9 rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-[#10141d] text-slate-600 dark:text-slate-300 flex items-center justify-center hover:bg-slate-50 dark:hover:bg-white/5 transition">
                    <i class="ri-moon-line text-base dark:hidden"></i>
                    <i class="ri-sun-line text-base hidden dark:inline text-amber-400"></i>
                </button>
                <a href="katalog.php"
                   class="hidden sm:inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-[11px] font-bold btn-theme-primary">
                    <i class="ri-camera-3-line"></i>
                    <span>Sewa Kamera</span>
                </a>
                <div class="w-9 h-9 rounded-full bg-purple-600 text-white items-center justify-center font-heading font-bold text-sm hidden lg:flex">
                    <?= htmlspecialchars($initial) ?>
                </div>
            </div>
        </header>

        <main class="flex-1 px-4 sm:px-6 lg:px-8 py-6 lg:py-8">
            <div class="max-w-6xl mx-auto space-y-6">
<?php
}

function user_layout_end(): void
{
    ?>
            </div>
        </main>
    </div>

    <script>
        (function () {
            var root = document.documentElement;
            var isAnimating = false;

            function applyTheme(isDark) {
                if (isDark) {
                    root.classList.add('dark');
                    localStorage.theme = 'dark';
                } else {
                    root.classList.remove('dark');
                    localStorage.theme = 'light';
                }
            }

            function toggleTheme() {
                if (isAnimating) return;
                var goingDark = !root.classList.contains('dark');

                if (typeof document.startViewTransition !== 'function') {
                    applyTheme(goingDark);
                    return;
                }

                isAnimating = true;

                var transition = document.startViewTransition(function () {
                    applyTheme(goingDark);
                });

                transition.ready.then(function () {
                    var cx = Math.round(window.innerWidth / 2);
                    var cy = Math.round(window.innerHeight / 2);
                    var radius = Math.hypot(
                        Math.max(cx, window.innerWidth - cx),
                        Math.max(cy, window.innerHeight - cy)
                    );

                    root.animate(
                        {
                            clipPath: [
                                'circle(1px at ' + cx + 'px ' + cy + 'px)',
                                'circle(' + radius + 'px at ' + cx + 'px ' + cy + 'px)'
                            ]
                        },
                        {
                            duration: 900,
                            easing: 'cubic-bezier(0.55, 0, 0.35, 1)',
                            pseudoElement: '::view-transition-new(root)'
                        }
                    );
                }).catch(function () {});

                transition.finished.finally(function () {
                    isAnimating = false;
                });
            }

            var themeBtn = document.getElementById('userThemeToggle');
            if (themeBtn) {
                themeBtn.addEventListener('click', toggleTheme);
            }

            var sidebar = document.getElementById('userSidebar');
            var backdrop = document.getElementById('userSidebarBackdrop');
            var openBtn = document.getElementById('userMenuBtn');
            var closeBtn = document.getElementById('userSidebarClose');

            function setSidebar(open) {
                if (!sidebar) return;
                sidebar.classList.toggle('-translate-x-full', !open);
                if (backdrop) backdrop.classList.toggle('hidden', !open);
                document.body.style.overflow = (open && window.innerWidth < 1024) ? 'hidden' : '';
            }

            if (openBtn) openBtn.addEventListener('click', function () { setSidebar(true); });
            if (closeBtn) closeBtn.addEventListener('click', function () { setSidebar(false); });
            if (backdrop) backdrop.addEventListener('click', function () { setSidebar(false); });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') setSidebar(false);
            });
            window.addEventListener('resize', function () {
                if (window.innerWidth >= 1024) {
                    setSidebar(true);
                    document.body.style.overflow = '';
                } else if (!sidebar.classList.contains('-translate-x-full')) {
                    setSidebar(false);
                }
            });
        })();
    </script>
</body>
</html>
<?php
}
