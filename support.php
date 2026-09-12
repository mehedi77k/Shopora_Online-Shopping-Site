<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
$pageTitle = 'My Support Messages';
$userId = (int)$_SESSION['user']['user_id'];

$stmt = $pdo->prepare(
    "SELECT c.*,
            COUNT(m.message_id) AS message_count,
            COALESCE(SUM(CASE WHEN m.message_type='staff' AND m.seen_by_requester=0 THEN 1 ELSE 0 END),0) AS unread_count
     FROM contact_conversations c
     LEFT JOIN contact_messages m ON m.conversation_id=c.conversation_id
     WHERE c.user_id=?
     GROUP BY c.conversation_id
     ORDER BY c.last_message_at DESC, c.conversation_id DESC"
);
$stmt->execute([$userId]);
$conversations = $stmt->fetchAll();
$counts = support_counts_for_user($pdo, $userId);

require __DIR__ . '/includes/header.php';
?>
<section class="page-hero"><div class="container page-heading">
    <span class="eyebrow">Support Center</span><h1>My messages</h1>
    <p>All support conversations connected to your account appear here, including replies from Admin and Super Admin.</p>
</div></section>
<section class="section-sm"><div class="container" data-support-list-page>
    <div class="support-metric-grid">
        <div class="support-metric"><strong data-support-total><?= $counts['total'] ?></strong><span>Total conversations</span></div>
        <div class="support-metric"><strong data-support-active><?= $counts['active'] ?></strong><span>Active conversations</span></div>
        <div class="support-metric"><strong data-support-unread><?= $counts['unread'] ?></strong><span>Unread replies</span></div>
    </div>
    <div class="section-heading"><div><h2>Conversation history</h2><p><?= count($conversations) ?> conversation<?= count($conversations) === 1 ? '' : 's' ?> found.</p></div><a class="btn btn-primary" href="<?= url('contact.php') ?>">+ New message</a></div>

    <?php if ($conversations): ?>
        <div class="support-list">
            <?php foreach ($conversations as $c): ?>
                <a class="support-list-item <?= (int)$c['unread_count'] > 0 ? 'has-unread' : '' ?>" href="<?= url('support_view.php?id='.(int)$c['conversation_id']) ?>">
                    <div class="support-list-main">
                        <div class="support-list-title"><strong>#<?= (int)$c['conversation_id'] ?> · <?= e($c['subject']) ?></strong><?php if((int)$c['unread_count']>0): ?><span class="message-badge"><?= (int)$c['unread_count'] ?> new</span><?php endif; ?></div>
                        <div class="muted"><?= (int)$c['message_count'] ?> message<?= (int)$c['message_count'] === 1 ? '' : 's' ?> · Last activity <?= e(date('d M Y, h:i A', strtotime($c['last_message_at']))) ?></div>
                    </div>
                    <span class="support-status <?= strtolower($c['status']) ?>"><?= e($c['status']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state"><div class="icon">✉</div><h3>No support messages yet</h3><p class="muted">Send your first message and the complete reply history will remain in your account.</p><a class="btn btn-primary" href="<?= url('contact.php') ?>">Contact support</a></div>
    <?php endif; ?>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
