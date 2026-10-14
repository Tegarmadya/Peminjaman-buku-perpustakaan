<?php
require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/fungsi.php';

requireRole('admin');

$pageTitle  = 'Tambah Buku';
$activePage = 'buku';
$errors     = [];

// Nilai default form
$formData = [
    'kode_buku'    => generateKodeBuku($pdo),
    'judul'        => '',
    'penulis'      => '',
    'penerbit'     => '',
    'tahun_terbit' => date('Y'),
    'kategori'     => '',
    'stok'         => 1,
];

// Proses form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
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
    if ($formData['penerbit']  === '') $errors[] = 'Penerbit wajib diisi.';
    if ($formData['kategori']  === '') $errors[] = 'Kategori wajib diisi.';

    // Cek kode buku unik
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM buku WHERE kode_buku = :kode LIMIT 1");
        $stmt->execute([':kode' => $formData['kode_buku']]);
        if ($stmt->fetch()) $errors[] = 'Kode buku sudah digunakan.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO buku (kode_buku, judul, penulis, penerbit, tahun_terbit, kategori, stok, stok_tersedia)
            VALUES (:kode_buku, :judul, :penulis, :penerbit, :tahun_terbit, :kategori, :stok, :stok)
        ");
        $stmt->execute([
            ':kode_buku'    => $formData['kode_buku'],
            ':judul'        => $formData['judul'],
            ':penulis'      => $formData['penulis'],
            ':penerbit'     => $formData['penerbit'],
            ':tahun_terbit' => $formData['tahun_terbit'],
            ':kategori'     => $formData['kategori'],
            ':stok'         => $formData['stok'],
        ]);
        setFlash('success', 'Buku berhasil ditambahkan.');
        redirect(APP_URL . '/admin/buku/index.php');
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card" style="max-width:680px;">
    <div class="card-title">Tambah Buku Baru</div>

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
                       value="<?= e($formData['kode_buku']) ?>"
                       required data-label="Kode buku" placeholder="cth: BK-019">
            </div>
            <div class="form-group">
                <label class="form-label" for="tahun_terbit">Tahun Terbit</label>
                <input type="number" id="tahun_terbit" name="tahun_terbit" class="form-control"
                       value="<?= e($formData['tahun_terbit']) ?>"
                       min="1800" max="<?= date('Y') + 1 ?>"
                       required data-label="Tahun terbit">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="judul">Judul Buku</label>
            <input type="text" id="judul" name="judul" class="form-control"
                   value="<?= e($formData['judul']) ?>"
                   required data-label="Judul buku" placeholder="Judul lengkap buku">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="penulis">Penulis</label>
                <input type="text" id="penulis" name="penulis" class="form-control"
                       value="<?= e($formData['penulis']) ?>"
                       required data-label="Penulis" placeholder="Nama penulis">
            </div>
            <div class="form-group">
                <label class="form-label" for="penerbit">Penerbit</label>
                <input type="text" id="penerbit" name="penerbit" class="form-control"
                       value="<?= e($formData['penerbit']) ?>"
                       required data-label="Penerbit" placeholder="Nama penerbit">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="kategori">Kategori</label>
                <input type="text" id="kategori" name="kategori" class="form-control"
                       value="<?= e($formData['kategori']) ?>"
                       required data-label="Kategori"
                       placeholder="Fiksi, Teknologi, dll." list="kategoriList">
                <datalist id="kategoriList">
                    <?php
                    $cats = $pdo->query("SELECT DISTINCT kategori FROM buku ORDER BY kategori")->fetchAll(PDO::FETCH_COLUMN);
                    foreach ($cats as $cat): ?>
                        <option value="<?= e($cat) ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
            <div class="form-group">
                <label class="form-label" for="stok">Jumlah Stok</label>
                <input type="number" id="stok" name="stok" class="form-control"
                       value="<?= $formData['stok'] ?>" min="1" max="999"
                       required data-label="Stok">
            </div>
        </div>

        <div class="form-actions">
            <a href="<?= APP_URL ?>/admin/buku/index.php" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan Buku</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
