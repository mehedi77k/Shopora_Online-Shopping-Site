<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
$pageTitle='My Account';
$userId=(int)$_SESSION['user']['user_id'];
$stmt=$pdo->prepare('SELECT user_id,full_name,email,phone,role,status,created_at FROM users WHERE user_id=?');
$stmt->execute([$userId]); $profile=$stmt->fetch();
$ordersStmt=$pdo->prepare('SELECT * FROM orders WHERE user_id=? ORDER BY order_id DESC');
$ordersStmt->execute([$userId]); $orders=$ordersStmt->fetchAll();
$spendStmt=$pdo->prepare("SELECT COUNT(*) order_count, COALESCE(SUM(total_amount),0) total_bdt, COALESCE(SUM(COALESCE(total_eur,total_amount/NULLIF(eur_exchange_rate,0))),0) total_eur FROM orders WHERE user_id=? AND order_status<>'Cancelled'");
$spendStmt->execute([$userId]); $spend=$spendStmt->fetch();
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero"><div class="container page-heading"><span class="eyebrow">Dashboard</span><h1>Hello, <?= e($profile['full_name']) ?></h1><p>Manage your profile and follow your orders.</p></div></section>
<section class="section-sm"><div class="container account-grid">
<aside class="account-card"><div class="avatar" style="width:54px;height:54px;font-size:1.2rem"><?= e(strtoupper(substr($profile['full_name'],0,1))) ?></div><h3 style="margin:12px 0 2px"><?= e($profile['full_name']) ?></h3><p class="muted" style="margin:0"><?= e($profile['email']) ?></p><p class="muted"><?= e($profile['phone'] ?: 'No phone added') ?></p><?php if (is_admin()): ?><a class="btn btn-primary btn-block" href="<?= url('admin/index.php') ?>">Admin dashboard</a><?php endif; ?></aside>
<div>
<div class="spending-summary"><div><span class="eyebrow">Lifetime spending</span><h2><?= money($spend['total_bdt']) ?></h2><div class="spending-eur"><?= eur_money($spend['total_eur']) ?></div></div><div class="spending-orders"><strong><?= (int)$spend['order_count'] ?></strong><span>Non-cancelled orders</span></div></div>
<div class="section-heading"><div><h2>Order history</h2><p><?= count($orders) ?> order<?= count($orders)==1?'':'s' ?> found.</p></div><a class="btn btn-ghost btn-small" href="<?= url('shop.php') ?>">Shop more</a></div>
<?php if ($orders): ?><div class="table-wrap"><table class="data-table"><thead><tr><th>Order</th><th>Date</th><th>Total</th><th>Payment</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($orders as $order): ?><tr><td><strong>#<?= (int)$order['order_id'] ?></strong></td><td><?= e(date('d M Y',strtotime($order['order_date']))) ?></td><td><?= dual_money($order['total_amount'], order_eur_amount($order), order_rate($order), false) ?></td><td><?= e($order['payment_method']) ?></td><td><span class="status <?= strtolower($order['order_status']) ?>"><?= e($order['order_status']) ?></span></td><td><a class="btn btn-ghost btn-small" href="<?= url('order_details.php?id='.(int)$order['order_id']) ?>">View</a></td></tr><?php endforeach; ?>
</tbody></table></div><?php else: ?><div class="empty-state"><div class="icon">📦</div><h3>No orders yet</h3><p class="muted">Your completed checkouts will appear here.</p><a class="btn btn-primary" href="<?= url('shop.php') ?>">Browse products</a></div><?php endif; ?>
</div></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
