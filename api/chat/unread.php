<?php
/** GET /api/chat/unread.php — total unread chat messages for the current user. */

require_once __DIR__ . '/../config.php';
require_method('GET');
$user = require_login();

if ($user['role'] === 'admin') {
    $stmt = db()->prepare('SELECT COUNT(*) AS n FROM tbl_chat_messages WHERE sender_id != ? AND read_at IS NULL');
    $stmt->execute([$user['id']]);
} else {
    $stmt = db()->prepare(
        'SELECT COUNT(*) AS n
         FROM tbl_chat_messages m
         JOIN tbl_projects p ON p.id = m.project_id
         WHERE p.client_id = ? AND m.sender_id != ? AND m.read_at IS NULL'
    );
    $stmt->execute([$user['id'], $user['id']]);
}

json_response(['unread' => (int) $stmt->fetch()['n']]);
