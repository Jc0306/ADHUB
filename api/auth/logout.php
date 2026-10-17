<?php
/** POST /api/auth/logout.php — end the session. */

require_once __DIR__ . '/../config.php';
require_method('POST');

start_session();

$_SESSION = [];

// Also expire the session cookie in the browser.
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

json_response(['ok' => true]);
