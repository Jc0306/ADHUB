<?php
/** POST /api/projects/delete.php?id= — delete a project and its files (admin only). */

require_once __DIR__ . '/../config.php';
require_method('POST');
require_admin();

$id      = (int) ($_GET['id'] ?? 0);
$project = find_project($id);

if (!$project) {
    json_error('Project not found.', 404);
}

// Remove deliverable files from disk first (their rows cascade away with the project).
$stmt = db()->prepare('SELECT file_path FROM tbl_deliverables WHERE project_id = ?');
$stmt->execute([$id]);

$base = realpath(__DIR__ . '/../../uploads/deliverables');

foreach ($stmt->fetchAll() as $row) {
    $full = realpath(__DIR__ . '/../../' . $row['file_path']);

    if ($full && $base && str_starts_with($full, $base . DIRECTORY_SEPARATOR)) {
        unlink($full);
    }
}

$stmt = db()->prepare('DELETE FROM tbl_projects WHERE id = ?');
$stmt->execute([$id]);

json_response(['ok' => true]);
