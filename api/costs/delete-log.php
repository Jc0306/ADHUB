<?php
/** POST /api/costs/delete-log.php — remove a wrongly logged time entry (admin only). */

require_once __DIR__ . '/../config.php';
require_method('POST');
require_admin();

$input = json_input();
$id    = (int) ($input['id'] ?? 0);

$stmt = db()->prepare('SELECT id, project_id FROM tbl_time_logs WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$log = $stmt->fetch();

if (!$log) {
    json_error('Time log not found.', 404);
}

$stmt = db()->prepare('DELETE FROM tbl_time_logs WHERE id = ?');
$stmt->execute([$id]);

// Updated project cost after the removal.
$stmt = db()->prepare('SELECT COALESCE(SUM(hours * hourly_rate), 0) FROM tbl_time_logs WHERE project_id = ?');
$stmt->execute([$log['project_id']]);

json_response(['ok' => true, 'project_cost' => (float) $stmt->fetchColumn()]);
