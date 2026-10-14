<?php
require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/fungsi.php';

if (isLoggedIn()) {
    $role = $_SESSION['role'];
    redirect(APP_URL . ($role === 'admin' ? '/admin/dashboard.php' : '/anggota/dashboard.php'));
} else {
    redirect(APP_URL . '/auth/login.php');
}
