<?php
require_once __DIR__ . '/functions.php';
$pageTitle = $pageTitle ?? APP_NAME;
$categoriesForNav = fetch_categories($pdo);
$cartCount = cart_count($pdo);
$errorMessage = get_flash('error');
$supportNavCounts = is_logged_in() ? support_counts_for_user($pdo, (int)$_SESSION['user']['user_id']) : ['total'=>0,'active'=>0,'unread'=>0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="same-origin">
    <meta name="description" content="Modern online shopping management system">
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
    <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="<?= url('assets/images/shopora-logo.png') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body data-user-id="<?= is_logged_in() ? (int)$_SESSION['user']['user_id'] : 0 ?>" data-user-role="<?= e(current_role()) ?>" data-session-context="<?= e(session_context()) ?>">
<header class="site-header">
    <div class="container nav-shell">
        <a class="brand" href="<?= url('index.php') ?>">
            <img class="brand-logo" src="<?= url('assets/images/shopora-logo.png') ?>" alt="<?= e(APP_NAME) ?> logo">
            <span><?= e(APP_NAME) ?></span>
        </a>

        <button class="menu-toggle" type="button" aria-label="Toggle navigation" data-menu-toggle>☰</button>

        <nav class="main-nav" data-menu>
            <a href="<?= url('index.php') ?>">Home</a>
            <a href="<?= url('shop.php') ?>">Shop</a>

            <?php if (is_logged_in()): ?>
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
            <?php else: ?>
                <a href="<?= url('shop.php') ?>">Categories</a>
            <?php endif; ?>

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
                        <span class="avatar"><?php if (!empty($_SESSION['user']['profile_image'])): ?><img src="<?= e(profile_image($_SESSION['user']['profile_image'])) ?>" alt=""><?php else: ?><?= e(strtoupper(substr($_SESSION['user']['full_name'], 0, 1))) ?><?php endif; ?></span>
                        <span class="hide-mobile"><?= e($_SESSION['user']['full_name']) ?></span>
                    </button>
                    <div class="user-menu-panel">
                        <?php if (is_admin()): ?>
                            <a href="<?= url('admin/index.php') ?>">Admin Dashboard</a>
                        <?php endif; ?>
                        <a href="<?= url('account.php') ?>">My Account</a>
                        <a href="<?= url('support.php') ?>">My Support Messages <span class="nav-live-count" data-support-user-badge <?= $supportNavCounts['unread'] ? '' : 'hidden' ?>><?= (int)$supportNavCounts['unread'] ?></span></a>
                        <a href="<?= url('logout.php') ?>">Sign Out This Account</a>
                    </div>
                </div>
            <?php else: ?>
                <a class="btn btn-ghost btn-small" href="<?= url('login.php') ?>">Sign in</a>
                <a class="btn btn-primary btn-small hide-mobile" href="<?= url('register.php') ?>">Create account</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<?php if ($errorMessage): ?>
    <div class="container flash-wrap">
        <div class="flash flash-error"><?= e($errorMessage) ?></div>
    </div>
<?php endif; ?>

<main>
