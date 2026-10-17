<?php
/** POST /api/auth/login.php — sign in with email + password. */

require_once __DIR__ . '/../config.php';
require_method('POST');

$input    = json_input();
$email    = strtolower(trim($input['email'] ?? ''));
$password = (string) ($input['password'] ?? '');

if ($email === '' || $password === '') {
    json_error('Email and password are required.');
}

$stmt = db()->prepare('SELECT * FROM tbl_users WHERE LOWER(email) = ? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    json_error('Invalid email or password.', 401);
}

$stored  = (string) $user['password'];
$isHash  = str_starts_with($stored, '$2y$') || str_starts_with($stored, '$argon2');
$isValid = $isHash ? password_verify($password, $stored) : hash_equals($stored, $password);

if (!$isValid) {
    json_error('Invalid email or password.', 401);
}

// Upgrade legacy plaintext passwords to a secure hash on first successful login.
if (!$isHash) {
    $update = db()->prepare('UPDATE tbl_users SET password = ? WHERE id = ?');
    $update->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
}

json_response(['user' => login_user($user)]);
