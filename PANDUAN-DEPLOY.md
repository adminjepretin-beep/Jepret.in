# Panduan Deploy ke Hosting cPanel

Panduan langkah-demi-langkah memindahkan Jepret.in ke hosting cPanel (shared hosting).
Perkiraan waktu: 20–30 menit untuk pemasangan pertama.

---

## 0. Prasyarat

| Kebutuhan | Keterangan |
|---|---|
| PHP | **8.0 atau lebih baru** (kode memakai `match`, `str_contains`). Disarankan 8.1/8.2 |
| Ekstensi PHP | `pdo_mysql`, `pdo_sqlite` (cadangan), `mbstring`, `fileinfo`, `openssl`, `curl` |
| Database | MySQL 5.7+ / MariaDB 10.3+ |
| Akses | cPanel + File Manager (atau FTP/SSH) |
| Konfigurasi | Environment variable `DB_*`, `APP_TIMEZONE`, dan `ADMIN_*` (lihat langkah 4) |

---

## 1. Setel PHP & ekstensi

1. cPanel → **MultiPHP Manager** → pilih domain → set versi **PHP 8.1/8.2** → Apply.
2. cPanel → **Select PHP Version** (atau **PHP Selector**) → tab **Extensions** → centang:
   `pdo_mysql`, `pdo_sqlite`, `mbstring`, `fileinfo`, `curl`, `openssl`.
3. Simpan.

---

## 2. Upload file

1. Zip folder proyek di komputer (**kecuali** file berikut):
   - `data/jepret.sqlite` (database lokal kamu)
   - `data/mail.log`, `data/_server_run.log`
   - `*.png` di root (tangkapan layar review: `login-review.png`, `akun-dashboard-review.png`)
2. cPanel → **File Manager** → masuk `public_html` → **Upload** zip → klik kanan → **Extract**.
3. Pastikan strukturnya seperti ini (bukan `public_html/jepret-in/...`):

```
public_html/
├── index.php, katalog.php, detail.php, login.php, ...
├── admin/
├── assets/          (css, js, images)
├── config/          (.htaccess di sini memblokir akses web)
├── data/            (.htaccess juga)
├── includes/        (.htaccess juga)
├── database.sql
├── robots.txt
└── .htaccess lain di root (kalau ada)
```

> File `.htaccess` **tersembunyi** di File Manager — aktifkan *Settings → Show Hidden Files* untuk memastikan ikut terupload.

---

## 3. Buat database MySQL

1. cPanel → **MySQL® Databases**.
2. **Create New Database**: `jepret_in` → jadi `akunmu_jepret_in`.
3. **Add New User**: user + password kuat → catat.
4. **Add User To Database** → pilih user & DB → centang **ALL PRIVILEGES** → Make Changes.

Catat tiga nilai ini: **nama DB**, **user DB**, **password** (semuanya biasanya berprefix `akunmu_`).

---

## 4. Isi kredensial database (environment variable)

Kredensial **tidak lagi ditulis di dalam kode**. Isi lewat environment variable:

| Variable | Contoh | Keterangan |
|---|---|---|
| `DB_DRIVER` | `mysql` | `auto` / `mysql` / `sqlite`. **Di hosting pakai `mysql`.** |
| `DB_HOST` | `127.0.0.1` | Pakai IP, bukan `localhost` |
| `DB_PORT` | `3306` | |
| `DB_NAME` | `akunmu_jepret_in` | Nama database dari cPanel |
| `DB_USER` | `akunmu_jepret` | User database |
| `DB_PASS` | `password-db-kamu` | Password database |
| `APP_TIMEZONE` | `Asia/Jakarta` | Zona tanggal booking & `created_at` |
| `ADMIN_USERNAME` | `admin` | Username login admin (opsional) |
| `ADMIN_PASSWORD_HASH` | `$2y$10$...` | Hash password admin — lihat langkah 8 |

Cara paling gampang di cPanel: buat/buka `.htaccess` di `public_html` lalu tambahkan:

```apache
SetEnv DB_DRIVER mysql
SetEnv DB_HOST 127.0.0.1
SetEnv DB_PORT 3306
SetEnv DB_NAME akunmu_jepret_in
SetEnv DB_USER akunmu_jepret
SetEnv DB_PASS password-db-kamu
SetEnv APP_TIMEZONE Asia/Jakarta
SetEnv ADMIN_USERNAME admin
SetEnv ADMIN_PASSWORD_HASH '$2y$10$....'
```

> Tanda kutip tunggal di `ADMIN_PASSWORD_HASH` dipakai karena hash bcrypt mengandung karakter `$`. Tanpa tanda kutip, sebagian hosting bisa memotong nilainya.

Kalau hosting menyediakan menu *Environment Variables* / *PHP Variables*, isi di sana dengan nilai yang sama.

> **Penting — `DB_DRIVER=mysql`:** kalau MySQL gagal, aplikasi langsung berhenti dengan pesan error yang menyebut host/database/user beserta penyebabnya. Jadi tidak ada lagi kasus "admin input data, ternyata yang tersimpan SQLite".
>
> Kalau `DB_DRIVER` dibiarkan `auto`, aplikasi tetap mencoba MySQL lalu pindah ke SQLite dan hanya mencatat peringatan di `error_log` cPanel. Mode itu hanya untuk development lokal.

> Pakai `127.0.0.1`, bukan `localhost`. Aplikasi mengecek port MySQL lewat TCP; kalau host diisi `localhost` dan server hanya menerima socket, koneksi dianggap gagal.

Tabel **tidak perlu dibuat manual** — `schema_install()` membuat semua tabel dan mengisi 10 kamera contoh saat request pertama. (Kalau mau manual: phpMyAdmin → Import → `database.sql`.)

### Kalau hosting tidak mendukung environment variable (InfinityFree, free hosting)

Sebagian hosting (contoh: InfinityFree) menonaktifkan `putenv` dan `SetEnv` di `.htaccess` tidak terbaca PHP. Untuk itu pakai file **`config/local.php`** (sudah diabaikan Git, jadi aman):

1. Salin `config/local.example.php` menjadi `config/local.php` di hosting.
2. Isi nilainya:

```php
<?php
return [
    'db' => [
        'driver' => 'mysql',
        'host'   => 'sqlXXX.infinityfree.com',
        'port'   => '3306',
        'name'   => 'if0_XXXXXXXX_jepret',
        'user'   => 'if0_XXXXXXXX',
        'pass'   => 'password-database',
    ],
    'admin' => [
        'username'      => 'admin',
        'password_hash' => '$2y$10$....',
    ],
    'whatsapp' => [
        'driver' => 'fonnte',
        'token'  => 'token-fonnte',
    ],
];
```

Nilai di `config/local.php` **menang** atas environment variable, jadi tidak perlu mengisi `.htaccess` sama sekali.

Cek cepat dari terminal hosting (kalau ada SSH):

```bash
php -r 'require "config/database.php"; echo Database::driver(), PHP_EOL;'
```

Harus mencetak `mysql`. Kalau `sqlite`, kredensial/host masih salah.

---

## 5. Atur permission

Lewat File Manager (klik kanan → **Change Permissions**) atau SSH:

```bash
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;
chmod 775 assets/images data     # dua folder ini harus bisa ditulis
```

- `assets/images` → tempat upload foto kamera dari admin.
- `data` → dipakai SQLite cadangan dan `mail.log` (mode log).

Pemilik file sebaiknya user akun hosting (bukan `root`), supaya PHP bisa menulis.

---

## 6. HTTPS & alamat website

1. cPanel → **SSL/TLS Status** → **Run AutoSSL** → tunggu sampai domain berstatus valid.
2. Edit `config/mail.php`:

```php
'app_url' => 'https://domainkamu.com',
```

Ini dipakai untuk link reset password di email — kalau salah, link akan mengarah ke localhost.

3. HTTPS sudah dipaksa oleh `.htaccess` di root (ikut terupload di langkah 2): semua permintaan `http://` dialihkan ke `https://`, sedangkan `localhost`/`127.0.0.1` dibiarkan supaya development tetap jalan. File itu juga mematikan daftar isi folder dan memblokir file `.sql`, `.log`, `.md`, `.sqlite`, serta `.png` di root.

> Kalau hosting mematikan `mod_rewrite`, buka bagian itu dan komentari blok `<IfModule mod_rewrite.c>` — sisa proteksinya tetap jalan.

---

## 7. Uji halaman utama

Buka berurutan dan pastikan hasilnya:

| URL | Harapan |
|---|---|
| `https://domainkamu.com/` | Beranda tampil, angka kamera sesuai data |
| `/katalog.php` | 10 kartu kamera, filter jalan |
| `/detail.php?id=1` | Detail + kalender ketersediaan |
| `/login.php` | Form masuk; login admin pakai username `ADMIN_USERNAME` + password yang hash-nya kamu isi di `ADMIN_PASSWORD_HASH` |
| `/admin/` setelah login | Dashboard admin + menu **Kamera** |
| Upload foto kamera di admin | Foto tersimpan di `assets/images` dan tampil |
| `/lupa-password.php` | Isi nomor WhatsApp + email akun; kalau cocok, link reset dikirim ke WhatsApp (lihat setelan `WA_DRIVER` / `WA_API_TOKEN` di langkah 8) |

### Cek proteksi folder

Buka tiga URL ini — **harus 403 Forbidden**, bukan menampilkan isi file:

```
https://domainkamu.com/config/mail.php
https://domainkamu.com/includes/auth.php
https://domainkamu.com/data/jepret.sqlite
```

Kalau salah satunya terbuka, berarti `.htaccess` tidak terupload atau `AllowOverride` dimatikan hosting — hubungi support hosting.

---

## 8. Amankan sebelum diumumkan

1. **Setel password admin** (wajib — tidak ada password bawaan, login admin mati sampai ini diisi):

```bash
php -r "echo password_hash('PasswordBaruKamu#2026', PASSWORD_DEFAULT), PHP_EOL;"
```

Tempel hasilnya ke `SetEnv ADMIN_PASSWORD_HASH` di `.htaccess` (pakai tanda kutip tunggal), lalu login ulang untuk memastikan. Kalau mau ganti password nanti, ulangi langkah ini dan timpa nilainya.

2. Hapus `data/jepret.sqlite` kalau tidak ingin data lokal ikut terpakai sebagai cadangan.
3. Pastikan `display_errors` **Off** di PHP (biasanya default di hosting).
4. Email **tidak wajib** disetel. Reset password memakai nomor WhatsApp + email akun sebagai kunci, lalu link-nya dikirim ke WhatsApp.

5. Supaya link benar-benar terkirim ke WhatsApp, siapkan gateway Fonnte lalu isi di `.htaccess`:

```apache
SetEnv WA_DRIVER fonnte
SetEnv WA_API_TOKEN token-dari-dashboard-fonnte
```

Tanpa token, pesan tidak dikirim dan hanya ditulis ke `data/whatsapp.log` (mode development). Pastikan file log itu tidak ikut ter-upload.

---

## 9. Kalau ada masalah

| Gejala | Penyebab | Solusi |
|---|---|---|
| Halaman putih / 500 | Versi PHP terlalu lama, atau error tersembunyi | Set PHP 8.1+, cek `error_log` di cPanel |
| `could not find driver` | Ekstensi `pdo_mysql` belum aktif | Aktifkan di Select PHP Version |
| Data hilang setelah refresh | Aplikasi memakai SQLite padahal mau MySQL | Set `DB_DRIVER=mysql` lalu cek `Database::driver()`; peringatan fallback ada di `error_log` |
| Halaman 500 "tidak bisa terhubung ke MySQL" | `DB_DRIVER=mysql` dan kredensial/host salah | Perbaiki `DB_HOST`/`DB_NAME`/`DB_USER`/`DB_PASS` sesuai pesan error |
| `Access denied for user` | User belum ditambahkan ke DB, atau password salah | cPanel → MySQL Databases → Add User To Database (ALL PRIVILEGES) |
| Jam booking/kode booking beda hari | `APP_TIMEZONE` salah/kosong, server memakai UTC | Set `APP_TIMEZONE=Asia/Jakarta` |
| Upload foto gagal | `assets/images` tidak writable | `chmod 775 assets/images` |
| Foto terupload tapi tidak tampil | Permission file 600 / owner salah | `chmod 644` pada file di `assets/images` |
| `.htaccess` bikin 500 | Hosting membatasi direktif | Ganti isi dengan `Deny from all` saja, atau hubungi support |
| CSS/JS tidak muncul | Folder `assets` tidak terupload lengkap | Cek `assets/css/style.css` bisa dibuka langsung |
| Login admin selalu gagal | `ADMIN_PASSWORD_HASH` kosong, salah kutip, atau hash tidak cocok | Cek env var-nya; generate hash baru dengan perintah di langkah 8, lalu pastikan `error_log` tidak berisi "ADMIN_PASSWORD_HASH" |

---

## 10. Checklist akhir

- [ ] PHP 8.1+ dan ekstensi `pdo_mysql` aktif.
- [ ] Database MySQL dibuat, user diberi ALL PRIVILEGES.
- [ ] `DB_*` terisi lewat environment variable, dengan `DB_DRIVER=mysql`.
- [ ] `APP_TIMEZONE=Asia/Jakarta` terisi.
- [ ] `Database::driver()` mencetak `mysql`.
- [ ] `assets/images` dan `data` writable (775).
- [ ] HTTPS aktif dan `app_url` sudah `https://domainkamu.com`.
- [ ] `.htaccess` di root, `config/`, `data/`, `includes/` terupload (uji 403).
- [ ] `ADMIN_USERNAME` & `ADMIN_PASSWORD_HASH` terisi, dan login admin berhasil.
- [ ] Email reset password dikonfigurasi (`PANDUAN-EMAIL.md`).
- [ ] Sudah coba: booking kamera, upload foto admin, dan alur lupa password sekali.
