<?php
/**
 * GET /api/chat/list.php?client_id=&project_id=&after_id= — messages in a
 * thread, oldest first. Threads: a client's General chat (no project_id) or
 * a per-project chat. Admins pick the client; clients always get their own.
 * after_id fetches only newer messages (polling).
 *
 * Opening/fetching a thread also marks the other side's messages as read.
 */

require_once __DIR__ . '/../config.php';
require_method('GET');
$user = require_login();

$afterId   = (int) ($_GET['after_id'] ?? 0);
$projectId = (int) ($_GET['project_id'] ?? 0);

if ($user['role'] === 'admin') {
    $clientId = (int) ($_GET['client_id'] ?? 0);

    $stmt = db()->prepare("SELECT id FROM tbl_users WHERE id = ? AND role = 'client' LIMIT 1");
    $stmt->execute([$clientId]);

    if (!$stmt->fetch()) {
        json_error('Client not found.', 404);
    }
} else {
    $clientId = $user['id'];
}

if ($projectId > 0) {
    require_chat_project($projectId, $clientId);
    $threadWhere = 'project_id = ?';
    $threadArgs  = [$projectId];
} else {
    $threadWhere = 'project_id IS NULL';
    $threadArgs  = [];
}

$stmt = db()->prepare(
    "SELECT id, client_id, project_id, sender_id, body, created_at, read_at
     FROM tbl_chat_messages
     WHERE client_id = ? AND $threadWhere AND id > ?
     ORDER BY id ASC"
);
$stmt->execute(array_merge([$clientId], $threadArgs, [$afterId]));
$messages = array_map('public_message', $stmt->fetchAll());

$stmt = db()->prepare(
    "UPDATE tbl_chat_messages SET read_at = NOW()
     WHERE client_id = ? AND $threadWhere AND sender_id != ? AND read_at IS NULL"
);
$stmt->execute(array_merge([$clientId], $threadArgs, [$user['id']]));

json_response(['messages' => $messages]);
