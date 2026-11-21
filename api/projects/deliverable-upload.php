<?php
/** POST /api/projects/deliverable-upload.php — upload a deliverable file (admin only, multipart). */

require_once __DIR__ . '/../config.php';
require_method('POST');
require_admin();

$projectId = (int) ($_POST['project_id'] ?? 0);
$project   = find_project($projectId);

if (!$project) {
    json_error('Project not found.', 404);
}

if (!isset($_FILES['file']) || !is_array($_FILES['file'])) {
    json_error('No file uploaded.');
}

$file = $_FILES['file'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    $messages = [
        UPLOAD_ERR_INI_SIZE   => 'File exceeds the server upload limit.',
        UPLOAD_ERR_FORM_SIZE  => 'File is too large.',
        UPLOAD_ERR_PARTIAL    => 'Upload was interrupted, please try again.',
        UPLOAD_ERR_NO_FILE    => 'No file uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Server is missing a temporary folder.',
        UPLOAD_ERR_CANT_WRITE => 'Server failed to write the file.',
    ];
    json_error($messages[$file['error']] ?? 'Upload failed.', 400);
}

if ($file['size'] > 25 * 1024 * 1024) {
    json_error('File is larger than the 25 MB limit.');
}

$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'ppt', 'pptx', 'txt', 'zip'];
$ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($ext, $allowed, true)) {
    json_error('File type not allowed. Allowed types: ' . implode(', ', $allowed));
}

$displayName = trim($_POST['display_name'] ?? '');
if ($displayName === '') {
    $displayName = $file['name'];
}
if (mb_strlen($displayName) > 255) {
    json_error('Display name is too long (max 255 characters).');
}

$dir = __DIR__ . '/../../uploads/deliverables';
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}

$storedName = bin2hex(random_bytes(8)) . '.' . $ext;

if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $storedName)) {
    json_error('Failed to store the uploaded file.', 500);
}

// Uploading the same display name again creates the next version.
$stmt = db()->prepare('SELECT COALESCE(MAX(version), 0) + 1 FROM tbl_deliverables WHERE project_id = ? AND display_name = ?');
$stmt->execute([$projectId, $displayName]);
$version = (int) $stmt->fetchColumn();

$stmt = db()->prepare(
    'INSERT INTO tbl_deliverables (project_id, file_path, display_name, version) VALUES (?, ?, ?, ?)'
);
$stmt->execute([$projectId, 'uploads/deliverables/' . $storedName, $displayName, $version]);
$deliverableId = (int) db()->lastInsertId();

// New work moves a brand-new or in-revision project back to in-progress.
if (in_array($project['status'], ['pending', 'revision'], true)) {
    $stmt = db()->prepare("UPDATE tbl_projects SET status = 'in-progress' WHERE id = ?");
    $stmt->execute([$projectId]);
}

$stmt = db()->prepare(
    'SELECT id, project_id, file_path, display_name, version, approval_status, review_note, reviewed_at, uploaded_at
     FROM tbl_deliverables WHERE id = ?'
);
$stmt->execute([$deliverableId]);

json_response([
    'deliverable' => $stmt->fetch(),
    'project'     => find_project($projectId),
], 201);
