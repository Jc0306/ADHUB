<?php
session_start();
include('db_connection.php');

if (!$conn) {
    die("Database Connection Failed: " . mysqli_connect_error());
}

if (isset($_POST['login_btn'])) {
    $email    = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    $query  = "SELECT * FROM tbl_users WHERE email = '$email' AND password_hash = '$password' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);

        $_SESSION['user_id']      = $row['id'];
        $_SESSION['full_name']    = $row['full_name'];
        $_SESSION['company_name'] = $row['company_name'];
        $_SESSION['role']         = $row['role'];

        // FIX: redirect to the correct dashboard by role
        if ($row['role'] === 'admin') {
            header("Location: admin_dashboard.php");
        } else {
            header("Location: client_dashboard.php");
        }
        exit();
    } else {
        $_SESSION['login_error'] = "Invalid email or password. Please try again.";
        header("Location: index.php?modal=login");
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
?>