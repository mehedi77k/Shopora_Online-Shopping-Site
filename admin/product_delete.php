<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/products.php');
}

verify_csrf();
$id = max(1, (int)($_POST['id'] ?? 0));

$stmt = $pdo->prepare('SELECT product_name,image FROM products WHERE product_id = ?');
$stmt->execute([$id]);
$oldProduct = $stmt->fetch();
$oldImage = $oldProduct['image'] ?? null;

$stmt = $pdo->prepare('DELETE FROM products WHERE product_id = ?');
$stmt->execute([$id]);

if ($oldImage) {
    delete_uploaded_image($oldImage);
}
if ($oldProduct) { log_user_activity($pdo,(int)$_SESSION['user']['user_id'],'product_deleted','Deleted product #' . $id . ': ' . $oldProduct['product_name'],['product_id'=>$id],(int)$_SESSION['user']['user_id']); }

flash('success', 'Product deleted.');
redirect('admin/products.php');
