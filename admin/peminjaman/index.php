<?php
require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/fungsi.php';

requireRole('admin');

$pageTitle  = 'Daftar Peminjaman';
$activePage = 'peminjaman';

// Filter status
$filterStatus = $_GET['status'] ?? '';
$where = '';
$params = [];
if (in_array($filterStatus, ['dipinjam', 'dikembalikan'])) {
    $where = "WHERE p.status = :status";
    $params[':status'] = $filterStatus;
}

$peminjaman = $pdo->prepare("
    SELECT p.*, u.nama,
           (SELECT COUNT(*) FROM detail_peminjaman dp WHERE dp.peminjaman_id = p.id) AS jumlah_buku
    FROM peminjaman p
    JOIN users u ON p.user_id = u.id
    $where
    ORDER BY p.id DESC
");
$peminjaman->execute($params);
$data = $peminjaman->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="toolbar">
    <div class="search-bar" style="flex:1;">
        <input type="text" class="form-control search-input"
               placeholder="Cari nama anggota..."
               data-search-target="#tablePinjam">
        <select class="form-control filter-select" onchange="window.location.href='?status='+this.value">
            <option value="" <?= $filterStatus === '' ? 'selected':'' ?>>Semua Status</option>
            <option value="dipinjam"    <?= $filterStatus === 'dipinjam'    ? 'selected':'' ?>>Dipinjam</option>
            <option value="dikembalikan"<?= $filterStatus === 'dikembalikan'? 'selected':'' ?>>Dikembalikan</option>
        </select>
    </div>
    <a href="<?= APP_URL ?>/admin/peminjaman/tambah.php" class="btn btn-primary btn-sm">
        + Pinjam Buku
    </a>
</div>

<div class="table-wrapper">
    <table id="tablePinjam">
        <thead>
            <tr>
                <th>#ID</th>
                <th>Anggota</th>
                <th>Jml Buku</th>
                <th>Tgl Pinjam</th>
                <th>Jatuh Tempo</th>
                <th>Tgl Kembali</th>
                <th>Status</th>
                <th>Denda</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data)): ?>
                <tr>
                    <td colspan="9" style="text-align:center;padding:2rem;color:var(--text-muted);">
                        Belum ada data peminjaman.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($data as $p): ?>
                    <?php $isLate = ($p['status'] === 'dipinjam' && isTelat($p['tanggal_jatuh_tempo'])); ?>
                    <tr>
                        <td class="td-muted">#<?= $p['id'] ?></td>
                        <td class="fw-bold"><?= e($p['nama']) ?></td>
                        <td><?= $p['jumlah_buku'] ?></td>
                        <td class="td-muted"><?= formatTanggal($p['tanggal_pinjam']) ?></td>
                        <td class="td-muted"><?= formatTanggal($p['tanggal_jatuh_tempo']) ?></td>
                        <td class="td-muted"><?= $p['tanggal_kembali'] ? formatTanggal($p['tanggal_kembali']) : '—' ?></td>
                        <td>
                            <?php if ($p['status'] === 'dikembalikan'): ?>
                                <span class="badge badge-dikembalikan">Dikembalikan</span>
                            <?php elseif ($isLate): ?>
                                <span class="badge badge-telat">Terlambat</span>
                            <?php else: ?>
                                <span class="badge badge-dipinjam">Dipinjam</span>
                            <?php endif; ?>
                        </td>
                        <td class="td-muted">
                            <?= $p['denda'] > 0 ? formatRupiah($p['denda']) : '—' ?>
                        </td>
                        <td>
                            <a href="<?= APP_URL ?>/admin/peminjaman/detail.php?id=<?= $p['id'] ?>"
                               class="btn btn-secondary btn-sm">Detail</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
