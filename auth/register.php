<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/fungsi.php';

// Jika sudah login, redirect
if (isLoggedIn()) {
    $role = $_SESSION['role'];
    redirect(APP_URL . ($role === 'admin' ? '/admin/dashboard.php' : '/anggota/dashboard.php'));
}

$errors = [];
$formData = ['nama' => '', 'username' => '', 'no_hp' => ''];

// Proses form registrasi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['nama']     = trim($_POST['nama']     ?? '');
    $formData['username'] = trim($_POST['username'] ?? '');
    $formData['no_hp']    = trim($_POST['no_hp']    ?? '');
    $password             = $_POST['password']      ?? '';
    $konfirmasi           = $_POST['konfirmasi']    ?? '';

    // Validasi
    if ($formData['nama'] === '')          $errors[] = 'Nama lengkap wajib diisi.';
    if ($formData['username'] === '')      $errors[] = 'Username wajib diisi.';
    elseif (strlen($formData['username']) < 3) $errors[] = 'Username minimal 3 karakter.';
    if ($password === '')                  $errors[] = 'Password wajib diisi.';
    elseif (strlen($password) < 6)        $errors[] = 'Password minimal 6 karakter.';
    elseif ($password !== $konfirmasi)    $errors[] = 'Konfirmasi password tidak cocok.';

    // Cek apakah username sudah terdaftar
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $formData['username']]);
        if ($stmt->fetch()) {
            $errors[] = 'Username sudah digunakan, silakan pilih yang lain.';
        }
    }

    // Simpan ke database
    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (nama, username, password, role, no_hp)
            VALUES (:nama, :username, :password, 'anggota', :no_hp)
        ");
        $stmt->execute([
            ':nama'     => $formData['nama'],
            ':username' => $formData['username'],
            ':password' => $hash,
            ':no_hp'    => $formData['no_hp'] ?: null,
        ]);

        // Set flash & redirect ke login
        setFlash('success', 'Registrasi berhasil! Silakan login.');
        redirect(APP_URL . '/auth/login.php');
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Daftar Akun Anggota Perpustakaan">
    <title>Daftar Anggota — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body>
<div class="login-page">
    <div class="login-box" style="max-width:420px;">
        <div class="login-brand">
            <span class="login-brand-icon">📚</span>
            <div class="login-brand-name"><?= APP_NAME ?></div>
            <div class="login-brand-sub">Daftar Akun Anggota</div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $err): ?>
                    <div>• <?= e($err) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" data-validate id="registerForm">
            <div class="form-group">
                <label class="form-label" for="nama">Nama Lengkap</label>
                <input type="text" id="nama" name="nama" class="form-control"
                       placeholder="Nama lengkap Anda"
                       value="<?= e($formData['nama']) ?>"
                       required data-label="Nama lengkap">
            </div>

            <div class="form-group">
                <label class="form-label" for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control"
                       placeholder="Pilih username unik"
                       value="<?= e($formData['username']) ?>"
                       required data-label="Username" autocomplete="username">
            </div>

            <div class="form-group">
                <label class="form-label" for="no_hp">No. HP <span style="color:var(--text-muted)">(opsional)</span></label>
                <input type="text" id="no_hp" name="no_hp" class="form-control"
                       placeholder="08xx-xxxx-xxxx"
                       value="<?= e($formData['no_hp']) ?>">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control"
                           placeholder="Min. 6 karakter"
                           required data-label="Password" autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label class="form-label" for="konfirmasi">Konfirmasi</label>
                    <input type="password" id="konfirmasi" name="konfirmasi" class="form-control"
                           placeholder="Ulangi password"
                           required data-label="Konfirmasi password" autocomplete="new-password">
                </div>
            </div>

            <div class="form-actions-full" style="margin-top:1rem;">
                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
                    Daftar Sekarang
                </button>
            </div>
        </form>

        <p style="text-align:center;margin-top:1rem;font-size:0.8rem;color:var(--text-muted);">
            Sudah punya akun?
            <a href="<?= APP_URL ?>/auth/login.php" style="color:var(--text-secondary);text-decoration:underline;">
                Masuk di sini
            </a>
        </p>
    </div>
</div>
<script src="<?= APP_URL ?>/assets/js/app.js"></script>
</body>
</html>
