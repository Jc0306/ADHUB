<?php
/** GET /api/analytics/overview.php — admin dashboard metrics (charts). */

require_once __DIR__ . '/../config.php';
require_method('GET');
require_admin();

$statusCounts = db()->query(
    'SELECT status, COUNT(*) AS count FROM tbl_projects GROUP BY status'
)->fetchAll();

$clients = db()->query(
    "SELECT u.id AS client_id, u.full_name, u.company_name,
            COALESCE(r.retained, 0) AS retained,
            COALESCE(c.consumed, 0) AS consumed
     FROM tbl_users u
     LEFT JOIN (SELECT client_id, SUM(amount) AS retained FROM tbl_retainers GROUP BY client_id) r
            ON r.client_id = u.id
     LEFT JOIN (SELECT p.client_id, SUM(t.hours * t.hourly_rate) AS consumed
                FROM tbl_time_logs t JOIN tbl_projects p ON p.id = t.project_id
                GROUP BY p.client_id) c
            ON c.client_id = u.id
     WHERE u.role = 'client'
     ORDER BY u.full_name"
)->fetchAll();

$totalProjects  = (int) db()->query('SELECT COUNT(*) FROM tbl_projects')->fetchColumn();
$totalRetained  = (float) db()->query('SELECT COALESCE(SUM(amount), 0) FROM tbl_retainers')->fetchColumn();
$totalConsumed  = (float) db()->query('SELECT COALESCE(SUM(hours * hourly_rate), 0) FROM tbl_time_logs')->fetchColumn();

json_response([
    'status_counts'   => $statusCounts,
    'clients'         => $clients,
    'total_projects'  => $totalProjects,
    'total_retained'  => $totalRetained,
    'total_consumed'  => $totalConsumed,
]);
