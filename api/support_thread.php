<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (!is_logged_in() || !refresh_authenticated_user($pdo)) {
    http_response_code(401);
    echo json_encode(['ok'=>false,'error'=>'Authentication required.']);
    exit;
}

$id = max(1, (int)($_GET['id'] ?? 0));
$userId = (int)$_SESSION['user']['user_id'];
$staff = is_admin();

if ($staff) {
    $stmt = $pdo->prepare('SELECT c.*, u.role AS account_role FROM contact_conversations c LEFT JOIN users u ON u.user_id=c.user_id WHERE c.conversation_id=? LIMIT 1');
    $stmt->execute([$id]);
} else {
    $stmt = $pdo->prepare('SELECT c.*, u.role AS account_role FROM contact_conversations c LEFT JOIN users u ON u.user_id=c.user_id WHERE c.conversation_id=? AND c.user_id=? LIMIT 1');
    $stmt->execute([$id, $userId]);
}
$conversation = $stmt->fetch();

if (!$conversation) {
    http_response_code(404);
    echo json_encode(['ok'=>false,'error'=>'Conversation not found.']);
    exit;
}

if ($staff) {
    $pdo->prepare("UPDATE contact_messages SET seen_by_staff=1 WHERE conversation_id=? AND message_type='requester'")->execute([$id]);
} else {
    $pdo->prepare("UPDATE contact_messages SET seen_by_requester=1 WHERE conversation_id=? AND message_type='staff'")->execute([$id]);
}

$stmt = $pdo->prepare('SELECT message_id, sender_user_id, sender_role, message_type, sender_name, message_text, created_at FROM contact_messages WHERE conversation_id=? ORDER BY message_id ASC');
$stmt->execute([$id]);
$messages = [];
foreach ($stmt->fetchAll() as $m) {
    $messages[] = [
        'message_id' => (int)$m['message_id'],
        'sender_user_id' => $m['sender_user_id'] !== null ? (int)$m['sender_user_id'] : null,
        'sender_role' => $m['sender_role'],
        'role_label' => support_role_label((string)$m['sender_role']),
        'message_type' => $m['message_type'],
        'sender_name' => $m['sender_name'],
        'message_text' => $m['message_text'],
        'created_at' => $m['created_at'],
        'created_at_display' => date('d M Y, h:i A', strtotime($m['created_at'])),
    ];
}

echo json_encode([
    'ok' => true,
    'conversation' => [
        'conversation_id' => (int)$conversation['conversation_id'],
        'subject' => $conversation['subject'],
        'status' => $conversation['status'],
        'requester_name' => $conversation['requester_name'],
        'requester_email' => $conversation['requester_email'],
        'account_role' => $conversation['account_role'] ?? null,
        'last_message_at' => $conversation['last_message_at'],
    ],
    'messages' => $messages,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
