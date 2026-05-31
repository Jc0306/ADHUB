<?php
session_start();
include('db_connection.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

if (isset($_POST['update_btn'])) {
    $project_id     = intval($_POST['project_id']);

    // Whitelist status against the DB enum
    $allowed_statuses = ['pending', 'in-progress', 'revision', 'completed', 'rejected'];
    $status = in_array($_POST['status'], $allowed_statuses) ? $_POST['status'] : 'pending';

    // Whitelist payment_status against known values
    $allowed_payment = ['unpaid', 'partial', 'paid'];
    $payment_status = in_array($_POST['payment_status'], $allowed_payment) ? $_POST['payment_status'] : 'unpaid';

    $progress = intval($_POST['progress_percent']);

    $sql = "UPDATE tbl_projects SET 
                status = '$status', 
                payment_status = '$payment_status',
                progress_percent = '$progress'
            WHERE id = '$project_id'";

    if (mysqli_query($conn, $sql)) {
        header("Location: admin_dashboard.php?msg=updated");
    } else {
        echo "Error updating record: " . mysqli_error($conn);
    }
}
?>