<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/fungsi.php';

requireRole('admin');

$pageTitle  = 'Laporan';
$activePage = 'laporan';

// Ringkasan statistik
$totalDenda   = (float) $pdo->query("SELECT COALESCE(SUM(denda),0) FROM peminjaman")->fetchColumn();
$totalKembali = (int)   $pdo->query("SELECT COUNT(*) FROM peminjaman WHERE status = 'dikembalikan'")->fetchColumn();
$totalAktif   = (int)   $pdo->query("SELECT COUNT(*) FROM peminjaman WHERE status = 'dipinjam'")->fetchColumn();

// Buku terpopuler
$bukuPopuler = $pdo->query("
    SELECT b.kode_buku, b.judul, b.penulis, b.kategori,
           COUNT(dp.id) AS total_pinjam
    FROM detail_peminjaman dp
    JOIN buku b ON dp.buku_id = b.id
    GROUP BY b.id
    ORDER BY total_pinjam DESC
    LIMIT 5
")->fetchAll();

// Daftar peminjaman terlambat
$telat = $pdo->query("
    SELECT p.id, u.nama, u.no_hp, p.tanggal_pinjam, p.tanggal_jatuh_tempo,
           DATEDIFF(CURDATE(), p.tanggal_jatuh_tempo) AS hari_telat,
           (SELECT COUNT(*) FROM detail_peminjaman dp WHERE dp.peminjaman_id = p.id) AS jumlah_buku
    FROM peminjaman p
    JOIN users u ON p.user_id = u.id
    WHERE p.status = 'dipinjam' AND p.tanggal_jatuh_tempo < CURDATE()
    ORDER BY hari_telat DESC
")->fetchAll();

// Riwayat transaksi
$riwayat = $pdo->query("
    SELECT p.id, u.nama, p.tanggal_pinjam, p.tanggal_jatuh_tempo,
           p.tanggal_kembali, p.status, p.denda,
           (SELECT COUNT(*) FROM detail_peminjaman dp WHERE dp.peminjaman_id = p.id) AS jumlah_buku
    FROM peminjaman p
    JOIN users u ON p.user_id = u.id
    ORDER BY p.id DESC
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Ringkasan Total -->
<div class="stats-grid" style="margin-bottom:1.5rem;">
    <div class="stat-card">
        <div class="stat-label">Total Transaksi</div>
        <div class="stat-value"><?= $totalAktif + $totalKembali ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Aktif / Dipinjam</div>
        <div class="stat-value"><?= $totalAktif ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Dikembalikan</div>
        <div class="stat-value"><?= $totalKembali ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Terlambat</div>
        <div class="stat-value"><?= count($telat) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Denda Terkumpul</div>
        <div class="stat-value" style="font-size:1.1rem;"><?= formatRupiah($totalDenda) ?></div>
    </div>
</div>

<!-- Buku Paling Sering Dipinjam -->
<div class="report-section">
    <div class="report-section-title">5 Buku Paling Sering Dipinjam</div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Peringkat</th>
                    <th>Kode</th>
                    <th>Judul</th>
                    <th>Penulis</th>
                    <th>Kategori</th>
                    <th>Total Dipinjam</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($bukuPopuler)): ?>
                    <tr><td colspan="6" class="text-center" style="padding:1.5rem;color:var(--text-muted);">Belum ada data.</td></tr>
                <?php else: ?>
                    <?php foreach ($bukuPopuler as $i => $b): ?>
                        <tr>
                            <td class="td-muted fw-bold" style="text-align:center;"><?= $i + 1 ?></td>
                            <td class="td-muted"><?= e($b['kode_buku']) ?></td>
                            <td class="fw-bold"><?= e($b['judul']) ?></td>
                            <td class="td-muted"><?= e($b['penulis']) ?></td>
                            <td><?= e($b['kategori']) ?></td>
                            <td><?= $b['total_pinjam'] ?> kali</td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Daftar Keterlambatan -->
<div class="report-section">
    <div class="report-section-title">Peminjaman Terlambat (<?= count($telat) ?>)</div>
    <?php if (empty($telat)): ?>
        <div class="alert alert-info">Tidak ada peminjaman yang terlambat. 🎉</div>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>#ID</th>
                        <th>Anggota</th>
                        <th>No. HP</th>
                        <th>Jatuh Tempo</th>
                        <th>Hari Telat</th>
                        <th>Jml Buku</th>
                        <th>Est. Denda</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($telat as $t): ?>
                        <?php $estDenda = $t['hari_telat'] * $t['jumlah_buku'] * DENDA_PER_HARI; ?>
                        <tr>
                            <td class="td-muted">#<?= $t['id'] ?></td>
                            <td class="fw-bold"><?= e($t['nama']) ?></td>
                            <td class="td-muted"><?= e($t['no_hp'] ?: '—') ?></td>
                            <td class="td-muted"><?= formatTanggal($t['tanggal_jatuh_tempo']) ?></td>
                            <td><span class="badge badge-telat"><?= $t['hari_telat'] ?> hari</span></td>
                            <td><?= $t['jumlah_buku'] ?></td>
                            <td class="fw-bold"><?= formatRupiah($estDenda) ?></td>
                            <td>
                                <a href="<?= APP_URL ?>/admin/peminjaman/detail.php?id=<?= $t['id'] ?>"
                                   class="btn btn-secondary btn-sm">Proses</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Riwayat Semua Transaksi -->
<div class="report-section">
    <div class="report-section-title">Riwayat Seluruh Transaksi</div>
    <div style="margin-bottom:0.75rem;">
        <input type="text" class="form-control" style="max-width:320px;"
               placeholder="Cari nama anggota, status..."
               data-search-target="#tableRiwayat">
    </div>
    <div class="table-wrapper">
        <table id="tableRiwayat">
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
                <?php foreach ($riwayat as $r): ?>
                    <?php $isLate = ($r['status'] === 'dipinjam' && isTelat($r['tanggal_jatuh_tempo'])); ?>
                    <tr>
                        <td class="td-muted">#<?= $r['id'] ?></td>
                        <td class="fw-bold"><?= e($r['nama']) ?></td>
                        <td><?= $r['jumlah_buku'] ?></td>
                        <td class="td-muted"><?= formatTanggal($r['tanggal_pinjam']) ?></td>
                        <td class="td-muted"><?= formatTanggal($r['tanggal_jatuh_tempo']) ?></td>
                        <td class="td-muted"><?= $r['tanggal_kembali'] ? formatTanggal($r['tanggal_kembali']) : '—' ?></td>
                        <td>
                            <?php if ($r['status'] === 'dikembalikan'): ?>
                                <span class="badge badge-dikembalikan">Dikembalikan</span>
                            <?php elseif ($isLate): ?>
                                <span class="badge badge-telat">Terlambat</span>
                            <?php else: ?>
                                <span class="badge badge-dipinjam">Dipinjam</span>
                            <?php endif; ?>
                        </td>
                        <td class="td-muted"><?= $r['denda'] > 0 ? formatRupiah($r['denda']) : '—' ?></td>
                        <td>
                            <a href="<?= APP_URL ?>/admin/peminjaman/detail.php?id=<?= $r['id'] ?>"
                               class="btn btn-secondary btn-sm">Detail</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
