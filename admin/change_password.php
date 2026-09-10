<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$adminPageTitle = 'Change Password';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($currentPassword === '') $errors[] = 'Enter your current password.';
    if (strlen($newPassword) < 8) $errors[] = 'New password must contain at least 8 characters.';
    if ($newPassword !== $confirmPassword) $errors[] = 'New passwords do not match.';
    if ($currentPassword !== '' && $currentPassword === $newPassword) $errors[] = 'Choose a new password different from your current password.';

    if (!$errors) {
        $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ? AND role IN ('admin','super_admin') LIMIT 1");
        $stmt->execute([(int)$_SESSION['user']['user_id']]);
        $storedHash = $stmt->fetchColumn();

        if (!$storedHash || !password_verify($currentPassword, $storedHash)) {
            $errors[] = 'Current password is incorrect.';
        } else {
            $update = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ? AND role IN ('admin','super_admin')");
            $update->execute([
                password_hash($newPassword, PASSWORD_DEFAULT),
                (int)$_SESSION['user']['user_id'],
            ]);
            session_regenerate_id(true);
            flash('success', 'Your password has been changed successfully.');
            redirect('admin/change_password.php');
        }
    }
}

require __DIR__ . '/includes/admin_header.php';
?>
<div class="admin-toolbar">
    <div><span class="eyebrow">Account security</span><h1>Change password</h1></div>
</div>
<div class="admin-card password-card">
    <p class="muted">Enter your current password first, then choose the password you want to use for your administrator account.</p>
    <?php if ($errors): ?><div class="flash flash-error"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>
    <form method="post">
        <?= csrf_field() ?>
        <div class="form-group">
            <label>Current password</label>
            <input class="form-control" type="password" name="current_password" required autocomplete="current-password">
        </div>
        <div class="form-group" style="margin-top:14px">
            <label>New password</label>
            <input class="form-control" type="password" name="new_password" minlength="8" required autocomplete="new-password">
            <span class="form-note">Use at least 8 characters.</span>
        </div>
        <div class="form-group" style="margin-top:14px">
            <label>Confirm new password</label>
            <input class="form-control" type="password" name="confirm_password" minlength="8" required autocomplete="new-password">
        </div>
        <div class="form-actions"><button class="btn btn-primary">Update password</button></div>
    </form>
</div>
<?php require __DIR__ . '/includes/admin_footer.php'; ?>
