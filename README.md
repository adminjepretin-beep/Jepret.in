# Jepret.in Surabaya - Website Rental Kamera & Cinema Gear

Website rental kamera sisi klien (client-side) modern dengan desain **Bento Grid**, efek **Glassmorphism**, dan **Scroll Animation (AOS)**. Didesain dengan palet warna **solid clean minimalist (tanpa gradien)**, icon berstandar industri (**Remix Icon**, bebas emoji), dan didukung integrasi **Database MySQL**.

---

## 🌟 Pembaruan Sesuai Permintaan

1. **NO Color Gradients**: Seluruh gradien warna telah dihapus sepenuhnya. Menggunakan kombinasi warna solid yang sharp, elegan, dan kontras tinggi (solid dark `#090c12`, solid white `#ffffff`, solid slate `#cbd5e1`, solid blue `#2563eb`, dan solid emerald `#10b981`).
2. **Icon Library Profesional (Remix Icon)**: Semua emoji dan emote kasar ditiadakan. Menggunakan **Remix Icon (`remixicon`)** yang tajam, minimalis, dan profesional.
3. **Generated Brand Logo**: Logo resmi telah digenerate dan terpasang di header navbar, favicon browser, serta footer (`assets/images/logo.jpg`).
4. **Foto Kamera Akurat Sesuai Unit**: 10 unit kamera menggunakan foto asli yang terverifikasi dan disimpan secara lokal di `assets/images/` untuk loading super cepat:
   - `sony_fx3.jpg` — Sony FX3 Cinema Line asli
   - `sony_a7iv.jpg` — Sony Alpha 7 IV asli
   - `canon_r5.jpg` — Canon EOS R5 asli
   - `canon_r6ii.jpg` — Canon EOS R6 Mark II asli
   - `fujifilm_xt5.jpg` — Fujifilm X-T5 asli
   - `fujifilm_x100vi.jpg` — Fujifilm X100VI asli
   - `bmpcc_6kpro.jpg` — Blackmagic Pocket Cinema 6K Pro asli
   - `nikon_z6iii.jpg` — Nikon Z6 II/III asli
   - `lumix_s5iix.jpg` — Panasonic Lumix S5 IIX asli
   - `leica_q3.jpg` — Leica Q3 asli
5. **Lokasi Studio Surabaya**:
   - `Area Surabaya, Jawa Timur` (Bisa ambil sendiri di studio atau kirim via Gojek / Grab)
6. **Nomor Kontak WhatsApp Resmi**:
   - `+6283832104793` (Link otomatis: `https://wa.me/6283832104793`)
7. **Database MySQL Support**:
   - Skrip migrasi database lengkap tersedia di [database.sql](file:///c:/Users/Fiha%20Aridhoi/OneDrive/Desktop/code/JASA/jepret-in/database.sql).
   - Koneksi PDO terkonfigurasi di [config/database.php](file:///c:/Users/Fiha%20Aridhoi/OneDrive/Desktop/code/JASA/jepret-in/config/database.php).
   - Dilengkapi fallback otomatis ke SQLite (`data/jepret.sqlite`): kalau MySQL belum jalan, semua data tetap tersimpan di database lokal (bukan array statis), jadi website tetap normal.

---

## 🗄️ Cara Setup Database MySQL

Semua data website (produk kamera, pelanggan, pemesanan, pembayaran) disimpan di database.

**Tanpa setup apa pun (default):** aplikasi otomatis memakai SQLite di `data/jepret.sqlite`. Tabel dibuat sendiri, dan 10 kamera di-seed dari `data/cameras_seed.php` saat tabel `cameras` masih kosong. Jadi website langsung jalan dan datanya tetap tersimpan di database.

**Kalau mau pakai MySQL (XAMPP / Laragon / MySQL CLI):**

1. Nyalakan MySQL, lalu buat database dan tabel:
   ```bash
   mysql -u root -p < database.sql
   ```
2. Isi kredensial lewat environment variable (tidak perlu mengedit kode):
   ```
   DB_DRIVER=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_NAME=jepret_in
   DB_USER=root
   DB_PASS=password-db
   APP_TIMEZONE=Asia/Jakarta
   ```
   Di hosting, isi lewat `.htaccess` (`SetEnv ...`) atau panel environment variable. Lihat `PANDUAN-DEPLOY.md` langkah 4.
3. Dengan `DB_DRIVER=mysql`, aplikasi berhenti dengan error jelas kalau MySQL gagal — bukan diam-diam pindah ke SQLite. Mode `auto` (default) tetap memakai SQLite sebagai cadangan development, dan mencatat peringatannya di `error_log`. Tabel yang belum ada dibuat otomatis, termasuk seed 10 kamera.

Tabel yang dipakai: `cameras`, `customers`, `bookings`, `payments`.

---

## 👤 Akun Pengguna & Login Terpadu

Halaman login cuma satu: `login.php` (juga ditautkan dari tombol **Masuk** di navbar).

- **Pengguna/customer** daftar di `daftar.php` (nama, email, no. WhatsApp, password), lalu masuk pakai email atau nomor WhatsApp.
- **Admin** masuk lewat halaman yang sama memakai username `admin`.
- Booking sekarang **wajib login**: kalau belum masuk, tombol booking mengarah ke login lalu kembali ke form kamera. Identitas penyewa diambil dari akun, jadi tidak bisa salah ketik.
- Setiap booking menyimpan `user_id`, sehingga **dashboard akun** (`akun.php`) menampilkan semua riwayat sewa, status pemesanan, dan status pembayaran milik pengguna itu.
- Password pengguna di-hash (`password_hash`), sesi memakai cookie HttpOnly + SameSite, dan login dibatasi 5 percobaan gagal per 10 menit.
- **Testimoni**: pengguna bisa kasih rating + cerita untuk pesanan berstatus **Selesai** langsung dari `akun.php` (satu testimoni per pesanan), dan testimoni itu otomatis muncul di section "Kata Teman Kreator" pada beranda.
- **Pencarian katalog** jalan real-time: mengetik di kolom cari atau ganti kategori/merk/urutan langsung menyaring kartu tanpa tombol Filter (tetap ada fallback filter server-side lewat URL).

---

## 🔐 Halaman Admin

Area admin ada di `http://localhost:8000/admin/` dan **tidak dipasang di menu/footer website publik** (juga diblokir di `robots.txt`). Kalau belum login admin, halaman admin otomatis melempar ke `login.php`.

- Kredensial admin **tidak ditulis di kode** dan tidak ada password bawaan. Isi lewat environment variable:
  - `ADMIN_USERNAME` (opsional, default `admin`)
  - `ADMIN_PASSWORD_HASH` (wajib, isinya hash bcrypt)
- Cara bikin hash, lalu isi ke `ADMIN_PASSWORD_HASH`:
  ```bash
  php -r "echo password_hash('PASSWORD_BARU', PASSWORD_DEFAULT), PHP_EOL;"
  ```
  Contoh development:
  ```bash
  ADMIN_PASSWORD_HASH='$2y$10$....' php -S localhost:8000
  ```
- Kalau `ADMIN_PASSWORD_HASH` kosong, login admin **dimatikan** (halaman publik tetap jalan). Detail di [config/admin.php](config/admin.php).
- Semua halaman di folder `admin/` dijaga `require_admin_login()` di `includes/auth.php`: tanpa sesi login, pengunjung langsung dilempar ke halaman login (HTTP 302).
- Login memakai token CSRF, `session_regenerate_id()` setelah berhasil, cookie HttpOnly + SameSite=Lax, dan dibatasi 5 percobaan gagal per 10 menit.

---

## 🧾 Pemesanan, Pelanggan & Pembayaran

Alur pemesanan online:

1. Customer buka detail kamera → isi form booking (jadwal, data diri, add-on, **metode pembayaran Cash / QRIS**, catatan).
2. Submit → `proses-booking.php` memvalidasi tanggal & menghitung ulang total di server, lalu menyimpan pesanan dengan kode `JP-YYMMDD-XXXX`.
3. Customer diarahkan ke `booking-sukses.php` untuk melihat rincian, instruksi bayar, dan tombol **Konfirmasi & Bayar via WhatsApp** (pesan WA otomatis berisi kode booking + metode bayar).
4. Admin memantau semuanya di area admin.

Penyimpanan: tabel `customers`, `bookings`, dan `payments` di database (lihat `includes/bookings.php` dan `includes/schema.php`).

**Cara tahu customer sudah bayar cash atau QRIS.** Pilihan di form booking cuma preferensi. Uangnya baru dianggap masuk setelah admin mencatatnya di menu **Pembayaran**: admin pilih pesanan, pilih metode yang benar-benar diterima (Cash / QRIS), isi nominal dan catatan bukti (misalnya "QRIS BCA ref 1234"). Sistem lalu menghitung status bayar otomatis: **Belum Dibayar**, **DP / Sebagian**, atau **Lunas** dari total nominal yang dicatat. Bisa dicatat berkali-kali (DP lalu pelunasan), dan semua riwayatnya tersimpan di tabel `payments`. Kalau transaksi batal, ubah status pemesanan jadi **Dibatalkan** supaya tidak ikut dihitung sebagai nilai pemesanan.

Di area admin tersedia (sidebar kiri):

- **Dashboard** — total pemesanan, yang menunggu konfirmasi, pembayaran yang sudah diterima, jumlah pelanggan, grafik riwayat pemesanan dengan pilihan rentang **7 / 14 / 30 / 90 hari atau semua riwayat**, rekap Cash vs QRIS, dan rincian status.
- **Pemesanan** — daftar semua booking (riwayat lengkap, tidak dibatasi 14 hari) + filter status, tombol WhatsApp ke penyewa, status bayar, dan ubah status (Menunggu Konfirmasi → Dikonfirmasi → Sedang Disewa → Selesai / Dibatalkan).
- **Pembayaran** — catat pembayaran cash/QRIS, lihat sisa tagihan, daftar pesanan yang belum lunas, dan riwayat 20 pembayaran terakhir.
- **Pelanggan** — data pelanggan dari tabel `customers`: jumlah pesanan, total nilai sewa, kode booking, dan tombol chat.

---

## 🚀 Menjalankan Website

Jalankan built-in server PHP di folder proyek:

```bash
php -S localhost:8000
```

Buka URL di browser:
```
http://localhost:8000
```
