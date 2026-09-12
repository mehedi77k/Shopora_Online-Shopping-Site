<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$id = max(1,(int)($_GET['id'] ?? $_POST['id'] ?? 0));
$errors = [];
$isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';

$stmt = $pdo->prepare('SELECT c.*, u.role AS account_role FROM contact_conversations c LEFT JOIN users u ON u.user_id=c.user_id WHERE c.conversation_id=? LIMIT 1');
$stmt->execute([$id]);
$conversation = $stmt->fetch();
if(!$conversation){ flash('error','Support conversation not found.'); redirect('admin/support.php'); }

if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    $action = $_POST['action'] ?? 'reply';
    $actorId = (int)$_SESSION['user']['user_id'];

    if($action==='reply'){
        $message = trim($_POST['message'] ?? '');
        if(text_length($message)<2 || text_length($message)>5000) $errors[]='Reply must be between 2 and 5000 characters.';
        if(!$errors){
            $stmt=$pdo->prepare("INSERT INTO contact_messages (conversation_id,sender_user_id,sender_role,message_type,sender_name,sender_email,message_text,seen_by_requester,seen_by_staff) VALUES (?,?,?,'staff',?,?,?,0,1)");
            $stmt->execute([$id,$actorId,current_role(),$_SESSION['user']['full_name'],$_SESSION['user']['email'],$message]);
            $pdo->prepare("UPDATE contact_conversations SET status='Answered',last_message_at=CURRENT_TIMESTAMP WHERE conversation_id=?")->execute([$id]);
            log_user_activity($pdo, $conversation['user_id'] ? (int)$conversation['user_id'] : null, 'support_staff_reply', 'Support staff replied to conversation #' . $id, ['conversation_id'=>$id], $actorId);
            if($isAjax){ header('Content-Type: application/json; charset=utf-8'); echo json_encode(['ok'=>true,'conversation_id'=>$id]); exit; }
            redirect('admin/support_view.php?id='.$id);
        }
    } elseif($action==='status'){
        $newStatus=$_POST['status'] ?? '';
        if(in_array($newStatus,['Open','Answered','Closed'],true)){
            $pdo->prepare('UPDATE contact_conversations SET status=? WHERE conversation_id=?')->execute([$newStatus,$id]);
            log_user_activity($pdo, $conversation['user_id'] ? (int)$conversation['user_id'] : null, 'support_status_changed', 'Support conversation #' . $id . ' status changed to ' . $newStatus, ['conversation_id'=>$id,'status'=>$newStatus], $actorId);
        }
        if($isAjax){ header('Content-Type: application/json; charset=utf-8'); echo json_encode(['ok'=>true,'conversation_id'=>$id]); exit; }
        redirect('admin/support_view.php?id='.$id);
    }
}

if($isAjax && $errors){ http_response_code(422); header('Content-Type: application/json; charset=utf-8'); echo json_encode(['ok'=>false,'error'=>implode(' ',$errors)]); exit; }

$pdo->prepare("UPDATE contact_messages SET seen_by_staff=1 WHERE conversation_id=? AND message_type='requester'")->execute([$id]);
$stmt=$pdo->prepare('SELECT * FROM contact_messages WHERE conversation_id=? ORDER BY message_id ASC');
$stmt->execute([$id]);
$messages=$stmt->fetchAll();
$adminPageTitle='Support #'.$id;
require __DIR__ . '/includes/admin_header.php';
?>
<div class="admin-toolbar"><div><span class="eyebrow">Support ticket #<?= $id ?></span><h1><?= e($conversation['subject']) ?></h1><p class="muted"><?= e($conversation['requester_name']) ?> · <?= e($conversation['requester_email']) ?> · <?= e($conversation['account_role'] ? support_role_label($conversation['account_role']) : 'Guest') ?></p></div><a class="btn btn-ghost" href="<?= url('admin/support.php') ?>">← Support Inbox</a></div>
<div class="support-thread-layout admin-support-layout">
    <div class="support-thread-card" data-support-thread data-conversation-id="<?= $id ?>">
        <?php foreach($messages as $m): $staff=$m['message_type']==='staff'; ?>
        <div class="support-message <?= $staff?'staff-message':'requester-message' ?>">
            <div class="support-message-meta"><strong><?= e($m['sender_name']) ?></strong><span><?= $staff?'Staff reply · ':'Requester · ' ?><?= e(support_role_label($m['sender_role'])) ?> · <?= e(date('d M Y, h:i A',strtotime($m['created_at']))) ?></span></div>
            <div class="support-message-body"><?= nl2br(e($m['message_text'])) ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    <aside class="admin-card support-reply-card">
        <div class="support-ticket-summary"><span>Status</span><span class="support-status <?= strtolower($conversation['status']) ?>" data-support-status><?= e($conversation['status']) ?></span></div>
        <?php if($conversation['user_id']): ?><a class="btn btn-ghost btn-block" href="<?= url('admin/user_history.php?email='.urlencode($conversation['requester_email'])) ?>">View complete user history</a><?php endif; ?>
        <h3>Send staff reply</h3>
        <?php if($errors): ?><div class="flash flash-error"><?= e(implode(' ',$errors)) ?></div><?php endif; ?>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="action" value="reply"><div class="form-group"><label>Reply</label><textarea class="form-control" name="message" maxlength="5000" required></textarea></div><div class="form-actions"><button class="btn btn-primary btn-block">Send reply</button></div></form>
        <form method="post" style="margin-top:16px"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="action" value="status"><div class="form-group"><label>Conversation status</label><select class="form-control" name="status"><?php foreach(['Open','Answered','Closed'] as $s): ?><option value="<?= $s ?>" <?= $conversation['status']===$s?'selected':'' ?>><?= $s ?></option><?php endforeach; ?></select></div><div class="form-actions"><button class="btn btn-ghost btn-block">Update status</button></div></form>
    </aside>
</div>
<?php require __DIR__ . '/includes/admin_footer.php'; ?>
