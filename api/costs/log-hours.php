<?php
/**
 * POST /api/costs/log-hours.php — log agency hours on a project (admin only).
 * The cost (hours x rate) is automatically deducted from the client's retainer
 * because balances are computed from the time logs.
 */

require_once __DIR__ . '/../config.php';
require_method('POST');
$user = require_admin();

$input      = json_input();
$projectId  = (int) ($input['project_id'] ?? 0);
$hours      = $input['hours'] ?? 0;
$hourlyRate = $input['hourly_rate'] ?? 0;
$note       = trim($input['note'] ?? '');

if (!is_numeric($hours) || (float) $hours <= 0 || (float) $hours > 1000) {
    json_error('Hours must be a number between 0 and 1000.');
}
if (!is_numeric($hourlyRate) || (float) $hourlyRate <= 0 || (float) $hourlyRate > 10000) {
    json_error('Hourly rate must be a number between 0 and 10,000.');
}
if (strlen($note) > 255) {
    json_error('Note is too long (max 255 characters).');
}

$project = find_project($projectId);
if (!$project) {
    json_error('Project not found.', 404);
}

$stmt = db()->prepare('INSERT INTO tbl_time_logs (project_id, admin_id, hours, hourly_rate, note) VALUES (?, ?, ?, ?, ?)');
$stmt->execute([$projectId, $user['id'], (float) $hours, (float) $hourlyRate, $note]);
$logId = (int) db()->lastInsertId();

// Project cost after this log.
$stmt = db()->prepare('SELECT COALESCE(SUM(hours * hourly_rate), 0) FROM tbl_time_logs WHERE project_id = ?');
$stmt->execute([$projectId]);
$projectCost = (float) $stmt->fetchColumn();

// Client balance after this log.
$stmt = db()->prepare('SELECT COALESCE(SUM(amount), 0) FROM tbl_retainers WHERE client_id = ?');
$stmt->execute([$project['client_id']]);
$retained = (float) $stmt->fetchColumn();

$stmt = db()->prepare(
    'SELECT COALESCE(SUM(t.hours * t.hourly_rate), 0)
     FROM tbl_time_logs t JOIN tbl_projects p ON p.id = t.project_id
     WHERE p.client_id = ?'
);
$stmt->execute([$project['client_id']]);
$consumed = (float) $stmt->fetchColumn();

$stmt = db()->prepare(
    'SELECT t.id, t.hours, t.hourly_rate, t.note, t.logged_at, u.full_name AS admin_name,
            ROUND(t.hours * t.hourly_rate, 2) AS cost
     FROM tbl_time_logs t JOIN tbl_users u ON u.id = t.admin_id
     WHERE t.id = ?'
);
$stmt->execute([$logId]);

json_response([
    'log'          => $stmt->fetch(),
    'project_cost' => $projectCost,
    'retained'     => $retained,
    'consumed'     => $consumed,
    'balance'      => $retained - $consumed,
], 201);
