<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/fungsi.php';

requireRole('anggota');

$pageTitle  = 'Katalog Buku';
$activePage = 'katalog';

// Ambil semua buku
$buku = $pdo->query("SELECT * FROM buku ORDER BY judul ASC")->fetchAll();

// Daftar kategori untuk filter
$kategoriList = $pdo->query("SELECT DISTINCT kategori FROM buku ORDER BY kategori")->fetchAll(PDO::FETCH_COLUMN);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="search-bar" style="margin-bottom:1rem;">
    <input type="text" id="katalogSearch" class="form-control search-input"
           placeholder="Cari judul, penulis, penerbit...">
    <select id="katalogFilter" class="form-control filter-select">
        <option value="">Semua Kategori</option>
        <?php foreach ($kategoriList as $kat): ?>
            <option value="<?= e(strtolower($kat)) ?>"><?= e($kat) ?></option>
        <?php endforeach; ?>
    </select>
</div>

<div class="table-wrapper">
    <table id="katalogTable">
        <thead>
            <tr>
                <th>Kode</th>
                <th>Judul</th>
                <th>Penulis</th>
                <th>Penerbit</th>
                <th>Kategori</th>
                <th>Tahun</th>
                <th>Stok</th>
                <th>Tersedia</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($buku)): ?>
                <tr>
                    <td colspan="8" style="text-align:center;padding:2rem;color:var(--text-muted);">
                        Belum ada buku di katalog.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($buku as $b): ?>
                    <tr>
                        <td class="td-muted"><?= e($b['kode_buku']) ?></td>
                        <td class="fw-bold"><?= e($b['judul']) ?></td>
                        <td class="td-muted"><?= e($b['penulis']) ?></td>
                        <td class="td-muted"><?= e($b['penerbit']) ?></td>
                        <td><?= e($b['kategori']) ?></td>
                        <td class="td-muted"><?= $b['tahun_terbit'] ?></td>
                        <td class="td-muted"><?= $b['stok'] ?></td>
                        <td>
                            <?php if ($b['stok_tersedia'] > 0): ?>
                                <span class="badge badge-tersedia"><?= $b['stok_tersedia'] ?> tersedia</span>
                            <?php else: ?>
                                <span class="badge badge-habis">Habis</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div style="margin-top:1rem;font-size:0.78rem;color:var(--text-muted);">
    Untuk meminjam buku, silakan hubungi petugas perpustakaan atau minta admin memproses peminjaman Anda.
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
