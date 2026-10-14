<?php
require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/fungsi.php';

requireRole('admin');

$pageTitle  = 'Data Buku';
$activePage = 'buku';

// Ambil semua buku
$buku = $pdo->query("SELECT * FROM buku ORDER BY judul ASC")->fetchAll();

// Ambil daftar kategori unik untuk filter
$kategoriList = $pdo->query("SELECT DISTINCT kategori FROM buku ORDER BY kategori")->fetchAll(PDO::FETCH_COLUMN);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="toolbar">
    <div class="search-bar" style="flex:1;">
        <input type="text" class="form-control search-input"
               placeholder="Cari judul, penulis, kode..."
               data-search-target="#tableBuku">
        <select class="form-control filter-select"
                data-filter-target="#tableBuku" data-filter-col="5">
            <option value="">Semua Kategori</option>
            <?php foreach ($kategoriList as $kat): ?>
                <option value="<?= e(strtolower($kat)) ?>"><?= e($kat) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <a href="<?= APP_URL ?>/admin/buku/tambah.php" class="btn btn-primary btn-sm">
        + Tambah Buku
    </a>
</div>

<div class="table-wrapper">
    <table id="tableBuku">
        <thead>
            <tr>
                <th>Kode</th>
                <th>Judul</th>
                <th>Penulis</th>
                <th>Penerbit</th>
                <th>Tahun</th>
                <th>Kategori</th>
                <th>Stok</th>
                <th>Tersedia</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($buku)): ?>
                <tr>
                    <td colspan="9" style="text-align:center;padding:2rem;color:var(--text-muted);">
                        Belum ada data buku.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($buku as $b): ?>
                    <tr>
                        <td class="td-muted"><?= e($b['kode_buku']) ?></td>
                        <td class="fw-bold"><?= e($b['judul']) ?></td>
                        <td class="td-muted"><?= e($b['penulis']) ?></td>
                        <td class="td-muted"><?= e($b['penerbit']) ?></td>
                        <td class="td-muted"><?= e($b['tahun_terbit']) ?></td>
                        <td><?= e($b['kategori']) ?></td>
                        <td><?= $b['stok'] ?></td>
                        <td>
                            <?php if ($b['stok_tersedia'] > 0): ?>
                                <span class="badge badge-tersedia"><?= $b['stok_tersedia'] ?></span>
                            <?php else: ?>
                                <span class="badge badge-habis">0 — Habis</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="<?= APP_URL ?>/admin/buku/edit.php?id=<?= $b['id'] ?>"
                                   class="btn btn-secondary btn-sm">Edit</a>
                                <form method="POST" action="<?= APP_URL ?>/admin/buku/hapus.php">
                                    <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm"
                                            data-confirm="Hapus buku '<?= e($b['judul']) ?>'? Buku yang sedang dipinjam tidak dapat dihapus.">
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
