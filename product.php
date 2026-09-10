<?php
require_once __DIR__ . '/includes/functions.php';
$id = max(1, (int)($_GET['id'] ?? 0));
$stmt = $pdo->prepare("SELECT p.*, c.category_name FROM products p LEFT JOIN categories c ON c.category_id=p.category_id WHERE p.product_id=? LIMIT 1");
$stmt->execute([$id]);
$product = $stmt->fetch();
if (!$product) {
    http_response_code(404);
    $pageTitle = 'Product not found';
    require __DIR__ . '/includes/header.php';
    echo '<section class="section"><div class="container empty-state"><div class="icon">📦</div><h1>Product not found</h1><a class="btn btn-primary" href="' . url('shop.php') . '">Back to shop</a></div></section>';
    require __DIR__ . '/includes/footer.php';
    exit;
}
$pageTitle = $product['product_name'];

$reviewsStmt = $pdo->prepare("SELECT r.*, u.full_name FROM reviews r JOIN users u ON u.user_id=r.user_id WHERE r.product_id=? ORDER BY r.created_at DESC");
$reviewsStmt->execute([$id]);
$reviews = $reviewsStmt->fetchAll();
$avgRating = $reviews ? array_sum(array_column($reviews, 'rating')) / count($reviews) : 0;
$canReview = is_logged_in() && can_review_product($pdo, (int)$_SESSION['user']['user_id'], $id);

require __DIR__ . '/includes/header.php';
?>
<section class="section">
    <div class="container product-detail">
        <div class="product-detail-image"><img src="<?= e(product_image($product['image'])) ?>" alt="<?= e($product['product_name']) ?>"></div>
        <div class="product-detail-copy">
            <span class="eyebrow"><?= e($product['category_name'] ?? 'Product') ?></span>
            <h1><?= e($product['product_name']) ?></h1>
            <div class="stars"><?= $reviews ? str_repeat('★', (int)round($avgRating)) . str_repeat('☆', 5 - (int)round($avgRating)) . ' <span class="muted">(' . count($reviews) . ')</span>' : '☆☆☆☆☆ <span class="muted">No reviews yet</span>' ?></div>
            <?= dual_money($product['price']) ?>
            <span class="stock-status <?= (int)$product['stock'] <= 0 ? 'out' : '' ?>"><?= (int)$product['stock'] > 0 ? '● In stock · ' . (int)$product['stock'] . ' available' : '● Out of stock' ?></span>
            <p class="product-description"><?= nl2br(e($product['description'] ?: 'A quality product available from our online store.')) ?></p>
            <?php if ((int)$product['stock'] > 0 && $product['status'] === 'available'): ?>
                <form class="buy-row" method="post" action="<?= url('add_to_cart.php') ?>">
                    <?= csrf_field() ?><input type="hidden" name="product_id" value="<?= $id ?>">
                    <div class="qty-control" data-qty><button type="button" data-minus>−</button><input type="number" name="quantity" value="1" min="1" max="<?= (int)$product['stock'] ?>"><button type="button" data-plus>+</button></div>
                    <button class="btn btn-primary">Add to cart 🛒</button>
                    <a class="btn btn-ghost" href="<?= url('cart.php') ?>">View cart</a>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>
<section class="section-sm">
    <div class="container">
        <div class="section-heading"><div><span class="eyebrow">Customer feedback</span><h2>Reviews</h2></div></div>
        <?php if ($canReview): ?>
            <form class="form-card" style="margin:0 0 24px; width:100%;" method="post" action="<?= url('review_submit.php') ?>">
                <?= csrf_field() ?><input type="hidden" name="product_id" value="<?= $id ?>">
                <div class="form-grid"><div class="form-group"><label>Rating</label><select class="form-control" name="rating" required><option value="5">5 - Excellent</option><option value="4">4 - Very good</option><option value="3">3 - Good</option><option value="2">2 - Fair</option><option value="1">1 - Poor</option></select></div><div class="form-group"><label>Comment</label><input class="form-control" name="comment" maxlength="1000" placeholder="Share your experience"></div></div>
                <div class="form-actions"><button class="btn btn-primary">Submit review</button></div>
            </form>
        <?php endif; ?>
        <?php if ($reviews): ?><div class="review-list">
            <?php foreach ($reviews as $review): ?><div class="review-card"><div class="review-head"><strong><?= e($review['full_name']) ?></strong><span class="stars"><?= str_repeat('★',(int)$review['rating']) . str_repeat('☆',5-(int)$review['rating']) ?></span></div><p><?= e($review['comment'] ?: 'No written comment.') ?></p><small class="muted"><?= e(date('d M Y', strtotime($review['created_at']))) ?></small></div><?php endforeach; ?>
        </div><?php else: ?><div class="empty-state"><div class="icon">⭐</div><h3>Be the first to review</h3><p class="muted">Reviews can be submitted after a delivered purchase.</p></div><?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
