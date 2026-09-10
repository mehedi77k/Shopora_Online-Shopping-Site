<?php
require_once __DIR__ . '/../includes/functions.php'; require_admin();
$adminPageTitle='Products';
$q=trim($_GET['q'] ?? '');
if($q){$stmt=$pdo->prepare("SELECT p.*,c.category_name FROM products p LEFT JOIN categories c ON c.category_id=p.category_id WHERE p.product_name LIKE ? ORDER BY p.product_id DESC");$stmt->execute(['%'.$q.'%']);$products=$stmt->fetchAll();}
else $products=$pdo->query("SELECT p.*,c.category_name FROM products p LEFT JOIN categories c ON c.category_id=p.category_id ORDER BY p.product_id DESC")->fetchAll();
require __DIR__ . '/includes/admin_header.php';
?>
<div class="admin-toolbar"><div><span class="eyebrow">Catalog</span><h1>Products</h1></div><a class="btn btn-primary" href="<?= url('admin/product_form.php') ?>">+ Add product</a></div>
<div class="admin-card"><div class="shop-toolbar"><form class="search-form"><input class="form-control" type="search" name="q" value="<?= e($q) ?>" placeholder="Search products"><button class="btn btn-ghost">Search</button></form><span class="muted"><?= count($products) ?> products</span></div>
<div class="table-wrap"><table class="data-table"><thead><tr><th>Product</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php foreach($products as $p): ?><tr><td><div style="display:flex;align-items:center;gap:10px"><img class="product-mini" src="<?= e(product_image($p['image'])) ?>"><strong><?= e($p['product_name']) ?></strong></div></td><td><?= e($p['category_name'] ?? '—') ?></td><td><?= dual_money($p['price']) ?></td><td><?= (int)$p['stock'] ?></td><td><span class="status <?= $p['status']==='available'?'active':'inactive' ?>"><?= e($p['status']) ?></span></td><td><a class="btn btn-ghost btn-small" href="<?= url('admin/product_form.php?id='.(int)$p['product_id']) ?>">Edit</a> <form class="inline-form" method="post" action="<?= url('admin/product_delete.php') ?>" style="margin-left:4px"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$p['product_id'] ?>"><button class="btn btn-danger btn-small" data-confirm="Delete this product permanently?">Delete</button></form></td></tr><?php endforeach; ?>
<?php if(!$products): ?><tr><td colspan="6" class="muted">No products found.</td></tr><?php endif; ?></tbody></table></div></div>
<?php require __DIR__ . '/includes/admin_footer.php'; ?>
