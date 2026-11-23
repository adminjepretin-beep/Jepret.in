<?php
// katalog.php - Katalog Lengkap Kamera Jepret.in Surabaya (jumlah mengikuti data aktif) (Default Light: White + Orange, Dark: Black + Purple)
$page_title = 'Pilihan Kamera Rental - Jepret.in Surabaya';
$current_page = 'katalog';
$wa_number = '6283832104793';

require_once __DIR__ . '/data/cameras.php';

$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$selected_category = isset($_GET['cat']) ? trim($_GET['cat']) : 'all';
$selected_brand = isset($_GET['brand']) ? trim($_GET['brand']) : 'all';
$sort_by = isset($_GET['sort']) ? trim($_GET['sort']) : 'default';

$filtered_cameras = array_filter($cameras, function($cam) use ($search_query, $selected_category, $selected_brand) {
    // Unit yang dinonaktifkan admin tidak muncul di katalog publik.
    if (empty($cam['is_available'])) {
        return false;
    }

    if ($search_query !== '') {
        $match_name = stripos($cam['name'], $search_query) !== false;
        $match_desc = stripos($cam['description'], $search_query) !== false;
        $match_brand = stripos($cam['brand'], $search_query) !== false;
        if (!$match_name && !$match_desc && !$match_brand) {
            return false;
        }
    }

    if ($selected_category !== 'all' && strtolower($cam['category']) !== strtolower($selected_category)) {
        return false;
    }

    if ($selected_brand !== 'all' && strtolower($cam['brand']) !== strtolower($selected_brand)) {
        return false;
    }

    return true;
});

if ($sort_by === 'price_asc') {
    usort($filtered_cameras, fn($a, $b) => $a['daily_price'] <=> $b['daily_price']);
} elseif ($sort_by === 'price_desc') {
    usort($filtered_cameras, fn($a, $b) => $b['daily_price'] <=> $a['daily_price']);
}

// Jumlah model aktif, tidak terpengaruh filter/pencarian yang sedang dipakai.
$active_camera_count = activeCameraCount($cameras);

include_once __DIR__ . '/includes/header.php';
?>

<!-- HEADER SECTION -->
<section class="pt-8 pb-8 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
    <div class="glass-panel rounded-3xl p-8 sm:p-12" data-aos="fade-down">
        <div class="max-w-2xl space-y-3">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-md bg-purple-50 border border-purple-200 dark:bg-[#161f30] dark:border-purple-500/30 text-purple-600 dark:text-purple-400 text-xs font-bold">
                <i class="ri-camera-lens-line"></i>
                <span>Ready <?= $active_camera_count ?> Kamera Pilihan di Surabaya</span>
            </div>
            <h1 class="text-3xl sm:text-5xl font-black text-slate-900 dark:text-white font-heading tracking-tight">
                Pilih Kamera yang Pas <br><span class="text-purple-600 dark:text-purple-400">Buat Project Kamu</span>
            </h1>
            <p class="text-slate-600 dark:text-slate-300 text-xs sm:text-sm leading-relaxed">
                Tinggal pilih mau pake Sony, Canon, Fujifilm, Nikon, sampe Leica. Semua kamera udah include 3 baterai original dan sensor kinclong siap pakai!
            </p>
        </div>
    </div>
</section>

<!-- FILTER & SEARCH BAR -->
<section class="pb-8 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
    <form method="GET" action="katalog.php" id="cameraFilterForm" class="glass-panel rounded-2xl p-4 flex flex-col md:flex-row items-center justify-between gap-4">
        
        <!-- Search Input -->
        <div class="relative w-full md:w-80">
            <i class="ri-search-line text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
            <input 
                type="text" 
                name="q" 
                id="cameraSearch" 
                autocomplete="off"

                value="<?= htmlspecialchars($search_query) ?>" 
                placeholder="Cari kamera (Sony, Canon, Fuji, dll)..." 
                class="glass-input w-full pl-10 pr-4 py-2.5 rounded-xl text-xs sm:text-sm placeholder:text-slate-400">
        </div>

        <!-- Filter Selects -->
        <div class="flex flex-wrap items-center gap-2.5 w-full md:w-auto">
            
            <select name="cat" id="cameraCategory" class="glass-input rounded-xl px-3 py-2 text-xs">
                <option value="all" <?= ($selected_category === 'all') ? 'selected' : '' ?>>Semua Kategori</option>
                <option value="Mirrorless" <?= ($selected_category === 'Mirrorless') ? 'selected' : '' ?>>Mirrorless</option>
                <option value="Cinema" <?= ($selected_category === 'Cinema') ? 'selected' : '' ?>>Cinema Line</option>
                <option value="Compact" <?= ($selected_category === 'Compact') ? 'selected' : '' ?>>Premium Compact</option>
            </select>

            <select name="brand" id="cameraBrand" class="glass-input rounded-xl px-3 py-2 text-xs">
                <option value="all" <?= ($selected_brand === 'all') ? 'selected' : '' ?>>Semua Merk</option>
                <option value="Sony" <?= ($selected_brand === 'Sony') ? 'selected' : '' ?>>Sony</option>
                <option value="Canon" <?= ($selected_brand === 'Canon') ? 'selected' : '' ?>>Canon</option>
                <option value="Fujifilm" <?= ($selected_brand === 'Fujifilm') ? 'selected' : '' ?>>Fujifilm</option>
                <option value="Blackmagic" <?= ($selected_brand === 'Blackmagic') ? 'selected' : '' ?>>Blackmagic</option>
                <option value="Nikon" <?= ($selected_brand === 'Nikon') ? 'selected' : '' ?>>Nikon</option>
                <option value="Lumix" <?= ($selected_brand === 'Lumix') ? 'selected' : '' ?>>Lumix</option>
                <option value="Leica" <?= ($selected_brand === 'Leica') ? 'selected' : '' ?>>Leica</option>
            </select>

            <select name="sort" id="cameraSort" class="glass-input rounded-xl px-3 py-2 text-xs">
                <option value="default" <?= ($sort_by === 'default') ? 'selected' : '' ?>>Urutan Default</option>
                <option value="price_asc" <?= ($sort_by === 'price_asc') ? 'selected' : '' ?>>Harga Termurah</option>
                <option value="price_desc" <?= ($sort_by === 'price_desc') ? 'selected' : '' ?>>Harga Tertinggi</option>
            </select>

            <button type="button" id="cameraReset" class="px-3 py-2 rounded-xl text-xs text-red-500 hover:bg-red-50 dark:hover:bg-red-950/20 transition border border-red-200 dark:border-red-500/20">
                Reset
            </button>

        </div>
    </form>
</section>

<!-- PRODUCT LIST: BENTO GRID 10 KAMERA -->
<section class="pb-24 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6 text-xs text-slate-500 dark:text-slate-400">
        <div>
            Ada <span id="cameraCount" class="text-slate-900 dark:text-white font-bold"><?= count($filtered_cameras) ?></span> kamera ready di Studio Surabaya
        </div>
        <div class="flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400">
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
            <span>Sensor dicek sebelum diserahkan</span>
        </div>
    </div>

    <?php if (empty($filtered_cameras)): ?>
        <div class="glass-panel rounded-3xl p-12 text-center max-w-lg mx-auto" data-aos="fade-up">
            <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-[#141b29] border border-slate-200 dark:border-white/10 flex items-center justify-center mx-auto mb-4 text-slate-400">
                <i class="ri-search-eye-line text-2xl"></i>
            </div>
            <h3 class="text-base font-bold text-slate-900 dark:text-white font-heading">Kamera Belum Ketemu</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">
                Kata kunci atau filter yang kamu cari belum ada. Coba reset filter buat liat <?= $active_camera_count ?> kamera lengkap kami ya!
            </p>
            <a href="katalog.php" class="btn-theme-secondary inline-block mt-4 px-4 py-2 text-xs font-semibold rounded-xl">
                Reset Filter
            </a>
        </div>
    <?php else: ?>
        <div id="cameraGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php $card_index = 0; foreach ($filtered_cameras as $camera): ?>
                <div class="glass-card rounded-3xl overflow-hidden flex flex-col group relative" data-aos="fade-up"
                     data-camera-card
                     data-index="<?= $card_index++ ?>"
                     data-search="<?= htmlspecialchars(mb_strtolower($camera['name'] . ' ' . $camera['brand'] . ' ' . $camera['category'] . ' ' . $camera['tag'] . ' ' . $camera['description'])) ?>"
                     data-brand="<?= htmlspecialchars($camera['brand']) ?>"
                     data-category="<?= htmlspecialchars($camera['category']) ?>"
                     data-price="<?= (int)$camera['daily_price'] ?>">
                    
                    <!-- Top Image Area -->
                    <div class="relative aspect-[4/3] bg-slate-100 dark:bg-black overflow-hidden border-b border-slate-200 dark:border-white/5">
                        <img src="<?= htmlspecialchars($camera['image']) ?>" alt="<?= htmlspecialchars($camera['name']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">

                        <!-- Brand & Category Badges -->
                        <div class="absolute top-4 left-4 flex flex-wrap gap-1.5">
                            <span class="px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider bg-white/90 text-slate-900 border border-slate-200 dark:bg-[#0e131d]/90 dark:text-white dark:border-white/15 shadow-xs">
                                <?= htmlspecialchars($camera['brand']) ?>
                            </span>
                            <span class="px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider bg-purple-100 text-purple-700 border border-purple-200 dark:bg-purple-950/60 dark:text-purple-300 dark:border-purple-500/30">
                                <?= htmlspecialchars($camera['category']) ?>
                            </span>
                        </div>

                        <!-- Price Tag -->
                        <div class="absolute bottom-3 left-4 right-4 flex items-end justify-between">
                            <div class="bg-white/95 dark:bg-[#0e131d]/90 px-3 py-1.5 rounded-lg border border-slate-200 dark:border-white/10 shadow-sm">
                                <span class="text-[10px] uppercase font-semibold tracking-wider text-slate-500 dark:text-slate-400 block -mb-0.5">Sewa Harian</span>
                                <div class="text-xl font-black text-slate-900 dark:text-white font-heading">
                                    <?= formatRupiah($camera['daily_price']) ?>
                                    <span class="text-xs text-slate-500 dark:text-slate-400 font-normal">/hr</span>
                                </div>
                            </div>
                            <span class="text-[11px] px-2.5 py-1 rounded-md bg-white/95 dark:bg-[#0e131d]/90 text-emerald-600 dark:text-emerald-400 border border-slate-200 dark:border-white/10 font-bold shadow-sm">
                                Ready
                            </span>
                        </div>
                    </div>

                    <!-- Content Details -->
                    <div class="p-6 flex-grow flex flex-col justify-between space-y-4">
                        <div>
                            <div class="text-[11px] font-bold text-purple-600 dark:text-purple-400 mb-1">
                                <?= htmlspecialchars($camera['tag']) ?>
                            </div>
                            <h2 class="text-lg font-bold text-slate-900 dark:text-white font-heading hover:text-purple-600 dark:hover:text-purple-400 transition-colors">
                                <a href="detail.php?id=<?= $camera['id'] ?>"><?= htmlspecialchars($camera['name']) ?></a>
                            </h2>
                            <p class="text-xs text-slate-600 dark:text-slate-400 mt-2 line-clamp-2 leading-relaxed">
                                <?= htmlspecialchars($camera['description']) ?>
                            </p>
                        </div>

                        <!-- Bento Specs Micro Grid -->
                        <div class="grid grid-cols-2 gap-2 text-[11px] text-slate-600 dark:text-slate-300 pt-3 border-t border-slate-100 dark:border-white/5">
                            <div class="p-2 rounded-lg bg-slate-50 dark:bg-[#141b29] flex items-center gap-1.5 truncate">
                                <i class="ri-focus-2-line text-purple-600 dark:text-purple-400 shrink-0"></i>
                                <span class="truncate" title="<?= htmlspecialchars($camera['sensor']) ?>"><?= htmlspecialchars($camera['sensor']) ?></span>
                            </div>
                            <div class="p-2 rounded-lg bg-slate-50 dark:bg-[#141b29] flex items-center gap-1.5 truncate">
                                <i class="ri-video-line text-purple-600 dark:text-purple-400 shrink-0"></i>
                                <span class="truncate" title="<?= htmlspecialchars($camera['video']) ?>"><?= htmlspecialchars($camera['video']) ?></span>
                            </div>
                            <div class="p-2 rounded-lg bg-slate-50 dark:bg-[#141b29] flex items-center gap-1.5 truncate">
                                <i class="ri-link text-slate-400 shrink-0"></i>
                                <span class="truncate" title="<?= htmlspecialchars($camera['mount']) ?>"><?= htmlspecialchars($camera['mount']) ?></span>
                            </div>
                            <div class="p-2 rounded-lg bg-slate-50 dark:bg-[#141b29] flex items-center gap-1.5 truncate">
                                <i class="ri-scales-3-line text-slate-400 shrink-0"></i>
                                <span class="truncate"><?= htmlspecialchars($camera['weight']) ?></span>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-2 pt-2">
                            <a href="detail.php?id=<?= $camera['id'] ?>" class="btn-theme-secondary flex-1 text-center py-2.5 px-3 rounded-xl text-xs font-semibold">
                                Spek Lengkap
                            </a>
                            <a href="detail.php?id=<?= $camera['id'] ?>#booking-form" class="btn-theme-primary flex-1 text-center py-2.5 px-3 rounded-xl text-xs font-bold flex items-center justify-center gap-1.5 shadow-sm">
                                <i class="ri-calendar-check-line text-sm"></i>
                                <span>Pilih Tanggal</span>
                            </a>
                        </div>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>

        <!-- Empty state versi live search (JS) -->
        <div id="cameraEmpty" class="hidden glass-panel rounded-3xl p-12 text-center max-w-lg mx-auto">
            <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-[#141b29] border border-slate-200 dark:border-white/10 flex items-center justify-center mx-auto mb-4 text-slate-400">
                <i class="ri-search-eye-line text-2xl"></i>
            </div>
            <h3 class="text-base font-bold text-slate-900 dark:text-white font-heading">Kamera Belum Ketemu</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">
                Coba kata kunci lain atau reset filter buat liat <?= $active_camera_count ?> kamera lengkap kami ya!
            </p>
        </div>
    <?php endif; ?>
</section>

<script>
(function () {
    const grid = document.getElementById('cameraGrid');
    if (!grid) return;

    const cards = Array.from(grid.querySelectorAll('[data-camera-card]'));
    const search = document.getElementById('cameraSearch');
    const category = document.getElementById('cameraCategory');
    const brand = document.getElementById('cameraBrand');
    const sort = document.getElementById('cameraSort');
    const countEl = document.getElementById('cameraCount');
    const emptyEl = document.getElementById('cameraEmpty');
    const form = document.getElementById('cameraFilterForm');
    const reset = document.getElementById('cameraReset');
    let typingTimer;

    function applyFilters() {
        const keyword = search.value.trim().toLowerCase();
        let visible = 0;

        cards.forEach(function (card) {
            const matchKeyword = keyword === '' || (card.dataset.search || '').includes(keyword);
            const matchCategory = category.value === 'all' || card.dataset.category === category.value;
            const matchBrand = brand.value === 'all' || card.dataset.brand === brand.value;
            const show = matchKeyword && matchCategory && matchBrand;

            card.classList.toggle('hidden', !show);
            if (show) visible++;
        });

        const mode = sort.value;
        cards.slice().sort(function (a, b) {
            if (mode === 'price_asc') return Number(a.dataset.price) - Number(b.dataset.price);
            if (mode === 'price_desc') return Number(b.dataset.price) - Number(a.dataset.price);
            return Number(a.dataset.index) - Number(b.dataset.index);
        }).forEach(function (card) {
            grid.appendChild(card);
        });

        if (countEl) countEl.textContent = visible;
        if (emptyEl) emptyEl.classList.toggle('hidden', visible !== 0);
    }

    search.addEventListener('input', function () {
        clearTimeout(typingTimer);
        typingTimer = setTimeout(applyFilters, 120);
    });

    [category, brand, sort].forEach(function (el) {
        el.addEventListener('change', applyFilters);
    });

    if (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            applyFilters();
        });
    }

    if (reset) {
        reset.addEventListener('click', function () {
            search.value = '';
            category.value = 'all';
            brand.value = 'all';
            sort.value = 'default';
            applyFilters();
        });
    }

    applyFilters();
})();
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>

