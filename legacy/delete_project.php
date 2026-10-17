<?php
session_start();
include('db_connection.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    exit("Unauthorized");
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    $sql = "DELETE FROM tbl_projects WHERE id = $id";
    
    if (mysqli_query($conn, $sql)) {
        header("Location: admin_dashboard.php?msg=deleted");
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>