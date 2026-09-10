<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Shopping Cart';
$items = cart_items($pdo);
$total = cart_total($pdo);
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero"><div class="container page-heading"><span class="eyebrow">Your selection</span><h1>Shopping cart</h1><p>Review quantities before checkout.</p></div></section>
<section class="section-sm"><div class="container">
<?php if ($items): ?>
<div class="cart-layout"><form class="cart-list" method="post" action="<?= url('update_cart.php') ?>"><?= csrf_field() ?>
    <?php foreach ($items as $item): ?><div class="cart-item">
        <a href="<?= url('product.php?id=' . (int)$item['product_id']) ?>"><img class="cart-thumb" src="<?= e(product_image($item['image'])) ?>" alt="<?= e($item['product_name']) ?>"></a>
        <div><a href="<?= url('product.php?id=' . (int)$item['product_id']) ?>"><h3><?= e($item['product_name']) ?></h3></a><span class="muted"><?= dual_money($item['price']) ?> <small>each</small></span><div class="cart-item-actions"><div class="qty-control" data-qty><button type="button" data-minus>−</button><input type="number" name="qty[<?= (int)$item['product_id'] ?>]" value="<?= (int)$item['quantity'] ?>" min="0" max="<?= max(0,(int)$item['stock']) ?>"><button type="button" data-plus>+</button></div><a class="text-danger" href="<?= url('remove_from_cart.php?id=' . (int)$item['product_id'] . '&token=' . urlencode(csrf_token())) ?>">Remove</a></div></div>
        <strong><?= dual_money($item['line_total']) ?></strong>
    </div><?php endforeach; ?>
    <div class="form-actions"><button class="btn btn-ghost">Update cart</button><a class="btn btn-ghost" href="<?= url('shop.php') ?>">Continue shopping</a></div>
</form>
<aside class="summary-card"><h3>Order summary</h3><div class="summary-row"><span>Subtotal</span><strong><?= dual_money($total) ?></strong></div><div class="summary-row"><span>Delivery</span><span>Calculated at checkout</span></div><div class="summary-row total"><span>Total</span><span><?= dual_money($total) ?></span></div><a class="btn btn-primary btn-block" href="<?= url('checkout.php') ?>">Proceed to checkout →</a></aside></div>
<?php else: ?><div class="empty-state"><div class="icon">🛒</div><h2>Your cart is empty</h2><p class="muted">Add a few products and come back here.</p><a class="btn btn-primary" href="<?= url('shop.php') ?>">Start shopping</a></div><?php endif; ?>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
