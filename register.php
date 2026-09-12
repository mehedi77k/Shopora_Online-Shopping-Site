<?php
require_once __DIR__ . '/includes/functions.php';
if (is_logged_in()) redirect(is_admin() ? 'admin/index.php' : 'account.php');

$pageTitle = 'Create Account';
$errors = [];
$name = $email = $phone = '';
$firstAdminAvailable = !admin_exists($pdo);
$selectedRole = $firstAdminAvailable ? ($_POST['account_type'] ?? 'customer') : 'customer';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    // Public privileged registration is available exactly once.
    // The first administrator is stored as the Super Admin.
    $firstAdminAvailable = !admin_exists($pdo);

    $name = trim($_POST['full_name'] ?? '');
    $email = trim(strtolower($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $requestedRole = $_POST['account_type'] ?? 'customer';
    $selectedRole = ($firstAdminAvailable && $requestedRole === 'admin') ? 'admin' : 'customer';

    if (mb_strlen($name) < 2) $errors[] = 'Enter your full name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if ($phone !== '' && mb_strlen($phone) > 20) $errors[] = 'Phone number is too long.';

    $minimumPasswordLength = $selectedRole === 'admin' ? 8 : 6;
    if (strlen($password) < $minimumPasswordLength) {
        $errors[] = $selectedRole === 'admin'
            ? 'Super Admin password must contain at least 8 characters.'
            : 'Password must contain at least 6 characters.';
    }
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (!$errors) {
        $check = $pdo->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
        $check->execute([$email]);

        if ($check->fetch()) {
            $errors[] = 'An account already exists with this email.';
        } else {
            // Safety re-check: once a privileged account exists, public signup
            // can never create another admin/super-admin account.
            if ($selectedRole === 'admin' && admin_exists($pdo)) {
                $errors[] = 'The Super Admin has already been created. Public signup can now create user accounts only.';
                $selectedRole = 'customer';
                $firstAdminAvailable = false;
            } else {
                $role = $selectedRole === 'admin' ? 'super_admin' : 'customer';
                $stmt = $pdo->prepare(
                    "INSERT INTO users (full_name, email, phone, joining_date, password, role, status) VALUES (?, ?, ?, CURDATE(), ?, ?, 'active')"
                );
                $stmt->execute([
                    $name,
                    $email,
                    $phone !== '' ? $phone : null,
                    password_hash($password, PASSWORD_DEFAULT),
                    $role,
                ]);
                $userId = (int)$pdo->lastInsertId();

                if ($role === 'super_admin') {
                    log_user_activity($pdo, $userId, 'account_created', 'Super Admin account created.', ['role' => $role], $userId);
                    flash('success', 'Super Admin account created successfully. Sign in with your credentials.');
                    redirect('login.php');
                }

                session_regenerate_id(true);
                $_SESSION['user'] = [
                    'user_id' => $userId,
                    'full_name' => $name,
                    'email' => $email,
                    'role' => 'customer',
                    'profile_image' => null,
                ];
                merge_guest_cart($pdo, $userId);
                log_user_activity($pdo, $userId, 'account_created', 'Customer account created.', ['role' => 'customer'], $userId);
                flash('success', 'Your account has been created.');
                redirect('account.php');
            }
        }
    }
}

require __DIR__ . '/includes/header.php';
?>
<section class="auth-section"><div class="container">
<form class="form-card" method="post">
    <?= csrf_field() ?>
    <span class="eyebrow"><?= $firstAdminAvailable ? 'First-time setup' : 'Join us' ?></span>
    <h1><?= $firstAdminAvailable ? 'Create your first account' : 'Create user account' ?></h1>
    <p class="muted">
        <?= $firstAdminAvailable
            ? 'No administrator exists yet. The first Admin registration becomes the Super Admin, or you may register as a User.'
            : 'Public signup is available for users only. Admin and Super Admin accounts sign in from the normal login page.' ?>
    </p>

    <?php if ($errors): ?><div class="flash flash-error"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>

    <?php if ($firstAdminAvailable): ?>
        <div class="form-group" style="margin-top:18px">
            <label>Register as</label>
            <div class="role-choice-grid">
                <label class="role-choice">
                    <input type="radio" name="account_type" value="customer" <?= $selectedRole === 'customer' ? 'checked' : '' ?>>
                    <span class="role-choice-title">User</span>
                    <span class="role-choice-text">Shop, manage cart, checkout and view orders.</span>
                </label>
                <label class="role-choice">
                    <input type="radio" name="account_type" value="admin" <?= $selectedRole === 'admin' ? 'checked' : '' ?>>
                    <span class="role-choice-title">Admin (Super Admin)</span>
                    <span class="role-choice-text">One-time first administrator with full account-control permission.</span>
                </label>
            </div>
        </div>
    <?php else: ?>
        <input type="hidden" name="account_type" value="customer">
    <?php endif; ?>

    <div class="form-group" style="margin-top:18px">
        <label>Full name</label>
        <input class="form-control" name="full_name" value="<?= e($name) ?>" required autocomplete="name">
    </div>
    <div class="form-grid" style="margin-top:14px">
        <div class="form-group">
            <label>Email</label>
            <input class="form-control" type="email" name="email" value="<?= e($email) ?>" required autocomplete="email">
        </div>
        <div class="form-group">
            <label>Phone</label>
            <input class="form-control" name="phone" value="<?= e($phone) ?>" placeholder="01XXXXXXXXX" autocomplete="tel">
        </div>
    </div>
    <div class="form-grid" style="margin-top:14px">
        <div class="form-group">
            <label>Password</label>
            <input class="form-control" type="password" name="password" minlength="6" required autocomplete="new-password">
            <span class="form-note">Super Admin: minimum 8 characters. User: minimum 6 characters.</span>
        </div>
        <div class="form-group">
            <label>Confirm password</label>
            <input class="form-control" type="password" name="confirm_password" minlength="6" required autocomplete="new-password">
        </div>
    </div>
    <div class="form-actions"><button class="btn btn-primary btn-block">Create account</button></div>
    <div class="auth-switch">Already registered? <a href="<?= url('login.php') ?>">Sign in</a></div>
</form>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
