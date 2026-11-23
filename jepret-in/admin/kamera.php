<?php
// admin/kamera.php - Kelola katalog kamera: tambah, ubah, hapus, foto, dan stok
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin_layout.php';
require_once __DIR__ . '/../data/cameras.php';

require_admin_login();

const KAMERA_CATEGORIES = ['Mirrorless', 'Cinema', 'Compact'];
const KAMERA_MAX_UPLOAD = 3145728; // 3 MB
const KAMERA_UPLOAD_DIR = 'assets/images';

$csrf = admin_csrf_token();
$pdo  = Database::getConnection();

function kamera_slugify(string $text): string
{
    $slug = strtolower(trim($text));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
    return trim($slug, '-');
}

/**
 * Buat slug unik, supaya dua kamera dengan nama mirip tidak bentrok.
 */
function kamera_unique_slug(PDO $pdo, string $base, int $ignore_id = 0): string
{
    $base = $base !== '' ? $base : 'kamera';
    $slug = $base;
    $suffix = 2;

    while (true) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM cameras WHERE slug = ? AND id <> ?');
        $stmt->execute([$slug, $ignore_id]);
        if ((int)$stmt->fetchColumn() === 0) {
            return $slug;
        }
        $slug = $base . '-' . $suffix;
        $suffix++;
    }
}

/**
 * Simpan foto kamera ke assets/images.
 * Nama file memakai slug + suffix acak supaya foto pengganti tidak nyangkut di cache browser.
 *
 * @return array{0: ?string, 1: ?string} [path relatif, pesan error]
 */
function kamera_save_upload(array $file, string $slug): array
{
    $error_code = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);

    if ($error_code === UPLOAD_ERR_NO_FILE) {
        return [null, null];
    }
    if ($error_code !== UPLOAD_ERR_OK) {
        return [null, 'Foto gagal diupload. Coba pilih ulang file-nya.'];
    }
    if ((int)($file['size'] ?? 0) > KAMERA_MAX_UPLOAD) {
        return [null, 'Ukuran foto maksimal 3 MB.'];
    }

    $info = @getimagesize((string)$file['tmp_name']);
    $allowed = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    if (!$info || !isset($allowed[$info[2]])) {
        return [null, 'Format foto harus JPG, PNG, atau WebP.'];
    }

    $filename = $slug . '-' . bin2hex(random_bytes(3)) . '.' . $allowed[$info[2]];
    $target   = dirname(__DIR__) . '/' . KAMERA_UPLOAD_DIR . '/' . $filename;

    if (!@move_uploaded_file((string)$file['tmp_name'], $target)) {
        return [null, 'Foto gagal disimpan ke folder assets/images.'];
    }

    return [KAMERA_UPLOAD_DIR . '/' . $filename, null];
}

/**
 * Hapus file foto hasil upload admin (pola: <slug>-<6 hex>.<ext>).
 * Foto bawaan/seed tidak pernah dihapus supaya aman.
 */
function kamera_delete_upload(?string $path): void
{
    if (!$path || !preg_match('#^' . preg_quote(KAMERA_UPLOAD_DIR, '#') . '/[a-z0-9\-]+-[0-9a-f]{6}\.(jpg|png|webp)$#', $path)) {
        return;
    }

    $file = dirname(__DIR__) . '/' . $path;
    if (is_file($file)) {
        @unlink($file);
    }
}

$defaults = [
    'id'            => 0,
    'name'          => '',
    'brand'         => '',
    'category'      => 'Mirrorless',
    'tag'           => '',
    'daily_price'   => '',
    'stock'         => '1',
    'image'         => '',
    'sensor'        => '',
    'video'         => '',
    'iso'           => '',
    'mount'         => '',
    'weight'        => '',
    'stabilization' => '',
    'description'   => '',
    'includes'      => '',
    'is_featured'   => 0,
    'is_available'  => 1,
];

$errors = [];
$stmt = null;
$form = $defaults;
$form_from_post = false;
$action  = (string)($_POST['action'] ?? '');
$edit_id = (int)($_GET['edit'] ?? ($_POST['camera_id'] ?? 0));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!admin_verify_csrf($_POST['csrf'] ?? null)) {
        $errors[] = 'Sesi form kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } elseif ($action === 'delete') {
        $id = (int)($_POST['camera_id'] ?? 0);

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM bookings WHERE camera_id = ?');
        $stmt->execute([$id]);
        $used = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare('SELECT image FROM cameras WHERE id = ?');
        $stmt->execute([$id]);
        $image = $stmt->fetchColumn();

        if ($id <= 0) {
            $errors[] = 'Kamera tidak ditemukan.';
        } elseif ($used > 0) {
            $errors[] = 'Kamera ini sudah punya ' . $used . ' pemesanan, jadi tidak dihapus. Nonaktifkan saja supaya riwayat sewa tetap utuh.';
            $edit_id = $id;
        } else {
            $pdo->prepare('DELETE FROM cameras WHERE id = ?')->execute([$id]);
            kamera_delete_upload(is_string($image) ? $image : null);
            header('Location: kamera.php?deleted=1');
            exit;
        }
    } elseif ($action === 'toggle') {
        $id    = (int)($_POST['camera_id'] ?? 0);
        $field = (string)($_POST['field'] ?? '');

        if ($id > 0 && in_array($field, ['is_available', 'is_featured'], true)) {
            $pdo->prepare("UPDATE cameras SET $field = CASE WHEN $field = 1 THEN 0 ELSE 1 END WHERE id = ?")
                ->execute([$id]);
            header('Location: kamera.php?updated=1');
            exit;
        }

        $errors[] = 'Gagal mengubah status kamera.';
    } elseif ($action === 'save') {
        $form = [
            'id'            => $edit_id,
            'name'          => trim((string)($_POST['name'] ?? '')),
            'brand'         => trim((string)($_POST['brand'] ?? '')),
            'category'      => (string)($_POST['category'] ?? 'Mirrorless'),
            'tag'           => trim((string)($_POST['tag'] ?? '')),
            'daily_price'   => trim((string)($_POST['daily_price'] ?? '')),
            'stock'         => trim((string)($_POST['stock'] ?? '1')),
            'image'         => trim((string)($_POST['current_image'] ?? '')),
            'sensor'        => trim((string)($_POST['sensor'] ?? '')),
            'video'         => trim((string)($_POST['video'] ?? '')),
            'iso'           => trim((string)($_POST['iso'] ?? '')),
            'mount'         => trim((string)($_POST['mount'] ?? '')),
            'weight'        => trim((string)($_POST['weight'] ?? '')),
            'stabilization' => trim((string)($_POST['stabilization'] ?? '')),
            'description'   => trim((string)($_POST['description'] ?? '')),
            'includes'      => trim((string)($_POST['includes'] ?? '')),
            'is_featured'   => !empty($_POST['is_featured']) ? 1 : 0,
            'is_available'  => !empty($_POST['is_available']) ? 1 : 0,
        ];
        $form_from_post = true;

        if (mb_strlen($form['name']) < 3) {
            $errors[] = 'Nama kamera minimal 3 karakter.';
        }
        if ($form['brand'] === '') {
            $errors[] = 'Merk/brand wajib diisi.';
        }
        if (!in_array($form['category'], KAMERA_CATEGORIES, true)) {
            $errors[] = 'Kategori kamera tidak dikenal.';
        }
        if (!preg_match('/^\d+$/', (string)$form['daily_price']) || (int)$form['daily_price'] <= 0) {
            $errors[] = 'Harga sewa harus angka lebih dari 0 (contoh: 250000).';
        }
        if (!preg_match('/^\d+$/', (string)$form['stock'])) {
            $errors[] = 'Stok harus angka 0 atau lebih.';
        }
        if (mb_strlen($form['description']) < 10) {
            $errors[] = 'Deskripsi minimal 10 karakter.';
        }

        $new_image = null;
        if (!$errors) {
            $slug = kamera_unique_slug($pdo, kamera_slugify($form['name']), (int)$form['id']);

            [$new_image, $upload_error] = kamera_save_upload($_FILES['image_file'] ?? [], $slug);
            if ($upload_error !== null) {
                $errors[] = $upload_error;
            } elseif ($new_image === null && $form['image'] === '') {
                $errors[] = 'Foto kamera wajib dipilih untuk kamera baru.';
            }
        }

        if (!$errors) {
            $image    = $new_image ?? $form['image'];
            $includes = array_values(array_filter(array_map(
                'trim',
                preg_split('/\r\n|\r|\n/', (string)$form['includes']) ?: []
            )));

            $params = [
                $slug,
                $form['name'],
                $form['brand'],
                $form['category'],
                $form['tag'],
                (int)$form['daily_price'],
                (int)$form['stock'],
                $image,
                $form['sensor'] !== '' ? $form['sensor'] : '-',
                $form['video'] !== '' ? $form['video'] : '-',
                $form['iso'] !== '' ? $form['iso'] : '-',
                $form['mount'] !== '' ? $form['mount'] : '-',
                $form['weight'] !== '' ? $form['weight'] : '-',
                $form['stabilization'] !== '' ? $form['stabilization'] : '-',
                $form['description'],
                json_encode($includes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                (int)$form['is_featured'],
                (int)$form['is_available'],
            ];

            try {
                if ((int)$form['id'] > 0) {
                    $stmt = $pdo->prepare('UPDATE cameras SET slug = ?, name = ?, brand = ?, category = ?, tag = ?,
                        daily_price = ?, stock = ?, image = ?, sensor = ?, video = ?, iso = ?, mount = ?, weight = ?,
                        stabilization = ?, description = ?, includes = ?, is_featured = ?, is_available = ?
                        WHERE id = ?');
                    $stmt->execute(array_merge($params, [(int)$form['id']]));
                    $old_image = $form['image'];
                } else {
                    $stmt = $pdo->prepare('INSERT INTO cameras
                        (slug, name, brand, category, tag, daily_price, stock, image, sensor, video, iso, mount,
                         weight, stabilization, description, includes, is_featured, is_available, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                    $stmt->execute(array_merge($params, [date('Y-m-d H:i:s')]));
                    $old_image = '';
                }
            } catch (Throwable $e) {
                $stmt = null;
                $errors[] = 'Kamera gagal disimpan ke database. Coba lagi.';
            }

            if (!$errors && $stmt instanceof PDOStatement) {
                if ($new_image !== null && $old_image !== '' && $old_image !== $new_image) {
                    kamera_delete_upload($old_image);
                }
                header('Location: kamera.php?' . ((int)$form['id'] > 0 ? 'updated=1' : 'saved=1'));
                exit;
            }
        }
    }
}

$rows = [];
if ($pdo) {
    try {
        $rows = $pdo->query('SELECT * FROM cameras ORDER BY id ASC')->fetchAll();
    } catch (Throwable $e) {
        $rows = [];
    }
}

// Prefill form dari database kalau sedang mengedit (dan belum ada input POST untuk dirender ulang).
if (!$form_from_post && $edit_id > 0) {
    foreach ($rows as $row) {
        if ((int)$row['id'] !== $edit_id) {
            continue;
        }

        $includes = json_decode((string)$row['includes'], true);
        if (!is_array($includes)) {
            $includes = array_values(array_filter(array_map('trim', explode("\n", (string)$row['includes']))));
        }

        $form = [
            'id'            => (int)$row['id'],
            'name'          => (string)$row['name'],
            'brand'         => (string)$row['brand'],
            'category'      => (string)$row['category'],
            'tag'           => (string)$row['tag'],
            'daily_price'   => (string)(int)$row['daily_price'],
            'stock'         => (string)(int)($row['stock'] ?? 1),
            'image'         => (string)$row['image'],
            'sensor'        => (string)$row['sensor'],
            'video'         => (string)$row['video'],
            'iso'           => (string)$row['iso'],
            'mount'         => (string)$row['mount'],
            'weight'        => (string)$row['weight'],
            'stabilization' => (string)$row['stabilization'],
            'description'   => (string)$row['description'],
            'includes'      => implode("\n", $includes),
            'is_featured'   => (int)$row['is_featured'],
            'is_available'  => (int)$row['is_available'],
        ];
        break;
    }
}

$total_cameras  = count($rows);
$ready_cameras  = 0;
$total_stock    = 0;
$featured_count = 0;
foreach ($rows as $row) {
    if (!empty($row['is_available'])) {
        $ready_cameras++;
    }
    if (!empty($row['is_featured'])) {
        $featured_count++;
    }
    $total_stock += (int)($row['stock'] ?? 0);
}

$is_editing = (int)$form['id'] > 0;

admin_layout_start('Kamera', 'kamera', $csrf, 'Tambah, ubah, dan atur ketersediaan unit katalog');
?>

<?php if (isset($_GET['saved'])): ?>
    <div class="rounded-xl border border-emerald-200 dark:border-emerald-500/30 bg-emerald-50 dark:bg-emerald-950/30 px-4 py-2.5 text-xs text-emerald-700 dark:text-emerald-300">
        Kamera baru berhasil ditambahkan.
    </div>
<?php endif; ?>

<?php if (isset($_GET['updated'])): ?>
    <div class="rounded-xl border border-emerald-200 dark:border-emerald-500/30 bg-emerald-50 dark:bg-emerald-950/30 px-4 py-2.5 text-xs text-emerald-700 dark:text-emerald-300">
        Perubahan kamera berhasil disimpan.
    </div>
<?php endif; ?>

<?php if (isset($_GET['deleted'])): ?>
    <div class="rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-[#10141d] px-4 py-2.5 text-xs text-slate-600 dark:text-slate-300">
        Kamera berhasil dihapus dari katalog.
    </div>
<?php endif; ?>

<?php if ($errors): ?>
    <div class="rounded-xl border border-red-200 dark:border-red-500/30 bg-red-50 dark:bg-red-950/30 px-4 py-3 text-xs text-red-700 dark:text-red-300 space-y-1">
        <?php foreach ($errors as $message): ?>
            <div class="flex items-start gap-2">
                <i class="ri-error-warning-line mt-0.5 shrink-0"></i>
                <span><?= htmlspecialchars($message) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Ringkasan katalog -->
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
    <div class="rounded-2xl border border-slate-200 dark:border-white/10 bg-white dark:bg-[#0b0e15] p-4">
        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Model</div>
        <div class="text-xl font-black font-heading text-slate-900 dark:text-white mt-1"><?= $total_cameras ?></div>
    </div>
    <div class="rounded-2xl border border-slate-200 dark:border-white/10 bg-white dark:bg-[#0b0e15] p-4">
        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Aktif</div>
        <div class="text-xl font-black font-heading text-emerald-600 dark:text-emerald-400 mt-1"><?= $ready_cameras ?></div>
    </div>
    <div class="rounded-2xl border border-slate-200 dark:border-white/10 bg-white dark:bg-[#0b0e15] p-4">
        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Stok</div>
        <div class="text-xl font-black font-heading text-slate-900 dark:text-white mt-1"><?= $total_stock ?> unit</div>
    </div>
    <div class="rounded-2xl border border-slate-200 dark:border-white/10 bg-white dark:bg-[#0b0e15] p-4">
        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Featured</div>
        <div class="text-xl font-black font-heading text-purple-600 dark:text-purple-400 mt-1"><?= $featured_count ?></div>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start">

    <!-- DAFTAR KAMERA -->
    <div class="xl:col-span-7 glass-panel rounded-2xl overflow-hidden">
        <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-slate-200 dark:border-white/10">
            <div>
                <h2 class="text-xs font-bold font-heading text-slate-900 dark:text-white">Daftar Kamera</h2>
                <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">Data ini yang tampil di katalog publik.</p>
            </div>
            <a href="kamera.php?new=1" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-[11px] font-bold btn-theme-primary">
                <i class="ri-add-line"></i>
                <span>Tambah</span>
            </a>
        </div>

        <?php if (!$rows): ?>
            <div class="px-5 py-14 text-center">
                <i class="ri-camera-off-line text-3xl text-slate-300 dark:text-slate-600"></i>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">Belum ada kamera. Tambahkan unit pertama kamu.</p>
            </div>
        <?php else: ?>
            <div class="divide-y divide-slate-100 dark:divide-white/5">
                <?php foreach ($rows as $row): ?>
                    <div class="flex items-start gap-3 px-4 py-3">
                        <img src="../<?= htmlspecialchars((string)$row['image']) ?>" alt="<?= htmlspecialchars((string)$row['name']) ?>"
                             class="w-14 h-14 rounded-xl object-cover border border-slate-200 dark:border-white/10 shrink-0">

                        <div class="min-w-0 flex-1">
                            <div class="flex items-start gap-2">
                                <div class="min-w-0">
                                    <div class="text-xs font-bold text-slate-900 dark:text-white truncate"><?= htmlspecialchars((string)$row['name']) ?></div>
                                    <div class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">
                                        <?= htmlspecialchars((string)$row['brand']) ?> &middot; <?= htmlspecialchars((string)$row['category']) ?>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-1.5 mt-1.5 text-[10px] font-semibold">
                                <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-[#141b29] text-slate-700 dark:text-slate-200">
                                    <?= formatRupiah((int)$row['daily_price']) ?>/hari
                                </span>
                                <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-[#141b29] text-slate-700 dark:text-slate-200">
                                    Stok <?= (int)($row['stock'] ?? 0) ?>
                                </span>
                                <?php if (!empty($row['is_available'])): ?>
                                    <span class="px-2 py-0.5 rounded-md bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-500/30">Aktif</span>
                                <?php else: ?>
                                    <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-[#141b29] text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-white/10">Nonaktif</span>
                                <?php endif; ?>
                                <?php if (!empty($row['is_featured'])): ?>
                                    <span class="px-2 py-0.5 rounded-md bg-purple-50 dark:bg-purple-950/40 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-500/30">Featured</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="flex items-center gap-1 shrink-0">
                            <a href="kamera.php?edit=<?= (int)$row['id'] ?>" title="Edit kamera"
                               class="w-8 h-8 rounded-lg border border-slate-200 dark:border-white/10 text-slate-500 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-white/5 flex items-center justify-center">
                                <i class="ri-pencil-line text-sm"></i>
                            </a>

                            <form method="POST" action="kamera.php">
                                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="camera_id" value="<?= (int)$row['id'] ?>">
                                <input type="hidden" name="field" value="is_available">
                                <button type="submit" title="<?= !empty($row['is_available']) ? 'Nonaktifkan dari katalog' : 'Aktifkan di katalog' ?>"
                                        class="w-8 h-8 rounded-lg border border-slate-200 dark:border-white/10 text-slate-500 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-white/5 flex items-center justify-center">
                                    <i class="<?= !empty($row['is_available']) ? 'ri-eye-off-line' : 'ri-eye-line' ?> text-sm"></i>
                                </button>
                            </form>

                            <form method="POST" action="kamera.php">
                                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="camera_id" value="<?= (int)$row['id'] ?>">
                                <input type="hidden" name="field" value="is_featured">
                                <button type="submit" title="<?= !empty($row['is_featured']) ? 'Keluarkan dari unggulan' : 'Jadikan unggulan' ?>"
                                        class="w-8 h-8 rounded-lg border border-slate-200 dark:border-white/10 <?= !empty($row['is_featured']) ? 'text-amber-500' : 'text-slate-500 dark:text-slate-300' ?> hover:bg-slate-50 dark:hover:bg-white/5 flex items-center justify-center">
                                    <i class="<?= !empty($row['is_featured']) ? 'ri-star-fill' : 'ri-star-line' ?> text-sm"></i>
                                </button>
                            </form>

                            <form method="POST" action="kamera.php" onsubmit="return confirm('Hapus kamera ini dari katalog? Tindakan ini tidak bisa dibatalkan.');">
                                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="camera_id" value="<?= (int)$row['id'] ?>">
                                <button type="submit" title="Hapus kamera"
                                        class="w-8 h-8 rounded-lg border border-slate-200 dark:border-white/10 text-slate-400 hover:text-red-500 hover:border-red-200 dark:hover:border-red-500/30 flex items-center justify-center">
                                    <i class="ri-delete-bin-line text-sm"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- FORM TAMBAH / EDIT -->
    <div class="xl:col-span-5 glass-panel rounded-2xl overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-200 dark:border-white/10 flex items-center justify-between gap-2">
            <div>
                <h2 class="text-xs font-bold font-heading text-slate-900 dark:text-white">
                    <?= $is_editing ? 'Edit Kamera' : 'Tambah Kamera' ?>
                </h2>
                <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">
                    <?= $is_editing ? 'Mengubah data #' . (int)$form['id'] : 'Isi data unit baru untuk katalog' ?>
                </p>
            </div>
            <?php if ($is_editing): ?>
                <a href="kamera.php?new=1" class="text-[10px] font-bold text-purple-600 dark:text-purple-400 hover:underline">Batal edit</a>
            <?php endif; ?>
        </div>

        <form method="POST" action="kamera.php" enctype="multipart/form-data" class="p-4 space-y-4">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="camera_id" value="<?= (int)$form['id'] ?>">
            <input type="hidden" name="current_image" value="<?= htmlspecialchars((string)$form['image']) ?>">

            <!-- Data utama -->
            <div class="space-y-3">
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Data Utama</div>

                <div>
                    <label for="name" class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">Nama Kamera</label>
                    <input id="name" name="name" type="text" required value="<?= htmlspecialchars((string)$form['name']) ?>"
                           placeholder="Sony FX3 Cinema Line"
                           class="glass-input w-full rounded-xl px-3 py-2 text-xs">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="brand" class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">Merk</label>
                        <input id="brand" name="brand" type="text" required value="<?= htmlspecialchars((string)$form['brand']) ?>"
                               placeholder="Sony"
                               class="glass-input w-full rounded-xl px-3 py-2 text-xs">
                    </div>
                    <div>
                        <label for="category" class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">Kategori</label>
                        <select id="category" name="category" class="glass-input w-full rounded-xl px-3 py-2 text-xs">
                            <?php foreach (KAMERA_CATEGORIES as $category): ?>
                                <option value="<?= htmlspecialchars($category) ?>" <?= $form['category'] === $category ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="tag" class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">Tagline Singkat</label>
                    <input id="tag" name="tag" type="text" value="<?= htmlspecialchars((string)$form['tag']) ?>"
                           placeholder="Cinema Masterpiece"
                           class="glass-input w-full rounded-xl px-3 py-2 text-xs">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="daily_price" class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">Harga / Hari (Rp)</label>
                        <input id="daily_price" name="daily_price" type="number" min="1" step="1000" required
                               value="<?= htmlspecialchars((string)$form['daily_price']) ?>" placeholder="550000"
                               class="glass-input w-full rounded-xl px-3 py-2 text-xs">
                    </div>
                    <div>
                        <label for="stock" class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">Stok Unit</label>
                        <input id="stock" name="stock" type="number" min="0" step="1" required
                               value="<?= htmlspecialchars((string)$form['stock']) ?>" placeholder="1"
                               class="glass-input w-full rounded-xl px-3 py-2 text-xs">
                    </div>
                </div>

                <div>
                    <label for="image_file" class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">
                        Foto Kamera <?= $is_editing ? '(kosongkan kalau tidak ganti)' : '' ?>
                    </label>
                    <div class="flex items-center gap-3">
                        <?php if (!empty($form['image'])): ?>
                            <img src="../<?= htmlspecialchars((string)$form['image']) ?>" alt="Foto kamera saat ini"
                                 class="w-14 h-14 rounded-xl object-cover border border-slate-200 dark:border-white/10 shrink-0">
                        <?php endif; ?>
                        <input id="image_file" name="image_file" type="file" accept="image/jpeg,image/png,image/webp"
                               class="glass-input w-full rounded-xl px-3 py-2 text-[11px] file:mr-2 file:py-1 file:px-2 file:rounded-lg file:border-0 file:text-[10px] file:font-bold file:bg-purple-600 file:text-white">
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1">JPG / PNG / WebP, maksimal 3 MB. Foto disimpan di assets/images.</p>
                </div>
            </div>

            <!-- Spesifikasi -->
            <div class="space-y-3 pt-3 border-t border-slate-200 dark:border-white/10">
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Spesifikasi</div>

                <div>
                    <label for="sensor" class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">Sensor &amp; Resolusi</label>
                    <input id="sensor" name="sensor" type="text" value="<?= htmlspecialchars((string)$form['sensor']) ?>"
                           placeholder="Full-Frame 12.1MP Exmor R CMOS"
                           class="glass-input w-full rounded-xl px-3 py-2 text-xs">
                </div>

                <div>
                    <label for="video" class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">Kualitas Video</label>
                    <input id="video" name="video" type="text" value="<?= htmlspecialchars((string)$form['video']) ?>"
                           placeholder="4K 120p, 10-bit 4:2:2 All-I"
                           class="glass-input w-full rounded-xl px-3 py-2 text-xs">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="iso" class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">Rentang ISO</label>
                        <input id="iso" name="iso" type="text" value="<?= htmlspecialchars((string)$form['iso']) ?>"
                               placeholder="80 - 409,600"
                               class="glass-input w-full rounded-xl px-3 py-2 text-xs">
                    </div>
                    <div>
                        <label for="mount" class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">Mount Lensa</label>
                        <input id="mount" name="mount" type="text" value="<?= htmlspecialchars((string)$form['mount']) ?>"
                               placeholder="Sony E-Mount"
                               class="glass-input w-full rounded-xl px-3 py-2 text-xs">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="weight" class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">Berat</label>
                        <input id="weight" name="weight" type="text" value="<?= htmlspecialchars((string)$form['weight']) ?>"
                               placeholder="715 gram"
                               class="glass-input w-full rounded-xl px-3 py-2 text-xs">
                    </div>
                    <div>
                        <label for="stabilization" class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">Stabilisasi</label>
                        <input id="stabilization" name="stabilization" type="text" value="<?= htmlspecialchars((string)$form['stabilization']) ?>"
                               placeholder="5-Axis Active In-Body"
                               class="glass-input w-full rounded-xl px-3 py-2 text-xs">
                    </div>
                </div>
            </div>

            <!-- Deskripsi -->
            <div class="space-y-3 pt-3 border-t border-slate-200 dark:border-white/10">
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Deskripsi &amp; Kelengkapan</div>

                <div>
                    <label for="description" class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">Deskripsi</label>
                    <textarea id="description" name="description" rows="3" required
                              placeholder="Ceritakan singkat kelebihan kamera ini..."
                              class="glass-input w-full rounded-xl px-3 py-2 text-xs resize-none"><?= htmlspecialchars((string)$form['description']) ?></textarea>
                </div>

                <div>
                    <label for="includes" class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">Kelengkapan (satu per baris)</label>
                    <textarea id="includes" name="includes" rows="5"
                              placeholder="1x Body Kamera&#10;3x Baterai Original&#10;1x Dual Charger"
                              class="glass-input w-full rounded-xl px-3 py-2 text-xs resize-none"><?= htmlspecialchars((string)$form['includes']) ?></textarea>
                </div>
            </div>

            <!-- Status -->
            <div class="space-y-2.5 pt-3 border-t border-slate-200 dark:border-white/10">
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Status</div>

                <label class="flex items-start gap-2.5 p-2.5 rounded-xl bg-slate-50 dark:bg-[#141b29] border border-slate-200 dark:border-white/5 cursor-pointer">
                    <input type="checkbox" name="is_available" value="1" <?= !empty($form['is_available']) ? 'checked' : '' ?>
                           class="mt-0.5 rounded text-purple-600 focus:ring-0">
                    <span class="text-[11px]">
                        <span class="block font-semibold text-slate-800 dark:text-slate-200">Tersedia di katalog</span>
                        <span class="block text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">Matikan kalau unit sedang diservis atau tidak disewakan.</span>
                    </span>
                </label>

                <label class="flex items-start gap-2.5 p-2.5 rounded-xl bg-slate-50 dark:bg-[#141b29] border border-slate-200 dark:border-white/5 cursor-pointer">
                    <input type="checkbox" name="is_featured" value="1" <?= !empty($form['is_featured']) ? 'checked' : '' ?>
                           class="mt-0.5 rounded text-purple-600 focus:ring-0">
                    <span class="text-[11px]">
                        <span class="block font-semibold text-slate-800 dark:text-slate-200">Tampilkan di beranda</span>
                        <span class="block text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">Masuk section "Kamera Pilihan" di halaman utama.</span>
                    </span>
                </label>
            </div>

            <div class="pt-1 flex items-center gap-2">
                <button type="submit" class="btn-theme-primary flex-1 py-3 px-4 rounded-xl text-xs font-bold flex items-center justify-center gap-2">
                    <i class="ri-save-3-line"></i>
                    <span><?= $is_editing ? 'Simpan Perubahan' : 'Tambah Kamera' ?></span>
                </button>
                <?php if ($is_editing): ?>
                    <a href="kamera.php" class="px-4 py-3 rounded-xl text-xs font-bold border border-slate-200 dark:border-white/10 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-white/5 transition">
                        Batal
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php admin_layout_end(); ?>
