<?php
require_once __DIR__ . '/../../includes/functions.php';
require_admin();
$adminPageTitle = $adminPageTitle ?? 'Admin';
$current = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($adminPageTitle) ?> | <?= e(APP_NAME) ?> Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body class="admin-body">
<div class="admin-shell">
    <aside class="admin-sidebar">
        <a class="brand" href="<?= url('admin/index.php') ?>"><span class="brand-mark">S</span><span><?= e(APP_NAME) ?> Admin</span></a>
        <nav class="admin-nav">
            <a class="<?= $current==='index.php'?'active':'' ?>" href="<?= url('admin/index.php') ?>">▦ Dashboard</a>
            <a class="<?= in_array($current,['products.php','product_form.php'],true)?'active':'' ?>" href="<?= url('admin/products.php') ?>">□ Products</a>
            <a class="<?= $current==='categories.php'?'active':'' ?>" href="<?= url('admin/categories.php') ?>">◇ Categories</a>
            <a class="<?= in_array($current,['orders.php','order_view.php'],true)?'active':'' ?>" href="<?= url('admin/orders.php') ?>">▤ Orders</a>
            <?php if (is_super_admin()): ?>
                <a class="<?= $current==='users.php'?'active':'' ?>" href="<?= url('admin/users.php') ?>">◎ Customers</a>
                <a class="<?= $current==='admins.php'?'active':'' ?>" href="<?= url('admin/admins.php') ?>">♙ Administrators</a>
                <a class="<?= $current==='currency_settings.php'?'active':'' ?>" href="<?= url('admin/currency_settings.php') ?>">€ Currency Settings</a>
            <?php endif; ?>
            <a class="<?= $current==='change_password.php'?'active':'' ?>" href="<?= url('admin/change_password.php') ?>">⚿ Change Password</a>
            <a href="<?= url('index.php') ?>">← View Store</a>
            <a href="<?= url('logout.php') ?>">↪ Sign Out</a>
        </nav>
    </aside>
    <div class="admin-main">
        <header class="admin-topbar"><div><strong><?= e($adminPageTitle) ?></strong></div><div class="user-chip"><span class="avatar"><?= e(strtoupper(substr($_SESSION['user']['full_name'],0,1))) ?></span><span><?= e($_SESSION['user']['full_name']) ?><small style="display:block;opacity:.7;font-size:11px"><?= is_super_admin() ? 'Super Admin' : 'Admin' ?></small></span></div></header>
        <main class="admin-content">
            <?php if($msg=get_flash('success')): ?><div class="flash flash-success" style="margin-bottom:18px"><?= e($msg) ?></div><?php endif; ?>
            <?php if($msg=get_flash('error')): ?><div class="flash flash-error" style="margin-bottom:18px"><?= e($msg) ?></div><?php endif; ?>
