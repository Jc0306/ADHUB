<?php
/** GET /api/chat/unread.php — total unread chat messages for the current user. */

require_once __DIR__ . '/../config.php';
require_method('GET');
$user = require_login();

if ($user['role'] === 'admin') {
    $stmt = db()->prepare('SELECT COUNT(*) AS n FROM tbl_chat_messages WHERE sender_id != ? AND read_at IS NULL');
    $stmt->execute([$user['id']]);
} else {
    $stmt = db()->prepare('SELECT COUNT(*) AS n FROM tbl_chat_messages WHERE client_id = ? AND sender_id != ? AND read_at IS NULL');
    $stmt->execute([$user['id'], $user['id']]);
}

json_response(['unread' => (int) $stmt->fetch()['n']]);
