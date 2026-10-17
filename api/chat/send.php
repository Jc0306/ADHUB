<?php
/**
 * POST /api/chat/send.php — post a message to a project's chat.
 *
 * Body: { project_id, body }
 */

require_once __DIR__ . '/../config.php';
require_method('POST');

$user  = require_login();
$input = json_input();
$body  = trim($input['body'] ?? '');

if ($body === '') {
    json_error('Message cannot be empty.');
}
if (mb_strlen($body) > 2000) {
    json_error('Message is too long (2000 characters max).');
}

$projectId = (int) ($input['project_id'] ?? 0);

if ($projectId <= 0) {
    json_error('project_id is required.');
}

require_chat_project($projectId, $user);

$stmt = db()->prepare('INSERT INTO tbl_chat_messages (project_id, sender_id, body) VALUES (?, ?, ?)');
$stmt->execute([$projectId, $user['id'], $body]);

json_response(['message' => find_chat_message((int) db()->lastInsertId())], 201);
