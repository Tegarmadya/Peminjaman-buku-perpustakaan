<?php
require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/fungsi.php';

requireRole('admin');

$pageTitle  = 'Data Anggota';
$activePage = 'anggota';

// Ambil semua anggota beserta jumlah pinjaman aktif
$anggota = $pdo->query("
    SELECT u.*,
           COUNT(CASE WHEN p.status = 'dipinjam' THEN 1 END) AS pinjaman_aktif,
           COUNT(p.id) AS total_pinjaman
    FROM users u
    LEFT JOIN peminjaman p ON u.id = p.user_id
    WHERE u.role = 'anggota'
    GROUP BY u.id
    ORDER BY u.nama ASC
")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="toolbar">
    <input type="text" class="form-control" style="max-width:300px;"
           placeholder="Cari nama, username, no. HP..."
           data-search-target="#tableAnggota">
    <a href="<?= APP_URL ?>/admin/anggota/tambah.php" class="btn btn-primary btn-sm">
        + Tambah Anggota
    </a>
</div>

<div class="table-wrapper">
    <table id="tableAnggota">
        <thead>
            <tr>
                <th>#</th>
                <th>Nama</th>
                <th>Username</th>
                <th>No. HP</th>
                <th>Terdaftar</th>
                <th>Aktif Pinjam</th>
                <th>Total</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($anggota)): ?>
                <tr>
                    <td colspan="8" style="text-align:center;padding:2rem;color:var(--text-muted);">
                        Belum ada data anggota.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($anggota as $i => $a): ?>
                    <tr>
                        <td class="td-muted"><?= $i + 1 ?></td>
                        <td class="fw-bold"><?= e($a['nama']) ?></td>
                        <td class="td-muted"><?= e($a['username']) ?></td>
                        <td class="td-muted"><?= e($a['no_hp'] ?: '—') ?></td>
                        <td class="td-muted"><?= formatTanggal($a['created_at']) ?></td>
                        <td>
                            <?php if ($a['pinjaman_aktif'] > 0): ?>
                                <span class="badge badge-dipinjam"><?= $a['pinjaman_aktif'] ?></span>
                            <?php else: ?>
                                <span class="td-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="td-muted"><?= $a['total_pinjaman'] ?> transaksi</td>
                        <td>
                            <div class="btn-group">
                                <a href="<?= APP_URL ?>/admin/anggota/edit.php?id=<?= $a['id'] ?>"
                                    class="btn btn-secondary btn-sm">Edit</a>
                                <form method="POST" action="<?= APP_URL ?>/admin/anggota/hapus.php">
                                    <input type="hidden" name="id" value="<?= $a['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm"
                                            data-confirm="Hapus anggota '<?= e($a['nama']) ?>'? Data pinjaman terkait mungkin terpengaruh.">
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
