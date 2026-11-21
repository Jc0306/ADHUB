<?php
/**
 * POST /api/requests/accept.php — accept a client's service request and open
 * its campaign project, starting In Progress (admin only).
 *
 * Body: { id }
 *
 * The project starts from the request's title and message; budget, service
 * type and description are refined inside the project itself (chat + details).
 */

require_once __DIR__ . '/../config.php';
require_method('POST');
require_admin();

$input = json_input();
$id    = (int) ($input['id'] ?? 0);

$pdo = db();

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        "SELECT id, client_id, title, message, status, project_id FROM tbl_requests WHERE id = ? FOR UPDATE"
    );
    $stmt->execute([$id]);
    $request = $stmt->fetch();

    if (!$request) {
        $pdo->rollBack();
        json_error('Request not found.', 404);
    }
    if ($request['project_id'] !== null) {
        $pdo->rollBack();
        json_error('This request already has its project.', 409);
    }
    if ($request['status'] === 'done') {
        $pdo->rollBack();
        json_error('Only open requests can be accepted.', 409);
    }

    $stmt = $pdo->prepare(
        "INSERT INTO tbl_projects (client_id, title, service_type, budget, description, status, progress_percent)
         VALUES (?, ?, '', 0, ?, 'in-progress', 0)"
    );
    $stmt->execute([(int) $request['client_id'], $request['title'], $request['message']]);
    $projectId = (int) $pdo->lastInsertId();

    $stmt = $pdo->prepare(
        "UPDATE tbl_requests SET status = 'accepted', project_id = ? WHERE id = ?"
    );
    $stmt->execute([$projectId, $id]);

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    json_error('Could not create the project. Please try again.', 500);
}

json_response([
    'request' => find_request($id),
    'project' => find_project($projectId),
], 201);
