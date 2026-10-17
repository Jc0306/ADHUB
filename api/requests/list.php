<?php
/** GET /api/requests/list.php — admins see all service requests, clients only their own. */

require_once __DIR__ . '/../config.php';
require_method('GET');
$user = require_login();

if ($user['role'] === 'admin') {
    $stmt = db()->query('SELECT r.*, u.full_name AS client_name, u.company_name AS client_company
                         FROM tbl_requests r
                         JOIN tbl_users u ON u.id = r.client_id
                         ORDER BY r.created_at DESC');
} else {
    $stmt = db()->prepare('SELECT r.* FROM tbl_requests r
                           WHERE r.client_id = ?
                           ORDER BY r.created_at DESC');
    $stmt->execute([$user['id']]);
}

json_response(['requests' => $stmt->fetchAll()]);
