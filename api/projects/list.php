<?php
/** GET /api/projects/list.php — admin sees all projects, clients only their own. */

require_once __DIR__ . '/../config.php';
require_method('GET');
$user = require_login();

$sql = 'SELECT p.id, p.client_id, p.title, p.service_type, p.budget, p.status, p.progress_percent,
               p.created_at, u.full_name AS client_name, u.company_name AS client_company
        FROM tbl_projects p
        JOIN tbl_users u ON u.id = p.client_id';

if ($user['role'] === 'admin') {
    $stmt = db()->query($sql . ' ORDER BY p.created_at DESC');
} else {
    $stmt = db()->prepare($sql . ' WHERE p.client_id = ? ORDER BY p.created_at DESC');
    $stmt->execute([$user['id']]);
}

json_response(['projects' => $stmt->fetchAll()]);
