<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/fungsi.php';

requireRole('anggota');

$pageTitle  = 'Riwayat Peminjaman';
$activePage = 'riwayat';
$user       = currentUser();

// Ambil semua riwayat pinjaman anggota beserta daftar buku
$riwayat = $pdo->prepare("
    SELECT p.*,
           GROUP_CONCAT(b.judul ORDER BY b.judul SEPARATOR ' | ') AS judul_buku,
           COUNT(dp.id) AS jumlah_buku
    FROM peminjaman p
    JOIN detail_peminjaman dp ON p.id = dp.peminjaman_id
    JOIN buku b ON dp.buku_id = b.id
    WHERE p.user_id = :uid
    GROUP BY p.id
    ORDER BY p.id DESC
");
$riwayat->execute([':uid' => $user['id']]);
$data = $riwayat->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div style="margin-bottom:1rem;">
    <input type="text" class="form-control" style="max-width:320px;"
           placeholder="Cari judul buku, status..."
           data-search-target="#tableRiwayat">
</div>

<?php if (empty($data)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">📋</div>
        <div class="empty-state-text">Belum ada riwayat peminjaman.</div>
        <a href="<?= APP_URL ?>/anggota/katalog.php" class="btn btn-secondary btn-sm" style="margin-top:1rem;">
            Lihat Katalog
        </a>
    </div>
<?php else: ?>
    <div class="table-wrapper">
        <table id="tableRiwayat">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Buku yang Dipinjam</th>
                    <th>Jml</th>
                    <th>Tgl Pinjam</th>
                    <th>Jatuh Tempo</th>
                    <th>Tgl Kembali</th>
                    <th>Status</th>
                    <th>Denda</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $p): ?>
                    <?php $isLate = ($p['status'] === 'dipinjam' && isTelat($p['tanggal_jatuh_tempo'])); ?>
                    <tr>
                        <td class="td-muted">#<?= $p['id'] ?></td>
                        <td style="max-width:280px;">
                            <div title="<?= e($p['judul_buku']) ?>">
                                <?= e(mb_strimwidth($p['judul_buku'], 0, 70, '...')) ?>
                            </div>
                        </td>
                        <td class="td-muted"><?= $p['jumlah_buku'] ?></td>
                        <td class="td-muted"><?= formatTanggal($p['tanggal_pinjam']) ?></td>
                        <td class="td-muted"><?= formatTanggal($p['tanggal_jatuh_tempo']) ?></td>
                        <td class="td-muted">
                            <?= $p['tanggal_kembali'] ? formatTanggal($p['tanggal_kembali']) : '—' ?>
                        </td>
                        <td>
                            <?php if ($p['status'] === 'dikembalikan'): ?>
                                <span class="badge badge-dikembalikan">Dikembalikan</span>
                            <?php elseif ($isLate): ?>
                                <span class="badge badge-telat">Terlambat</span>
                            <?php else: ?>
                                <span class="badge badge-dipinjam">Dipinjam</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($p['denda'] > 0): ?>
                                <span class="td-muted"><?= formatRupiah($p['denda']) ?></span>
                            <?php elseif ($isLate): ?>
                                <span class="td-muted">
                                    ~<?= formatRupiah(hitungDenda($p['tanggal_jatuh_tempo'], date('Y-m-d'), $p['jumlah_buku'])) ?>
                                </span>
                            <?php else: ?>
                                <span class="td-muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Catatan denda -->
    <div style="margin-top:0.75rem;font-size:0.75rem;color:var(--text-muted);">
        * Denda dihitung Rp<?= number_format(DENDA_PER_HARI,0,',','.') ?>/hari/buku.
        Tanda ~ = estimasi denda saat ini (belum final hingga dikembalikan).
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
