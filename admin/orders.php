<?php
require_once __DIR__ . '/../includes/functions.php'; require_admin();
$adminPageTitle='Orders';
$status=trim($_GET['status'] ?? '');
$allowed=['Pending','Processing','Shipped','Delivered','Cancelled'];
if(in_array($status,$allowed,true)){$stmt=$pdo->prepare('SELECT o.*,u.full_name,u.email FROM orders o JOIN users u ON u.user_id=o.user_id WHERE o.order_status=? ORDER BY o.order_id DESC');$stmt->execute([$status]);$orders=$stmt->fetchAll();}
else $orders=$pdo->query('SELECT o.*,u.full_name,u.email FROM orders o JOIN users u ON u.user_id=o.user_id ORDER BY o.order_id DESC')->fetchAll();
require __DIR__ . '/includes/admin_header.php';
?>
<div class="admin-toolbar"><div><span class="eyebrow">Sales</span><h1>Orders</h1></div></div>
<div class="admin-card"><div class="filter-list" style="display:flex;flex-wrap:wrap;margin-bottom:18px"><a class="<?= $status===''?'active':'' ?>" href="<?= url('admin/orders.php') ?>">All</a><?php foreach($allowed as $s): ?><a class="<?= $status===$s?'active':'' ?>" href="<?= url('admin/orders.php?status='.urlencode($s)) ?>"><?= e($s) ?></a><?php endforeach; ?></div>
<div class="table-wrap"><table class="data-table"><thead><tr><th>Order</th><th>Customer</th><th>Date</th><th>Total</th><th>Payment</th><th>Status</th><th></th></tr></thead><tbody><?php foreach($orders as $o): ?><tr><td><strong>#<?= (int)$o['order_id'] ?></strong></td><td><?= e($o['full_name']) ?><div class="muted"><?= e($o['email']) ?></div></td><td><?= e(date('d M Y',strtotime($o['order_date']))) ?></td><td><?= dual_money($o['total_amount'],order_usd_amount($o),order_rate($o),false) ?></td><td><?= e($o['payment_method']) ?></td><td><span class="status <?= strtolower($o['order_status']) ?>"><?= e($o['order_status']) ?></span></td><td><a class="btn btn-ghost btn-small" href="<?= url('admin/order_view.php?id='.(int)$o['order_id']) ?>">Manage</a></td></tr><?php endforeach; ?><?php if(!$orders): ?><tr><td colspan="7" class="muted">No orders found.</td></tr><?php endif; ?></tbody></table></div></div>
<?php require __DIR__ . '/includes/admin_footer.php'; ?>
