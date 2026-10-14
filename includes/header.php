<?php
$pageTitle  = $pageTitle  ?? APP_NAME;
$activePage = $activePage ?? '';
$user       = currentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistem Peminjaman Buku Perpustakaan Digital">
    <title><?= e($pageTitle) ?> — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body>

<div class="layout">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <span class="brand-icon">📚</span>
            <span class="brand-text"><?= APP_NAME ?></span>
        </div>

        <nav class="sidebar-nav">
            <?php if ($user['role'] === 'admin'): ?>
                <!-- Menu Admin -->
                <a href="<?= APP_URL ?>/admin/dashboard.php"
                   class="nav-item <?= $activePage === 'dashboard' ? 'active' : '' ?>">
                    <span class="nav-icon">▪</span> Dashboard
                </a>
                <div class="nav-group-label">Manajemen</div>
                <a href="<?= APP_URL ?>/admin/buku/index.php"
                   class="nav-item <?= $activePage === 'buku' ? 'active' : '' ?>">
                    <span class="nav-icon">▪</span> Data Buku
                </a>
                <a href="<?= APP_URL ?>/admin/anggota/index.php"
                   class="nav-item <?= $activePage === 'anggota' ? 'active' : '' ?>">
                    <span class="nav-icon">▪</span> Data Anggota
                </a>
                <div class="nav-group-label">Transaksi</div>
                <a href="<?= APP_URL ?>/admin/peminjaman/index.php"
                   class="nav-item <?= $activePage === 'peminjaman' ? 'active' : '' ?>">
                    <span class="nav-icon">▪</span> Peminjaman
                </a>
                <a href="<?= APP_URL ?>/admin/peminjaman/tambah.php"
                   class="nav-item <?= $activePage === 'pinjam_baru' ? 'active' : '' ?>">
                    <span class="nav-icon">▪</span> Pinjam Buku
                </a>
                <div class="nav-group-label">Laporan</div>
                <a href="<?= APP_URL ?>/admin/laporan.php"
                   class="nav-item <?= $activePage === 'laporan' ? 'active' : '' ?>">
                    <span class="nav-icon">▪</span> Laporan
                </a>
            <?php else: ?>
                <!-- Menu Anggota -->
                <a href="<?= APP_URL ?>/anggota/dashboard.php"
                   class="nav-item <?= $activePage === 'dashboard' ? 'active' : '' ?>">
                    <span class="nav-icon">▪</span> Dashboard
                </a>
                <a href="<?= APP_URL ?>/anggota/katalog.php"
                   class="nav-item <?= $activePage === 'katalog' ? 'active' : '' ?>">
                    <span class="nav-icon">▪</span> Katalog Buku
                </a>
                <a href="<?= APP_URL ?>/anggota/riwayat.php"
                   class="nav-item <?= $activePage === 'riwayat' ? 'active' : '' ?>">
                    <span class="nav-icon">▪</span> Riwayat Pinjam
                </a>
            <?php endif; ?>
        </nav>

        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar"><?= strtoupper(substr($user['nama'], 0, 1)) ?></div>
                <div>
                    <div class="user-name"><?= e($user['nama']) ?></div>
                    <div class="user-role"><?= ucfirst($user['role']) ?></div>
                </div>
            </div>
            <a href="<?= APP_URL ?>/auth/logout.php" class="btn-logout">Keluar</a>
        </div>
    </aside>

    <main class="main-content">
        <div class="topbar">
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">☰</button>
            <h1 class="page-title"><?= e($pageTitle) ?></h1>
        </div>

        <!-- Flash message (sukses/gagal) -->
        <?= tampilkanFlash() ?>

        <div class="content-body">
