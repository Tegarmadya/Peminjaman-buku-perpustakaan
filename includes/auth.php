<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Pastikan user sudah login, jika tidak redirect ke login
 */
function requireLogin(): void {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . APP_URL . '/auth/login.php');
        exit;
    }
}

/**
 * Pastikan user memiliki role tertentu
 * @param string $role Role yang dibutuhkan ('admin' atau 'anggota')
 */
function requireRole(string $role): void {
    requireLogin();
    if ($_SESSION['role'] !== $role) {
        // Redirect ke dashboard sesuai role masing-masing
        if ($_SESSION['role'] === 'admin') {
            header('Location: ' . APP_URL . '/admin/dashboard.php');
        } else {
            header('Location: ' . APP_URL . '/anggota/dashboard.php');
        }
        exit;
    }
}

/**
 * Cek apakah user sudah login
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

/**
 * Ambil data user yang sedang login
 */
function currentUser(): array {
    return [
        'id'       => $_SESSION['user_id']   ?? null,
        'nama'     => $_SESSION['nama']      ?? '',
        'username' => $_SESSION['username']  ?? '',
        'role'     => $_SESSION['role']      ?? '',
    ];
}
