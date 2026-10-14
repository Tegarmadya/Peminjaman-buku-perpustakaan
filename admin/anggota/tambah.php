<?php
require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/fungsi.php';

requireRole('admin');

$pageTitle  = 'Tambah Anggota';
$activePage = 'anggota';
$errors     = [];
$formData   = ['nama' => '', 'username' => '', 'no_hp' => '', 'role' => 'anggota'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'nama'     => trim($_POST['nama']     ?? ''),
        'username' => trim($_POST['username'] ?? ''),
        'no_hp'    => trim($_POST['no_hp']    ?? ''),
        'role'     => in_array($_POST['role'] ?? '', ['admin','anggota']) ? $_POST['role'] : 'anggota',
    ];
    $password   = $_POST['password']   ?? '';
    $konfirmasi = $_POST['konfirmasi'] ?? '';

    if ($formData['nama']     === '') $errors[] = 'Nama wajib diisi.';
    if ($formData['username'] === '') $errors[] = 'Username wajib diisi.';
    if ($password             === '') $errors[] = 'Password wajib diisi.';
    elseif (strlen($password)  < 6)  $errors[] = 'Password minimal 6 karakter.';
    elseif ($password !== $konfirmasi) $errors[] = 'Konfirmasi password tidak cocok.';

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :u LIMIT 1");
        $stmt->execute([':u' => $formData['username']]);
        if ($stmt->fetch()) $errors[] = 'Username sudah digunakan.';
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare("
            INSERT INTO users (nama, username, password, role, no_hp)
            VALUES (:nama, :username, :password, :role, :no_hp)
        ")->execute([
            ':nama'     => $formData['nama'],
            ':username' => $formData['username'],
            ':password' => $hash,
            ':role'     => $formData['role'],
            ':no_hp'    => $formData['no_hp'] ?: null,
        ]);
        setFlash('success', 'Anggota berhasil ditambahkan.');
        redirect(APP_URL . '/admin/anggota/index.php');
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card" style="max-width:560px;">
    <div class="card-title">Tambah Pengguna Baru</div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $err): ?><div>• <?= e($err) ?></div><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="" data-validate>
        <div class="form-group">
            <label class="form-label" for="nama">Nama Lengkap</label>
            <input type="text" id="nama" name="nama" class="form-control"
                   value="<?= e($formData['nama']) ?>" required data-label="Nama">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control"
                       value="<?= e($formData['username']) ?>" required data-label="Username">
            </div>
            <div class="form-group">
                <label class="form-label" for="role">Role</label>
                <select id="role" name="role" class="form-control">
                    <option value="anggota" <?= $formData['role'] === 'anggota' ? 'selected' : '' ?>>Anggota</option>
                    <option value="admin"   <?= $formData['role'] === 'admin'   ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="no_hp">No. HP <span style="color:var(--text-muted)">(opsional)</span></label>
            <input type="text" id="no_hp" name="no_hp" class="form-control"
                   value="<?= e($formData['no_hp']) ?>" placeholder="08xx-xxxx-xxxx">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control"
                       placeholder="Min. 6 karakter" required data-label="Password">
            </div>
            <div class="form-group">
                <label class="form-label" for="konfirmasi">Konfirmasi Password</label>
                <input type="password" id="konfirmasi" name="konfirmasi" class="form-control"
                       placeholder="Ulangi password" required data-label="Konfirmasi">
            </div>
        </div>

        <div class="form-actions">
            <a href="<?= APP_URL ?>/admin/anggota/index.php" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
