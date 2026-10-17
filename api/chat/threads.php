<?php
/**
 * GET /api/chat/threads.php — project chat threads, most recent first. Each
 * thread is a campaign project with its client, last message time and unread
 * count. Admins get every project; clients get their own projects only.
 */

require_once __DIR__ . '/../config.php';
require_method('GET');
$user = require_login();

$isAdmin = $user['role'] === 'admin';

$sql = "SELECT p.id AS project_id, p.title AS project_title,
        u.id AS client_id, u.full_name, u.company_name,
        (SELECT MAX(created_at) FROM tbl_chat_messages m
          WHERE m.project_id = p.id) AS last_message_at,
        (SELECT COUNT(*) FROM tbl_chat_messages m
          WHERE m.project_id = p.id AND m.read_at IS NULL
            AND m.sender_id != ?) AS unread
    FROM tbl_projects p
    JOIN tbl_users u ON u.id = p.client_id"
    . ($isAdmin ? '' : ' WHERE p.client_id = ?');

$stmt = db()->prepare($sql);
$stmt->execute($isAdmin ? [$user['id']] : [$user['id'], $user['id']]);

$threads = array_map(static fn (array $row): array => [
    'project_id'      => (int) $row['project_id'],
    'project_title'   => $row['project_title'],
    'client_id'       => (int) $row['client_id'],
    'full_name'       => $row['full_name'],
    'company_name'    => $row['company_name'],
    'last_message_at' => $row['last_message_at'],
    'unread'          => (int) $row['unread'],
], $stmt->fetchAll());

usort($threads, static function (array $a, array $b): int {
    if ($a['last_message_at'] !== $b['last_message_at']) {
        if ($a['last_message_at'] === null) {
            return 1;
        }
        if ($b['last_message_at'] === null) {
            return -1;
        }
        return strcmp($b['last_message_at'], $a['last_message_at']);
    }

    return strcmp((string) $a['project_title'], (string) $b['project_title'])
        ?: ($a['project_id'] <=> $b['project_id']);
});

json_response(['threads' => $threads]);
