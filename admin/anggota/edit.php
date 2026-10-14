<?php
require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/fungsi.php';

requireRole('admin');

$pageTitle  = 'Edit Anggota';
$activePage = 'anggota';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) { setFlash('error', 'ID tidak valid.'); redirect(APP_URL . '/admin/anggota/index.php'); }

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$user = $stmt->fetch();
if (!$user) { setFlash('error', 'Anggota tidak ditemukan.'); redirect(APP_URL . '/admin/anggota/index.php'); }

$errors   = [];
$formData = $user;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'id'       => $id,
        'nama'     => trim($_POST['nama']     ?? ''),
        'username' => trim($_POST['username'] ?? ''),
        'no_hp'    => trim($_POST['no_hp']    ?? ''),
        'role'     => in_array($_POST['role'] ?? '', ['admin','anggota']) ? $_POST['role'] : 'anggota',
    ];
    $passwordBaru = $_POST['password_baru'] ?? '';
    $konfirmasi   = $_POST['konfirmasi']    ?? '';

    if ($formData['nama']     === '') $errors[] = 'Nama wajib diisi.';
    if ($formData['username'] === '') $errors[] = 'Username wajib diisi.';

    // Validasi password baru (opsional)
    if ($passwordBaru !== '') {
        if (strlen($passwordBaru) < 6) $errors[] = 'Password baru minimal 6 karakter.';
        elseif ($passwordBaru !== $konfirmasi) $errors[] = 'Konfirmasi password tidak cocok.';
    }

    // Cek username unik
    if (empty($errors)) {
        $stmt2 = $pdo->prepare("SELECT id FROM users WHERE username = :u AND id != :id LIMIT 1");
        $stmt2->execute([':u' => $formData['username'], ':id' => $id]);
        if ($stmt2->fetch()) $errors[] = 'Username sudah digunakan oleh pengguna lain.';
    }

    if (empty($errors)) {
        if ($passwordBaru !== '') {
            $hash = password_hash($passwordBaru, PASSWORD_DEFAULT);
            $pdo->prepare("
                UPDATE users SET nama=:nama, username=:username, role=:role, no_hp=:no_hp, password=:pw
                WHERE id=:id
            ")->execute([
                ':nama' => $formData['nama'], ':username' => $formData['username'],
                ':role' => $formData['role'], ':no_hp'    => $formData['no_hp'] ?: null,
                ':pw'   => $hash,             ':id'       => $id,
            ]);
        } else {
            $pdo->prepare("
                UPDATE users SET nama=:nama, username=:username, role=:role, no_hp=:no_hp
                WHERE id=:id
            ")->execute([
                ':nama' => $formData['nama'], ':username' => $formData['username'],
                ':role' => $formData['role'], ':no_hp'    => $formData['no_hp'] ?: null,
                ':id'   => $id,
            ]);
        }
        setFlash('success', 'Data anggota berhasil diperbarui.');
        redirect(APP_URL . '/admin/anggota/index.php');
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card" style="max-width:560px;">
    <div class="card-title">Edit Anggota: <?= e($user['nama']) ?></div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $err): ?><div>• <?= e($err) ?></div><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="" data-validate>
        <div class="form-group">
            <label class="form-label" for="nama">Nama Lengkap</label>
            <input type="text" id="nama" name="nama" class="form-control"
                   value="<?= e($formData['nama']) ?>" required>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control"
                       value="<?= e($formData['username']) ?>" required>
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
            <label class="form-label" for="no_hp">No. HP</label>
            <input type="text" id="no_hp" name="no_hp" class="form-control"
                   value="<?= e($formData['no_hp'] ?? '') ?>">
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="password_baru">Password Baru <span style="color:var(--text-muted)">(kosongkan = tidak diubah)</span></label>
                <input type="password" id="password_baru" name="password_baru" class="form-control"
                       placeholder="Isi jika ingin mengubah">
            </div>
            <div class="form-group">
                <label class="form-label" for="konfirmasi">Konfirmasi Password</label>
                <input type="password" id="konfirmasi" name="konfirmasi" class="form-control"
                       placeholder="Ulangi password baru">
            </div>
        </div>

        <div class="form-actions">
            <a href="<?= APP_URL ?>/admin/anggota/index.php" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
