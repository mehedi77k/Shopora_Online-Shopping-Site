<?php
require_once __DIR__ . '/includes/functions.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('cart.php');
verify_csrf();
$quantities = $_POST['qty'] ?? [];
if (is_logged_in()) {
    $cartId = get_or_create_cart_id($pdo, (int)$_SESSION['user']['user_id']);
    $update = $pdo->prepare('UPDATE cart_items ci JOIN products p ON p.product_id=ci.product_id SET ci.quantity = LEAST(?, p.stock) WHERE ci.cart_id=? AND ci.product_id=?');
    $delete = $pdo->prepare('DELETE FROM cart_items WHERE cart_id=? AND product_id=?');
    foreach ($quantities as $productId => $qty) {
        $productId=(int)$productId; $qty=(int)$qty;
        if ($qty <= 0) $delete->execute([$cartId,$productId]); else $update->execute([$qty,$cartId,$productId]);
    }
} else {
    foreach ($quantities as $productId => $qty) {
        $productId=(int)$productId; $qty=(int)$qty;
        if ($qty <= 0) unset($_SESSION['guest_cart'][$productId]); else $_SESSION['guest_cart'][$productId]=$qty;
    }
}
flash('success','Cart updated.');
redirect('cart.php');
