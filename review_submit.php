<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
if ($_SERVER['REQUEST_METHOD']!=='POST') redirect('shop.php');
verify_csrf();
$productId=max(1,(int)($_POST['product_id'] ?? 0)); $rating=(int)($_POST['rating'] ?? 0); $comment=trim($_POST['comment'] ?? '');
if ($rating<1 || $rating>5) { flash('error','Choose a rating from 1 to 5.'); redirect('product.php?id='.$productId); }
if (!can_review_product($pdo,(int)$_SESSION['user']['user_id'],$productId)) { flash('error','A delivered purchase is required before reviewing this product.'); redirect('product.php?id='.$productId); }
$stmt=$pdo->prepare("INSERT INTO reviews (user_id,product_id,rating,comment) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE rating=VALUES(rating),comment=VALUES(comment),created_at=CURRENT_TIMESTAMP");
$uid=(int)$_SESSION['user']['user_id'];
$stmt->execute([$uid,$productId,$rating,$comment]);
log_user_activity($pdo, $uid, 'review_saved', 'Saved a ' . $rating . '/5 product review.', ['product_id'=>$productId,'rating'=>$rating], $uid);
flash('success','Your review has been saved.'); redirect('product.php?id='.$productId);
