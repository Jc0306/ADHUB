<?php
/** POST /api/projects/create.php — create a campaign project for a client (admin only). */

require_once __DIR__ . '/../config.php';
require_method('POST');
require_admin();

$input       = json_input();
$clientId    = (int) ($input['client_id'] ?? 0);
$title       = trim($input['title'] ?? '');
$serviceType = trim($input['service_type'] ?? '');
$budget      = $input['budget'] ?? 0;
$description = trim($input['description'] ?? '');

if ($title === '') {
    json_error('Project title is required.');
}
if ($serviceType === '') {
    json_error('Service type is required.');
}
if (!is_numeric($budget) || (float) $budget < 0) {
    json_error('Budget must be a non-negative number.');
}

$stmt = db()->prepare('SELECT id, role FROM tbl_users WHERE id = ? LIMIT 1');
$stmt->execute([$clientId]);
$client = $stmt->fetch();

if (!$client || $client['role'] !== 'client') {
    json_error('Selected client was not found.', 404);
}

$stmt = db()->prepare(
    "INSERT INTO tbl_projects (client_id, title, service_type, budget, description, status, progress_percent)
     VALUES (?, ?, ?, ?, ?, 'pending', 0)"
);
$stmt->execute([$clientId, $title, $serviceType, (float) $budget, $description]);

json_response(['project' => find_project((int) db()->lastInsertId())], 201);
