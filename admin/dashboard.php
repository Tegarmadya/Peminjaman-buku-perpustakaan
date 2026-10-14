<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/fungsi.php';

requireRole('admin');

$pageTitle  = 'Dashboard';
$activePage = 'dashboard';

// Statistik ringkasan
$totalBuku    = (int) $pdo->query("SELECT COUNT(*) FROM buku")->fetchColumn();
$totalAnggota = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'anggota'")->fetchColumn();
$sedangDipinjam = (int) $pdo->query("SELECT COUNT(*) FROM peminjaman WHERE status = 'dipinjam'")->fetchColumn();
$jumlahTelat  = (int) $pdo->query("
    SELECT COUNT(*) FROM peminjaman
    WHERE status = 'dipinjam' AND tanggal_jatuh_tempo < CURDATE()
")->fetchColumn();
$totalDenda   = (float) $pdo->query("SELECT COALESCE(SUM(denda), 0) FROM peminjaman")->fetchColumn();

// Transaksi terbaru
$transaksiTerbaru = $pdo->query("
    SELECT p.id, u.nama, p.tanggal_pinjam, p.tanggal_jatuh_tempo,
           p.tanggal_kembali, p.status, p.denda,
           (SELECT COUNT(*) FROM detail_peminjaman dp WHERE dp.peminjaman_id = p.id) AS jumlah_buku
    FROM peminjaman p
    JOIN users u ON p.user_id = u.id
    ORDER BY p.id DESC
    LIMIT 10
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Statistik -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Total Buku</div>
        <div class="stat-value"><?= $totalBuku ?></div>
        <div class="stat-sub">Judul terdaftar</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Anggota</div>
        <div class="stat-value"><?= $totalAnggota ?></div>
        <div class="stat-sub">Pengguna terdaftar</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Sedang Dipinjam</div>
        <div class="stat-value"><?= $sedangDipinjam ?></div>
        <div class="stat-sub">Transaksi aktif</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Terlambat</div>
        <div class="stat-value"><?= $jumlahTelat ?></div>
        <div class="stat-sub">Lewat jatuh tempo</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Denda</div>
        <div class="stat-value" style="font-size:1.3rem;"><?= formatRupiah($totalDenda) ?></div>
        <div class="stat-sub">Denda terkumpul</div>
    </div>
</div>

<!-- Transaksi Terbaru -->
<div class="card">
    <div class="card-title">Transaksi Peminjaman Terbaru</div>
    <div class="toolbar">
        <div></div>
        <a href="<?= APP_URL ?>/admin/peminjaman/tambah.php" class="btn btn-primary btn-sm">
            + Pinjam Buku
        </a>
    </div>

    <div class="table-wrapper">
        <table id="dashboardTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Anggota</th>
                    <th>Jml Buku</th>
                    <th>Tgl Pinjam</th>
                    <th>Jatuh Tempo</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transaksiTerbaru)): ?>
                    <tr>
                        <td colspan="7" class="text-center" style="padding:2rem;color:var(--text-muted);">
                            Belum ada transaksi.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($transaksiTerbaru as $t): ?>
                        <?php
                        $isLate = ($t['status'] === 'dipinjam' && isTelat($t['tanggal_jatuh_tempo']));
                        ?>
                        <tr>
                            <td class="td-muted"><?= $t['id'] ?></td>
                            <td><?= e($t['nama']) ?></td>
                            <td><?= $t['jumlah_buku'] ?> buku</td>
                            <td class="td-muted"><?= formatTanggal($t['tanggal_pinjam']) ?></td>
                            <td class="td-muted"><?= formatTanggal($t['tanggal_jatuh_tempo']) ?></td>
                            <td>
                                <?php if ($t['status'] === 'dikembalikan'): ?>
                                    <span class="badge badge-dikembalikan">Dikembalikan</span>
                                <?php elseif ($isLate): ?>
                                    <span class="badge badge-telat">Terlambat</span>
                                <?php else: ?>
                                    <span class="badge badge-dipinjam">Dipinjam</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?= APP_URL ?>/admin/peminjaman/detail.php?id=<?= $t['id'] ?>"
                                   class="btn btn-secondary btn-sm">Detail</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
