<?php
/**
 * POST /api/requests/accept.php — accept a client's service request and turn
 * it into a campaign project that starts In Progress (admin only).
 *
 * Body: { id, service_type, budget, title?, reply? }
 */

require_once __DIR__ . '/../config.php';
require_method('POST');
require_admin();

$input       = json_input();
$id          = (int) ($input['id'] ?? 0);
$serviceType = trim($input['service_type'] ?? '');
$budget      = $input['budget'] ?? 0;
$reply       = trim($input['reply'] ?? '');

$request = find_request($id);

if (!$request) {
    json_error('Request not found.', 404);
}
if ($request['project_id'] !== null) {
    json_error('This request already has its project.', 409);
}
if ($request['status'] === 'done') {
    json_error('Only open requests can be accepted.', 409);
}

$title = trim($input['title'] ?? '');
if ($title === '') {
    $title = $request['title'];
}

if ($serviceType === '') {
    json_error('Service type is required.');
}
if (!is_numeric($budget) || (float) $budget < 0) {
    json_error('Budget must be a non-negative number.');
}

$pdo = db();

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        "INSERT INTO tbl_projects (client_id, title, service_type, budget, description, status, progress_percent)
         VALUES (?, ?, ?, ?, ?, 'in-progress', 0)"
    );
    $stmt->execute([
        (int) $request['client_id'],
        $title,
        $serviceType,
        (float) $budget,
        $request['message'],
    ]);
    $projectId = (int) $pdo->lastInsertId();

    if ($reply !== '') {
        $stmt = $pdo->prepare(
            "UPDATE tbl_requests
             SET status = 'accepted', project_id = ?, admin_reply = ?, replied_at = NOW()
             WHERE id = ?"
        );
        $stmt->execute([$projectId, $reply, $id]);
    } else {
        $stmt = $pdo->prepare(
            "UPDATE tbl_requests SET status = 'accepted', project_id = ? WHERE id = ?"
        );
        $stmt->execute([$projectId, $id]);
    }

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    json_error('Could not create the project. Please try again.', 500);
}

json_response([
    'request' => find_request($id),
    'project' => find_project($projectId),
], 201);
