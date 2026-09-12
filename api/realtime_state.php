<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$loggedIn = is_logged_in() && refresh_authenticated_user($pdo);
$userId = $loggedIn ? (int)$_SESSION['user']['user_id'] : 0;
$role = $loggedIn ? current_role() : '';
$userSupport = $loggedIn ? support_counts_for_user($pdo, $userId) : ['total'=>0,'active'=>0,'unread'=>0];
$staffUnread = ($loggedIn && is_admin()) ? support_unread_for_staff($pdo) : 0;

try {
    $cart = cart_count($pdo);
} catch (Throwable $e) {
    $cart = 0;
}

echo json_encode([
    'ok' => true,
    'logged_in' => $loggedIn,
    'user_id' => $userId,
    'role' => $role,
    'cart_count' => $cart,
    'support' => $userSupport,
    'staff_support_unread' => $staffUnread,
    'server_time' => date(DATE_ATOM),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
