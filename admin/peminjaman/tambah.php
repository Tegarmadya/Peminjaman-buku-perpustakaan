<?php
require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/fungsi.php';

requireRole('admin');

$pageTitle  = 'Pinjam Buku';
$activePage = 'pinjam_baru';
$errors     = [];

// Ambil daftar anggota
$anggotaList = $pdo->query("SELECT id, nama, username FROM users WHERE role = 'anggota' ORDER BY nama")->fetchAll();

// Ambil buku yang tersedia (stok_tersedia > 0)
$bukuTersedia = $pdo->query("SELECT id, kode_buku, judul, penulis, kategori, stok_tersedia FROM buku WHERE stok_tersedia > 0 ORDER BY judul")->fetchAll();

// Default tanggal
$tanggalPinjam = date('Y-m-d');

// Proses form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId    = (int)   ($_POST['user_id']   ?? 0);
    $bukuIds   = array_map('intval', $_POST['buku_ids'] ?? []);
    $bukuIds   = array_values(array_unique($bukuIds));
    $tglPinjam = trim($_POST['tanggal_pinjam'] ?? '');

    // Validasi
    if ($userId <= 0)                  $errors[] = 'Pilih anggota.';
    if (empty($bukuIds))               $errors[] = 'Pilih minimal satu buku.';
    if (count($bukuIds) > MAKS_PINJAM) $errors[] = 'Maksimal ' . MAKS_PINJAM . ' buku per transaksi.';

    // Validasi format tanggal pinjam dan hitung jatuh tempo
    $d = DateTime::createFromFormat('Y-m-d', $tglPinjam);
    if (!$d || $d->format('Y-m-d') !== $tglPinjam) {
        $errors[] = 'Format tanggal pinjam tidak valid.';
    } else {
        $tglJatuh = (clone $d)->modify('+' . LAMA_PINJAM . ' days')->format('Y-m-d');
    }

    if (empty($errors)) {
        // Cek batas pinjaman aktif anggota
        $aktif = hitungPinjamanAktif($pdo, $userId);
        if ($aktif + count($bukuIds) > MAKS_PINJAM) {
            $sisa = MAKS_PINJAM - $aktif;
            $errors[] = "Anggota sudah meminjam $aktif buku aktif. Hanya boleh tambah $sisa buku lagi (maks " . MAKS_PINJAM . ").";
        }
    }

    if (empty($errors)) {
        // Validasi stok masing-masing buku
        foreach ($bukuIds as $bid) {
            $stmtCek = $pdo->prepare("SELECT judul, stok_tersedia FROM buku WHERE id = :id LIMIT 1");
            $stmtCek->execute([':id' => $bid]);
            $bk = $stmtCek->fetch();
            if (!$bk) {
                $errors[] = 'Buku tidak ditemukan.';
                continue;
            }
            if ($bk['stok_tersedia'] < 1) {
                $errors[] = "Buku '{$bk['judul']}' sudah tidak tersedia.";
            }
        }
    }

    if (empty($errors)) {
        // Mulai transaksi database
        $pdo->beginTransaction();
        try {
            // Insert ke peminjaman
            $stmtP = $pdo->prepare("
                INSERT INTO peminjaman (user_id, tanggal_pinjam, tanggal_jatuh_tempo, status)
                VALUES (:user_id, :tgl_pinjam, :tgl_jatuh, 'dipinjam')
            ");
            $stmtP->execute([
                ':user_id'   => $userId,
                ':tgl_pinjam'=> $tglPinjam,
                ':tgl_jatuh' => $tglJatuh,
            ]);
            $pinjamanId = (int) $pdo->lastInsertId();

            // Insert detail dan kurangi stok
            $stmtD = $pdo->prepare("INSERT INTO detail_peminjaman (peminjaman_id, buku_id) VALUES (:pid, :bid)");
            $stmtS = $pdo->prepare("UPDATE buku SET stok_tersedia = stok_tersedia - 1 WHERE id = :id AND stok_tersedia > 0");
            foreach ($bukuIds as $bid) {
                $stmtD->execute([':pid' => $pinjamanId, ':bid' => $bid]);
                $stmtS->execute([':id'  => $bid]);
            }

            $pdo->commit();
            setFlash('success', 'Peminjaman berhasil dicatat. ID transaksi: #' . $pinjamanId);
            redirect(APP_URL . '/admin/peminjaman/detail.php?id=' . $pinjamanId);
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Gagal menyimpan data: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card" style="max-width:720px;">
    <div class="card-title">Form Peminjaman Buku</div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $err): ?><div>• <?= e($err) ?></div><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="" data-validate id="formPinjam">

        <!-- Pilih Anggota -->
        <div class="form-group">
            <label class="form-label" for="user_id">Anggota</label>
            <select id="user_id" name="user_id" class="form-control" required data-label="Anggota">
                <option value="">— Pilih Anggota —</option>
                <?php foreach ($anggotaList as $a): ?>
                    <option value="<?= $a['id'] ?>"
                        <?= (int)($_POST['user_id'] ?? 0) === (int)$a['id'] ? 'selected' : '' ?>>
                        <?= e($a['nama']) ?> (<?= e($a['username']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="form-text">Maks. <?= MAKS_PINJAM ?> buku aktif per anggota.</div>
        </div>

        <!-- Tanggal -->
        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="tanggal_pinjam">Tanggal Pinjam</label>
                <input type="date" id="tanggal_pinjam" name="tanggal_pinjam" class="form-control"
                       value="<?= e($_POST['tanggal_pinjam'] ?? $tanggalPinjam) ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Jatuh Tempo</label>
                <div class="form-text" style="margin-top:0.6rem;">Otomatis <?= LAMA_PINJAM ?> hari dari tanggal pinjam.</div>
            </div>
        </div>

        <!-- Pilih Buku -->
        <div class="form-group">
            <label class="form-label">Pilih Buku</label>
            <?php if (empty($bukuTersedia)): ?>
                <div class="alert alert-warning">Tidak ada buku yang tersedia saat ini.</div>
            <?php else: ?>
                <!-- Search buku dalam picker -->
                <input type="text" id="searchBukuPicker" class="form-control mb-1"
                       placeholder="Cari judul atau penulis..." style="margin-bottom:0.5rem;">
                <div class="book-picker" id="bookPicker">
                    <?php
                    $selectedBukuIds = array_map('intval', $_POST['buku_ids'] ?? []);
                    foreach ($bukuTersedia as $b): ?>
                        <label class="book-picker-item" for="buku_<?= $b['id'] ?>">
                            <input type="checkbox"
                                   id="buku_<?= $b['id'] ?>"
                                   name="buku_ids[]"
                                   value="<?= $b['id'] ?>"
                                   <?= in_array((int)$b['id'], $selectedBukuIds) ? 'checked' : '' ?>>
                            <div class="book-picker-info">
                                <div class="book-picker-title"><?= e($b['judul']) ?></div>
                                <div class="book-picker-meta">
                                    <?= e($b['penulis']) ?> &bull; <?= e($b['kategori']) ?>
                                    &bull; Tersedia: <?= $b['stok_tersedia'] ?>
                                </div>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
                <div id="pickerCounter" class="picker-counter">Belum ada buku dipilih</div>
            <?php endif; ?>
        </div>

        <div class="form-actions">
            <a href="<?= APP_URL ?>/admin/peminjaman/index.php" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">Catat Peminjaman</button>
        </div>
    </form>
</div>

<script>
// Filter buku dalam picker
document.getElementById('searchBukuPicker').addEventListener('input', function() {
    const kw = this.value.toLowerCase();
    document.querySelectorAll('#bookPicker .book-picker-item').forEach(function(item) {
        const txt = item.textContent.toLowerCase();
        item.style.display = kw === '' || txt.includes(kw) ? '' : 'none';
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
