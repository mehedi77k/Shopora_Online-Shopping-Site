<?php
require_once __DIR__ . '/functions.php';
$pageTitle = $pageTitle ?? APP_NAME;
$categoriesForNav = fetch_categories($pdo);
$cartCount = cart_count($pdo);
$successMessage = get_flash('success');
$errorMessage = get_flash('error');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Modern online shopping management system">
    <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body>
<div class="top-strip">Free delivery on orders over <?= money(3000) ?> · Easy returns · Secure checkout</div>
<header class="site-header">
    <div class="container nav-shell">
        <a class="brand" href="<?= url('index.php') ?>">
            <span class="brand-mark">S</span>
            <span><?= e(APP_NAME) ?></span>
        </a>

        <button class="menu-toggle" type="button" aria-label="Toggle navigation" data-menu-toggle>☰</button>

        <nav class="main-nav" data-menu>
            <a href="<?= url('index.php') ?>">Home</a>
            <a href="<?= url('shop.php') ?>">Shop</a>
            <div class="nav-dropdown">
                <button type="button">Categories ▾</button>
                <div class="nav-dropdown-menu">
                    <?php foreach ($categoriesForNav as $cat): ?>
                        <a href="<?= url('shop.php?category=' . (int)$cat['category_id']) ?>"><?= e($cat['category_name']) ?></a>
                    <?php endforeach; ?>
                    <?php if (!$categoriesForNav): ?>
                        <span class="dropdown-empty">No categories yet</span>
                    <?php endif; ?>
                </div>
            </div>
            <a href="<?= url('about.php') ?>">About</a>
            <a href="<?= url('contact.php') ?>">Contact</a>
        </nav>

        <div class="nav-actions">
            <a class="icon-link cart-link" href="<?= url('cart.php') ?>" aria-label="Cart">
                <span>🛒</span>
                <span class="cart-badge"><?= $cartCount ?></span>
            </a>
            <?php if (is_logged_in()): ?>
                <div class="user-menu">
                    <button class="user-chip" type="button">
                        <span class="avatar"><?= e(strtoupper(substr($_SESSION['user']['full_name'], 0, 1))) ?></span>
                        <span class="hide-mobile"><?= e($_SESSION['user']['full_name']) ?></span>
                    </button>
                    <div class="user-menu-panel">
                        <?php if (is_admin()): ?>
                            <a href="<?= url('admin/index.php') ?>">Admin Dashboard</a>
                        <?php endif; ?>
                        <a href="<?= url('account.php') ?>">My Account</a>
                        <a href="<?= url('logout.php') ?>">Sign Out</a>
                    </div>
                </div>
            <?php else: ?>
                <a class="btn btn-ghost btn-small" href="<?= url('login.php') ?>">Sign in</a>
                <a class="btn btn-primary btn-small hide-mobile" href="<?= url('register.php') ?>">Create account</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<?php if ($successMessage || $errorMessage): ?>
    <div class="container flash-wrap">
        <?php if ($successMessage): ?><div class="flash flash-success"><?= e($successMessage) ?></div><?php endif; ?>
        <?php if ($errorMessage): ?><div class="flash flash-error"><?= e($errorMessage) ?></div><?php endif; ?>
    </div>
<?php endif; ?>

<main>
