<?php
/**
 * AdHub API — shared bootstrap.
 *
 * Every endpoint starts with:
 *   require_once __DIR__ . '/../config.php';
 *
 * Provides the PDO connection, JSON request/response helpers,
 * and session-based authentication helpers.
 */

declare(strict_types=1);

/* ------------------------------------------------------------------ Database */

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=127.0.0.1;dbname=adhubdb;charset=utf8mb4',
            'root',
            '',
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }

    return $pdo;
}

/* -------------------------------------------------------------- JSON helpers */

function json_response(mixed $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function json_error(string $message, int $status = 400): never
{
    json_response(['error' => $message], $status);
}

function json_input(): array
{
    $raw  = file_get_contents('php://input');
    $data = $raw !== false && $raw !== '' ? json_decode($raw, true) : null;

    return is_array($data) ? $data : $_POST;
}

function require_method(string $method): void
{
    if ($_SERVER['REQUEST_METHOD'] !== $method) {
        json_error('Method not allowed.', 405);
    }
}

/* ----------------------------------------------------------- Session helpers */

function start_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/** Logged-in user from the session, or null. Shape is safe for the frontend. */
function current_user(): ?array
{
    start_session();

    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    return [
        'id'        => (int) $_SESSION['user_id'],
        'full_name' => $_SESSION['full_name'] ?? '',
        'email'     => $_SESSION['email'] ?? '',
        'role'      => $_SESSION['role'] ?? 'client',
    ];
}

function require_login(): array
{
    $user = current_user();

    if ($user === null) {
        json_error('Authentication required.', 401);
    }

    return $user;
}

function require_admin(): array
{
    $user = require_login();

    if ($user['role'] !== 'admin') {
        json_error('Admin access required.', 403);
    }

    return $user;
}

/** Store the user in the session and return the public user shape. */
function login_user(array $row): array
{
    start_session();
    session_regenerate_id(true);

    $_SESSION['user_id']   = (int) $row['id'];
    $_SESSION['full_name'] = $row['full_name'];
    $_SESSION['email']     = $row['email'];
    $_SESSION['role']      = $row['role'];

    return public_user($row);
}

/** Map a tbl_users row to the user shape the frontend receives. */
function public_user(array $row): array
{
    return [
        'id'           => (int) $row['id'],
        'full_name'    => $row['full_name'],
        'email'        => $row['email'],
        'role'         => $row['role'],
        'company_name' => $row['company_name'] ?? null,
    ];
}

/* ----------------------------------------------------------- Project helpers */

/** Same set as tbl_projects.status (see DATABASE/adhubdb.sql). */
const PROJECT_STATUSES = ['pending', 'in-progress', 'revision', 'completed', 'rejected'];

/** Same set as tbl_requests.status (see DATABASE/adhubdb.sql). */
const REQUEST_STATUSES = ['open', 'accepted', 'done'];

/** Fetch one project with its client's display fields, or null. */
function find_project(int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT p.*, u.full_name AS client_name, u.company_name AS client_company, u.email AS client_email
         FROM tbl_projects p
         JOIN tbl_users u ON u.id = p.client_id
         WHERE p.id = ? LIMIT 1'
    );
    $stmt->execute([$id]);

    return $stmt->fetch() ?: null;
}

/** Admins can access any project; clients only their own. */
function require_project_access(array $user, array $project): void
{
    if ($user['role'] !== 'admin' && (int) $project['client_id'] !== $user['id']) {
        json_error('You do not have access to this project.', 403);
    }
}

/* ----------------------------------------------------------- Request helpers */

/** Fetch one service request with its client's display fields, or null. */
function find_request(int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT r.*, u.full_name AS client_name, u.company_name AS client_company
         FROM tbl_requests r
         JOIN tbl_users u ON u.id = r.client_id
         WHERE r.id = ? LIMIT 1'
    );
    $stmt->execute([$id]);

    return $stmt->fetch() ?: null;
}

/* -------------------------------------------------------------- Chat helpers */

/** Map a tbl_chat_messages row to the JSON shape the frontend expects. */
function public_message(array $row): array
{
    return [
        'id'         => (int) $row['id'],
        'client_id'  => (int) $row['client_id'],
        'sender_id'  => (int) $row['sender_id'],
        'body'       => $row['body'],
        'created_at' => $row['created_at'],
        'read_at'    => $row['read_at'],
    ];
}

/** Fetch one chat message, or null. */
function find_chat_message(int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT id, client_id, sender_id, body, created_at, read_at
         FROM tbl_chat_messages WHERE id = ? LIMIT 1'
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    return $row ? public_message($row) : null;
}
