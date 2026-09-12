<?php
require_once __DIR__ . '/../../includes/functions.php';
require_admin();
$adminPageTitle = $adminPageTitle ?? 'Admin';
$current = basename($_SERVER['PHP_SELF']);
$supportUnread = support_unread_for_staff($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="same-origin">
    <meta name="shopora-base-url" content="<?= e(rtrim(BASE_URL, '/')) ?>">
    <meta name="shopora-session-context" content="<?= e(session_context()) ?>">
    <script>
        (function () {
            var ctx = <?= json_encode(session_context(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            var prefix = <?= json_encode(SESSION_CONTEXT_WINDOW_PREFIX, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            var expected = prefix + ctx;

            // A ctx belongs to one browser tab/workspace. If an authenticated ctx
            // URL is pasted/opened in another fresh tab, do not attach that tab to
            // the existing account. Start a new guest workspace from the same path.
            if (window.name !== expected) {
                try { document.documentElement.style.visibility = 'hidden'; } catch (e) {}
                var clean = new URL(window.location.href);
                clean.searchParams.delete('ctx');
                window.location.replace(clean.pathname + clean.search + clean.hash);
                return;
            }

            try { sessionStorage.setItem('shopora_session_context', ctx); } catch (e) {}
        })();
    </script>
    <meta name="shopora-realtime-enabled" content="<?= REALTIME_ENABLED ? '1' : '0' ?>">
    <meta name="shopora-ws-port" content="<?= (int)REALTIME_WS_PORT ?>">
    <title><?= e($adminPageTitle) ?> | <?= e(APP_NAME) ?> Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="<?= url('assets/images/shopora-logo.png') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body class="admin-body" data-user-id="<?= (int)$_SESSION['user']['user_id'] ?>" data-user-role="<?= e(current_role()) ?>" data-session-context="<?= e(session_context()) ?>">
<div class="admin-shell">
    <aside class="admin-sidebar">
        <a class="brand" href="<?= url('admin/index.php') ?>"><img class="brand-logo brand-logo-admin" src="<?= url('assets/images/shopora-logo.png') ?>" alt="<?= e(APP_NAME) ?> logo"><span><?= e(APP_NAME) ?> Admin</span></a>
        <nav class="admin-nav">
            <a class="<?= $current==='index.php'?'active':'' ?>" href="<?= url('admin/index.php') ?>">▦ Dashboard</a>
            <a class="<?= in_array($current,['products.php','product_form.php'],true)?'active':'' ?>" href="<?= url('admin/products.php') ?>">□ Products</a>
            <a class="<?= $current==='categories.php'?'active':'' ?>" href="<?= url('admin/categories.php') ?>">◇ Categories</a>
            <a class="<?= in_array($current,['orders.php','order_view.php'],true)?'active':'' ?>" href="<?= url('admin/orders.php') ?>">▤ Orders</a>
            <a class="<?= in_array($current,['support.php','support_view.php'],true)?'active':'' ?>" href="<?= url('admin/support.php') ?>">✉ Support <span class="nav-live-count" data-support-staff-badge <?= $supportUnread ? '' : 'hidden' ?>><?= (int)$supportUnread ?></span></a>
            <a class="<?= $current==='user_history.php'?'active':'' ?>" href="<?= url('admin/user_history.php') ?>">⌕ User History</a>
            <a href="<?= url('account.php#profile-settings') ?>">◉ My Profile</a>
            <?php if (is_super_admin()): ?>
                <a class="<?= $current==='users.php'?'active':'' ?>" href="<?= url('admin/users.php') ?>">◎ Customers</a>
                <a class="<?= $current==='admins.php'?'active':'' ?>" href="<?= url('admin/admins.php') ?>">♙ Administrators</a>
                <a class="<?= $current==='currency_settings.php'?'active':'' ?>" href="<?= url('admin/currency_settings.php') ?>">$ Exchange Rate</a>
            <?php endif; ?>
            <a class="<?= $current==='change_password.php'?'active':'' ?>" href="<?= url('admin/change_password.php') ?>">⚿ Change Password</a>
            <a href="<?= url('index.php') ?>">← View Store</a>
            <a href="<?= url('logout.php') ?>">↪ Sign Out This Account</a>
        </nav>
    </aside>
    <div class="admin-main">
        <header class="admin-topbar"><div><strong><?= e($adminPageTitle) ?></strong></div><a class="user-chip admin-user-chip-link" href="<?= url('account.php#profile-settings') ?>"><span class="avatar"><?php if (!empty($_SESSION['user']['profile_image'])): ?><img src="<?= e(profile_image($_SESSION['user']['profile_image'])) ?>" alt=""><?php else: ?><?= e(strtoupper(substr($_SESSION['user']['full_name'],0,1))) ?><?php endif; ?></span><span><?= e($_SESSION['user']['full_name']) ?><small style="display:block;opacity:.7;font-size:11px"><?= is_super_admin() ? 'Super Admin' : 'Admin' ?></small></span></a></header>
        <main class="admin-content">
            <?php if($msg=get_flash('error')): ?><div class="flash flash-error" style="margin-bottom:18px"><?= e($msg) ?></div><?php endif; ?>
