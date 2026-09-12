<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$adminPageTitle='Dashboard';
$metrics=[
    'products'=>(int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn(),
    'orders'=>(int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
    'customers'=>(int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn(),
];
$sales=$pdo->query("SELECT COALESCE(SUM(total_amount),0) eur, COALESCE(SUM(COALESCE(total_usd,total_amount*NULLIF(usd_exchange_rate,0))),0) usd FROM orders WHERE order_status <> 'Cancelled'")->fetch();
$today=$pdo->query("SELECT COALESCE(SUM(total_amount),0) eur, COALESCE(SUM(COALESCE(total_usd,total_amount*NULLIF(usd_exchange_rate,0))),0) usd FROM orders WHERE order_status <> 'Cancelled' AND DATE(order_date)=CURDATE()")->fetch();
$avg=$pdo->query("SELECT COALESCE(AVG(total_amount),0) eur, COALESCE(AVG(COALESCE(total_usd,total_amount*NULLIF(usd_exchange_rate,0))),0) usd FROM orders WHERE order_status <> 'Cancelled'")->fetch();
$recent=$pdo->query("SELECT o.*,u.full_name FROM orders o JOIN users u ON u.user_id=o.user_id ORDER BY o.order_id DESC LIMIT 7")->fetchAll();
$lowStock=$pdo->query("SELECT product_id,product_name,stock,image FROM products WHERE stock<=5 ORDER BY stock ASC,product_id DESC LIMIT 6")->fetchAll();
$currentRate=current_usd_rate($pdo);
require __DIR__ . '/includes/admin_header.php';
?>
<div class="admin-toolbar"><div><span class="eyebrow">Overview</span><h1>Store dashboard</h1></div><a class="btn btn-primary" href="<?= url('admin/product_form.php') ?>">+ Add product</a></div>
<div class="admin-rate-bar"><div><span>Reference exchange rate</span><strong data-live-exchange-rate data-rate-mode="pair" data-rate-value="<?= $currentRate ? e((string)$currentRate) : '' ?>"><?= $currentRate ? '€1 = '.usd_rate($currentRate) : 'USD rate unavailable' ?></strong></div><?php if(is_super_admin()): ?><a href="<?= url('admin/currency_settings.php') ?>">Rate details →</a><?php endif; ?></div>
<div class="metric-grid metric-grid-finance">
<div class="metric-card"><div class="label">TOTAL SALES</div><div class="value"><?= money($sales['eur']) ?></div><div class="secondary-value"><?= usd_money($sales['usd']) ?></div><div class="trend">Non-cancelled orders</div></div>
<div class="metric-card"><div class="label">TODAY'S SALES</div><div class="value"><?= money($today['eur']) ?></div><div class="secondary-value"><?= usd_money($today['usd']) ?></div><div class="trend">Today, non-cancelled</div></div>
<div class="metric-card"><div class="label">AVERAGE ORDER</div><div class="value"><?= money($avg['eur']) ?></div><div class="secondary-value"><?= usd_money($avg['usd']) ?></div><div class="trend">Non-cancelled orders</div></div>
<div class="metric-card"><div class="label">ORDERS</div><div class="value"><?= $metrics['orders'] ?></div><div class="trend">All customer orders</div></div>
<div class="metric-card"><div class="label">PRODUCTS</div><div class="value"><?= $metrics['products'] ?></div><div class="trend">Catalog items</div></div>
<div class="metric-card"><div class="label">CUSTOMERS</div><div class="value"><?= $metrics['customers'] ?></div><div class="trend">Registered customers</div></div>
</div>
<div class="admin-grid-2" style="margin-top:22px">
<div class="admin-card"><div class="admin-toolbar"><h2>Recent orders</h2><a class="btn btn-ghost btn-small" href="<?= url('admin/orders.php') ?>">View all</a></div>
<?php if($recent): ?><div class="table-wrap"><table class="data-table"><thead><tr><th>Order</th><th>Customer</th><th>Total</th><th>Status</th><th></th></tr></thead><tbody><?php foreach($recent as $o): ?><tr><td>#<?= (int)$o['order_id'] ?></td><td><?= e($o['full_name']) ?></td><td><?= dual_money($o['total_amount'],order_usd_amount($o),order_rate($o),false) ?></td><td><span class="status <?= strtolower($o['order_status']) ?>"><?= e($o['order_status']) ?></span></td><td><a href="<?= url('admin/order_view.php?id='.(int)$o['order_id']) ?>">Open</a></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><p class="muted">No orders yet.</p><?php endif; ?></div>
<div class="admin-card"><h2>Low stock</h2><?php if($lowStock): ?><?php foreach($lowStock as $p): ?><div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--border)"><img class="product-mini" src="<?= e(product_image($p['image'])) ?>"><div style="flex:1"><strong><?= e($p['product_name']) ?></strong><div class="muted"><?= (int)$p['stock'] ?> left</div></div><a href="<?= url('admin/product_form.php?id='.(int)$p['product_id']) ?>">Edit</a></div><?php endforeach; ?><?php else: ?><p class="muted">Stock levels look healthy.</p><?php endif; ?></div>
</div>
<?php require __DIR__ . '/includes/admin_footer.php'; ?>
