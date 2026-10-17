<?php
/**
 * GET /api/chat/threads.php — admin only: every client with the time of the
 * last chat message and how many client messages are still unread.
 */

require_once __DIR__ . '/../config.php';
require_method('GET');
require_admin();

$rows = db()->query(
    "SELECT u.id AS client_id, u.full_name, u.company_name,
            (SELECT MAX(created_at) FROM tbl_chat_messages m WHERE m.client_id = u.id) AS last_message_at,
            (SELECT COUNT(*) FROM tbl_chat_messages m
             WHERE m.client_id = u.id AND m.sender_id = u.id AND m.read_at IS NULL) AS unread
     FROM tbl_users u
     WHERE u.role = 'client'
     ORDER BY last_message_at DESC, u.full_name"
)->fetchAll();

$threads = array_map(static fn (array $row): array => [
    'client_id'       => (int) $row['client_id'],
    'full_name'       => $row['full_name'],
    'company_name'    => $row['company_name'],
    'last_message_at' => $row['last_message_at'],
    'unread'          => (int) $row['unread'],
], $rows);

json_response(['threads' => $threads]);
