<?php
require_once __DIR__ . '/../includes/functions.php'; require_super_admin();
$adminPageTitle='Customers';
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf(); $id=max(1,(int)($_POST['id'] ?? 0)); $status=$_POST['status'] ?? '';
    if(in_array($status,['active','inactive'],true)){$stmt=$pdo->prepare("UPDATE users SET status=? WHERE user_id=? AND role='customer'");$stmt->execute([$status,$id]);flash('success','Customer status updated.');}
    redirect('admin/users.php');
}
$users=$pdo->query("SELECT u.user_id,u.full_name,u.email,u.phone,u.status,u.created_at,COUNT(o.order_id) order_count,COALESCE(SUM(CASE WHEN o.order_status<>'Cancelled' THEN o.total_amount ELSE 0 END),0) total_spent_bdt,COALESCE(SUM(CASE WHEN o.order_status<>'Cancelled' THEN COALESCE(o.total_eur,o.total_amount/NULLIF(o.eur_exchange_rate,0)) ELSE 0 END),0) total_spent_eur FROM users u LEFT JOIN orders o ON o.user_id=u.user_id WHERE u.role='customer' GROUP BY u.user_id ORDER BY u.user_id DESC")->fetchAll();
require __DIR__ . '/includes/admin_header.php';
?>
<div class="admin-toolbar"><div><span class="eyebrow">Super Admin only</span><h1>Customers</h1><p class="muted">Account control and lifetime spending overview.</p></div><span class="muted"><?= count($users) ?> registered</span></div>
<div class="admin-card"><div class="table-wrap"><table class="data-table"><thead><tr><th>Customer</th><th>Phone</th><th>Orders</th><th>Total spent</th><th>Status</th><th>Action</th></tr></thead><tbody><?php foreach($users as $u): ?><tr><td><strong><?= e($u['full_name']) ?></strong><div class="muted"><?= e($u['email']) ?></div></td><td><?= e($u['phone'] ?: '—') ?></td><td><?= (int)$u['order_count'] ?></td><td><?= dual_money($u['total_spent_bdt'],(float)$u['total_spent_eur'],null,false) ?></td><td><span class="status <?= e($u['status']) ?>"><?= e($u['status']) ?></span></td><td><form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$u['user_id'] ?>"><input type="hidden" name="status" value="<?= $u['status']==='active'?'inactive':'active' ?>"><button class="btn btn-ghost btn-small"><?= $u['status']==='active'?'Deactivate':'Activate' ?></button></form></td></tr><?php endforeach; ?><?php if(!$users): ?><tr><td colspan="6" class="muted">No customer accounts yet.</td></tr><?php endif; ?></tbody></table></div></div>
<?php require __DIR__ . '/includes/admin_footer.php'; ?>
