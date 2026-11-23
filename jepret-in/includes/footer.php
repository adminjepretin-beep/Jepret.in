<?php
// includes/footer.php
$wa_number = '6283832104793';

// Pastikan data kamera tersedia di halaman mana pun yang memakai footer ini.
require_once __DIR__ . '/../data/cameras.php';
?>
    </main>

    <!-- Global Floating WhatsApp Button (Bulat Rapi, Tanpa Teks Bocor) -->
    <div class="fixed bottom-6 right-6 z-40 flex items-center group">
        <!-- Floating Tooltip to the left on hover -->
        <span class="mr-3 bg-slate-900 text-white dark:bg-[#141b29] dark:text-white text-xs font-semibold px-3 py-1.5 rounded-xl border border-slate-800 dark:border-white/10 opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap shadow-xl">
            Tanya Admin via WhatsApp
        </span>
        <!-- Round WhatsApp Circle Button -->
        <a href="https://wa.me/<?= $wa_number ?>?text=Halo%20Admin%20Jepret.in%20Surabaya%2C%20mau%20tanya%20sewa%20kamera%20dong" 
           target="_blank" 
           rel="noopener noreferrer" 
           class="w-14 h-14 bg-emerald-500 hover:bg-emerald-600 text-white rounded-full shadow-2xl flex items-center justify-center transition-transform duration-200 hover:scale-105 active:scale-95 shrink-0"
           aria-label="Chat WhatsApp Admin Jepret.in">
            <i class="ri-whatsapp-fill text-3xl leading-none"></i>
        </a>
    </div>

    <!-- Footer Santai & Clean (Polos, No Gradients) -->
    <footer class="relative z-10 border-t border-slate-200 dark:border-white/10 bg-white dark:bg-[#0a0d14] mt-24 transition-colors">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-10">
                <!-- Col 1: Brand & Sapaan Santai -->
                <div class="lg:col-span-2 space-y-4">
                    <a href="index.php" class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl overflow-hidden border border-slate-200 dark:border-white/15 bg-black shrink-0">
                            <img src="assets/images/logo.jpg" alt="Jepret.in Logo" class="w-full h-full object-cover">
                        </div>
                        <span class="font-heading font-extrabold text-2xl tracking-tight text-slate-900 dark:text-white">
                            Jepret<span class="text-purple-600 dark:text-purple-400">.in</span>
                        </span>
                    </a>
                    <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed max-w-sm">
                        Tempat sewa kamera paling asik di Surabaya. Mau bikin video konten, prewed, liburan, atau tugas kampus, tinggal sewa di sini. Alat bersih, dapet 3 baterai, gak ribet!
                    </p>
                    
                    <div class="pt-2">
                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-emerald-50 dark:bg-[#141b29] border border-emerald-200 dark:border-white/10 text-emerald-700 dark:text-emerald-400 text-xs font-semibold">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            Studio Surabaya Buka Setiap Hari (08.00 - 22.00 WIB)
                        </div>
                    </div>
                </div>

                <!-- Col 2: Menu Santai -->
                <div>
                    <h3 class="text-xs font-bold text-slate-900 dark:text-white tracking-wider uppercase mb-4 font-heading">Menu Cepat</h3>
                    <ul class="space-y-2.5 text-xs text-slate-600 dark:text-slate-400">
                        <li><a href="index.php" class="hover:text-purple-600 dark:hover:text-purple-400 transition">Halaman Utama</a></li>
                        <li><a href="katalog.php" class="hover:text-purple-600 dark:hover:text-purple-400 transition">Pilihan <?= activeCameraCount($cameras) ?> Kamera</a></li>
                        <li><a href="cara-sewa.php" class="hover:text-purple-600 dark:hover:text-purple-400 transition">Gimana Cara Sewanya?</a></li>
                        <li><a href="tentang.php" class="hover:text-purple-600 dark:hover:text-purple-400 transition">Kenalan sama Jepret.in</a></li>
                        <li><a href="kontak.php" class="hover:text-purple-600 dark:hover:text-purple-400 transition">Lokasi Studio & Kontak</a></li>
                    </ul>
                </div>

                <!-- Col 3: Kamera Favorit -->
                <div>
                    <h3 class="text-xs font-bold text-slate-900 dark:text-white tracking-wider uppercase mb-4 font-heading">Kamera Paling Laris</h3>
                    <ul class="space-y-2.5 text-xs text-slate-600 dark:text-slate-400">
                        <li><a href="detail.php?id=1" class="hover:text-purple-600 dark:hover:text-purple-400 transition flex items-center gap-1.5"><i class="ri-arrow-right-s-line text-purple-600 dark:text-purple-400"></i> Sony FX3 Cinema</a></li>
                        <li><a href="detail.php?id=2" class="hover:text-purple-600 dark:hover:text-purple-400 transition flex items-center gap-1.5"><i class="ri-arrow-right-s-line text-purple-600 dark:text-purple-400"></i> Sony A7 IV All-Rounder</a></li>
                        <li><a href="detail.php?id=6" class="hover:text-purple-600 dark:hover:text-purple-400 transition flex items-center gap-1.5"><i class="ri-arrow-right-s-line text-purple-600 dark:text-purple-400"></i> Fujifilm X100VI Viral</a></li>
                        <li><a href="detail.php?id=3" class="hover:text-purple-600 dark:hover:text-purple-400 transition flex items-center gap-1.5"><i class="ri-arrow-right-s-line text-purple-600 dark:text-purple-400"></i> Canon EOS R5 Resolusi 8K</a></li>
                        <li><a href="detail.php?id=10" class="hover:text-purple-600 dark:hover:text-purple-400 transition flex items-center gap-1.5"><i class="ri-arrow-right-s-line text-purple-600 dark:text-purple-400"></i> Leica Q3 Mewah</a></li>
                    </ul>
                </div>

                <!-- Col 4: Studio Surabaya -->
                <div>
                    <h3 class="text-xs font-bold text-slate-900 dark:text-white tracking-wider uppercase mb-4 font-heading">Studio Surabaya</h3>
                    <ul class="space-y-3 text-xs text-slate-600 dark:text-slate-400">
                        <li class="flex items-start gap-2.5">
                            <i class="ri-map-pin-2-line text-purple-600 dark:text-purple-400 text-sm shrink-0 mt-0.5"></i>
                            <span>Area Surabaya, Jawa Timur</span>
                        </li>
                        <li class="flex items-center gap-2.5">
                            <i class="ri-whatsapp-line text-emerald-600 dark:text-emerald-400 text-sm shrink-0"></i>
                            <a href="https://wa.me/<?= $wa_number ?>" target="_blank" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition">+62 838-3210-4793 (Fast Respon)</a>
                        </li>
                        <li class="flex items-center gap-2.5">
                            <i class="ri-mail-line text-purple-600 dark:text-purple-400 text-sm shrink-0"></i>
                            <a href="mailto:admin.jepretin@gmail.com" class="hover:text-purple-600 dark:hover:text-purple-400 transition">admin.jepretin@gmail.com</a>
                        </li>
                        <li class="flex items-center gap-2.5">
                            <i class="ri-instagram-line text-slate-500 dark:text-slate-300 text-sm shrink-0"></i>
                            <span>@jepret.in_surabaya</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Bottom Disclaimer & Copyright -->
            <div class="mt-14 pt-8 border-t border-slate-200 dark:border-white/10 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">
                <p>&copy; <?= date('Y') ?> Jepret.in Surabaya. Semua hak cipta dilindungi.</p>
                <div class="flex items-center gap-4 text-slate-500">
                    <span class="flex items-center gap-1"><i class="ri-map-pin-line text-purple-600 dark:text-purple-400"></i> Surabaya, Jatim</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- AOS (Animate on Scroll) JS Initialization -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof AOS !== 'undefined') {
                AOS.init({
                    once: true,
                    duration: 600,
                    easing: 'ease-out-cubic',
                    offset: 30
                });
            }
        });
    </script>
</body>
</html>

