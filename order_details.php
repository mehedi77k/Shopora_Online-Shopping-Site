<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
$orderId=max(1,(int)($_GET['id'] ?? 0));
$stmt=$pdo->prepare('SELECT * FROM orders WHERE order_id=? AND user_id=?'); $stmt->execute([$orderId,(int)$_SESSION['user']['user_id']]); $order=$stmt->fetch();
if (!$order) { flash('error','Order not found.'); redirect('account.php'); }
$itemsStmt=$pdo->prepare('SELECT oi.*,p.product_name,p.image FROM order_items oi LEFT JOIN products p ON p.product_id=oi.product_id WHERE oi.order_id=?'); $itemsStmt->execute([$orderId]); $items=$itemsStmt->fetchAll();
$payStmt=$pdo->prepare('SELECT * FROM payments WHERE order_id=? ORDER BY payment_id DESC LIMIT 1'); $payStmt->execute([$orderId]); $payment=$payStmt->fetch();
$orderRate=order_rate($order); $orderUsd=order_usd_amount($order);
$pageTitle='Order #'.$orderId;
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero"><div class="container page-heading"><span class="eyebrow">Order details</span><h1>Order #<?= $orderId ?></h1><p>Placed on <?= e(date('d M Y, h:i A',strtotime($order['order_date']))) ?></p></div></section>
<section class="section-sm"><div class="container checkout-layout"><div class="cart-list">
<?php foreach ($items as $item): $itemUsd=$orderRate ? round((float)$item['subtotal']*$orderRate,2) : null; ?><div class="cart-item"><img class="cart-thumb" src="<?= e(product_image($item['image'])) ?>" alt=""><div><h3><?= e($item['product_name'] ?: 'Product') ?></h3><span class="muted"><?= (int)$item['quantity'] ?> × <?= dual_money($item['unit_price'], $orderRate ? round((float)$item['unit_price']*$orderRate,2) : null, $orderRate, false) ?></span></div><strong><?= dual_money($item['subtotal'],$itemUsd,$orderRate,false) ?></strong></div><?php endforeach; ?>
<div class="content-card" style="padding:22px"><h3>Shipping address</h3><p class="muted"><?= nl2br(e($order['shipping_address'])) ?></p></div></div>
<aside class="summary-card"><h3>Summary</h3><div class="summary-row"><span>Order status</span><span class="status <?= strtolower($order['order_status']) ?>"><?= e($order['order_status']) ?></span></div><div class="summary-row"><span>Payment method</span><strong><?= e($order['payment_method']) ?></strong></div><div class="summary-row"><span>Payment status</span><span class="status <?= strtolower($payment['payment_status'] ?? 'pending') ?>"><?= e($payment['payment_status'] ?? 'Pending') ?></span></div><div class="summary-row total"><span>Total</span><span><?= dual_money($order['total_amount'],$orderUsd,$orderRate,false) ?></span></div><?php if ($orderRate): ?><div class="currency-checkout-note"><strong>Exchange rate at purchase</strong><span>€1 = <?= usd_rate($orderRate) ?></span><small>This historical rate is locked to this order.</small></div><?php endif; ?><a class="btn btn-ghost btn-block" href="<?= url('account.php') ?>">Back to account</a></aside></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
