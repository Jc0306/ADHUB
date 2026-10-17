<?php
/** POST /api/projects/update.php?id= — edit project details (admin only). */

require_once __DIR__ . '/../config.php';
require_method('POST');
require_admin();

$id      = (int) ($_GET['id'] ?? 0);
$project = find_project($id);

if (!$project) {
    json_error('Project not found.', 404);
}

$input       = json_input();
$title       = trim($input['title'] ?? '');
$serviceType = trim($input['service_type'] ?? '');
$budget      = $input['budget'] ?? 0;
$description = trim($input['description'] ?? '');
$status      = $input['status'] ?? $project['status'];
$progress    = (int) ($input['progress_percent'] ?? 0);

if ($title === '') {
    json_error('Project title is required.');
}
if ($serviceType === '') {
    json_error('Service type is required.');
}
if (!is_numeric($budget) || (float) $budget < 0) {
    json_error('Budget must be a non-negative number.');
}
if (!in_array($status, PROJECT_STATUSES, true)) {
    json_error('Invalid status.');
}
if ($progress < 0 || $progress > 100) {
    json_error('Progress must be between 0 and 100.');
}

$stmt = db()->prepare(
    'UPDATE tbl_projects
     SET title = ?, service_type = ?, budget = ?, description = ?, status = ?, progress_percent = ?
     WHERE id = ?'
);
$stmt->execute([$title, $serviceType, (float) $budget, $description, $status, $progress, $id]);

json_response(['project' => find_project($id)]);
