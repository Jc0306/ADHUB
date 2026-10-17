<?php
/** GET /api/auth/session.php — current user (or null). Used on app load. */

require_once __DIR__ . '/../config.php';
require_method('GET');

json_response(['user' => current_user()]);
