<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
$orderId=max(1,(int)($_GET['id'] ?? 0));
$stmt=$pdo->prepare('SELECT * FROM orders WHERE order_id=? AND user_id=?');
$stmt->execute([$orderId,(int)$_SESSION['user']['user_id']]); $order=$stmt->fetch();
if (!$order) redirect('account.php');
$pageTitle='Order Confirmed';
require __DIR__ . '/includes/header.php';
?>
<section class="section"><div class="container"><div class="empty-state" style="max-width:720px;margin:auto"><div class="icon">✅</div><span class="eyebrow">Order received</span><h1>Thank you for your order.</h1><p class="muted">Order <strong>#<?= $orderId ?></strong> has been created successfully. Current status: <span class="status <?= strtolower($order['order_status']) ?>"><?= e($order['order_status']) ?></span></p><div class="success-total"><?= dual_money($order['total_amount'],order_usd_amount($order),order_rate($order),false) ?></div><?php if(order_rate($order)): ?><p class="muted">Purchase-time rate: €1 = <?= usd_money(order_rate($order)) ?></p><?php endif; ?><div class="form-actions" style="justify-content:center"><a class="btn btn-primary" href="<?= url('order_details.php?id='.$orderId) ?>">View order</a><a class="btn btn-ghost" href="<?= url('shop.php') ?>">Continue shopping</a></div></div></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
