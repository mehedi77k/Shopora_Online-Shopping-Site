<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$adminPageTitle = 'Support Inbox';

$q = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$allowedStatuses = ['Open','Answered','Closed'];
$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(c.requester_name LIKE ? OR c.requester_email LIKE ? OR c.subject LIKE ? OR CAST(c.conversation_id AS CHAR) = ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, ltrim($q, '#'));
}
if (in_array($status, $allowedStatuses, true)) {
    $where[] = 'c.status=?';
    $params[] = $status;
}
$sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$stmt = $pdo->prepare(
    "SELECT c.*, u.role AS account_role,
            COUNT(m.message_id) AS message_count,
            COALESCE(SUM(CASE WHEN m.message_type='requester' AND m.seen_by_staff=0 THEN 1 ELSE 0 END),0) AS unread_count
     FROM contact_conversations c
     LEFT JOIN users u ON u.user_id=c.user_id
     LEFT JOIN contact_messages m ON m.conversation_id=c.conversation_id
     $sqlWhere
     GROUP BY c.conversation_id
     ORDER BY c.last_message_at DESC, c.conversation_id DESC"
);
$stmt->execute($params);
$conversations = $stmt->fetchAll();
$unread = support_unread_for_staff($pdo);

require __DIR__ . '/includes/admin_header.php';
?>
<div data-support-list-page>
<div class="admin-toolbar"><div><span class="eyebrow">Customer service</span><h1>Support Inbox</h1><p class="muted">Review messages from customers, Admins, Super Admin and guests, then reply in the same conversation.</p></div><span class="message-badge" data-support-staff-label><?= $unread ?> unread</span></div>
<div class="admin-card" style="margin-bottom:18px">
    <form method="get" class="support-filter-form">
        <?= session_context_field() ?>
        <div class="form-group"><label>Search</label><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Name, email, subject or ticket number"></div>
        <div class="form-group"><label>Status</label><select class="form-control" name="status"><option value="">All statuses</option><?php foreach($allowedStatuses as $s): ?><option value="<?= e($s) ?>" <?= $status===$s?'selected':'' ?>><?= e($s) ?></option><?php endforeach; ?></select></div>
        <div class="form-actions support-filter-actions"><button class="btn btn-primary">Search</button><a class="btn btn-ghost" href="<?= url('admin/support.php') ?>">Reset</a></div>
    </form>
</div>
<div class="admin-card">
<?php if($conversations): ?>
    <div class="table-wrap"><table class="data-table"><thead><tr><th>Ticket</th><th>Requester</th><th>Subject</th><th>Messages</th><th>Last activity</th><th>Status</th><th></th></tr></thead><tbody>
    <?php foreach($conversations as $c): ?><tr class="<?= (int)$c['unread_count'] > 0 ? 'support-unread-row' : '' ?>">
        <td><strong>#<?= (int)$c['conversation_id'] ?></strong><?php if((int)$c['unread_count']>0): ?><div><span class="message-badge"><?= (int)$c['unread_count'] ?> new</span></div><?php endif; ?></td>
        <td><strong><?= e($c['requester_name']) ?></strong><div class="muted"><?= e($c['requester_email']) ?></div><div class="muted"><?= e($c['account_role'] ? support_role_label($c['account_role']) : 'Guest') ?></div></td>
        <td><?= e($c['subject']) ?></td><td><?= (int)$c['message_count'] ?></td><td><?= e(date('d M Y, h:i A',strtotime($c['last_message_at']))) ?></td><td><span class="support-status <?= strtolower($c['status']) ?>"><?= e($c['status']) ?></span></td><td><a class="btn btn-ghost btn-small" href="<?= url('admin/support_view.php?id='.(int)$c['conversation_id']) ?>">Open</a></td>
    </tr><?php endforeach; ?>
    </tbody></table></div>
<?php else: ?><div class="empty-state"><div class="icon">✉</div><h3>No conversations found</h3><p class="muted">New contact messages will appear here.</p></div><?php endif; ?>
</div>
</div>
<?php require __DIR__ . '/includes/admin_footer.php'; ?>
