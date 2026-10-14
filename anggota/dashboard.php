<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/fungsi.php';

requireRole('anggota');

$pageTitle  = 'Dashboard Saya';
$activePage = 'dashboard';
$user       = currentUser();

// Pinjaman aktif anggota
$pinjamanAktif = $pdo->prepare("
    SELECT p.*, GROUP_CONCAT(b.judul ORDER BY b.judul SEPARATOR ', ') AS judul_buku,
           COUNT(dp.id) AS jumlah_buku
    FROM peminjaman p
    JOIN detail_peminjaman dp ON p.id = dp.peminjaman_id
    JOIN buku b ON dp.buku_id = b.id
    WHERE p.user_id = :uid AND p.status = 'dipinjam'
    GROUP BY p.id
    ORDER BY p.tanggal_jatuh_tempo ASC
");
$pinjamanAktif->execute([':uid' => $user['id']]);
$aktif = $pinjamanAktif->fetchAll();

// Statistik
$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM peminjaman WHERE user_id = :uid");
$stmtTotal->execute([':uid' => $user['id']]);
$totalPinjam = (int) $stmtTotal->fetchColumn();

$stmtTotalDenda = $pdo->prepare("SELECT COALESCE(SUM(denda),0) FROM peminjaman WHERE user_id = :uid");
$stmtTotalDenda->execute([':uid' => $user['id']]);
$totalDenda = (float) $stmtTotalDenda->fetchColumn();

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Statistik Singkat -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Sedang Dipinjam</div>
        <div class="stat-value"><?= count($aktif) ?></div>
        <div class="stat-sub">dari maks. <?= MAKS_PINJAM ?> transaksi</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Peminjaman</div>
        <div class="stat-value"><?= $totalPinjam ?></div>
        <div class="stat-sub">sepanjang waktu</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Denda</div>
        <div class="stat-value" style="font-size:1.2rem;"><?= formatRupiah($totalDenda) ?></div>
        <div class="stat-sub">sudah dibayar</div>
    </div>
</div>

<!-- Pinjaman Aktif -->
<div class="card">
    <div class="card-title">Pinjaman Aktif Saya</div>

    <?php if (empty($aktif)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">📖</div>
            <div class="empty-state-text">Tidak ada pinjaman aktif saat ini.</div>
            <a href="<?= APP_URL ?>/anggota/katalog.php" class="btn btn-secondary btn-sm" style="margin-top:1rem;">
                Lihat Katalog Buku
            </a>
        </div>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Buku</th>
                        <th>Tgl Pinjam</th>
                        <th>Jatuh Tempo</th>
                        <th>Status</th>
                        <th>Est. Denda</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($aktif as $p): ?>
                        <?php
                        $isLate  = isTelat($p['tanggal_jatuh_tempo']);
                        $estDenda = $isLate ? hitungDenda($p['tanggal_jatuh_tempo'], date('Y-m-d'), $p['jumlah_buku']) : 0;
                        ?>
                        <tr>
                            <td class="td-muted">#<?= $p['id'] ?></td>
                            <td>
                                <strong><?= e(mb_strimwidth($p['judul_buku'], 0, 60, '...')) ?></strong>
                                <div class="td-muted" style="font-size:0.75rem;"><?= $p['jumlah_buku'] ?> judul</div>
                            </td>
                            <td class="td-muted"><?= formatTanggal($p['tanggal_pinjam']) ?></td>
                            <td class="td-muted"><?= formatTanggal($p['tanggal_jatuh_tempo']) ?></td>
                            <td>
                                <?php if ($isLate): ?>
                                    <span class="badge badge-telat">Terlambat</span>
                                <?php else: ?>
                                    <span class="badge badge-dipinjam">Dipinjam</span>
                                <?php endif; ?>
                            </td>
                            <td class="td-muted">
                                <?= $estDenda > 0 ? formatRupiah($estDenda) : '—' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div style="margin-top:0.75rem;">
            <a href="<?= APP_URL ?>/anggota/riwayat.php" class="btn btn-secondary btn-sm">
                Lihat Semua Riwayat →
            </a>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
