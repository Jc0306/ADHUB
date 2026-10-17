<?php
/**
 * POST /api/chat/send.php — post a chat message.
 *
 * Body: { body, client_id?, project_id? } — client_id is required for admins
 * (which client), ignored for clients (their own threads). project_id picks a
 * project thread; omit it for the client's General chat.
 */

require_once __DIR__ . '/../config.php';
require_method('POST');

$user = require_login();
$input = json_input();
$body = trim($input['body'] ?? '');

if ($body === '') {
    json_error('Message cannot be empty.');
}
if (mb_strlen($body) > 2000) {
    json_error('Message is too long (2000 characters max).');
}

if ($user['role'] === 'admin') {
    $clientId = (int) ($input['client_id'] ?? 0);

    $stmt = db()->prepare("SELECT id FROM tbl_users WHERE id = ? AND role = 'client' LIMIT 1");
    $stmt->execute([$clientId]);

    if (!$stmt->fetch()) {
        json_error('Client not found.', 404);
    }
} else {
    $clientId = $user['id'];
}

$projectId = (int) ($input['project_id'] ?? 0);

if ($projectId > 0) {
    require_chat_project($projectId, $clientId);
}

$stmt = db()->prepare('INSERT INTO tbl_chat_messages (client_id, project_id, sender_id, body) VALUES (?, ?, ?, ?)');
$stmt->execute([$clientId, $projectId > 0 ? $projectId : null, $user['id'], $body]);

json_response(['message' => find_chat_message((int) db()->lastInsertId())], 201);
