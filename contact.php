<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
$pageTitle = 'Contact';
$errors = [];
$name = is_logged_in() ? (string)$_SESSION['user']['full_name'] : '';
$email = is_logged_in() ? (string)$_SESSION['user']['email'] : '';
$subject = '';
$message = '';
$submittedTicket = max(0, (int)($_GET['ticket'] ?? 0));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    if (is_logged_in()) {
        // Never trust posted identity fields for an authenticated conversation.
        refresh_authenticated_user($pdo);
        $name = (string)$_SESSION['user']['full_name'];
        $email = (string)$_SESSION['user']['email'];
        $userId = (int)$_SESSION['user']['user_id'];
        $senderRole = current_role();
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim(strtolower($_POST['email'] ?? ''));
        $userId = null;
        $senderRole = 'guest';
    }

    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (text_length($name) < 2 || text_length($name) > 100) $errors[] = 'Enter a valid name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || text_length($email) > 150) $errors[] = 'Enter a valid email address.';
    if (text_length($subject) < 3 || text_length($subject) > 180) $errors[] = 'Subject must be between 3 and 180 characters.';
    if (text_length($message) < 5 || text_length($message) > 5000) $errors[] = 'Message must be between 5 and 5000 characters.';

    if (!$errors) {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare(
                "INSERT INTO contact_conversations (user_id, requester_name, requester_email, subject, status, last_message_at)
                 VALUES (?, ?, ?, ?, 'Open', CURRENT_TIMESTAMP)"
            );
            $stmt->execute([$userId, $name, $email, $subject]);
            $conversationId = (int)$pdo->lastInsertId();

            $stmt = $pdo->prepare(
                "INSERT INTO contact_messages
                 (conversation_id, sender_user_id, sender_role, message_type, sender_name, sender_email, message_text, seen_by_requester, seen_by_staff)
                 VALUES (?, ?, ?, 'requester', ?, ?, ?, 1, 0)"
            );
            $stmt->execute([$conversationId, $userId, $senderRole, $name, $email, $message]);
            $pdo->commit();

            // Logged-in conversations are also published by log_user_activity below.
            // Guest tickets need an explicit event because they have no account audit row.
            if ($userId === null) {
                realtime_notify('support.updated', ['conversation_id' => $conversationId, 'source' => 'guest_contact']);
            }

            if ($userId !== null) {
                log_user_activity($pdo, $userId, 'support_created', 'Created support conversation #' . $conversationId . ': ' . $subject, [
                    'conversation_id' => $conversationId,
                    'subject' => $subject,
                ], $userId);
                redirect('support_view.php?id=' . $conversationId);
            }

            $_SESSION['guest_support_ticket'] = $conversationId;
            redirect('contact.php?ticket=' . $conversationId);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = 'The message could not be saved. Please make sure the Support Center database migration has been imported.';
        }
    }
}

require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
    <div class="container page-heading">
        <span class="eyebrow">Contact us</span>
        <h1>How can we help?</h1>
        <p>Send a support message. Admin and Super Admin can review it and reply from the Support Inbox.</p>
        <?php if ($submittedTicket): ?><p class="submission-note">Ticket #<?= $submittedTicket ?> submitted.</p><?php endif; ?>
        <?php if (is_logged_in()): ?>
            <a class="btn btn-ghost btn-small" href="<?= url('support.php') ?>">View my support messages</a>
        <?php endif; ?>
    </div>
</section>
<section class="section-sm"><div class="container">
<form class="form-card" method="post">
    <?= csrf_field() ?>
    <?php if ($errors): ?><div class="flash flash-error"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>
    <?php if (!is_logged_in()): ?>
        <p class="form-note form-note-block">Guest messages are saved for the support team. Sign in to keep replies connected to your account.</p>
    <?php endif; ?>
    <div class="form-grid">
        <div class="form-group">
            <label>Name</label>
            <input class="form-control" name="name" value="<?= e($name) ?>" <?= is_logged_in() ? 'readonly' : '' ?> required maxlength="100">
        </div>
        <div class="form-group">
            <label>Email</label>
            <input class="form-control" type="email" name="email" value="<?= e($email) ?>" <?= is_logged_in() ? 'readonly' : '' ?> required maxlength="150">
        </div>
    </div>
    <div class="form-group" style="margin-top:14px">
        <label>Subject</label>
        <input class="form-control" name="subject" value="<?= e($subject) ?>" required maxlength="180">
    </div>
    <div class="form-group" style="margin-top:14px">
        <label>Message</label>
        <textarea class="form-control" name="message" required maxlength="5000"><?= e($message) ?></textarea>
    </div>
    <div class="form-actions"><button class="btn btn-primary">Send message</button></div>
</form>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
