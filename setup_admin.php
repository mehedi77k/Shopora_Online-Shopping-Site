<?php
require_once __DIR__ . '/includes/functions.php';

// Backward-compatible route for old bookmarks/setup instructions.
// The first privileged registration now happens on register.php and becomes
// the one Super Admin account.
if (!admin_exists($pdo)) {
    flash('success', 'Create the first administrator from the registration page. That first Admin becomes the Super Admin.');
    redirect('register.php');
}

if (is_super_admin()) {
    redirect('admin/admins.php');
}

if (is_admin()) {
    flash('error', 'Only the Super Admin can create or control accounts.');
    redirect('admin/index.php');
}

flash('error', 'A Super Admin already exists. Sign in from the normal login page.');
redirect('login.php');
