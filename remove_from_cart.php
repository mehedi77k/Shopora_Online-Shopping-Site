<?php
require_once __DIR__ . '/includes/functions.php';
$token = $_GET['token'] ?? '';
if (!$token || !hash_equals(csrf_token(), $token)) die('Invalid request.');
$productId = max(1,(int)($_GET['id'] ?? 0));
if (is_logged_in()) {
    $cartId=get_or_create_cart_id($pdo,(int)$_SESSION['user']['user_id']);
    $stmt=$pdo->prepare('DELETE FROM cart_items WHERE cart_id=? AND product_id=?'); $stmt->execute([$cartId,$productId]);
} else unset($_SESSION['guest_cart'][$productId]);
flash('success','Item removed from cart.');
redirect('cart.php');
