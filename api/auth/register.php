<?php
/** POST /api/auth/register.php — create a client account and sign in. */

require_once __DIR__ . '/../config.php';
require_method('POST');

$input     = json_input();
$full_name = trim($input['full_name'] ?? '');
$email     = strtolower(trim($input['email'] ?? ''));
$company   = trim($input['company_name'] ?? '');
$password  = (string) ($input['password'] ?? '');

if ($full_name === '' || $email === '' || $password === '') {
    json_error('Full name, email, and password are required.');
}
if (mb_strlen($password) < 8) {
    json_error('Password must be at least 8 characters.');
}
if (mb_strlen($full_name) > 255) {
    json_error('Full name is too long (max 255 characters).');
}
if (mb_strlen($email) > 255) {
    json_error('Email is too long (max 255 characters).');
}
if (mb_strlen($company) > 255) {
    json_error('Company name is too long (max 255 characters).');
}

// Same rule as the legacy app: Gmail addresses only.
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !str_ends_with($email, '@gmail.com')) {
    json_error('Only @gmail.com addresses are allowed.');
}

$stmt = db()->prepare('SELECT id FROM tbl_users WHERE LOWER(email) = ? LIMIT 1');
$stmt->execute([$email]);

if ($stmt->fetch()) {
    json_error('That email is already registered. Please login instead.', 409);
}

$stmt = db()->prepare(
    'INSERT INTO tbl_users (full_name, email, password, role, company_name) VALUES (?, ?, ?, ?, ?)'
);
$stmt->execute([$full_name, $email, password_hash($password, PASSWORD_DEFAULT), 'client', $company]);

$stmt = db()->prepare('SELECT * FROM tbl_users WHERE id = ?');
$stmt->execute([(int) db()->lastInsertId()]);

json_response(['user' => login_user($stmt->fetch())], 201);
