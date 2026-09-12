<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$adminPageTitle = 'User History';
$email = trim(strtolower($_GET['email'] ?? ''));
$user = null;
$orders = $reviews = $cartItems = $conversations = $activities = $mobileNumbers = [];
$orderItemsById = [];
$paymentsByOrder = [];
$summary = ['orders'=>0,'spent_eur'=>0,'spent_usd'=>0,'reviews'=>0,'support'=>0];
$error = null;

if($email !== ''){
    if(!filter_var($email,FILTER_VALIDATE_EMAIL)){
        $error='Enter a valid email address.';
    } else {
        $stmt=$pdo->prepare('SELECT user_id,full_name,email,phone,profile_number,profile_image,address,blood_group,joining_date,gender,role,status,created_at FROM users WHERE email=? LIMIT 1');
        $stmt->execute([$email]);
        $user=$stmt->fetch();
        if(!$user){
            $error='No registered account was found with this email address.';
        } else {
            $uid=(int)$user['user_id'];
            $stmt=$pdo->prepare('SELECT label,mobile_number FROM user_mobile_numbers WHERE user_id=? ORDER BY sort_order,mobile_id'); $stmt->execute([$uid]); $mobileNumbers=$stmt->fetchAll();
            $stmt=$pdo->prepare("SELECT o.* FROM orders o WHERE o.user_id=? ORDER BY o.order_id DESC"); $stmt->execute([$uid]); $orders=$stmt->fetchAll();
            if($orders){
                $ids=array_column($orders,'order_id'); $ph=implode(',',array_fill(0,count($ids),'?'));
                $stmt=$pdo->prepare("SELECT oi.*,p.product_name FROM order_items oi LEFT JOIN products p ON p.product_id=oi.product_id WHERE oi.order_id IN ($ph) ORDER BY oi.order_id DESC,oi.order_item_id"); $stmt->execute($ids);
                foreach($stmt->fetchAll() as $i){$orderItemsById[(int)$i['order_id']][]=$i;}
                $stmt=$pdo->prepare("SELECT * FROM payments WHERE order_id IN ($ph) ORDER BY payment_id DESC"); $stmt->execute($ids);
                foreach($stmt->fetchAll() as $p){$oid=(int)$p['order_id']; if(!isset($paymentsByOrder[$oid])) $paymentsByOrder[$oid]=$p;}
            }
            $stmt=$pdo->prepare('SELECT r.*,p.product_name FROM reviews r LEFT JOIN products p ON p.product_id=r.product_id WHERE r.user_id=? ORDER BY r.created_at DESC'); $stmt->execute([$uid]); $reviews=$stmt->fetchAll();
            $stmt=$pdo->prepare('SELECT ci.quantity,p.product_id,p.product_name,p.price,p.stock,p.status FROM carts c JOIN cart_items ci ON ci.cart_id=c.cart_id JOIN products p ON p.product_id=ci.product_id WHERE c.user_id=? ORDER BY ci.cart_item_id DESC'); $stmt->execute([$uid]); $cartItems=$stmt->fetchAll();
            $stmt=$pdo->prepare("SELECT c.*,COUNT(m.message_id) message_count FROM contact_conversations c LEFT JOIN contact_messages m ON m.conversation_id=c.conversation_id WHERE c.user_id=? OR LOWER(c.requester_email)=? GROUP BY c.conversation_id ORDER BY c.last_message_at DESC"); $stmt->execute([$uid,strtolower($user['email'])]); $conversations=$stmt->fetchAll();
            $stmt=$pdo->prepare('SELECT a.*,actor.full_name actor_name,actor.email actor_email,subject.full_name subject_name FROM user_activity_logs a LEFT JOIN users actor ON actor.user_id=a.actor_user_id LEFT JOIN users subject ON subject.user_id=a.user_id WHERE a.user_id=? OR a.actor_user_id=? ORDER BY a.activity_id DESC'); $stmt->execute([$uid,$uid]); $activities=$stmt->fetchAll();

            foreach($orders as $o){if($o['order_status']!=='Cancelled'){$summary['spent_eur']+=(float)$o['total_amount'];$summary['spent_usd']+=(float)(order_usd_amount($o) ?? 0);}}
            $summary['orders']=count($orders); $summary['reviews']=count($reviews); $summary['support']=count($conversations);
        }
    }
}
require __DIR__ . '/includes/admin_header.php';
?>
<div class="admin-toolbar"><div><span class="eyebrow">Admin & Super Admin</span><h1>User history lookup</h1><p class="muted">Search an exact account email to view its stored history and meaningful activity log.</p></div></div>
<div class="admin-card user-history-search-card"><form method="get" class="user-history-search"><?= session_context_field() ?><div class="form-group"><label>User email</label><input class="form-control" type="email" name="email" value="<?= e($email) ?>" placeholder="user@example.com" required></div><div class="form-actions"><button class="btn btn-primary">Search full history</button></div></form></div>
<?php if($error): ?><div class="flash flash-error" style="margin-top:18px"><?= e($error) ?></div><?php endif; ?>
<?php if($user): ?>
<div class="history-profile-card history-profile-expanded">
    <div class="history-profile-main">
        <div class="avatar history-avatar"><?php if (!empty($user['profile_image'])): ?><img src="<?= e(profile_image($user['profile_image'])) ?>" alt=""><?php else: ?><?= e(strtoupper(substr($user['full_name'],0,1))) ?><?php endif; ?></div>
        <div><h2><?= e($user['full_name']) ?></h2><div class="muted"><?= e($user['email']) ?> · <?= e($user['phone'] ?: 'No primary phone') ?></div><div class="history-tags"><span class="message-badge"><?= e(support_role_label($user['role'])) ?></span><span class="support-status <?= e($user['status']) ?>"><?= e(ucfirst($user['status'])) ?></span><span class="muted">Account created <?= e(date('d M Y, h:i A',strtotime($user['created_at']))) ?></span></div></div>
    </div>
    <div class="history-profile-details">
        <div><span>Profile / ID</span><strong><?= e($user['profile_number'] ?: '—') ?></strong></div>
        <div><span>Gender</span><strong><?= e($user['gender'] ?: '—') ?></strong></div>
        <div><span>Blood group</span><strong><?= e($user['blood_group'] ?: '—') ?></strong></div>
        <div><span>Joining date</span><strong><?= e($user['joining_date'] ?: '—') ?></strong></div>
        <div class="history-detail-wide"><span>Address</span><strong><?= $user['address'] ? nl2br(e($user['address'])) : '—' ?></strong></div>
        <div class="history-detail-wide"><span>Mobile numbers</span><strong><?php if($mobileNumbers): ?><?= e(implode(' · ', array_map(fn($m)=>($m['label'] ?: 'Mobile').': '.$m['mobile_number'],$mobileNumbers))) ?><?php else: ?>—<?php endif; ?></strong></div>
    </div>
</div>
<div class="support-metric-grid history-metrics"><div class="support-metric"><strong><?= $summary['orders'] ?></strong><span>Orders</span></div><div class="support-metric"><strong><?= money($summary['spent_eur']) ?></strong><span>Non-cancelled spend</span><small><?= usd_money($summary['spent_usd']) ?></small></div><div class="support-metric"><strong><?= $summary['reviews'] ?></strong><span>Reviews</span></div><div class="support-metric"><strong><?= $summary['support'] ?></strong><span>Support tickets</span></div></div>

<div class="history-section admin-card"><div class="section-heading"><div><h2>Orders & payments</h2><p>Complete stored order history.</p></div></div>
<?php if($orders): ?><div class="history-order-list"><?php foreach($orders as $o): $oid=(int)$o['order_id']; $pay=$paymentsByOrder[$oid]??null; ?><details class="history-order"><summary><span><strong>Order #<?= $oid ?></strong><small><?= e(date('d M Y, h:i A',strtotime($o['order_date']))) ?></small></span><span><?= dual_money($o['total_amount'],order_usd_amount($o),order_rate($o),false) ?></span><span class="support-status <?= strtolower($o['order_status']) ?>"><?= e($o['order_status']) ?></span></summary><div class="history-order-body"><div class="muted">Payment: <?= e($o['payment_method']) ?> · <?= e($pay['payment_status'] ?? 'Pending') ?></div><div class="muted">Shipping: <?= nl2br(e($o['shipping_address'])) ?></div><ul><?php foreach($orderItemsById[$oid]??[] as $i): ?><li><?= e($i['product_name'] ?: 'Deleted product') ?> × <?= (int)$i['quantity'] ?> — <?= money($i['subtotal']) ?></li><?php endforeach; ?></ul></div></details><?php endforeach; ?></div><?php else: ?><p class="muted">No orders recorded.</p><?php endif; ?>
</div>

<div class="admin-grid-2 history-grid">
<div class="admin-card"><h2>Reviews</h2><?php if($reviews): ?><div class="history-simple-list"><?php foreach($reviews as $r): ?><div><strong><?= e($r['product_name'] ?: 'Deleted product') ?> · <?= (int)$r['rating'] ?>/5</strong><p><?= e($r['comment'] ?: 'No comment') ?></p><small><?= e(date('d M Y, h:i A',strtotime($r['created_at']))) ?></small></div><?php endforeach; ?></div><?php else: ?><p class="muted">No reviews recorded.</p><?php endif; ?></div>
<div class="admin-card"><h2>Current cart</h2><?php if($cartItems): ?><div class="history-simple-list"><?php foreach($cartItems as $i): ?><div><strong><?= e($i['product_name']) ?> × <?= (int)$i['quantity'] ?></strong><p><?= money((float)$i['price']*(int)$i['quantity']) ?> · Product <?= e($i['status']) ?> · Stock <?= (int)$i['stock'] ?></p></div><?php endforeach; ?></div><?php else: ?><p class="muted">Cart is currently empty.</p><?php endif; ?></div>
</div>

<div class="history-section admin-card"><div class="section-heading"><div><h2>Support conversations</h2><p>All account-linked contact history.</p></div></div><?php if($conversations): ?><div class="table-wrap"><table class="data-table"><thead><tr><th>Ticket</th><th>Subject</th><th>Messages</th><th>Last activity</th><th>Status</th><th></th></tr></thead><tbody><?php foreach($conversations as $c): ?><tr><td>#<?= (int)$c['conversation_id'] ?></td><td><?= e($c['subject']) ?></td><td><?= (int)$c['message_count'] ?></td><td><?= e(date('d M Y, h:i A',strtotime($c['last_message_at']))) ?></td><td><span class="support-status <?= strtolower($c['status']) ?>"><?= e($c['status']) ?></span></td><td><a href="<?= url('admin/support_view.php?id='.(int)$c['conversation_id']) ?>">Open</a></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><p class="muted">No support conversations recorded.</p><?php endif; ?></div>

<div class="history-section admin-card"><div class="section-heading"><div><h2>Meaningful activity log</h2><p>Recorded actions after the activity-history migration. Older orders, reviews, carts and support data remain visible in the sections above.</p></div><span class="muted"><?= count($activities) ?> logged event<?= count($activities)===1?'':'s' ?></span></div>
<?php if($activities): ?><div class="activity-timeline"><?php foreach($activities as $a): $isActor=(int)$a['actor_user_id']===(int)$user['user_id']; ?><div class="activity-event"><span class="activity-dot"></span><div><div class="activity-event-top"><strong><?= e(str_replace('_',' ',ucwords($a['activity_type'],'_'))) ?></strong><time><?= e(date('d M Y, h:i:s A',strtotime($a['created_at']))) ?></time></div><p><?= e($a['description']) ?></p><?php if($a['actor_user_id'] && (int)$a['actor_user_id']!==(int)$a['user_id']): ?><small>Performed by <?= e($a['actor_name'] ?: 'Unknown account') ?><?= $a['actor_email']?' · '.e($a['actor_email']):'' ?></small><?php elseif($isActor): ?><small>Performed by this account</small><?php endif; ?></div></div><?php endforeach; ?></div><?php else: ?><p class="muted">No audit events have been recorded yet. Activity logging starts after this update is installed.</p><?php endif; ?>
</div>
<?php endif; ?>
<?php require __DIR__ . '/includes/admin_footer.php'; ?>
