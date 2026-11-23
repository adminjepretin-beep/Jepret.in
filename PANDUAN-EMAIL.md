# Panduan Singkat: SMTP Hosting & Uji Email Reset Password

Panduan ini untuk mengaktifkan pengiriman email reset password di hosting produksi.
Total waktu ±10 menit setelah email domain dibuat.

---

## 1. Siapkan email domain di hosting

Di cPanel (atau panel setara):

1. **Email Accounts** → buat `no-reply@domainkamu.com` (password kuat).
2. Buka **Connect Devices / Configure Mail Client**, catat:
   - **Outgoing server (SMTP)**: biasanya `mail.domainkamu.com`
   - **Port**: `465` dengan SSL, atau `587` dengan TLS
   - **Username**: alamat email **penuh** (`no-reply@domainkamu.com`), bukan `no-reply`
   - **Password**: password email tersebut
3. Pastikan domain punya record **SPF** dan **DKIM** aktif (biasanya ada menu *Email Deliverability* di cPanel). Tanpa ini email sering masuk spam.

---

## 2. Isi konfigurasi

Edit `config/mail.php`:

```php
return [
    'driver'      => 'smtp',
    'from_email'  => 'no-reply@domainkamu.com',
    'from_name'   => 'Jepret.in Surabaya',
    'app_url'     => 'https://domainkamu.com',   // wajib: dipakai untuk link reset

    'smtp_host'   => 'mail.domainkamu.com',
    'smtp_port'   => 465,
    'smtp_secure' => 'ssl',        // 'ssl' untuk 465, 'tls' untuk 587
    'smtp_user'   => 'no-reply@domainkamu.com',
    'smtp_pass'   => 'password-email-kamu',
];
```

**Alternatif (lebih aman, password tidak masuk git)** — isi lewat environment variable di hosting:

```
MAIL_DRIVER=smtp
MAIL_FROM=no-reply@domainkamu.com
SMTP_HOST=mail.domainkamu.com
SMTP_PORT=465
SMTP_SECURE=ssl
SMTP_USER=no-reply@domainkamu.com
SMTP_PASS=password-email-kamu
```

Nilai environment otomatis menimpa nilai di file. `app_url` tetap diisi di file karena bukan rahasia.

> Kalau hosting hanya menyediakan API (tanpa SMTP), pakai driver `api`:
> `'driver' => 'api', 'api_provider' => 'resend', 'api_key' => 're_...'` (atau `brevo` + `MAIL_API_KEY`).

---

## 2b. Alternatif: EmailJS (tanpa domain & tanpa SMTP)

EmailJS meneruskan email lewat akun email yang kamu hubungkan (mis. Gmail), jadi tidak perlu
verifikasi domain atau record DNS. Cocok untuk skala kecil; kuota gratis 200 email/bulan.

**Di dashboard EmailJS (https://dashboard.emailjs.com):**

1. **Email Services** → **Add New Service** → pilih **Gmail** → hubungkan akun Gmail → catat **Service ID**.
2. **Email Templates** → **Create New Template**. Isi:
   - **To Email**: `{{to_email}}`
   - **Subject**: `{{subject}}`
   - **Body**: contoh isi — `Halo {{to_name}}, klik link ini untuk membuat password baru: {{reset_link}} (berlaku {{expires}}).`
   Simpan, lalu catat **Template ID**.
3. **Account** → **General** → catat **Public Key**.
4. **Account** → **Security** → aktifkan **Allow EmailJS API for non-browser applications**
   dan **Use Private Key**, lalu buat/catat **Private Key**. Tanpa dua opsi ini, panggilan dari PHP akan ditolak.

**Konfigurasi (environment variable):**

```
MAIL_DRIVER=api
MAIL_API_PROVIDER=emailjs
EMAILJS_SERVICE_ID=service_xxxxxxx
EMAILJS_TEMPLATE_ID=template_xxxxxxx
EMAILJS_PUBLIC_KEY=xxxxxxxxxxxxxxx
EMAILJS_PRIVATE_KEY=xxxxxxxxxxxxxxx
```

**Uji kirim:**

```bash
php -r 'require "includes/mailer.php"; var_export(app_send_mail("emailkamu@gmail.com", "Tes Jepret.in", "<p>Halo</p>", ["to_name" => "Tes", "reset_link" => "https://example.com", "expires" => "60 menit"]));'
```

Catatan: isi email sepenuhnya mengikuti template di dashboard EmailJS, bukan HTML dari kode.
Karena itu variabel `to_name`, `reset_link`, dan `expires` harus ada di template.

---

## 3. Uji kirim email (tanpa lewat web)

Jalankan dari folder proyek di server (SSH/terminal hosting):

```bash
php -r 'require "includes/mailer.php"; $r = app_send_mail("emailkamu@gmail.com", "Tes Jepret.in", "<p>Halo, ini email uji dari server.</p>"); var_export($r);'
```

Harapan: `array('ok' => true, 'error' => '', 'driver' => 'smtp')`.

Kalau `ok => false`, pesan `error` menunjukkan penyebabnya (mis. "Gagal terhubung ke SMTP", "Password SMTP ditolak").

---

## 4. Uji alur lengkap di website

1. Buka `https://domainkamu.com/lupa-password.php`.
2. Masukkan email akun yang benar → muncul pesan netral "Kalau email itu terdaftar…".
3. Cek inbox (dan folder **spam**) → email "Reset Password Akun Jepret.in".
4. Klik **Buat Password Baru** → isi password baru (minimal 8 karakter) → simpan.
5. Login di `login.php` dengan password baru → harus masuk ke `akun.php`.
6. Buka lagi link lama dari email → harus muncul "tidak valid, sudah dipakai" (token sekali pakai).
7. Coba lagi `/lupa-password.php` dengan email yang **tidak** terdaftar → pesannya harus sama persis.

Kalau langkah 3 tidak ada email, cek `data/mail.log` — kalau ada isinya, berarti masih mode `log` (konfigurasi belum terbaca).

---

## 5. Kalau gagal

| Gejala | Penyebab umum | Solusi |
|---|---|---|
| "Gagal terhubung ke SMTP" | Host/port salah, atau port keluar diblokir hosting | Coba port 587 + `'tls'`; pastikan host `mail.domain…` bukan `domain…` |
| "Password SMTP ditolak" | Username bukan email penuh / password salah | Username harus `no-reply@domainkamu.com` |
| "Server SMTP tidak menerima AUTH LOGIN" | Server hanya mendukung AUTH PLAIN | Pakai driver `api` (Resend/Brevo) |
| "Alamat tujuan ditolak SMTP" | Server membatasi penerima / email salah ketik | Uji ke Gmail pribadi dulu |
| Email masuk spam | SPF/DKIM/DMARC belum diatur | Aktifkan di cPanel, tunggu propagasi DNS |
| Link mengarah ke `localhost` | `app_url` kosong/salah | Isi `app_url` dengan `https://domainkamu.com` |
| Halaman error 500 setelah klik kirim | Ekstensi `openssl`/`curl` tidak aktif | Aktifkan di panel hosting, atau pakai port 587 |

---

## 6. Alternatif tanpa email: link reset dari admin

Kalau belum mau menyiapkan SMTP/API sama sekali, alur reset tetap bisa jalan lewat admin:

1. Admin login → menu **Akun Pengguna** (`/admin/akun.php`).
2. Cari akun penyewa → klik **Buat Link Reset**.
3. Muncul link `…/reset-password.php?token=…` (hanya ditampilkan **sekali**).
4. Klik **Kirim via WhatsApp** — pesan sudah berisi link tersebut.
5. Penyewa membuka link, membuat password sendiri, lalu login seperti biasa.

Link tetap berlaku 60 menit, sekali pakai, dan mematikan link lama untuk akun itu. Tidak ada password sementara yang perlu dikirim, jadi admin tidak pernah tahu password penyewa.

Kelemahan: harus ada admin yang membalas permintaan. Untuk operasional 24 jam tanpa admin, aktifkan email (bagian 1–5 di atas).

---

## 7. Checklist sebelum online

- [ ] `driver` **bukan** `log` (mode log hanya untuk development).
- [ ] `app_url` sudah `https://…` domain asli.
- [ ] Folder `data/`, `config/`, dan `includes/` tidak bisa diakses dari browser (sudah ada `.htaccess` di ketiganya; untuk Nginx tambahkan `location ^~ /data/ { deny all; }`, `location ^~ /config/ { deny all; }`, `location ^~ /includes/ { deny all; }`).
- [ ] Password SMTP disimpan di environment, bukan di file yang di-commit.
- [ ] SPF + DKIM domain aktif.
- [ ] Sudah uji alur lupa password → email → reset → login sekali dari akun nyata.
- [ ] Kredensial admin default (`config/admin.php`) sudah diganti.
