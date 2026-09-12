<?php
require_once __DIR__ . '/includes/functions.php';
$token = $_GET['token'] ?? '';
if (!$token || !hash_equals(csrf_token(), $token)) die('Invalid request.');
$productId = max(1,(int)($_GET['id'] ?? 0));
if (is_logged_in()) {
    $uid=(int)$_SESSION['user']['user_id'];
    $nameStmt=$pdo->prepare('SELECT product_name FROM products WHERE product_id=?'); $nameStmt->execute([$productId]); $productName=$nameStmt->fetchColumn() ?: ('Product #'.$productId);
    $cartId=get_or_create_cart_id($pdo,(int)$_SESSION['user']['user_id']);
    $stmt=$pdo->prepare('DELETE FROM cart_items WHERE cart_id=? AND product_id=?'); $stmt->execute([$cartId,$productId]);
    log_user_activity($pdo, $uid, 'cart_remove', 'Removed ' . $productName . ' from cart.', ['product_id'=>$productId], $uid);
} else {
    unset($_SESSION['guest_cart'][$productId]);
    realtime_notify('cart.updated', ['user_id'=>0, 'source'=>'guest']);
}
flash('success','Item removed from cart.');
redirect('cart.php');
