<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/products.php');
}

verify_csrf();
$id = max(1, (int)($_POST['id'] ?? 0));

$stmt = $pdo->prepare('SELECT image FROM products WHERE product_id = ?');
$stmt->execute([$id]);
$oldImage = $stmt->fetchColumn() ?: null;

$stmt = $pdo->prepare('DELETE FROM products WHERE product_id = ?');
$stmt->execute([$id]);

if ($oldImage) {
    delete_uploaded_image($oldImage);
}

flash('success', 'Product deleted.');
redirect('admin/products.php');
