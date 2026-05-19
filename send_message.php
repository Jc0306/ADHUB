<?php
session_start();
include('db_connection.php');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$sender_id  = $_SESSION['user_id'];
$project_id = intval($_POST['project_id'] ?? 0);
$message    = trim($_POST['message'] ?? '');

if ($project_id <= 0 || $message === '') {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit();
}

// Ownership check: clients can only message their own projects
if ($_SESSION['role'] === 'client') {
    $check = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT id FROM tbl_projects WHERE id = '$project_id' AND client_id = '$sender_id' LIMIT 1"));
    if (!$check) {
        echo json_encode(['success' => false, 'error' => 'Forbidden']);
        exit();
    }
}

$msg_escaped = mysqli_real_escape_string($conn, $message);
mysqli_query($conn, "INSERT INTO tbl_messages (project_id, sender_id, message) VALUES ('$project_id', '$sender_id', '$msg_escaped')");

$insert_id = mysqli_insert_id($conn);

$row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT m.*, u.full_name, u.role FROM tbl_messages m JOIN tbl_users u ON u.id = m.sender_id WHERE m.id = '$insert_id'"));

echo json_encode([
    'success'     => true,
    'message'     => htmlspecialchars($row['message']),
    'sender_name' => htmlspecialchars($row['full_name']),
    'role'        => $row['role'],
    'sender_id'   => $row['sender_id'],
    'sent_at'     => date('M d, H:i', strtotime($row['sent_at'])),
]);
?>