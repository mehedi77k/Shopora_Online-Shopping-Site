<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Modern Online Shopping';

$featured = $pdo->query(
    "SELECT p.*, c.category_name
     FROM products p
     LEFT JOIN categories c ON c.category_id = p.category_id
     WHERE p.status = 'available'
     ORDER BY p.product_id DESC
     LIMIT 8"
)->fetchAll();

$categoryCards = $pdo->query(
    "SELECT c.*, COUNT(p.product_id) AS product_count
     FROM categories c
     LEFT JOIN products p ON p.category_id = c.category_id AND p.status = 'available'
     GROUP BY c.category_id
     ORDER BY product_count DESC, c.category_name
     LIMIT 5"
)->fetchAll();

require __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <div class="container hero-grid">
        <div class="hero-copy">
            <span class="eyebrow">Simple · Fresh · Reliable</span>
            <h1>Everything you love, <span>one click away.</span></h1>
            <p>Discover quality products in a clean and easy shopping experience. Browse, add to cart, checkout and track your orders from one place.</p>
            <div class="hero-actions">
                <a class="btn btn-primary" href="<?= url('shop.php') ?>">Start shopping →</a>
                <a class="btn btn-ghost" href="#featured">Explore featured</a>
            </div>
            <div class="hero-meta">
                <span>✓ Secure account</span>
                <span>✓ Fast checkout</span>
                <span>✓ Order tracking</span>
            </div>
        </div>
        <div class="hero-card" aria-hidden="true">
            <div class="hero-orb"></div>
            <div class="hero-bag"></div>
            <div class="floating-card float-one">Fast delivery<small>Across Bangladesh</small></div>
            <div class="floating-card float-two">Fresh arrivals<small>Updated regularly</small></div>
        </div>
    </div>
</section>

<section class="section-sm">
    <div class="container">
        <div class="section-heading">
            <div><span class="eyebrow">Browse</span><h2>Shop by category</h2></div>
            <a class="btn btn-ghost btn-small" href="<?= url('shop.php') ?>">View all products</a>
        </div>
        <div class="category-grid">
            <?php $icons = ['⌚','👕','📱','💻','🏠']; ?>
            <?php foreach ($categoryCards as $i => $cat): ?>
                <a class="category-card <?= !empty($cat['image']) ? 'has-image' : '' ?>" href="<?= url('shop.php?category=' . (int)$cat['category_id']) ?>">
                    <?php if (!empty($cat['image'])): ?>
                        <div class="category-media">
                            <img src="<?= e(category_image($cat['image'])) ?>" alt="<?= e($cat['category_name']) ?>">
                        </div>
                    <?php else: ?>
                        <div class="category-icon"><?= $icons[$i % count($icons)] ?></div>
                    <?php endif; ?>
                    <div class="category-card-copy">
                        <h3><?= e($cat['category_name']) ?></h3>
                        <p><?= (int)$cat['product_count'] ?> products</p>
                    </div>
                </a>
            <?php endforeach; ?>
            <?php if (!$categoryCards): ?>
                <div class="empty-state" style="grid-column:1/-1"><div class="icon">🛍️</div><h3>No categories yet</h3><p class="muted">Add categories from the admin panel.</p></div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="section" id="featured">
    <div class="container">
        <div class="section-heading">
            <div><span class="eyebrow">Picked for you</span><h2>Featured products</h2><p>Popular choices and the latest additions to the shop.</p></div>
            <a class="btn btn-ghost btn-small" href="<?= url('shop.php') ?>">See all →</a>
        </div>
        <?php if ($featured): ?>
            <div class="product-grid">
                <?php foreach ($featured as $product): ?>
                    <article class="product-card">
                        <a href="<?= url('product.php?id=' . (int)$product['product_id']) ?>" class="product-image-wrap">
                            <img src="<?= e(product_image($product['image'])) ?>" alt="<?= e($product['product_name']) ?>">
                            <span class="stock-pill <?= (int)$product['stock'] <= 0 ? 'out' : '' ?>"><?= (int)$product['stock'] > 0 ? 'In stock' : 'Out of stock' ?></span>
                        </a>
                        <div class="product-body">
                            <div class="product-category"><?= e($product['category_name'] ?? 'General') ?></div>
                            <a href="<?= url('product.php?id=' . (int)$product['product_id']) ?>"><h3 class="product-title"><?= e($product['product_name']) ?></h3></a>
                            <div class="product-bottom">
                                <?= dual_money($product['price']) ?>
                                <?php if ((int)$product['stock'] > 0): ?>
                                    <form method="post" action="<?= url('add_to_cart.php') ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="product_id" value="<?= (int)$product['product_id'] ?>">
                                        <input type="hidden" name="quantity" value="1">
                                        <button class="product-add" title="Add to cart">＋</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state"><div class="icon">📦</div><h3>No products available</h3><p class="muted">Products added from the admin panel will appear here.</p></div>
        <?php endif; ?>
    </div>
</section>

<section class="section-sm">
    <div class="container benefit-grid">
        <div class="benefit-card"><div class="icon">🚚</div><div><h4>Fast delivery</h4><p>Quick order processing and delivery tracking.</p></div></div>
        <div class="benefit-card"><div class="icon">🔒</div><div><h4>Secure checkout</h4><p>Server-side validation and protected accounts.</p></div></div>
        <div class="benefit-card"><div class="icon">↩️</div><div><h4>Simple ordering</h4><p>Easy cart, checkout and order history.</p></div></div>
        <div class="benefit-card"><div class="icon">💬</div><div><h4>Customer reviews</h4><p>Verified delivered-order customers can review.</p></div></div>
    </div>
</section>

<section class="section-sm">
    <div class="container promo">
        <div><span class="eyebrow">New customer?</span><h2>Create your account and start shopping.</h2><p>Keep your orders, checkout information and reviews together.</p></div>
        <a class="btn btn-primary" href="<?= url(is_logged_in() ? 'shop.php' : 'register.php') ?>"><?= is_logged_in() ? 'Browse products' : 'Create account' ?> →</a>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
