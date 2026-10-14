<?php
require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/fungsi.php';

requireRole('admin');

$pageTitle  = 'Edit Buku';
$activePage = 'buku';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    setFlash('error', 'ID buku tidak valid.');
    redirect(APP_URL . '/admin/buku/index.php');
}

// Ambil data buku
$stmt = $pdo->prepare("SELECT * FROM buku WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$buku = $stmt->fetch();

if (!$buku) {
    setFlash('error', 'Buku tidak ditemukan.');
    redirect(APP_URL . '/admin/buku/index.php');
}

$errors   = [];
$formData = $buku;

// Proses form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'id'           => $id,
        'kode_buku'    => strtoupper(trim($_POST['kode_buku']   ?? '')),
        'judul'        => trim($_POST['judul']       ?? ''),
        'penulis'      => trim($_POST['penulis']     ?? ''),
        'penerbit'     => trim($_POST['penerbit']    ?? ''),
        'tahun_terbit' => (int) ($_POST['tahun_terbit'] ?? date('Y')),
        'kategori'     => trim($_POST['kategori']    ?? ''),
        'stok'         => max(1, (int) ($_POST['stok'] ?? 1)),
    ];

    // Validasi
    if ($formData['kode_buku'] === '') $errors[] = 'Kode buku wajib diisi.';
    if ($formData['judul']     === '') $errors[] = 'Judul buku wajib diisi.';
    if ($formData['penulis']   === '') $errors[] = 'Penulis wajib diisi.';
    if ($formData['kategori']  === '') $errors[] = 'Kategori wajib diisi.';

    // Stok tidak boleh kurang dari yang sedang dipinjam
    $dipinjam = $buku['stok'] - $buku['stok_tersedia']; // jumlah yang sedang dipinjam
    if ($formData['stok'] < $dipinjam) {
        $errors[] = "Stok tidak boleh kurang dari $dipinjam (jumlah yang sedang dipinjam).";
    }

    // Kode buku unik (kecuali milik buku ini sendiri)
    if (empty($errors)) {
        $stmt2 = $pdo->prepare("SELECT id FROM buku WHERE kode_buku = :kode AND id != :id LIMIT 1");
        $stmt2->execute([':kode' => $formData['kode_buku'], ':id' => $id]);
        if ($stmt2->fetch()) $errors[] = 'Kode buku sudah digunakan oleh buku lain.';
    }

    if (empty($errors)) {
        // Hitung stok tersedia baru
        $stokBaru       = $formData['stok'];
        $stokTersedia   = $stokBaru - $dipinjam;

        $stmt3 = $pdo->prepare("
            UPDATE buku SET
                kode_buku    = :kode_buku,
                judul        = :judul,
                penulis      = :penulis,
                penerbit     = :penerbit,
                tahun_terbit = :tahun_terbit,
                kategori     = :kategori,
                stok         = :stok,
                stok_tersedia = :stok_tersedia
            WHERE id = :id
        ");
        $stmt3->execute([
            ':kode_buku'    => $formData['kode_buku'],
            ':judul'        => $formData['judul'],
            ':penulis'      => $formData['penulis'],
            ':penerbit'     => $formData['penerbit'],
            ':tahun_terbit' => $formData['tahun_terbit'],
            ':kategori'     => $formData['kategori'],
            ':stok'         => $stokBaru,
            ':stok_tersedia'=> max(0, $stokTersedia),
            ':id'           => $id,
        ]);
        setFlash('success', 'Data buku berhasil diperbarui.');
        redirect(APP_URL . '/admin/buku/index.php');
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card" style="max-width:680px;">
    <div class="card-title">Edit Buku: <?= e($buku['judul']) ?></div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $err): ?><div>• <?= e($err) ?></div><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="" data-validate>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="kode_buku">Kode Buku</label>
                <input type="text" id="kode_buku" name="kode_buku" class="form-control"
                       value="<?= e($formData['kode_buku']) ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="tahun_terbit">Tahun Terbit</label>
                <input type="number" id="tahun_terbit" name="tahun_terbit" class="form-control"
                       value="<?= e($formData['tahun_terbit']) ?>"
                       min="1800" max="<?= date('Y') + 1 ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="judul">Judul Buku</label>
            <input type="text" id="judul" name="judul" class="form-control"
                   value="<?= e($formData['judul']) ?>" required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="penulis">Penulis</label>
                <input type="text" id="penulis" name="penulis" class="form-control"
                       value="<?= e($formData['penulis']) ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="penerbit">Penerbit</label>
                <input type="text" id="penerbit" name="penerbit" class="form-control"
                       value="<?= e($formData['penerbit']) ?>" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="kategori">Kategori</label>
                <input type="text" id="kategori" name="kategori" class="form-control"
                       value="<?= e($formData['kategori']) ?>" required list="kategoriList">
                <datalist id="kategoriList">
                    <?php
                    $cats = $pdo->query("SELECT DISTINCT kategori FROM buku ORDER BY kategori")->fetchAll(PDO::FETCH_COLUMN);
                    foreach ($cats as $cat): ?>
                        <option value="<?= e($cat) ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
            <div class="form-group">
                <label class="form-label" for="stok">Total Stok</label>
                <input type="number" id="stok" name="stok" class="form-control"
                       value="<?= $formData['stok'] ?>" min="1" max="999" required>
                <div class="form-text">
                    Sedang dipinjam: <?= $buku['stok'] - $buku['stok_tersedia'] ?> buku
                </div>
            </div>
        </div>

        <div class="form-actions">
            <a href="<?= APP_URL ?>/admin/buku/index.php" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
