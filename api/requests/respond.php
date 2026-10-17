<?php
/**
 * POST /api/requests/respond.php — the agency replies to a service request and/or
 * updates its status (admin only).
 *
 * Body: { id, status: "open" | "accepted" | "done", reply? }
 */

require_once __DIR__ . '/../config.php';
require_method('POST');
require_admin();

$input  = json_input();
$id     = (int) ($input['id'] ?? 0);
$status = $input['status'] ?? '';
$reply  = trim($input['reply'] ?? '');

if (!in_array($status, REQUEST_STATUSES, true)) {
    json_error('Invalid status.');
}

$stmt = db()->prepare('SELECT id FROM tbl_requests WHERE id = ? LIMIT 1');
$stmt->execute([$id]);

if (!$stmt->fetch()) {
    json_error('Request not found.', 404);
}

if ($reply !== '') {
    $stmt = db()->prepare(
        'UPDATE tbl_requests SET status = ?, admin_reply = ?, replied_at = NOW() WHERE id = ?'
    );
    $stmt->execute([$status, $reply, $id]);
} else {
    $stmt = db()->prepare('UPDATE tbl_requests SET status = ? WHERE id = ?');
    $stmt->execute([$status, $id]);
}

json_response(['request' => find_request($id)]);
