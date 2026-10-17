<?php
/** GET /api/projects/show.php?id= — one project with its deliverables (owner or admin). */

require_once __DIR__ . '/../config.php';
require_method('GET');
$user = require_login();

$project = find_project((int) ($_GET['id'] ?? 0));

if (!$project) {
    json_error('Project not found.', 404);
}

require_project_access($user, $project);

$stmt = db()->prepare(
    'SELECT id, project_id, file_path, display_name, version, approval_status, review_note, reviewed_at, uploaded_at
     FROM tbl_deliverables WHERE project_id = ? ORDER BY uploaded_at DESC, id DESC'
);
$stmt->execute([$project['id']]);
$deliverables = $stmt->fetchAll();

// Billed hours for this project.
$stmt = db()->prepare('SELECT COALESCE(SUM(hours * hourly_rate), 0) FROM tbl_time_logs WHERE project_id = ?');
$stmt->execute([$project['id']]);
$cost = (float) $stmt->fetchColumn();

$stmt = db()->prepare(
    'SELECT t.id, t.hours, t.hourly_rate, t.note, t.logged_at, u.full_name AS admin_name
     FROM tbl_time_logs t JOIN tbl_users u ON u.id = t.admin_id
     WHERE t.project_id = ? ORDER BY t.logged_at DESC, t.id DESC'
);
$stmt->execute([$project['id']]);
$logs = $stmt->fetchAll();

json_response([
    'project'      => $project,
    'deliverables' => $deliverables,
    'cost'         => $cost,
    'logs'         => $logs,
]);
