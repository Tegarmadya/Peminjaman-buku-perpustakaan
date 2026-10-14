<?php
require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/fungsi.php';

requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/admin/buku/index.php');
}

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) {
    setFlash('error', 'ID buku tidak valid.');
    redirect(APP_URL . '/admin/buku/index.php');
}

// Cek apakah buku sedang dipinjam
$stmt = $pdo->prepare("
    SELECT b.judul, b.stok, b.stok_tersedia
    FROM buku b
    WHERE b.id = :id LIMIT 1
");
$stmt->execute([':id' => $id]);
$buku = $stmt->fetch();

if (!$buku) {
    setFlash('error', 'Buku tidak ditemukan.');
    redirect(APP_URL . '/admin/buku/index.php');
}

// Cek apakah ada detail_peminjaman aktif untuk buku ini
$stmtCek = $pdo->prepare("
    SELECT COUNT(*) FROM detail_peminjaman dp
    JOIN peminjaman p ON dp.peminjaman_id = p.id
    WHERE dp.buku_id = :id AND p.status = 'dipinjam'
");
$stmtCek->execute([':id' => $id]);
$sedangDipinjam = (int) $stmtCek->fetchColumn();

if ($sedangDipinjam > 0) {
    setFlash('error', "Buku '{$buku['judul']}' tidak dapat dihapus karena sedang dipinjam.");
    redirect(APP_URL . '/admin/buku/index.php');
}

// Hapus buku
try {
    $pdo->prepare("DELETE FROM buku WHERE id = :id")->execute([':id' => $id]);
    setFlash('success', "Buku '{$buku['judul']}' berhasil dihapus.");
} catch (PDOException $e) {
    setFlash('error', 'Tidak dapat dihapus karena memiliki riwayat peminjaman.');
}
redirect(APP_URL . '/admin/buku/index.php');
