<?php
// kontak.php - Lokasi Studio Surabaya, Jam Operasional & Hubungi Kami (Default Light: White + Orange, Dark: Black + Purple, NO GRADIENTS)
$page_title = 'Lokasi Studio Surabaya & Kontak - Jepret.in';
$current_page = 'kontak';
$wa_number = '6283832104793';

require_once __DIR__ . '/config/database.php';

$message_sent = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim(htmlspecialchars($_POST['nama'] ?? ''));
    $wa = trim(htmlspecialchars($_POST['wa'] ?? ''));
    $pesan = trim(htmlspecialchars($_POST['pesan'] ?? ''));

    if (!empty($nama) && !empty($pesan)) {
        $message_sent = true;

        // Save to MySQL if connected
        $pdo = Database::getConnection();
        if ($pdo) {
            try {
                $stmt = $pdo->prepare("INSERT INTO messages (name, phone, message) VALUES (?, ?, ?)");
                $stmt->execute([$nama, $wa, $pesan]);
            } catch (\Exception $e) {
                // Ignore DB error
            }
        }
    }
}

include_once __DIR__ . '/includes/header.php';
?>

<!-- HERO SECTION (BAHASA SANTAI) -->
<section class="pt-8 pb-8 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
    <div class="glass-panel rounded-3xl p-8 sm:p-12" data-aos="fade-down">
        <div class="max-w-2xl space-y-3">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-md bg-purple-50 border border-purple-200 dark:bg-[#161f30] dark:border-purple-500/30 text-purple-600 dark:text-purple-400 text-xs font-bold">
                <i class="ri-map-pin-2-line"></i>
                <span>Studio Surabaya: Area Surabaya, Jawa Timur</span>
            </div>
            <h1 class="text-3xl sm:text-5xl font-black text-slate-900 dark:text-white font-heading tracking-tight">
                Mau Main ke Studio <br><span class="text-purple-600 dark:text-purple-400">Atau Mau Ngobrol Dulu?</span>
            </h1>
            <p class="text-slate-600 dark:text-slate-300 text-xs sm:text-sm leading-relaxed">
                Punya pertanyaan seputar kamera, butuh saran lensa yang pas, atau mau ambil unit langsung di studio? Tim kami standby tiap hari di Surabaya.
            </p>
        </div>
    </div>
</section>

<!-- MAIN BENTO CONTACT SECTION -->
<section class="pb-24 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-stretch">
        
        <!-- Left: Bento Cards Info & Maps (7 cols) -->
        <div class="lg:col-span-7 space-y-5">
            
            <!-- Bento Contact Grid (2x2) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" data-aos="fade-right">
                
                <!-- Contact 1: WhatsApp CS -->
                <div class="glass-card rounded-2xl p-6 flex flex-col justify-between">
                    <div class="space-y-2.5">
                        <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/30 flex items-center justify-center">
                            <i class="ri-whatsapp-fill text-xl"></i>
                        </div>
                        <h4 class="font-bold text-slate-900 dark:text-white text-sm font-heading">WhatsApp Admin</h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Respon kilat buat cek jadwal dan tanya-tanya seputar kamera.</p>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 dark:border-white/5">
                        <a href="https://wa.me/<?= $wa_number ?>" target="_blank" class="text-xs text-emerald-600 dark:text-emerald-400 font-bold hover:underline flex items-center gap-1.5">
                            <span>+62 838-3210-4793</span>
                            <i class="ri-arrow-right-up-line text-sm"></i>
                        </a>
                    </div>
                </div>

                <!-- Contact 2: Jam Operasional -->
                <div class="glass-card rounded-2xl p-6 flex flex-col justify-between">
                    <div class="space-y-2.5">
                        <div class="w-10 h-10 rounded-xl bg-purple-100 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300 border border-purple-200 dark:border-purple-500/30 flex items-center justify-center">
                            <i class="ri-time-line text-xl"></i>
                        </div>
                        <h4 class="font-bold text-slate-900 dark:text-white text-sm font-heading">Jam Buka Studio</h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Senin - Minggu: 08.00 - 22.00 WIB</p>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 dark:border-white/5 flex items-center gap-1.5 text-xs text-slate-600 dark:text-slate-300 font-medium">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span>Buka Setiap Hari (Termasuk Tanggal Merah)</span>
                    </div>
                </div>

                <!-- Contact 3: Email Support -->
                <div class="glass-card rounded-2xl p-6 flex flex-col justify-between">
                    <div class="space-y-2.5">
                        <div class="w-10 h-10 rounded-xl bg-purple-100 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300 border border-purple-200 dark:border-purple-500/30 flex items-center justify-center">
                            <i class="ri-mail-line text-xl"></i>
                        </div>
                        <h4 class="font-bold text-slate-900 dark:text-white text-sm font-heading">Email Resmi</h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Buat kirim Purchase Order (PO) kantor atau kolaborasi project.</p>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 dark:border-white/5">
                        <a href="mailto:admin.jepretin@gmail.com" class="text-xs text-purple-600 dark:text-purple-400 font-bold hover:underline">
                            admin.jepretin@gmail.com
                        </a>
                    </div>
                </div>

                <!-- Contact 4: Social Media -->
                <div class="glass-card rounded-2xl p-6 flex flex-col justify-between">
                    <div class="space-y-2.5">
                        <div class="w-10 h-10 rounded-xl bg-purple-100 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300 border border-purple-200 dark:border-purple-500/30 flex items-center justify-center">
                            <i class="ri-instagram-line text-xl"></i>
                        </div>
                        <h4 class="font-bold text-slate-900 dark:text-white text-sm font-heading">Instagram Kami</h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Liat hasil jepretan temen-temen penyewa dan info kamera baru.</p>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 dark:border-white/5">
                        <a href="https://instagram.com" target="_blank" class="text-xs text-slate-600 dark:text-slate-300 font-bold hover:underline">
                            @jepret.in_surabaya
                        </a>
                    </div>
                </div>

            </div>

            <!-- Bento Studio Map Preview (Surabaya) -->
            <div class="glass-panel rounded-3xl p-6 sm:p-8 space-y-4" data-aos="fade-up">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-purple-100 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300 border border-purple-200 dark:border-purple-500/30 flex items-center justify-center shrink-0">
                            <i class="ri-map-pin-range-line text-xl"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-slate-900 dark:text-white text-sm font-heading">Studio Jepret.in Surabaya</h4>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Area Surabaya, Jawa Timur</p>
                        </div>
                    </div>
                    <a href="https://maps.google.com/?q=Surabaya" target="_blank" class="btn-theme-secondary px-3 py-1.5 rounded-xl text-xs font-semibold flex items-center gap-1.5">
                        <span>Buka di Google Maps</span>
                        <i class="ri-navigation-line"></i>
                    </a>
                </div>

                <!-- Visual Card Map Simulation -->
                <div class="relative rounded-2xl overflow-hidden aspect-[21/9] bg-slate-100 dark:bg-[#0c1018] border border-slate-200 dark:border-white/10 flex items-center justify-center text-center p-6">
                    <div class="space-y-2">
                        <div class="w-10 h-10 rounded-full bg-purple-600 dark:bg-purple-600 text-white flex items-center justify-center mx-auto shadow-md">
                            <i class="ri-map-pin-2-fill text-lg"></i>
                        </div>
                        <div class="text-slate-900 dark:text-white font-bold font-heading text-sm">Studio Jepret.in Surabaya</div>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 max-w-sm mx-auto">
                            Akses gampang di Surabaya, parkiran motor & mobil aman, dan bisa kirim via kurir instan se-Surabaya & Sidoarjo.
                        </p>
                    </div>
                </div>
            </div>

        </div>

        <!-- Right: Send Message Form (5 cols) -->
        <div class="lg:col-span-5 glass-panel rounded-3xl p-6 sm:p-8 flex flex-col justify-between" data-aos="fade-left">
            <div>
                <span class="text-xs uppercase tracking-widest text-purple-600 dark:text-purple-400 font-bold">Tinggalkan Pesan</span>
                <h3 class="text-2xl font-bold text-slate-900 dark:text-white font-heading mt-1">Form Tanya Santai</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Mau tanya kamera yang cocok atau jadwal sewa? Tulis pesan di bawah, nanti kami hubungi ke WhatsApp kamu ya.
                </p>

                <?php if ($message_sent): ?>
                    <div class="mt-4 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-500/30 text-emerald-800 dark:text-emerald-300 text-xs flex items-start gap-2.5">
                        <i class="ri-checkbox-circle-line text-base shrink-0 mt-0.5"></i>
                        <div>
                            <strong>Pesan Berhasil Terkirim!</strong>
                            <p class="mt-0.5">Makasih ya Kak <?= $nama ?>, tim Jepret.in bakal segera ngehubungin ke nomor WhatsApp kamu.</p>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="kontak.php" class="mt-5 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Nama Kamu</label>
                        <input type="text" name="nama" required placeholder="Contoh: Budi Santoso" class="glass-input w-full rounded-xl px-4 py-2.5 text-xs">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Nomor WhatsApp</label>
                        <input type="tel" name="wa" required placeholder="Contoh: 083832104793" class="glass-input w-full rounded-xl px-4 py-2.5 text-xs">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Pesan atau Pertanyaan Kamu</label>
                        <textarea name="pesan" rows="4" required placeholder="Mau nanya kamera apa, buat tanggal berapa, atau tanya syarat sewa..." class="glass-input w-full rounded-xl px-4 py-2.5 text-xs"></textarea>
                    </div>

                    <button type="submit" class="btn-theme-primary w-full py-3.5 px-6 rounded-xl text-xs font-bold flex items-center justify-center gap-2">
                        <i class="ri-send-plane-line text-sm"></i>
                        <span>Kirim Pesan Sekarang</span>
                    </button>
                </form>
            </div>

            <div class="mt-6 pt-4 border-t border-slate-100 dark:border-white/5 text-center">
                <span class="text-[11px] text-slate-500 dark:text-slate-400">Atau langsung chat WhatsApp admin kami di:</span>
                <a href="https://wa.me/<?= $wa_number ?>" target="_blank" class="text-xs text-emerald-600 dark:text-emerald-400 font-bold block mt-1 hover:underline">
                    +62 838-3210-4793 (Standby)
                </a>
            </div>
        </div>

    </div>
</section>

<?php include_once __DIR__ . '/includes/footer.php'; ?>

