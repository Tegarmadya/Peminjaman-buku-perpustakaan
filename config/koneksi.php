<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'perpustakaan');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Pengaturan aplikasi
define('APP_NAME', 'Perpustakaan');
define('APP_URL',  'http://localhost/Peminjaman_Buku');
define('DENDA_PER_HARI', 1000);  // Rp 1.000 per hari per buku
define('LAMA_PINJAM', 7);         // 7 hari
define('MAKS_PINJAM', 3);         // Maksimal 3 buku aktif per anggota

// Buat koneksi PDO
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    // Tampilkan pesan error yang ramah
    die('<div style="font-family:sans-serif;background:#111;color:#f55;padding:2rem;border:1px solid #f55;margin:2rem;border-radius:6px;">
        <h2>Gagal Terhubung ke Database</h2>
        <p>Pastikan MySQL XAMPP sudah berjalan dan database <strong>perpustakaan</strong> sudah diimport.</p>
        <p style="color:#888;font-size:0.85rem;">Detail: ' . htmlspecialchars($e->getMessage()) . '</p>
    </div>');
}
