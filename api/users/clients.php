<?php
/** GET /api/users/clients.php — client accounts for project assignment (admin only). */

require_once __DIR__ . '/../config.php';
require_method('GET');
require_admin();

$rows = db()->query(
    "SELECT id, full_name, company_name, email FROM tbl_users WHERE role = 'client' ORDER BY full_name"
)->fetchAll();

json_response(['clients' => $rows]);
