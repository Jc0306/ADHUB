<?php
/**
 * POST /api/projects/deliverable-review.php — client approves or requests revision.
 *
 * Body: { id, action: "approve" | "revision", note? }
 *
 * Workflow rules:
 *  - approve:  deliverable -> approved. If every latest version in the project is
 *              approved, the project becomes completed at 100%.
 *  - revision: deliverable -> revision (+note), project -> revision.
 */

require_once __DIR__ . '/../config.php';
require_method('POST');

$user = require_login();

if ($user['role'] !== 'client') {
    json_error('Only the client can review deliverables.', 403);
}

$input  = json_input();
$id     = (int) ($input['id'] ?? 0);
$action = $input['action'] ?? '';
$note   = trim($input['note'] ?? '');

if (!in_array($action, ['approve', 'revision'], true)) {
    json_error('Invalid action. Use "approve" or "revision".');
}

$stmt = db()->prepare(
    'SELECT d.id, d.project_id, p.client_id FROM tbl_deliverables d
     JOIN tbl_projects p ON p.id = d.project_id
     WHERE d.id = ? LIMIT 1'
);
$stmt->execute([$id]);
$deliverable = $stmt->fetch();

if (!$deliverable) {
    json_error('Deliverable not found.', 404);
}

if ((int) $deliverable['client_id'] !== $user['id']) {
    json_error('You do not have access to this deliverable.', 403);
}

$projectId = (int) $deliverable['project_id'];

if ($action === 'approve') {
    $stmt = db()->prepare(
        "UPDATE tbl_deliverables SET approval_status = 'approved', review_note = NULL, reviewed_at = NOW() WHERE id = ?"
    );
    $stmt->execute([$id]);

    // Project is complete when every latest version has been approved.
    $stmt = db()->prepare(
        "SELECT COUNT(*) AS total, SUM(d.approval_status = 'approved') AS approved
         FROM tbl_deliverables d
         JOIN (
             SELECT display_name, MAX(version) AS v
             FROM tbl_deliverables WHERE project_id = ? GROUP BY display_name
         ) latest ON latest.display_name = d.display_name AND latest.v = d.version
         WHERE d.project_id = ?"
    );
    $stmt->execute([$projectId, $projectId]);
    $counts = $stmt->fetch();

    if ((int) $counts['total'] > 0 && (int) $counts['total'] === (int) $counts['approved']) {
        $stmt = db()->prepare("UPDATE tbl_projects SET status = 'completed', progress_percent = 100 WHERE id = ?");
        $stmt->execute([$projectId]);
    }
} else {
    $stmt = db()->prepare(
        "UPDATE tbl_deliverables SET approval_status = 'revision', review_note = ?, reviewed_at = NOW() WHERE id = ?"
    );
    $stmt->execute([$note, $id]);

    $stmt = db()->prepare("UPDATE tbl_projects SET status = 'revision' WHERE id = ?");
    $stmt->execute([$projectId]);
}

$stmt = db()->prepare(
    'SELECT id, project_id, file_path, display_name, version, approval_status, review_note, reviewed_at, uploaded_at
     FROM tbl_deliverables WHERE id = ?'
);
$stmt->execute([$id]);

json_response([
    'deliverable' => $stmt->fetch(),
    'project'     => find_project($projectId),
]);
