<?php
/**
 * Format angka menjadi format Rupiah
 */
function formatRupiah(float $angka): string {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

/**
 * Format tanggal dari format database ke format Indonesia
 * Contoh: 2024-01-15 -> 15 Januari 2024
 */
function formatTanggal(?string $tanggal): string {
    if (!$tanggal) return '-';
    $bulan = [
        1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',
        5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',
        9=>'September',10=>'Oktober',11=>'November',12=>'Desember'
    ];
    $d = new DateTime($tanggal);
    return $d->format('d') . ' ' . $bulan[(int)$d->format('n')] . ' ' . $d->format('Y');
}

/**
 * Hitung selisih hari antara dua tanggal
 * Positif = tanggal2 lebih besar, Negatif = sudah telat
 */
function selisihHari(string $tanggal1, string $tanggal2): int {
    $d1 = new DateTime($tanggal1);
    $d2 = new DateTime($tanggal2);
    $diff = $d1->diff($d2);
    return $diff->invert ? -$diff->days : $diff->days;
}

/**
 * Hitung denda keterlambatan
 * @param string $jatuhTempo   Tanggal jatuh tempo (Y-m-d)
 * @param string $tanggalKembali Tanggal pengembalian (Y-m-d)
 * @param int    $jumlahBuku   Jumlah buku yang dipinjam
 */
function hitungDenda(string $jatuhTempo, string $tanggalKembali, int $jumlahBuku): float {
    $selisih = selisihHari($tanggalKembali, $jatuhTempo); // negatif = telat
    if ($selisih >= 0) return 0.00; // tidak terlambat
    $hariTelat = abs($selisih);
    return $hariTelat * $jumlahBuku * DENDA_PER_HARI;
}

/**
 * Cek apakah peminjaman sudah telat (berdasarkan hari ini)
 */
function isTelat(string $jatuhTempo): bool {
    $today   = new DateTime();
    $expired = new DateTime($jatuhTempo);
    return $today > $expired;
}

/**
 * Tampilkan pesan flash (dari session)
 * Dipanggil di header setelah session dibuka
 */
function tampilkanFlash(): string {
    if (!isset($_SESSION['flash'])) return '';
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $type    = htmlspecialchars($flash['type']);
    $message = htmlspecialchars($flash['message']);
    return "<div class=\"alert alert-{$type}\" role=\"alert\">{$message}</div>";
}

/**
 * Set pesan flash ke session
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Redirect ke URL tertentu
 */
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

/**
 * Bersihkan dan escape string untuk HTML output
 */
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Hitung jumlah buku aktif yang sedang dipinjam oleh seorang anggota
 */
function hitungPinjamanAktif(PDO $pdo, int $userId): int {
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(jumlah_buku), 0) AS total
        FROM peminjaman p
        JOIN (SELECT peminjaman_id, COUNT(*) AS jumlah_buku FROM detail_peminjaman GROUP BY peminjaman_id) d
          ON p.id = d.peminjaman_id
        WHERE p.user_id = :uid AND p.status = 'dipinjam'
    ");
    $stmt->execute([':uid' => $userId]);
    return (int) $stmt->fetchColumn();
}

/**
 * Generate kode buku otomatis
 */
function generateKodeBuku(PDO $pdo): string {
    $stmt = $pdo->query("SELECT MAX(id) FROM buku");
    $maxId = (int) $stmt->fetchColumn();
    return 'BK-' . str_pad($maxId + 1, 3, '0', STR_PAD_LEFT);
}
