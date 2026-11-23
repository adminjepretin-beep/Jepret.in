<?php
// index.php - Beranda Jepret.in Surabaya (Putih + Ungu, NO GRADIENTS)
$page_title = 'Jepret.in - Sewa Kamera Santai & Lengkap di Surabaya';
$current_page = 'home';
$wa_number = '6283832104793';

require_once __DIR__ . '/data/cameras.php';
require_once __DIR__ . '/includes/testimonials.php';
include_once __DIR__ . '/includes/header.php';

// Featured cameras
$featured_cameras = array_filter($cameras, function($c) {
    return ($c['is_featured'] ?? false) && !empty($c['is_available']);
});

// Angka dinamis: jumlah model aktif & total unit aktif (ikut stok).
$active_camera_count = activeCameraCount($cameras);
$active_unit_count   = activeCameraStock($cameras);

// Harga sewa termurah buat highlight di hero (hanya dari unit yang aktif).
$active_cameras = activeCameras($cameras);
$min_price = $active_cameras ? (int)min(array_column($active_cameras, 'daily_price')) : 0;
?>

<!-- HERO SECTION: EDITORIAL — headline besar + panel media full width -->
<section class="relative pt-4 pb-10 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">

    <!-- Baris Judul: headline kiri, deskripsi + CTA kanan -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-12 items-end">
        <div class="lg:col-span-7" data-aos="fade-up">
            <h1 class="text-[1.8rem] sm:text-[3.4rem] lg:text-[4.15rem] font-black uppercase font-heading tracking-tight text-slate-900 dark:text-white leading-[0.98]">
                <span class="block whitespace-nowrap">Mau Sewa Kamera,</span>
                <span class="block whitespace-nowrap">di Jepret.in Aja</span>
            </h1>
        </div>

        <div class="lg:col-span-5 lg:pb-1 space-y-4" data-aos="fade-up" data-aos-delay="100">
            <p class="text-sm sm:text-base text-slate-600 dark:text-slate-300 leading-relaxed max-w-md">
                Sewa kamera flagship buat konten, prewed, atau wisuda di Surabaya. Dibikin santai buat para kreator: sensor bersih, dapet 3 baterai original, dan prosesnya cuma lewat WhatsApp.
            </p>
            <div class="flex flex-wrap items-center gap-3">
                <a href="katalog.php" class="btn-theme-primary inline-flex items-center gap-2 px-6 py-3.5 text-sm font-bold rounded-2xl shadow-md">
                    <i class="ri-grid-fill text-base"></i>
                    <span>Pilih Kamera</span>
                </a>
                <a href="cara-sewa.php" class="btn-theme-secondary inline-flex items-center gap-2 px-5 py-3.5 text-sm font-semibold rounded-2xl">
                    <span>Cara Sewanya?</span>
                    <i class="ri-arrow-right-up-line text-purple-600 dark:text-purple-400"></i>
                </a>
            </div>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                <i class="ri-price-tag-3-line text-purple-600 dark:text-purple-400"></i>
                <?php if ($min_price > 0): ?>
                <span>Mulai <span class="font-bold text-slate-900 dark:text-white"><?= formatRupiah($min_price) ?></span> / 24 jam &middot; tanpa deposit berbelit</span>
                <?php else: ?>
                <span>Belum ada unit yang siap disewa saat ini</span>
                <?php endif; ?>
            </p>
        </div>
    </div>

    <!-- Panel Media Besar -->
    <div class="relative mt-8 sm:mt-10 rounded-[1.75rem] sm:rounded-[2.5rem] overflow-hidden border border-slate-200 dark:border-white/10 bg-slate-900" data-aos="fade-up" data-aos-delay="150">
        <img src="assets/images/sony_fx3.jpg" alt="Unit Sony FX3 siap disewa di Jepret.in Surabaya" class="w-full h-[340px] sm:h-[440px] lg:h-[520px] object-cover">
        <div class="absolute inset-0 bg-black/25"></div>

        <!-- Chip kanan atas -->
        <div class="absolute top-4 right-4 sm:top-6 sm:right-6 flex items-center gap-2 px-3 py-1.5 rounded-xl bg-white/90 dark:bg-black/60 backdrop-blur-md border border-white/60 dark:border-white/15 text-[10px] font-bold uppercase tracking-wider text-slate-900 dark:text-white">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
            <span><?= $active_unit_count ?> Unit Ready Surabaya</span>
        </div>

        <!-- Titik penanda -->
        <span class="hidden sm:flex absolute top-[38%] left-[57%] w-3 h-3 items-center justify-center">
            <span class="absolute w-3 h-3 rounded-full bg-white/60 animate-ping"></span>
            <span class="w-3 h-3 rounded-full bg-white ring-2 ring-white/50"></span>
        </span>

        <!-- Kartu kaca: Studio Surabaya -->
        <div class="absolute left-4 sm:left-8 bottom-4 sm:bottom-8 w-[258px] max-w-[calc(100%-2rem)] rounded-2xl bg-white/90 dark:bg-black/65 backdrop-blur-md border border-white/60 dark:border-white/15 p-4 shadow-xl">
            <div class="flex items-center gap-2">
                <i class="ri-map-pin-2-line text-purple-600 dark:text-purple-400 text-sm"></i>
                <h3 class="text-xs font-bold text-slate-900 dark:text-white font-heading">Studio Surabaya</h3>
            </div>
            <p class="text-[11px] text-slate-600 dark:text-slate-300 leading-snug mt-1">
                Ambil sendiri di studio, atau kami kirim via Gojek / Grab ke lokasi kamu.
            </p>
            <a href="katalog.php" class="mt-3 flex items-center justify-between gap-2 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-full pl-4 pr-1.5 py-1.5 text-[11px] font-bold">
                <span>Sewa sekarang</span>
                <span class="w-6 h-6 rounded-full bg-white/20 dark:bg-slate-900/10 flex items-center justify-center">
                    <i class="ri-arrow-right-line text-sm"></i>
                </span>
            </a>
        </div>

        <!-- Chip unit kanan bawah -->
        <div class="hidden sm:flex absolute right-8 bottom-8 items-center gap-2 px-3.5 py-2 rounded-xl bg-white/90 dark:bg-black/60 backdrop-blur-md border border-white/60 dark:border-white/15">
            <i class="ri-camera-lens-line text-purple-600 dark:text-purple-400 text-sm"></i>
            <span class="text-[11px] font-bold text-slate-900 dark:text-white">Sony FX3 &middot; Cinema Line</span>
        </div>
    </div>

    <!-- Stats ringkas -->
    <div class="grid grid-cols-3 gap-4 sm:gap-8 mt-8 pt-6 border-t border-slate-200 dark:border-white/10" data-aos="fade-up">
        <div>
            <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white font-heading">100%</div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 leading-snug">Sensor bersih tiap unit</div>
        </div>
        <div>
            <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white font-heading">3 Baterai</div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 leading-snug">Original, dapet semua</div>
        </div>
        <div>
            <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white font-heading">15 Menit</div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 leading-snug">Verifikasi KTP via WA</div>
        </div>
    </div>

    <!-- Brand Marquee -->
    <div class="jp-marquee mt-8 overflow-hidden" data-aos="fade-up">
        <div class="jp-marquee-track">
            <?php
            $brand_chips = [
                ['icon' => 'ri-camera-lens-line', 'label' => 'Sony Alpha'],
                ['icon' => 'ri-camera-3-line',    'label' => 'Canon EOS R'],
                ['icon' => 'ri-aperture-line',    'label' => 'Fujifilm X'],
                ['icon' => 'ri-focus-3-line',     'label' => 'Nikon Z'],
                ['icon' => 'ri-video-line',       'label' => 'Blackmagic 6K'],
                ['icon' => 'ri-movie-2-line',     'label' => 'Lumix S'],
                ['icon' => 'ri-flashlight-line',  'label' => 'Leica Q3'],
            ];
            $chips_double = array_merge($brand_chips, $brand_chips);
            for ($loop = 0; $loop < 2; $loop++):
            ?>
            <div class="flex items-center gap-3 pr-3 shrink-0">
                <?php foreach ($chips_double as $chip): ?>
                    <span class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white dark:bg-[#11131a] border border-slate-200 dark:border-white/10 text-xs font-semibold text-slate-600 dark:text-slate-300 whitespace-nowrap">
                        <i class="<?= $chip['icon'] ?> text-purple-600 dark:text-purple-400 text-sm"></i>
                        <span><?= $chip['label'] ?></span>
                    </span>
                <?php endforeach; ?>
            </div>
            <?php endfor; ?>
        </div>
    </div>
</section>


<!-- SECTION: BENTO GRID KEUNGGULAN (UNIK — ASYMMETRIC) -->
<section class="py-14 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
    <div class="text-center max-w-2xl mx-auto mb-10" data-aos="fade-up">
        <span class="text-xs uppercase tracking-widest text-purple-600 dark:text-purple-400 font-bold">Kenapa Pilih Jepret.in?</span>
        <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white font-heading mt-1">
            Alasan Kenapa Sewa di Sini Paling Enak
        </h2>
        <p class="text-slate-600 dark:text-slate-400 text-xs sm:text-sm mt-2">
            Kami gak cuma minjemin barang, tapi mastiin kamu langsung bisa berkarya tanpa hambatan.
        </p>
    </div>

    <!-- Unique Asymmetric Bento Grid -->
    <div class="grid grid-cols-1 md:grid-cols-6 gap-4">

        <!-- Wide card: Sensor Clean -->
        <div class="md:col-span-4 glass-panel rounded-3xl p-8" data-aos="fade-up">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-5 mb-6">
                <div>
                    <div class="w-11 h-11 rounded-2xl bg-purple-100 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 border border-purple-200 dark:border-purple-500/30 flex items-center justify-center mb-4">
                        <i class="ri-sparkling-2-line text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white font-heading">Sensor Spotless, Selalu Dicek</h3>
                    <p class="text-sm text-slate-600 dark:text-slate-300 mt-2 max-w-md leading-relaxed">
                        Pernah sewa kamera tapi pas ngedit ada bintik hitam di langit? Di sini gak bakal terjadi. Tiap kamera dicek pakai loupe pembesar dan ditiup blower sebelum diserahkan ke kamu.
                    </p>
                </div>
                <span class="shrink-0 px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-[#141b29] border border-slate-200 dark:border-white/10 text-slate-600 dark:text-slate-300 text-xs font-semibold">
                    Inspeksi Pra-Sewa
                </span>
            </div>
            <div class="grid grid-cols-3 gap-3 pt-5 border-t border-slate-100 dark:border-white/5 text-center">
                <div class="p-3 rounded-2xl bg-slate-50 dark:bg-[#141b29]">
                    <div class="text-sm font-bold text-slate-900 dark:text-white">0% Noda</div>
                    <div class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">Sensor Terjamin</div>
                </div>
                <div class="p-3 rounded-2xl bg-slate-50 dark:bg-[#141b29]">
                    <div class="text-sm font-bold text-slate-900 dark:text-white">Firmware Baru</div>
                    <div class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">AF Makin Cepat</div>
                </div>
                <div class="p-3 rounded-2xl bg-slate-50 dark:bg-[#141b29]">
                    <div class="text-sm font-bold text-slate-900 dark:text-white">IBIS On</div>
                    <div class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">Anti Goyang</div>
                </div>
            </div>
        </div>

        <!-- Tall card: 3 Batteries -->
        <div class="md:col-span-2 glass-card rounded-3xl p-7 flex flex-col justify-between" data-aos="fade-up" data-aos-delay="80">
            <div>
                <div class="w-11 h-11 rounded-2xl bg-purple-100 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 border border-purple-200 dark:border-purple-500/30 flex items-center justify-center mb-4">
                    <i class="ri-battery-2-charge-line text-2xl"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-900 dark:text-white font-heading">Otomatis 3 Baterai</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 leading-relaxed">
                    Sewa di tempat lain kadang cuma dikasih 1 baterai. Di Jepret.in, langsung dapet 3 baterai original, charger dual-slot, dan memory card kencang!
                </p>
            </div>
            <div class="mt-5 pt-4 border-t border-slate-100 dark:border-white/10 flex items-center justify-between text-xs text-purple-600 dark:text-purple-400 font-semibold">
                <span>Aman Seharian Penuh</span>
                <i class="ri-flashlight-line text-base"></i>
            </div>
        </div>

        <!-- Verifikasi card -->
        <div class="md:col-span-3 glass-card rounded-3xl p-7 flex flex-col justify-between" data-aos="fade-up" data-aos-delay="120">
            <div>
                <div class="w-11 h-11 rounded-2xl bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/30 flex items-center justify-center mb-4">
                    <i class="ri-shield-user-line text-2xl"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-900 dark:text-white font-heading">Syarat Gak Bikin Pusing</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 leading-relaxed">
                    Cukup kirim foto KTP, 1 dokumen pendukung (SIM/KTM), dan link Instagram aktif. Verifikasi lewat WhatsApp, beres dalam 15 menit. Tanpa deposit berbelit-belit.
                </p>
            </div>
            <div class="mt-5 pt-4 border-t border-slate-100 dark:border-white/10 flex items-center justify-between text-xs text-emerald-600 dark:text-emerald-400 font-semibold">
                <span>Verifikasi 15 Menit</span>
                <i class="ri-time-line text-base"></i>
            </div>
        </div>

        <!-- Delivery card -->
        <div class="md:col-span-3 glass-card rounded-3xl p-7 flex flex-col justify-between" data-aos="fade-up" data-aos-delay="160">
            <div>
                <div class="w-11 h-11 rounded-2xl bg-purple-100 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 border border-purple-200 dark:border-purple-500/30 flex items-center justify-center mb-4">
                    <i class="ri-inbox-archive-line text-2xl"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-900 dark:text-white font-heading">Ambil Sendiri atau Dikirim</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 leading-relaxed">
                    Mampir langsung ke studio kami di Surabaya, atau tinggal order kurir instan via Gojek/Grab. Kamera dikemas dalam hardcase busa tebal yang aman dari benturan.
                </p>
            </div>
            <div class="mt-5 pt-4 border-t border-slate-100 dark:border-white/10 flex items-center justify-between text-xs text-purple-600 dark:text-purple-400 font-semibold">
                <span>Pengiriman Instan se-Surabaya</span>
                <i class="ri-navigation-line text-base"></i>
            </div>
        </div>

    </div>
</section>

<!-- SECTION: FEATURED CAMERAS -->
<section class="py-14 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
    <div class="flex flex-col md:flex-row md:items-end justify-between mb-8 gap-4" data-aos="fade-up">
        <div>
            <span class="text-xs uppercase tracking-widest text-purple-600 dark:text-purple-400 font-bold">Paling Banyak Disewa</span>
            <h2 class="text-3xl font-extrabold text-slate-900 dark:text-white font-heading mt-1">
                Kamera Pilihan Anak Surabaya
            </h2>
            <p class="text-slate-600 dark:text-slate-400 text-xs sm:text-sm mt-1">Sering dipake buat prewed, hunting foto, dan project video komersial.</p>
        </div>
        <a href="katalog.php" class="btn-theme-secondary inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-semibold shrink-0">
            <span>Lihat Semua <?= $active_camera_count ?> Kamera</span>
            <i class="ri-arrow-right-line"></i>
        </a>
    </div>

    <!-- Product Bento Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($featured_cameras as $camera): ?>
            <div class="glass-card rounded-3xl overflow-hidden flex flex-col group" data-aos="fade-up">

                <!-- Image -->
                <div class="relative aspect-[4/3] bg-slate-100 dark:bg-black overflow-hidden border-b border-slate-100 dark:border-white/5">
                    <img src="<?= htmlspecialchars($camera['image']) ?>" alt="<?= htmlspecialchars($camera['name']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">

                    <div class="absolute top-4 left-4 flex flex-wrap gap-2">
                        <span class="px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider bg-white/90 text-slate-900 border border-slate-200 dark:bg-[#0e131d]/90 dark:text-white dark:border-white/15 shadow-xs">
                            <?= htmlspecialchars($camera['brand']) ?>
                        </span>
                        <span class="px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider bg-purple-100 text-purple-700 border border-purple-200 dark:bg-purple-950/60 dark:text-purple-300 dark:border-purple-500/30">
                            <?= htmlspecialchars($camera['tag']) ?>
                        </span>
                    </div>

                    <div class="absolute top-4 right-4">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[10px] font-semibold bg-white/90 text-emerald-600 border border-slate-200 dark:bg-[#0e131d]/90 dark:text-emerald-400 dark:border-white/15 shadow-xs">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            Ready Surabaya
                        </span>
                    </div>

                    <div class="absolute bottom-3 left-4 right-4 flex items-center justify-between">
                        <div class="bg-white/95 dark:bg-[#0e131d]/90 px-3 py-1.5 rounded-lg border border-slate-200 dark:border-white/10 shadow-sm">
                            <span class="text-[10px] text-slate-500 dark:text-slate-400 block -mb-0.5">Harga Sewa</span>
                            <span class="text-base font-black text-slate-900 dark:text-white font-heading"><?= formatRupiah($camera['daily_price']) ?> <span class="text-xs text-slate-500 dark:text-slate-400 font-normal">/ 24 jam</span></span>
                        </div>
                    </div>
                </div>

                <!-- Info -->
                <div class="p-6 flex-grow flex flex-col justify-between space-y-4">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white font-heading hover:text-purple-600 dark:hover:text-purple-400 transition-colors">
                            <a href="detail.php?id=<?= $camera['id'] ?>"><?= htmlspecialchars($camera['name']) ?></a>
                        </h3>
                        <p class="text-xs text-slate-600 dark:text-slate-400 mt-2 line-clamp-2 leading-relaxed">
                            <?= htmlspecialchars($camera['description']) ?>
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-[11px] text-slate-600 dark:text-slate-300 pt-2 border-t border-slate-100 dark:border-white/5">
                        <div class="p-2 rounded-lg bg-slate-50 dark:bg-[#141b29] flex items-center gap-1.5 truncate">
                            <i class="ri-focus-2-line text-purple-600 dark:text-purple-400 shrink-0"></i>
                            <span class="truncate"><?= htmlspecialchars($camera['sensor']) ?></span>
                        </div>
                        <div class="p-2 rounded-lg bg-slate-50 dark:bg-[#141b29] flex items-center gap-1.5 truncate">
                            <i class="ri-video-line text-purple-600 dark:text-purple-400 shrink-0"></i>
                            <span class="truncate"><?= htmlspecialchars($camera['video']) ?></span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <a href="detail.php?id=<?= $camera['id'] ?>" class="btn-theme-secondary flex-1 text-center py-2.5 px-3 rounded-xl text-xs font-semibold">
                            Lihat Spek
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
</section>

<!-- SECTION: TESTIMONI -->
<section class="py-14 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
    <div class="text-center max-w-xl mx-auto mb-10" data-aos="fade-up">
        <span class="text-xs uppercase tracking-widest text-purple-600 dark:text-purple-400 font-bold">Kata Teman Kreator</span>
        <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white font-heading mt-1">
            Cerita dari yang Udah Pernah Sewa
        </h2>
        <p class="text-xs text-slate-600 dark:text-slate-400 mt-2 leading-relaxed">
            Testimoni asli dari penyewa yang sudah selesai sewa di Jepret.in.
        </p>
        <a href="<?= !empty($nav_user) ? 'akun.php#testimoni' : 'login.php?next=' . urlencode('akun.php#testimoni') ?>"
           class="inline-flex items-center gap-1.5 mt-3 px-3 py-1.5 rounded-lg text-[11px] font-bold border border-purple-200 dark:border-purple-500/30 text-purple-600 dark:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-950/30 transition">
            <i class="ri-quill-pen-line"></i>
            <span>Tulis testimoni kamu</span>
        </a>
    </div>

    <?php $home_testimonials = testimonials_all(6); ?>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <?php if ($home_testimonials): ?>
            <?php foreach ($home_testimonials as $testimonial): ?>
                <div class="glass-card rounded-3xl p-6 flex flex-col justify-between" data-aos="fade-up">
                    <div class="space-y-3">
                        <div class="flex text-amber-500 gap-1 text-xs">
                            <?php for ($star = 1; $star <= 5; $star++): ?>
                                <i class="<?= $star <= (int)$testimonial['rating'] ? 'ri-star-fill' : 'ri-star-line' ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed italic">
                            "<?= htmlspecialchars((string)$testimonial['message']) ?>"
                        </p>
                    </div>
                    <div class="mt-6 pt-4 border-t border-slate-100 dark:border-white/5 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-purple-100 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300 font-bold flex items-center justify-center text-xs">
                            <?= htmlspecialchars(strtoupper(mb_substr((string)$testimonial['name'], 0, 1))) ?>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-slate-900 dark:text-white font-heading"><?= htmlspecialchars((string)$testimonial['name']) ?></h4>
                            <span class="text-[10px] text-slate-500 dark:text-slate-400">
                                <?= htmlspecialchars(testimonial_label($testimonial)) ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<!-- SECTION: BOTTOM CTA BANNER -->
<section class="py-14 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto" data-aos="fade-up">
    <div class="glass-panel rounded-3xl p-8 sm:p-12 text-center">
        <div class="max-w-2xl mx-auto space-y-5">
            <span class="px-3 py-1 rounded-xl text-xs font-bold uppercase tracking-wider bg-purple-100 text-purple-700 dark:bg-purple-950/60 dark:text-purple-400 border border-purple-200 dark:border-purple-500/30">
                Gak Perlu Ragu
            </span>
            <h2 class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white font-heading">
                Mau Konsultasi Dulu Sebelum Sewa?
            </h2>
            <p class="text-slate-600 dark:text-slate-300 text-xs sm:text-sm leading-relaxed">
                Bingung mending sewa Sony A7 IV atau Canon R6? Butuh rekomendasi lensa buat foto portrait? Tanya-tanya santai ke admin via WhatsApp, kami siap bantu!
            </p>
            <div class="flex flex-wrap items-center justify-center gap-3 pt-2">
                <a href="https://wa.me/<?= $wa_number ?>?text=Halo%20Admin%20Jepret.in%2C%20mau%20konsultasi%20kamera%20yang%20cocok%20dong" target="_blank" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold inline-flex items-center gap-2 px-6 py-3.5 text-xs rounded-xl shadow-md transition">
                    <i class="ri-whatsapp-fill text-base"></i>
                    <span>Chat Admin (+62 838-3210-4793)</span>
                </a>
                <a href="katalog.php" class="btn-theme-secondary inline-flex items-center gap-2 px-5 py-3.5 text-xs font-semibold rounded-xl">
                    <i class="ri-camera-lens-line text-base"></i>
                    <span>Lihat <?= $active_camera_count ?> Kamera</span>
                </a>
            </div>
        </div>
    </div>
</section>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
