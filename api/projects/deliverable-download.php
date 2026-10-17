<?php
/** GET /api/projects/deliverable-download.php?id= — stream a deliverable file (owner or admin). */

require_once __DIR__ . '/../config.php';
require_method('GET');
$user = require_login();

$stmt = db()->prepare(
    'SELECT d.*, p.client_id
     FROM tbl_deliverables d
     JOIN tbl_projects p ON p.id = d.project_id
     WHERE d.id = ? LIMIT 1'
);
$stmt->execute([(int) ($_GET['id'] ?? 0)]);
$deliverable = $stmt->fetch();

if (!$deliverable) {
    json_error('Deliverable not found.', 404);
}

if ($user['role'] !== 'admin' && (int) $deliverable['client_id'] !== $user['id']) {
    json_error('You do not have access to this file.', 403);
}

$full = realpath(__DIR__ . '/../../' . $deliverable['file_path']);
$base = realpath(__DIR__ . '/../../uploads/deliverables');

if ($full === false || $base === false || !str_starts_with($full, $base . DIRECTORY_SEPARATOR)) {
    json_error('File not found on the server.', 404);
}

$name      = $deliverable['display_name'];
$safeName  = preg_replace('/[^\w\-. ]+/u', '_', $name) ?: 'deliverable';

header('Content-Type: application/octet-stream');
header('Content-Length: ' . filesize($full));
header('Content-Disposition: attachment; filename="' . $safeName . '"; filename*=UTF-8\'\'' . rawurlencode($name));
readfile($full);
exit;
