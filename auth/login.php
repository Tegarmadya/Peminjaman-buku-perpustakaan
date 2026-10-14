<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/fungsi.php';

// Jika sudah login, redirect ke dashboard sesuai role
if (isLoggedIn()) {
    $role = $_SESSION['role'];
    redirect(APP_URL . ($role === 'admin' ? '/admin/dashboard.php' : '/anggota/dashboard.php'));
}

$error = '';

// Proses form login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        // Ambil user dari database menggunakan prepared statement
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Login berhasil — simpan ke session
            session_regenerate_id(true);
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['nama']     = $user['nama'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];

            // Redirect berdasarkan role
            redirect(APP_URL . ($user['role'] === 'admin'
                ? '/admin/dashboard.php'
                : '/anggota/dashboard.php'));
        } else {
            $error = 'Username atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Login Sistem Perpustakaan">
    <title>Login — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body>
<div class="login-page">
    <div class="login-box">
        <div class="login-brand">
            <span class="login-brand-icon">📚</span>
            <div class="login-brand-name"><?= APP_NAME ?></div>
            <div class="login-brand-sub">Sistem Peminjaman Buku</div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="" data-validate id="loginForm">
            <div class="form-group">
                <label class="form-label" for="username">Username</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    class="form-control"
                    placeholder="Masukkan username"
                    value="<?= e($_POST['username'] ?? '') ?>"
                    required
                    data-label="Username"
                    autocomplete="username"
                >
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control"
                    placeholder="Masukkan password"
                    required
                    data-label="Password"
                    autocomplete="current-password"
                >
            </div>

            <div class="form-actions-full" style="margin-top:1.25rem;">
                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
                    Masuk
                </button>
            </div>
        </form>

        <p style="text-align:center;margin-top:1.2rem;font-size:0.8rem;color:var(--text-muted);">
            Belum punya akun?
            <a href="<?= APP_URL ?>/auth/register.php" style="color:var(--text-secondary);text-decoration:underline;">
                Daftar sebagai anggota
            </a>
        </p>

        <!-- Info akun demo -->
        <div style="margin-top:1.5rem;padding-top:1rem;border-top:1px solid var(--border);">
            <p style="font-size:0.7rem;color:var(--text-muted);text-align:center;margin-bottom:0.5rem;">
                AKUN DEMO
            </p>
            <div style="font-size:0.75rem;color:var(--text-secondary);display:flex;flex-direction:column;gap:0.3rem;">
                <div>Admin: <strong>admin</strong> / <strong>admin123</strong></div>
                <div>Anggota: <strong>tegar</strong> / <strong>12345</strong></div>
            </div>
        </div>
    </div>
</div>
<script src="<?= APP_URL ?>/assets/js/app.js"></script>
</body>
</html>
