<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
$userId = (int)$_SESSION['user']['user_id'];
$id = max(1, (int)($_GET['id'] ?? $_POST['id'] ?? 0));
$errors = [];
$isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';

$stmt = $pdo->prepare('SELECT * FROM contact_conversations WHERE conversation_id=? AND user_id=? LIMIT 1');
$stmt->execute([$id, $userId]);
$conversation = $stmt->fetch();
if (!$conversation) {
    flash('error', 'Support conversation not found or you do not have access to it.');
    redirect('support.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $message = trim($_POST['message'] ?? '');
    if (mb_strlen($message) < 2 || mb_strlen($message) > 5000) $errors[] = 'Reply must be between 2 and 5000 characters.';
    if ($conversation['status'] === 'Closed') $errors[] = 'This conversation is closed. Start a new support message if you still need help.';

    if (!$errors) {
        $stmt = $pdo->prepare(
            "INSERT INTO contact_messages
             (conversation_id, sender_user_id, sender_role, message_type, sender_name, sender_email, message_text, seen_by_requester, seen_by_staff)
             VALUES (?, ?, ?, 'requester', ?, ?, ?, 1, 0)"
        );
        $stmt->execute([$id, $userId, current_role(), $_SESSION['user']['full_name'], $_SESSION['user']['email'], $message]);
        $pdo->prepare("UPDATE contact_conversations SET status='Open', last_message_at=CURRENT_TIMESTAMP WHERE conversation_id=?")->execute([$id]);
        log_user_activity($pdo, $userId, 'support_reply', 'Replied to support conversation #' . $id, ['conversation_id' => $id], $userId);
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok'=>true,'conversation_id'=>$id]);
            exit;
        }
        redirect('support_view.php?id=' . $id);
    }
}

if ($isAjax && $errors) {
    http_response_code(422);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok'=>false,'error'=>implode(' ', $errors)]);
    exit;
}

// Opening the thread marks staff replies as read for this account.
$pdo->prepare("UPDATE contact_messages SET seen_by_requester=1 WHERE conversation_id=? AND message_type='staff'")->execute([$id]);
$stmt = $pdo->prepare('SELECT * FROM contact_messages WHERE conversation_id=? ORDER BY message_id ASC');
$stmt->execute([$id]);
$messages = $stmt->fetchAll();
$pageTitle = 'Support #' . $id;
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero"><div class="container page-heading"><span class="eyebrow">Support ticket #<?= $id ?></span><h1><?= e($conversation['subject']) ?></h1><p>Status: <span class="support-status <?= strtolower($conversation['status']) ?>" data-support-status><?= e($conversation['status']) ?></span></p></div></section>
<section class="section-sm"><div class="container support-thread-layout">
    <div class="support-thread-card" data-support-thread data-conversation-id="<?= $id ?>">
        <?php foreach ($messages as $m): $staff = $m['message_type'] === 'staff'; ?>
            <div class="support-message <?= $staff ? 'staff-message' : 'requester-message' ?>">
                <div class="support-message-meta"><strong><?= e($m['sender_name']) ?></strong><span><?= $staff ? 'Support staff · ' : '' ?><?= e(support_role_label($m['sender_role'])) ?> · <?= e(date('d M Y, h:i A', strtotime($m['created_at']))) ?></span></div>
                <div class="support-message-body"><?= nl2br(e($m['message_text'])) ?></div>
            </div>
        <?php endforeach; ?>
    </div>
    <aside class="admin-card support-reply-card">
        <h3>Reply</h3>
        <?php if ($errors): ?><div class="flash flash-error"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>
        <div data-support-reply-open <?= $conversation['status'] === 'Closed' ? 'hidden' : '' ?>>
            <form method="post" data-realtime-form="support-reply"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><div class="form-group"><label>Your message</label><textarea class="form-control" name="message" required maxlength="5000"></textarea></div><p class="form-inline-error" data-form-error hidden></p><div class="form-actions"><button class="btn btn-primary btn-block">Send reply</button></div></form>
        </div>
        <div data-support-reply-closed <?= $conversation['status'] !== 'Closed' ? 'hidden' : '' ?>>
            <p class="form-note form-note-block">This conversation is closed.</p><a class="btn btn-primary btn-block" href="<?= url('contact.php') ?>">Start new conversation</a>
        </div>
        <a class="btn btn-ghost btn-block" style="margin-top:10px" href="<?= url('support.php') ?>">← All messages</a>
    </aside>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
