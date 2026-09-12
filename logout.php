<?php
require_once __DIR__ . '/includes/functions.php';
if (is_logged_in()) {
    $uid = (int)$_SESSION['user']['user_id'];
    log_user_activity($pdo, $uid, 'logout', 'Signed out of the account.', [], $uid);
}
unset($_SESSION['user']);
session_regenerate_id(true);
flash('success','You have been signed out.');
redirect('index.php');
