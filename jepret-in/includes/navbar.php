<?php
// includes/navbar.php
$wa_number = '6283832104793';

require_once __DIR__ . '/auth.php';
$nav_user = auth_current_user();
?>
<header class="sticky top-4 z-50 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto w-full transition-all duration-300">
    <nav class="glass-panel rounded-2xl px-5 py-3 flex items-center justify-between border shadow-sm dark:shadow-xl transition-colors duration-200">
        
        <!-- Logo Brand -->
        <a href="index.php" class="flex items-center gap-3 group">
            <div class="w-10 h-10 rounded-xl overflow-hidden border border-slate-200 dark:border-white/15 bg-white dark:bg-black flex items-center justify-center shrink-0 shadow-sm">
                <img src="assets/images/logo.jpg" alt="Jepret.in Logo" class="w-full h-full object-cover">
            </div>
            <div class="flex flex-col">
                <span class="font-heading font-extrabold text-xl tracking-tight text-slate-900 dark:text-white flex items-center">
                    Jepret<span class="text-purple-600 dark:text-purple-400">.in</span>
                </span>
                <span class="text-[10px] tracking-widest uppercase text-slate-500 dark:text-slate-400 -mt-1 font-semibold">Surabaya Rental</span>
            </div>
        </a>

        <!-- Desktop Navigation Links — Urutan: Beranda, Tentang, Kamera, Cara Sewa, Kontak -->
        <div class="hidden md:flex items-center gap-1 bg-slate-100 dark:bg-[#151822] p-1.5 rounded-xl border border-slate-200 dark:border-white/10 transition-colors">
            <a href="index.php" class="px-4 py-1.5 text-xs font-semibold rounded-lg transition-all <?= ($current_page === 'home') ? 'bg-purple-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200/60 dark:hover:bg-white/5' ?>">
                Beranda
            </a>
            <a href="tentang.php" class="px-4 py-1.5 text-xs font-semibold rounded-lg transition-all <?= ($current_page === 'tentang') ? 'bg-purple-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200/60 dark:hover:bg-white/5' ?>">
                Tentang
            </a>
            <a href="katalog.php" class="px-4 py-1.5 text-xs font-semibold rounded-lg transition-all <?= ($current_page === 'katalog') ? 'bg-purple-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200/60 dark:hover:bg-white/5' ?>">
                Kamera
            </a>
            <a href="cara-sewa.php" class="px-4 py-1.5 text-xs font-semibold rounded-lg transition-all <?= ($current_page === 'cara-sewa') ? 'bg-purple-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200/60 dark:hover:bg-white/5' ?>">
                Cara Sewa
            </a>
            <a href="kontak.php" class="px-4 py-1.5 text-xs font-semibold rounded-lg transition-all <?= ($current_page === 'kontak') ? 'bg-purple-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200/60 dark:hover:bg-white/5' ?>">
                Kontak
            </a>
        </div>

        <!-- Action Buttons + Theme Toggle -->
        <div class="hidden sm:flex items-center gap-3">

            <?php if ($nav_user): ?>
                <a href="akun.php" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-white/10 hover:bg-slate-100 dark:hover:bg-white/5 transition">
                    <i class="ri-user-3-line text-sm text-purple-600 dark:text-purple-400"></i>
                    <span>Akun</span>
                </a>
            <?php else: ?>
                <a href="login.php" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-white/10 hover:bg-slate-100 dark:hover:bg-white/5 transition">
                    <i class="ri-user-3-line text-sm text-purple-600 dark:text-purple-400"></i>
                    <span>Masuk</span>
                </a>
            <?php endif; ?>

            <!-- Theme Toggle (Sun/Moon) -->
            <button id="themeToggleBtn" type="button" aria-label="Ganti Tema Terang/Gelap"
                class="w-9 h-9 rounded-xl flex items-center justify-center bg-slate-100 hover:bg-slate-200 dark:bg-[#151822] dark:hover:bg-[#1c212e] border border-slate-200 dark:border-white/10 text-slate-700 dark:text-slate-300 transition-colors"
                title="Ganti Tema">
                <i class="ri-moon-line text-base dark:hidden"></i>
                <i class="ri-sun-line text-base hidden dark:inline text-amber-400"></i>
            </button>

            <a href="https://wa.me/<?= $wa_number ?>?text=Halo%20Jepret.in%20Surabaya%2C%20mau%20tanya%20sewa%20kamera%20dong"
               target="_blank" rel="noopener noreferrer"
               class="btn-theme-secondary flex items-center gap-2 px-3.5 py-2 text-xs font-semibold rounded-xl">
                <i class="ri-whatsapp-line text-emerald-600 dark:text-emerald-400 text-sm"></i>
            </a>
            <a href="katalog.php" class="btn-theme-primary inline-flex items-center gap-2 px-4 py-2 text-xs font-bold rounded-xl shadow-sm">
                <i class="ri-camera-3-line text-sm"></i>
                <span>Sewa Sekarang</span>
            </a>
        </div>

        <!-- Mobile Controls -->
        <div class="flex items-center gap-2 md:hidden">
            <button id="mobileThemeToggleBtn" type="button" aria-label="Ganti Tema"
                class="w-9 h-9 rounded-xl flex items-center justify-center bg-slate-100 dark:bg-[#151822] border border-slate-200 dark:border-white/10 text-slate-700 dark:text-slate-300">
                <i class="ri-moon-line text-base dark:hidden"></i>
                <i class="ri-sun-line text-base hidden dark:inline text-amber-400"></i>
            </button>
            <button id="mobileMenuBtn" type="button" aria-label="Menu"
                class="p-2 rounded-xl text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-white/10 border border-slate-200 dark:border-white/10 transition">
                <i class="ri-menu-line text-lg" id="menuOpenIcon"></i>
                <i class="ri-close-line text-lg hidden" id="menuCloseIcon"></i>
            </button>
        </div>
    </nav>

    <!-- Mobile Dropdown — same order: Beranda, Tentang, Kamera, Cara Sewa, Kontak -->
    <div id="mobileMenu" class="hidden md:hidden mt-2 p-4 glass-panel rounded-2xl border border-slate-200 dark:border-white/10 space-y-2">
        <a href="index.php" class="block px-4 py-2.5 rounded-xl text-sm font-semibold <?= ($current_page === 'home') ? 'bg-purple-600 text-white' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-white/5' ?>">
            Beranda
        </a>
        <a href="tentang.php" class="block px-4 py-2.5 rounded-xl text-sm font-semibold <?= ($current_page === 'tentang') ? 'bg-purple-600 text-white' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-white/5' ?>">
            Tentang
        </a>
        <a href="katalog.php" class="block px-4 py-2.5 rounded-xl text-sm font-semibold <?= ($current_page === 'katalog') ? 'bg-purple-600 text-white' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-white/5' ?>">
            Kamera
        </a>
        <a href="cara-sewa.php" class="block px-4 py-2.5 rounded-xl text-sm font-semibold <?= ($current_page === 'cara-sewa') ? 'bg-purple-600 text-white' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-white/5' ?>">
            Cara Sewa
        </a>
        <a href="kontak.php" class="block px-4 py-2.5 rounded-xl text-sm font-semibold <?= ($current_page === 'kontak') ? 'bg-purple-600 text-white' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-white/5' ?>">
            Kontak
        </a>
        <div class="pt-3 border-t border-slate-200 dark:border-white/10 flex flex-col gap-2">
            <a href="<?= $nav_user ? 'akun.php' : 'login.php' ?>"
               class="flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-bold rounded-xl border border-slate-200 dark:border-white/10 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-white/5 transition">
                <i class="ri-user-3-line text-base text-purple-600 dark:text-purple-400"></i>
                <span><?= $nav_user ? 'Akun Saya' : 'Masuk / Daftar' ?></span>
            </a>
            <a href="https://wa.me/<?= $wa_number ?>" target="_blank"
               class="flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-bold rounded-xl text-white bg-emerald-600 hover:bg-emerald-500 transition">
                <i class="ri-whatsapp-fill text-base"></i>
                <span>Chat WhatsApp Admin</span>
            </a>
            <a href="katalog.php" class="btn-theme-primary flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-bold rounded-xl">
                <i class="ri-camera-3-line text-base"></i>
                <span>Pilih & Sewa Kamera</span>
            </a>
        </div>
    </div>
</header>

<!-- Global Theme Toggle JS — transisi lingkaran dari TENGAH layar pakai View Transitions (tanpa layar kosong) -->
<script>
(function() {
    var isAnimating = false;

    function applyTheme(isDark) {
        var root = document.documentElement;
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
        var goingDark = !document.documentElement.classList.contains('dark');

        // Browser tanpa View Transitions: ganti tema langsung, warna ikut cross-fade CSS
        if (typeof document.startViewTransition !== 'function') {
            applyTheme(goingDark);
            return;
        }

        isAnimating = true;

        // Tema baru digambar sebagai lapisan di atas tema lama, lalu dibuka
        // dengan lingkaran dari tengah layar. Tidak ada momen layar kosong.
        var transition = document.startViewTransition(function() {
            applyTheme(goingDark);
        });

        transition.ready.then(function() {
            // Titik pusat layar yang sebenarnya (bukan posisi tombol)
            var cx = Math.round(window.innerWidth / 2);
            var cy = Math.round(window.innerHeight / 2);
            var radius = Math.hypot(
                Math.max(cx, window.innerWidth - cx),
                Math.max(cy, window.innerHeight - cy)
            );

            document.documentElement.animate(
                {
                    // Mulai dari titik kecil (diameter ~2px) di tengah, lalu menyebar
                    clipPath: [
                        'circle(1px at ' + cx + 'px ' + cy + 'px)',
                        'circle(' + radius + 'px at ' + cx + 'px ' + cy + 'px)'
                    ]
                },
                {
                    duration: 900,
                    // Ease-in-out: titik kelihatan tumbuh dulu baru melesat menyebar
                    easing: 'cubic-bezier(0.55, 0, 0.35, 1)',
                    pseudoElement: '::view-transition-new(root)'
                }
            );
        }).catch(function() {});

        transition.finished.finally(function() {
            isAnimating = false;
        });
    }

    function attachToggle(id) {
        var btn = document.getElementById(id);
        if (btn) {
            btn.addEventListener('click', toggleTheme);
        }
    }

    attachToggle('themeToggleBtn');
    attachToggle('mobileThemeToggleBtn');


    // Mobile hamburger menu
    var menuBtn  = document.getElementById('mobileMenuBtn');
    var mobileMenu = document.getElementById('mobileMenu');
    var openIcon = document.getElementById('menuOpenIcon');
    var closeIcon = document.getElementById('menuCloseIcon');
    if (menuBtn && mobileMenu) {
        menuBtn.addEventListener('click', function() {
            var hidden = mobileMenu.classList.toggle('hidden');
            if (openIcon)  openIcon.classList.toggle('hidden', !hidden);
            if (closeIcon) closeIcon.classList.toggle('hidden', hidden);
        });
    }
})();
</script>
