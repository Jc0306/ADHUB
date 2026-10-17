<?php
/**
 * GET /api/chat/list.php?project_id=&after_id= — a project's chat messages,
 * oldest first. after_id fetches only newer messages (polling). Admins can
 * open any project; clients only their own (404 otherwise).
 *
 * Opening/fetching a thread also marks the other side's messages as read.
 */

require_once __DIR__ . '/../config.php';
require_method('GET');
$user = require_login();

$projectId = (int) ($_GET['project_id'] ?? 0);
$afterId   = (int) ($_GET['after_id'] ?? 0);

if ($projectId <= 0) {
    json_error('project_id is required.');
}

require_chat_project($projectId, $user);

$stmt = db()->prepare(
    "SELECT id, project_id, sender_id, body, created_at, read_at
     FROM tbl_chat_messages
     WHERE project_id = ? AND id > ?
     ORDER BY id ASC"
);
$stmt->execute([$projectId, $afterId]);
$messages = array_map('public_message', $stmt->fetchAll());

$stmt = db()->prepare(
    "UPDATE tbl_chat_messages SET read_at = NOW()
     WHERE project_id = ? AND sender_id != ? AND read_at IS NULL"
);
$stmt->execute([$projectId, $user['id']]);

json_response(['messages' => $messages]);
