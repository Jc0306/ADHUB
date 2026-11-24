<?php
/** POST /api/projects/deliverable-delete.php — remove a deliverable and its file (admin only). */

require_once __DIR__ . '/../config.php';
require_method('POST');
require_admin();

$input = json_input();
$id    = (int) ($input['id'] ?? 0);

$stmt = db()->prepare('SELECT * FROM tbl_deliverables WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$deliverable = $stmt->fetch();

if (!$deliverable) {
    json_error('Deliverable not found.', 404);
}

$full = realpath(__DIR__ . '/../../' . $deliverable['file_path']);
$base = realpath(__DIR__ . '/../../uploads/deliverables');

if ($full && $base && str_starts_with($full, $base . DIRECTORY_SEPARATOR)) {
    unlink($full);
}

$stmt = db()->prepare('DELETE FROM tbl_deliverables WHERE id = ?');
$stmt->execute([$id]);

json_response(['ok' => true]);
