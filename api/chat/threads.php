<?php
/**
 * GET /api/chat/threads.php — the current user's chat threads, most recent
 * first. Each thread is a client's General chat (project_id null) or one of
 * their project chats, with its unread count. Admins get every client's
 * threads; clients get their own.
 */

require_once __DIR__ . '/../config.php';
require_method('GET');
$user = require_login();

$isAdmin = $user['role'] === 'admin';

$generalSql = "SELECT u.id AS client_id, u.full_name, u.company_name,
        NULL AS project_id, NULL AS project_title,
        (SELECT MAX(created_at) FROM tbl_chat_messages m
          WHERE m.client_id = u.id AND m.project_id IS NULL) AS last_message_at,
        (SELECT COUNT(*) FROM tbl_chat_messages m
          WHERE m.client_id = u.id AND m.project_id IS NULL AND m.read_at IS NULL
            AND m.sender_id " . ($isAdmin ? '= u.id' : '!= u.id') . ") AS unread
    FROM tbl_users u
    WHERE u.role = 'client'" . ($isAdmin ? '' : ' AND u.id = ?');

$stmt = db()->prepare($generalSql);
$stmt->execute($isAdmin ? [] : [$user['id']]);
$general = $stmt->fetchAll();

$projectSql = "SELECT p.client_id, u.full_name, u.company_name,
        p.id AS project_id, p.title AS project_title,
        (SELECT MAX(created_at) FROM tbl_chat_messages m
          WHERE m.project_id = p.id) AS last_message_at,
        (SELECT COUNT(*) FROM tbl_chat_messages m
          WHERE m.project_id = p.id AND m.read_at IS NULL
            AND m.sender_id " . ($isAdmin ? '= p.client_id' : '!= p.client_id') . ") AS unread
    FROM tbl_projects p
    JOIN tbl_users u ON u.id = p.client_id" . ($isAdmin ? '' : ' WHERE p.client_id = ?');

$stmt = db()->prepare($projectSql);
$stmt->execute($isAdmin ? [] : [$user['id']]);
$projects = $stmt->fetchAll();

$threads = array_map(static fn (array $row): array => [
    'client_id'       => (int) $row['client_id'],
    'full_name'       => $row['full_name'],
    'company_name'    => $row['company_name'],
    'project_id'      => $row['project_id'] !== null ? (int) $row['project_id'] : null,
    'project_title'   => $row['project_title'],
    'last_message_at' => $row['last_message_at'],
    'unread'          => (int) $row['unread'],
], array_merge($general, $projects));

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
    if ($a['client_id'] !== $b['client_id']) {
        return $a['client_id'] <=> $b['client_id'];
    }
    if ($a['project_id'] === null) {
        return -1; // General first within the same client.
    }
    if ($b['project_id'] === null) {
        return 1;
    }
    return strcmp((string) $a['project_title'], (string) $b['project_title']);
});

json_response(['threads' => $threads]);
