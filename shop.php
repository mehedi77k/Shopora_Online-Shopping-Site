<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Shop';
$categoryId = max(0, (int)($_GET['category'] ?? 0));
$q = trim($_GET['q'] ?? '');

$sql = "SELECT p.*, c.category_name FROM products p LEFT JOIN categories c ON c.category_id = p.category_id WHERE p.status = 'available'";
$params = [];
if ($categoryId > 0) {
    $sql .= ' AND p.category_id = ?';
    $params[] = $categoryId;
}
if ($q !== '') {
    $sql .= ' AND (p.product_name LIKE ? OR p.description LIKE ?)';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}
$sql .= ' ORDER BY p.product_id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
$categories = fetch_categories($pdo);

require __DIR__ . '/includes/header.php';
?>
<section class="page-hero"><div class="container page-heading"><span class="eyebrow">Collection</span><h1>Shop all products</h1><p>Search or browse by category to find what you need.</p></div></section>
<section class="section-sm">
    <div class="container shop-layout">
        <aside class="filters">
            <h3>Categories</h3>
            <div class="filter-list">
                <a class="<?= $categoryId === 0 ? 'active' : '' ?>" href="<?= url('shop.php' . ($q ? '?q=' . urlencode($q) : '')) ?>">All products</a>
                <?php foreach ($categories as $cat): ?>
                    <?php $query = ['category' => $cat['category_id']]; if ($q) $query['q'] = $q; ?>
                    <a class="<?= $categoryId === (int)$cat['category_id'] ? 'active' : '' ?>" href="<?= url('shop.php?' . http_build_query($query)) ?>"><?= e($cat['category_name']) ?></a>
                <?php endforeach; ?>
            </div>
        </aside>
        <div>
            <div class="shop-toolbar">
                <form class="search-form" method="get" action="<?= url('shop.php') ?>">
                    <?php if ($categoryId): ?><input type="hidden" name="category" value="<?= $categoryId ?>"><?php endif; ?>
                    <input class="form-control" type="search" name="q" value="<?= e($q) ?>" placeholder="Search products...">
                    <button class="btn btn-primary">Search</button>
                </form>
                <span class="muted"><?= count($products) ?> result<?= count($products) === 1 ? '' : 's' ?></span>
            </div>
            <?php if ($products): ?>
                <div class="product-grid">
                    <?php foreach ($products as $product): ?>
                        <article class="product-card">
                            <a href="<?= url('product.php?id=' . (int)$product['product_id']) ?>" class="product-image-wrap">
                                <img src="<?= e(product_image($product['image'])) ?>" alt="<?= e($product['product_name']) ?>">
                                <span class="stock-pill <?= (int)$product['stock'] <= 0 ? 'out' : '' ?>"><?= (int)$product['stock'] > 0 ? (int)$product['stock'] . ' in stock' : 'Out of stock' ?></span>
                            </a>
                            <div class="product-body">
                                <div class="product-category"><?= e($product['category_name'] ?? 'General') ?></div>
                                <a href="<?= url('product.php?id=' . (int)$product['product_id']) ?>"><h3 class="product-title"><?= e($product['product_name']) ?></h3></a>
                                <div class="product-bottom"><?= dual_money($product['price']) ?>
                                <?php if ((int)$product['stock'] > 0): ?>
                                    <form method="post" action="<?= url('add_to_cart.php') ?>"><?= csrf_field() ?><input type="hidden" name="product_id" value="<?= (int)$product['product_id'] ?>"><input type="hidden" name="quantity" value="1"><button class="product-add">＋</button></form>
                                <?php endif; ?></div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state"><div class="icon">🔎</div><h3>No matching products</h3><p class="muted">Try another search or choose a different category.</p><a class="btn btn-ghost" href="<?= url('shop.php') ?>">Clear filters</a></div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
