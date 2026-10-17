<?php
session_start();
include('db_connection.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    exit("Unauthorized");
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    $sql = "UPDATE tbl_projects SET status = 'rejected' WHERE id = $id";

    if (mysqli_query($conn, $sql)) {
        header("Location: admin_dashboard.php?msg=rejected");
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>