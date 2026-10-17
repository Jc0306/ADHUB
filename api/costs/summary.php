<?php
/**
 * GET /api/costs/summary.php — retainer balances and consumed hours.
 *
 * Admin without ?client_id → overview of every client.
 * Admin or client with ?client_id → detail (client may only request their own).
 * Client without ?client_id → their own detail.
 */

require_once __DIR__ . '/../config.php';
require_method('GET');
$user = require_login();

$consumedSql = 'SELECT COALESCE(SUM(t.hours * t.hourly_rate), 0)
                FROM tbl_time_logs t
                JOIN tbl_projects p ON p.id = t.project_id
                WHERE p.client_id = ?';

$retainedSql = 'SELECT COALESCE(SUM(amount), 0) FROM tbl_retainers WHERE client_id = ?';

// ---- Client: always their own detail view ---------------------------------
if ($user['role'] !== 'admin') {
    $requested = (int) ($_GET['client_id'] ?? 0);

    if ($requested !== 0 && $requested !== $user['id']) {
        json_error('You do not have access to this client.', 403);
    }

    $clientId = $user['id'];
} else {
    $clientId = (int) ($_GET['client_id'] ?? 0);
}

// ---- Admin overview --------------------------------------------------------
if ($user['role'] === 'admin' && $clientId === 0) {
    $rows = db()->query(
        "SELECT u.id AS client_id, u.full_name, u.company_name,
                COALESCE(r.retained, 0) AS retained,
                COALESCE(c.consumed, 0) AS consumed,
                COALESCE(r.retained, 0) - COALESCE(c.consumed, 0) AS balance
         FROM tbl_users u
         LEFT JOIN (SELECT client_id, SUM(amount) AS retained FROM tbl_retainers GROUP BY client_id) r
                ON r.client_id = u.id
         LEFT JOIN (SELECT p.client_id, SUM(t.hours * t.hourly_rate) AS consumed
                    FROM tbl_time_logs t JOIN tbl_projects p ON p.id = t.project_id
                    GROUP BY p.client_id) c
                ON c.client_id = u.id
         WHERE u.role = 'client'
         ORDER BY u.full_name"
    )->fetchAll();

    json_response(['clients' => $rows]);
}

// ---- Detail for one client -------------------------------------------------
$stmt = db()->prepare('SELECT id, full_name, company_name FROM tbl_users WHERE id = ? LIMIT 1');
$stmt->execute([$clientId]);
$client = $stmt->fetch();

if (!$client) {
    json_error('Client not found.', 404);
}

if ($user['role'] !== 'admin' && (int) $client['id'] !== $user['id']) {
    json_error('You do not have access to this client.', 403);
}

$stmt = db()->prepare($retainedSql);
$stmt->execute([$clientId]);
$retained = (float) $stmt->fetchColumn();

$stmt = db()->prepare($consumedSql);
$stmt->execute([$clientId]);
$consumed = (float) $stmt->fetchColumn();

$stmt = db()->prepare(
    'SELECT p.id, p.title, COALESCE(SUM(t.hours * t.hourly_rate), 0) AS consumed
     FROM tbl_projects p
     LEFT JOIN tbl_time_logs t ON t.project_id = p.id
     WHERE p.client_id = ?
     GROUP BY p.id, p.title
     ORDER BY p.created_at DESC'
);
$stmt->execute([$clientId]);
$projects = $stmt->fetchAll();

$stmt = db()->prepare(
    'SELECT t.id, t.hours, t.hourly_rate, t.note, t.logged_at,
            p.title AS project_title,
            ROUND(t.hours * t.hourly_rate, 2) AS cost
     FROM tbl_time_logs t
     JOIN tbl_projects p ON p.id = t.project_id
     WHERE p.client_id = ?
     ORDER BY t.logged_at DESC, t.id DESC
     LIMIT 20'
);
$stmt->execute([$clientId]);
$logs = $stmt->fetchAll();

json_response([
    'client'   => $client,
    'retained' => $retained,
    'consumed' => $consumed,
    'balance'  => $retained - $consumed,
    'projects' => $projects,
    'logs'     => $logs,
]);
