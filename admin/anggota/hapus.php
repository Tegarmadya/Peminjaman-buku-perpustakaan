<?php
require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/fungsi.php';

requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/admin/anggota/index.php');
}

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) { setFlash('error', 'ID tidak valid.'); redirect(APP_URL . '/admin/anggota/index.php'); }

// Tidak boleh hapus diri sendiri
if ($id === (int) $_SESSION['user_id']) {
    setFlash('error', 'Tidak dapat menghapus akun sendiri.');
    redirect(APP_URL . '/admin/anggota/index.php');
}

$stmt = $pdo->prepare("SELECT nama FROM users WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$user = $stmt->fetch();

if (!$user) { setFlash('error', 'Anggota tidak ditemukan.'); redirect(APP_URL . '/admin/anggota/index.php'); }

// Cek apakah ada pinjaman aktif
$stmtCek = $pdo->prepare("SELECT COUNT(*) FROM peminjaman WHERE user_id = :id AND status = 'dipinjam'");
$stmtCek->execute([':id' => $id]);
if ((int)$stmtCek->fetchColumn() > 0) {
    setFlash('error', "Tidak dapat menghapus '{$user['nama']}' karena masih memiliki pinjaman aktif.");
    redirect(APP_URL . '/admin/anggota/index.php');
}

try {
    $pdo->prepare("DELETE FROM users WHERE id = :id")->execute([':id' => $id]);
    setFlash('success', "Anggota '{$user['nama']}' berhasil dihapus.");
} catch (PDOException $e) {
    setFlash('error', 'Tidak dapat dihapus karena memiliki riwayat peminjaman.');
}
redirect(APP_URL . '/admin/anggota/index.php');
