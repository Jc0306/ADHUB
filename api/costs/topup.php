<?php
/** POST /api/costs/topup.php — add a pre-paid retainer amount for a client (admin only). */

require_once __DIR__ . '/../config.php';
require_method('POST');
require_admin();

$input    = json_input();
$clientId = (int) ($input['client_id'] ?? 0);
$amount   = $input['amount'] ?? 0;
$note     = trim($input['note'] ?? '');

if (!is_numeric($amount) || (float) $amount <= 0) {
    json_error('Amount must be a positive number.');
}
if ((float) $amount > 1000000) {
    json_error('Amount exceeds the $1,000,000 limit.');
}

$stmt = db()->prepare("SELECT id FROM tbl_users WHERE id = ? AND role = 'client' LIMIT 1");
$stmt->execute([$clientId]);
if (!$stmt->fetch()) {
    json_error('Client not found.', 404);
}

$stmt = db()->prepare('INSERT INTO tbl_retainers (client_id, amount, note) VALUES (?, ?, ?)');
$stmt->execute([$clientId, (float) $amount, $note]);

$stmt = db()->prepare('SELECT COALESCE(SUM(amount), 0) FROM tbl_retainers WHERE client_id = ?');
$stmt->execute([$clientId]);
$retained = (float) $stmt->fetchColumn();

$stmt = db()->prepare(
    'SELECT COALESCE(SUM(t.hours * t.hourly_rate), 0)
     FROM tbl_time_logs t JOIN tbl_projects p ON p.id = t.project_id
     WHERE p.client_id = ?'
);
$stmt->execute([$clientId]);
$consumed = (float) $stmt->fetchColumn();

json_response([
    'retained' => $retained,
    'consumed' => $consumed,
    'balance'  => $retained - $consumed,
], 201);
