<?php
require_once __DIR__ . '/includes/functions.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('shop.php');
verify_csrf();
$productId = max(1, (int)($_POST['product_id'] ?? 0));
$quantity = max(1, (int)($_POST['quantity'] ?? 1));
$stmt = $pdo->prepare("SELECT product_id, stock, status FROM products WHERE product_id=?");
$stmt->execute([$productId]);
$product = $stmt->fetch();
if (!$product || $product['status'] !== 'available' || (int)$product['stock'] < 1) {
    flash('error', 'This product is currently unavailable.');
    redirect('shop.php');
}
$quantity = min($quantity, (int)$product['stock']);
add_cart_item($pdo, $productId, $quantity);
flash('success', 'Product added to your cart.');
$back = $_SERVER['HTTP_REFERER'] ?? url('cart.php');
header('Location: ' . $back);
exit;
