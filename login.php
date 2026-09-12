<?php
require_once __DIR__ . '/includes/functions.php';
if (is_logged_in()) redirect(is_admin() ? 'admin/index.php' : 'account.php');
$pageTitle = 'Sign In';
$errors = [];
$email = '';
$hasAdmin = admin_exists($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim(strtolower($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if ($password === '') $errors[] = 'Enter your password.';

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $errors[] = 'Email or password is incorrect.';
        } elseif ($user['status'] !== 'active') {
            $errors[] = 'This account is inactive. Please contact support.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'user_id' => (int)$user['user_id'],
                'full_name' => $user['full_name'],
                'email' => $user['email'],
                'role' => $user['role'],
                'profile_image' => $user['profile_image'] ?? null,
            ];

            log_user_activity($pdo, (int)$user['user_id'], 'login', 'Signed in successfully.', ['role' => $user['role']], (int)$user['user_id']);

            if ($user['role'] === 'customer') {
                merge_guest_cart($pdo, (int)$user['user_id']);
            }

            flash('success', 'Welcome back, ' . $user['full_name'] . '.');
            $after = $_SESSION['redirect_after_login'] ?? null;
            unset($_SESSION['redirect_after_login']);

            if ($after && str_starts_with($after, BASE_URL . '/')) {
                header('Location: ' . $after);
                exit;
            }

            redirect(in_array($user['role'], ['admin', 'super_admin'], true) ? 'admin/index.php' : 'account.php');
        }
    }
}
require __DIR__ . '/includes/header.php';
?>
<section class="auth-section"><div class="container">
<form class="form-card" method="post">
    <?= csrf_field() ?>
    <span class="eyebrow">Welcome back</span>
    <h1>Sign in</h1>
    <p class="muted">Users, Admins and the Super Admin sign in from this same page using their own credentials.</p>
    <?php if (!$hasAdmin): ?>
        <p class="form-note form-note-block">No administrator has been created yet. The first Admin registration becomes the Super Admin.</p>
    <?php endif; ?>
    <?php if ($errors): ?><div class="flash flash-error"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>
    <div class="form-group"><label>Email address</label><input class="form-control" type="email" name="email" value="<?= e($email) ?>" required autocomplete="email"></div>
    <div class="form-group" style="margin-top:14px"><label>Password</label><input class="form-control" type="password" name="password" required autocomplete="current-password"></div>
    <div class="form-actions"><button class="btn btn-primary btn-block">Sign in</button></div>
    <div class="auth-switch">New to <?= e(APP_NAME) ?>? <a href="<?= url('register.php') ?>">Create an account</a></div>
</form>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
