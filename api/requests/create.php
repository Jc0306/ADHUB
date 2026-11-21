<?php
/** POST /api/requests/create.php — a client sends a service request to the agency. */

require_once __DIR__ . '/../config.php';
require_method('POST');

$user = require_login();

if ($user['role'] !== 'client') {
    json_error('Only clients can send service requests.', 403);
}

$input   = json_input();
$title   = trim($input['title'] ?? '');
$message = trim($input['message'] ?? '');

if ($title === '') {
    json_error('Request title is required.');
}
if (mb_strlen($title) > 255) {
    json_error('Request title is too long (max 255 characters).');
}
if ($message === '') {
    json_error('Please describe what you need.');
}

$stmt = db()->prepare(
    "INSERT INTO tbl_requests (client_id, title, message, status) VALUES (?, ?, ?, 'open')"
);
$stmt->execute([$user['id'], $title, $message]);

json_response(['request' => find_request((int) db()->lastInsertId())], 201);
