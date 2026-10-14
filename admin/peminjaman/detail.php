<?php
require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/fungsi.php';

requireRole('admin');

$pageTitle  = 'Detail Peminjaman';
$activePage = 'peminjaman';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) { setFlash('error', 'ID tidak valid.'); redirect(APP_URL . '/admin/peminjaman/index.php'); }

// Ambil data peminjaman beserta anggota
$stmtP = $pdo->prepare("
    SELECT p.*, u.nama, u.username, u.no_hp
    FROM peminjaman p
    JOIN users u ON p.user_id = u.id
    WHERE p.id = :id LIMIT 1
");
$stmtP->execute([':id' => $id]);
$pinjam = $stmtP->fetch();

if (!$pinjam) { setFlash('error', 'Data peminjaman tidak ditemukan.'); redirect(APP_URL . '/admin/peminjaman/index.php'); }

// Ambil detail buku yang dipinjam
$detailBuku = $pdo->prepare("
    SELECT b.id, b.kode_buku, b.judul, b.penulis, b.kategori
    FROM detail_peminjaman dp
    JOIN buku b ON dp.buku_id = b.id
    WHERE dp.peminjaman_id = :pid
");
$detailBuku->execute([':pid' => $id]);
$bukuList = $detailBuku->fetchAll();

$jumlahBuku = count($bukuList);
$errors     = [];

// Proses pengembalian
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pinjam['status'] === 'dipinjam') {
    $tanggalKembali = $_POST['tanggal_kembali'] ?? date('Y-m-d');

    // Validasi tanggal kembali tidak sebelum tanggal pinjam
    if ($tanggalKembali < $pinjam['tanggal_pinjam']) {
        $errors[] = 'Tanggal kembali tidak boleh sebelum tanggal pinjam.';
    }

    if (empty($errors)) {
        // Hitung denda
        $denda = hitungDenda($pinjam['tanggal_jatuh_tempo'], $tanggalKembali, $jumlahBuku);

        $pdo->beginTransaction();
        try {
            // Update status peminjaman
            $pdo->prepare("
                UPDATE peminjaman
                SET status = 'dikembalikan', tanggal_kembali = :tgl, denda = :denda
                WHERE id = :id
            ")->execute([':tgl' => $tanggalKembali, ':denda' => $denda, ':id' => $id]);

            // Kembalikan stok buku
            foreach ($bukuList as $b) {
                $pdo->prepare("UPDATE buku SET stok_tersedia = stok_tersedia + 1 WHERE id = :id")
                    ->execute([':id' => $b['id']]);
            }

            $pdo->commit();

            // Pesan sukses dengan info denda
            $pesan = 'Pengembalian berhasil dicatat.';
            if ($denda > 0) {
                $pesan .= ' Denda keterlambatan: ' . formatRupiah($denda) . '.';
            }
            setFlash('success', $pesan);
            redirect(APP_URL . '/admin/peminjaman/detail.php?id=' . $id);
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Gagal menyimpan: ' . $e->getMessage();
        }
    }

    // Reload data setelah proses
    $stmtP->execute([':id' => $id]);
    $pinjam = $stmtP->fetch();
}

// Hitung estimasi denda jika belum dikembalikan
$estimasiDenda = 0;
if ($pinjam['status'] === 'dipinjam' && isTelat($pinjam['tanggal_jatuh_tempo'])) {
    $estimasiDenda = hitungDenda($pinjam['tanggal_jatuh_tempo'], date('Y-m-d'), $jumlahBuku);
}

$isLate = ($pinjam['status'] === 'dipinjam' && isTelat($pinjam['tanggal_jatuh_tempo']));

require_once __DIR__ . '/../../includes/header.php';
?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <?php foreach ($errors as $err): ?><div>• <?= e($err) ?></div><?php endforeach; ?>
    </div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">
    <!-- Info Transaksi -->
    <div class="card">
        <div class="card-title">Info Transaksi #<?= $pinjam['id'] ?></div>
        <table style="width:100%;font-size:0.875rem;border-collapse:collapse;">
            <tr><td style="padding:0.4rem 0;color:var(--text-secondary);width:45%;">Anggota</td>
                <td><strong><?= e($pinjam['nama']) ?></strong></td></tr>
            <tr><td style="padding:0.4rem 0;color:var(--text-secondary);">Username</td>
                <td><?= e($pinjam['username']) ?></td></tr>
            <tr><td style="padding:0.4rem 0;color:var(--text-secondary);">No. HP</td>
                <td><?= e($pinjam['no_hp'] ?: '—') ?></td></tr>
            <tr><td style="padding:0.4rem 0;color:var(--text-secondary);">Tgl Pinjam</td>
                <td><?= formatTanggal($pinjam['tanggal_pinjam']) ?></td></tr>
            <tr><td style="padding:0.4rem 0;color:var(--text-secondary);">Jatuh Tempo</td>
                <td><?= formatTanggal($pinjam['tanggal_jatuh_tempo']) ?></td></tr>
            <tr><td style="padding:0.4rem 0;color:var(--text-secondary);">Tgl Kembali</td>
                <td><?= $pinjam['tanggal_kembali'] ? formatTanggal($pinjam['tanggal_kembali']) : '—' ?></td></tr>
            <tr><td style="padding:0.4rem 0;color:var(--text-secondary);">Status</td>
                <td>
                    <?php if ($pinjam['status'] === 'dikembalikan'): ?>
                        <span class="badge badge-dikembalikan">Dikembalikan</span>
                    <?php elseif ($isLate): ?>
                        <span class="badge badge-telat">Terlambat</span>
                    <?php else: ?>
                        <span class="badge badge-dipinjam">Dipinjam</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr><td style="padding:0.4rem 0;color:var(--text-secondary);">Denda</td>
                <td><strong><?= $pinjam['denda'] > 0 ? formatRupiah($pinjam['denda']) : '—' ?></strong></td></tr>
        </table>
    </div>

    <!-- Form Pengembalian / Info Estimasi Denda -->
    <div class="card">
        <?php if ($pinjam['status'] === 'dipinjam'): ?>
            <div class="card-title">Proses Pengembalian</div>

            <?php if ($isLate): ?>
                <div class="alert alert-warning">
                    Sudah terlambat! Estimasi denda hari ini: <strong><?= formatRupiah($estimasiDenda) ?></strong>
                    (<?= $jumlahBuku ?> buku × Rp<?= number_format(DENDA_PER_HARI,0,',','.') ?>/hari)
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label" for="tanggal_kembali">Tanggal Pengembalian</label>
                    <input type="date" id="tanggal_kembali" name="tanggal_kembali" class="form-control"
                           value="<?= date('Y-m-d') ?>"
                           min="<?= $pinjam['tanggal_pinjam'] ?>"
                           max="<?= date('Y-m-d') ?>">
                    <div class="form-text">Denda dihitung otomatis jika melewati jatuh tempo.</div>
                </div>
                <div class="form-actions" style="margin-top:0.5rem;">
                    <button type="submit" class="btn btn-primary"
                            onclick="return confirm('Konfirmasi pengembalian buku? Stok akan dikembalikan dan denda dihitung.')">
                        Proses Pengembalian
                    </button>
                </div>
            </form>
        <?php else: ?>
            <div class="card-title">Pengembalian Selesai</div>
            <div style="color:var(--text-secondary);font-size:0.875rem;">
                <p>Buku telah dikembalikan pada:</p>
                <p style="color:var(--text-primary);font-weight:600;margin-top:0.5rem;">
                    <?= formatTanggal($pinjam['tanggal_kembali']) ?>
                </p>
                <?php if ($pinjam['denda'] > 0): ?>
                    <p style="margin-top:0.75rem;">Total denda dibayar:</p>
                    <p style="color:var(--text-primary);font-weight:600;">
                        <?= formatRupiah($pinjam['denda']) ?>
                    </p>
                <?php else: ?>
                    <p style="margin-top:0.75rem;color:var(--text-muted);">Tidak ada denda.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Daftar Buku yang Dipinjam -->
<div class="card">
    <div class="card-title">Buku yang Dipinjam (<?= $jumlahBuku ?> judul)</div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Kode</th>
                    <th>Judul</th>
                    <th>Penulis</th>
                    <th>Kategori</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bukuList as $i => $b): ?>
                    <tr>
                        <td class="td-muted"><?= $i + 1 ?></td>
                        <td class="td-muted"><?= e($b['kode_buku']) ?></td>
                        <td class="fw-bold"><?= e($b['judul']) ?></td>
                        <td class="td-muted"><?= e($b['penulis']) ?></td>
                        <td><?= e($b['kategori']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="btn-group">
    <a href="<?= APP_URL ?>/admin/peminjaman/index.php" class="btn btn-secondary">← Kembali ke Daftar</a>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
