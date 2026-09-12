<?php
require_once __DIR__ . '/../includes/functions.php';
require_super_admin();

$adminPageTitle = 'Administrators';
$errors = [];
$name = $email = $phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? 'create';

    if ($action === 'toggle_status') {
        $id = max(1, (int)($_POST['id'] ?? 0));
        $status = $_POST['status'] ?? '';

        if (in_array($status, ['active', 'inactive'], true)) {
            // Only normal Admin accounts can be toggled here. The Super Admin
            // cannot accidentally deactivate or demote their own account.
            $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE user_id = ? AND role = 'admin'");
            $stmt->execute([$status, $id]);
            if ($stmt->rowCount()) { log_user_activity($pdo, $id, 'account_status_changed', 'Administrator account status changed to ' . $status . '.', ['status'=>$status], (int)$_SESSION['user']['user_id']); }
            flash('success', 'Administrator status updated.');
        }
        redirect('admin/admins.php');
    }

    $name = trim($_POST['full_name'] ?? '');
    $email = trim(strtolower($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (text_length($name) < 2) $errors[] = 'Enter the administrator full name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid administrator email address.';
    if ($phone !== '' && text_length($phone) > 30) $errors[] = 'Phone number is too long.';
    if (strlen($password) < 8) $errors[] = 'Temporary Admin password must contain at least 8 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (!$errors) {
        $check = $pdo->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
        $check->execute([$email]);
        if ($check->fetch()) {
            $errors[] = 'An account already exists with this email address.';
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO users (full_name, email, phone, joining_date, password, role, status) VALUES (?, ?, ?, CURDATE(), ?, 'admin', 'active')"
            );
            $stmt->execute([
                $name,
                $email,
                $phone !== '' ? $phone : null,
                password_hash($password, PASSWORD_DEFAULT),
            ]);
            $newAdminId=(int)$pdo->lastInsertId();
            log_user_activity($pdo,$newAdminId,'account_created','Admin account created by Super Admin.',['role'=>'admin'],(int)$_SESSION['user']['user_id']);

            flash('success', 'New Admin created. They can sign in and change their own password.');
            redirect('admin/admins.php');
        }
    }
}

$superAdminStmt = $pdo->query(
    "SELECT user_id, full_name, email, phone, profile_image, status, created_at FROM users WHERE role = 'super_admin' ORDER BY user_id ASC LIMIT 1"
);
$superAdmin = $superAdminStmt->fetch();

$admins = $pdo->query(
    "SELECT user_id, full_name, email, phone, profile_image, status, created_at
     FROM users
     WHERE role = 'admin'
     ORDER BY user_id DESC"
)->fetchAll();

require __DIR__ . '/includes/admin_header.php';
?>
<div class="admin-toolbar">
    <div><span class="eyebrow">Super Admin only</span><h1>Administrator accounts</h1></div>
    <span class="muted"><?= count($admins) ?> normal Admin<?= count($admins) === 1 ? '' : 's' ?></span>
</div>

<div class="admin-grid-2 admin-grid-admins">
    <div class="admin-card">
        <h2 style="margin-top:0">Create normal Admin</h2>
        <p class="muted">Only the Super Admin can create Admin accounts. A normal Admin cannot create or control any account.</p>

        <?php if ($errors): ?><div class="flash flash-error"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>

        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create">
            <div class="form-group">
                <label>Full name</label>
                <input class="form-control" name="full_name" value="<?= e($name) ?>" required autocomplete="name">
            </div>
            <div class="form-grid" style="margin-top:14px">
                <div class="form-group">
                    <label>Email address</label>
                    <input class="form-control" type="email" name="email" value="<?= e($email) ?>" required autocomplete="email">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input class="form-control" name="phone" value="<?= e($phone) ?>" placeholder="01XXXXXXXXX" autocomplete="tel">
                </div>
            </div>
            <div class="form-grid" style="margin-top:14px">
                <div class="form-group">
                    <label>Temporary password</label>
                    <input class="form-control" type="password" name="password" minlength="8" required autocomplete="new-password">
                    <span class="form-note">Minimum 8 characters.</span>
                </div>
                <div class="form-group">
                    <label>Confirm password</label>
                    <input class="form-control" type="password" name="confirm_password" minlength="8" required autocomplete="new-password">
                </div>
            </div>
            <div class="form-actions"><button class="btn btn-primary">Create Admin</button></div>
        </form>
    </div>

    <div class="admin-card">
        <h2 style="margin-top:0">Super Admin</h2>
        <?php if ($superAdmin): ?>
            <div class="admin-list-item">
                <div class="avatar"><?php if(!empty($superAdmin['profile_image'])): ?><img src="<?= e(profile_image($superAdmin['profile_image'])) ?>" alt=""><?php else: ?><?= e(strtoupper(substr($superAdmin['full_name'], 0, 1))) ?><?php endif; ?></div>
                <div>
                    <strong><?= e($superAdmin['full_name']) ?> (You)</strong>
                    <div class="muted"><?= e($superAdmin['email']) ?></div>
                    <div class="muted">Full account-control permission</div>
                </div>
            </div>
        <?php endif; ?>

        <h2>Normal Admin accounts</h2>
        <div class="admin-list">
            <?php foreach ($admins as $admin): ?>
                <div class="admin-list-item" style="align-items:center">
                    <div class="avatar"><?php if(!empty($admin['profile_image'])): ?><img src="<?= e(profile_image($admin['profile_image'])) ?>" alt=""><?php else: ?><?= e(strtoupper(substr($admin['full_name'], 0, 1))) ?><?php endif; ?></div>
                    <div style="flex:1">
                        <strong><?= e($admin['full_name']) ?></strong>
                        <div class="muted"><?= e($admin['email']) ?></div>
                        <div class="muted"><?= e($admin['phone'] ?: 'No phone') ?> · <?= e(ucfirst($admin['status'])) ?></div>
                    </div>
                    <form method="post" class="inline-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="toggle_status">
                        <input type="hidden" name="id" value="<?= (int)$admin['user_id'] ?>">
                        <input type="hidden" name="status" value="<?= $admin['status'] === 'active' ? 'inactive' : 'active' ?>">
                        <button class="btn btn-ghost btn-small"><?= $admin['status'] === 'active' ? 'Deactivate' : 'Activate' ?></button>
                    </form>
                </div>
            <?php endforeach; ?>
            <?php if (!$admins): ?><p class="muted">No normal Admin accounts yet.</p><?php endif; ?>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/admin_footer.php'; ?>
