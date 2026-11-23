<?php
// tentang.php - Tentang Jepret.in Rental Gear Surabaya (Default Light: White + Orange, Dark: Black + Purple, NO GRADIENTS)
$page_title = 'Kenalan Sama Jepret.in - Sewa Kamera Surabaya';
$current_page = 'tentang';
$wa_number = '6283832104793';

require_once __DIR__ . '/data/cameras.php';
$active_camera_count = activeCameraCount($cameras);
$active_unit_count   = activeCameraStock($cameras);

require_once __DIR__ . '/includes/testimonials.php';
$rating_stats = testimonials_rating_stats();
$rating_value = $rating_stats['count'] > 0
    ? number_format($rating_stats['average'], 1, '.', '') . ' / 5.0'
    : '—';
$rating_label = $rating_stats['count'] > 0 ? 'Kepuasan Klien Surabaya' : 'Belum Ada Ulasan Penyewa';

include_once __DIR__ . '/includes/header.php';
?>

<!-- HERO SECTION (BAHASA SANTAI) -->
<section class="pt-8 pb-8 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
    <div class="glass-panel rounded-3xl p-8 sm:p-12" data-aos="fade-down">
        <div class="max-w-3xl space-y-3">
            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-md bg-purple-50 border border-purple-200 dark:bg-[#161f30] dark:border-purple-500/30 text-purple-600 dark:text-purple-400 text-xs font-bold">
                <i class="ri-user-smile-line"></i>
                <span>Dari Sesama Kreator Buat Temen-Temen di Surabaya</span>
            </span>
            <h1 class="text-3xl sm:text-5xl font-black text-slate-900 dark:text-white font-heading tracking-tight leading-tight">
                Biar Bikin Karya Keren <br>
                <span class="text-purple-600 dark:text-purple-400">Gak Harus Beli Kamera Mahal.</span>
            </h1>
            <p class="text-slate-600 dark:text-slate-300 text-xs sm:text-sm leading-relaxed">
                Jepret.in berawal dari pengalaman kami sendiri pas mau bikin karya video komersial dan foto estetik tapi modal beli kamera puluhan juta belum cukup. Makanya kami bikin tempat rental yang alatnya terawat, sensornya bersih, dan pelayanannya santai kayak temen sendiri.
            </p>
        </div>
    </div>
</section>

<!-- BENTO STATS -->
<section class="pb-14 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4" data-aos="fade-up">
        <div class="glass-card rounded-2xl p-6 text-center">
            <div class="text-3xl font-black text-slate-900 dark:text-white font-heading">10.000+</div>
            <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Jam Berkarya Sukses</div>
        </div>
        <div class="glass-card rounded-2xl p-6 text-center">
            <div class="text-3xl font-black text-purple-600 dark:text-purple-400 font-heading">100%</div>
            <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Sensor Kinclong Bersih</div>
        </div>
        <div class="glass-card rounded-2xl p-6 text-center">
            <div class="text-3xl font-black text-emerald-600 dark:text-emerald-400 font-heading"><?= $active_unit_count ?> Unit</div>
            <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Kamera Flagship Terawat</div>
        </div>
        <div class="glass-card rounded-2xl p-6 text-center">
            <div class="text-3xl font-black text-amber-500 font-heading"><?= $rating_value ?></div>
            <div class="text-xs text-slate-500 dark:text-slate-400 mt-1"><?= $rating_label ?></div>
        </div>
    </div>
</section>

<!-- BENTO SECTION: FILOSOFI ALAT & STANDARD KAMI -->
<section class="pb-14 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
    <div class="text-center max-w-xl mx-auto mb-10" data-aos="fade-up">
        <span class="text-xs uppercase tracking-widest text-purple-600 dark:text-purple-400 font-bold">Perawatan Kamera</span>
        <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white font-heading mt-1">Standar Alat yang Selalu Kami Jaga</h2>
        <p class="text-slate-600 dark:text-slate-400 text-xs sm:text-sm mt-1">Kami rawat tiap kamera layaknya kamera pribadi yang siap dipakai besok pagi.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Box 1 -->
        <div class="glass-card rounded-3xl p-7 flex flex-col justify-between" data-aos="fade-up" data-aos-delay="50">
            <div>
                <div class="w-10 h-10 rounded-xl bg-purple-100 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300 border border-purple-200 dark:border-purple-500/30 flex items-center justify-center mb-4">
                    <i class="ri-focus-3-line text-xl"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-900 dark:text-white font-heading">Dry Box & Bebas Debu</h3>
                <p class="text-xs text-slate-600 dark:text-slate-400 mt-2 leading-relaxed">
                    Semua body kamera dan lensa disimpan di dry box elektronik dengan kelembaban stabil 40% RH, bebas jamur, dan ditiup blower antistatik tiap kali selesai disewa.
                </p>
            </div>
            <div class="mt-6 pt-4 border-t border-slate-100 dark:border-white/5 text-purple-600 dark:text-purple-400 text-xs font-semibold flex items-center gap-1.5">
                <i class="ri-checkbox-circle-line"></i>
                <span>Nol Debu Sensor</span>
            </div>
        </div>

        <!-- Box 2 -->
        <div class="glass-card rounded-3xl p-7 flex flex-col justify-between" data-aos="fade-up" data-aos-delay="100">
            <div>
                <div class="w-10 h-10 rounded-xl bg-purple-100 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300 border border-purple-200 dark:border-purple-500/30 flex items-center justify-center mb-4">
                    <i class="ri-battery-2-charge-line text-xl"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-900 dark:text-white font-heading">Kesehatan Baterai Asli</h3>
                <p class="text-xs text-slate-600 dark:text-slate-400 mt-2 leading-relaxed">
                    Kami gak pake baterai tiruan atau KW murah yang gampang ngedrop. Baterai Sony, Canon, dan Fuji di Jepret.in 100% original dan kapasitas prima.
                </p>
            </div>
            <div class="mt-6 pt-4 border-t border-slate-100 dark:border-white/5 text-purple-600 dark:text-purple-400 text-xs font-semibold flex items-center gap-1.5">
                <i class="ri-checkbox-circle-line"></i>
                <span>3 Baterai Included</span>
            </div>
        </div>

        <!-- Box 3 -->
        <div class="glass-card rounded-3xl p-7 flex flex-col justify-between" data-aos="fade-up" data-aos-delay="150">
            <div>
                <div class="w-10 h-10 rounded-xl bg-purple-100 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300 border border-purple-200 dark:border-purple-500/30 flex items-center justify-center mb-4">
                    <i class="ri-shield-check-line text-xl"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-900 dark:text-white font-heading">Hardcase Busa Aman</h3>
                <p class="text-xs text-slate-600 dark:text-slate-400 mt-2 leading-relaxed">
                    Setiap paket kamera dikemas dalam tas atau koper pelindung berbusa tebal yang tahan benturan dan cipratan air saat dibawa naik motor atau mobil.
                </p>
            </div>
            <div class="mt-6 pt-4 border-t border-slate-100 dark:border-white/5 text-purple-600 dark:text-purple-400 text-xs font-semibold flex items-center gap-1.5">
                <i class="ri-checkbox-circle-line"></i>
                <span>Aman Dibawa Hunting</span>
            </div>
        </div>
    </div>
</section>

<!-- STUDIO CULTURE CALLOUT -->
<section class="pb-24 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
    <div class="glass-panel rounded-3xl p-8 sm:p-12 flex flex-col md:flex-row items-center justify-between gap-6" data-aos="fade-up">
        <div class="space-y-2 max-w-xl">
            <h3 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white font-heading">Mampir ke Studio Kami di Surabaya Yuk!</h3>
            <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                Mau liat-liat fisik kamera dulu, nyobain megang bodinya, atau ngobrol santai seputar lensa dan settingan kamera? Dateng aja ke studio kami di Surabaya.
            </p>
        </div>
        <div class="shrink-0 flex items-center gap-3">
            <a href="kontak.php" class="btn-theme-primary px-5 py-3 rounded-xl text-xs font-bold">
                Cek Lokasi Studio
            </a>
            <a href="katalog.php" class="btn-theme-secondary px-4 py-3 rounded-xl text-xs font-semibold">
                Lihat <?= $active_camera_count ?> Kamera
            </a>
        </div>
    </div>
</section>

<?php include_once __DIR__ . '/includes/footer.php'; ?>

