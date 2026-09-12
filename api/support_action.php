<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

function support_json_error(string $message, int $status = 400): never
{
    http_response_code($status);
    echo json_encode(['ok' => false, 'error' => $message], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    support_json_error('Method not allowed.', 405);
}

if (!is_logged_in() || !refresh_authenticated_user($pdo)) {
    support_json_error('Your session has expired. Please sign in again.', 401);
}

$token = (string)($_POST['csrf_token'] ?? '');
if ($token === '' || !hash_equals((string)($_SESSION['csrf_token'] ?? ''), $token)) {
    support_json_error('Your form session has expired. Refresh the page and try again.', 419);
}

$id = max(0, (int)($_POST['id'] ?? 0));
if ($id < 1) {
    support_json_error('Invalid support conversation.', 422);
}

$userId = (int)$_SESSION['user']['user_id'];
$staff = is_admin();
$action = (string)($_POST['action'] ?? 'reply');

try {
    if ($staff) {
        $stmt = $pdo->prepare('SELECT c.*, u.role AS account_role FROM contact_conversations c LEFT JOIN users u ON u.user_id=c.user_id WHERE c.conversation_id=? LIMIT 1');
        $stmt->execute([$id]);
    } else {
        $stmt = $pdo->prepare('SELECT c.*, u.role AS account_role FROM contact_conversations c LEFT JOIN users u ON u.user_id=c.user_id WHERE c.conversation_id=? AND c.user_id=? LIMIT 1');
        $stmt->execute([$id, $userId]);
    }
    $conversation = $stmt->fetch();

    if (!$conversation) {
        support_json_error('Support conversation not found or you do not have access to it.', 404);
    }

    if ($action === 'reply') {
        $message = trim((string)($_POST['message'] ?? ''));
        $length = text_length($message);
        if ($length < 2 || $length > 5000) {
            support_json_error('Reply must be between 2 and 5000 characters.', 422);
        }
        if (!$staff && $conversation['status'] === 'Closed') {
            support_json_error('This conversation is closed. Start a new support message if you still need help.', 422);
        }

        $pdo->beginTransaction();

        $messageType = $staff ? 'staff' : 'requester';
        $seenByRequester = $staff ? 0 : 1;
        $seenByStaff = $staff ? 1 : 0;

        $stmt = $pdo->prepare(
            'INSERT INTO contact_messages
             (conversation_id, sender_user_id, sender_role, message_type, sender_name, sender_email, message_text, seen_by_requester, seen_by_staff)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $id,
            $userId,
            current_role(),
            $messageType,
            (string)$_SESSION['user']['full_name'],
            (string)$_SESSION['user']['email'],
            $message,
            $seenByRequester,
            $seenByStaff,
        ]);

        $newStatus = $staff ? 'Answered' : 'Open';
        $stmt = $pdo->prepare('UPDATE contact_conversations SET status=?, last_message_at=CURRENT_TIMESTAMP WHERE conversation_id=?');
        $stmt->execute([$newStatus, $id]);

        $pdo->commit();

        if ($staff) {
            log_user_activity(
                $pdo,
                $conversation['user_id'] ? (int)$conversation['user_id'] : null,
                'support_staff_reply',
                'Support staff replied to conversation #' . $id,
                ['conversation_id' => $id],
                $userId
            );
        } else {
            log_user_activity(
                $pdo,
                $userId,
                'support_reply',
                'Replied to support conversation #' . $id,
                ['conversation_id' => $id],
                $userId
            );
        }


        echo json_encode([
            'ok' => true,
            'conversation_id' => $id,
            'status' => $newStatus,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($action === 'status') {
        if (!$staff) {
            support_json_error('Only Admin or Super Admin can change the conversation status.', 403);
        }

        $newStatus = (string)($_POST['status'] ?? '');
        if (!in_array($newStatus, ['Open', 'Answered', 'Closed'], true)) {
            support_json_error('Invalid conversation status.', 422);
        }

        $stmt = $pdo->prepare('UPDATE contact_conversations SET status=? WHERE conversation_id=?');
        $stmt->execute([$newStatus, $id]);

        log_user_activity(
            $pdo,
            $conversation['user_id'] ? (int)$conversation['user_id'] : null,
            'support_status_changed',
            'Support conversation #' . $id . ' status changed to ' . $newStatus,
            ['conversation_id' => $id, 'status' => $newStatus],
            $userId
        );


        echo json_encode([
            'ok' => true,
            'conversation_id' => $id,
            'status' => $newStatus,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    support_json_error('Unknown support action.', 422);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Shopora support action failed: ' . $e->getMessage());
    support_json_error('The support reply could not be saved. Please refresh the page and try again.', 500);
}
