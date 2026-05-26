<?php
session_start();
include('db_connection.php');

if (isset($_POST['register_btn'])) {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email     = mysqli_real_escape_string($conn, $_POST['email']);
    $company   = mysqli_real_escape_string($conn, $_POST['company_name']);
    $password  = mysqli_real_escape_string($conn, $_POST['password']);

    // Validate Gmail only
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !str_ends_with($email, '@gmail.com')) {
        $_SESSION['register_error'] = "Only @gmail.com addresses are allowed!";
        header("Location: index.php?modal=register");
        exit();
    }

    // Check if email already exists
    $check = mysqli_query($conn, "SELECT email FROM tbl_users WHERE email='$email' LIMIT 1");
    if (mysqli_num_rows($check) > 0) {
        $_SESSION['register_error'] = "That email is already registered. Please login instead.";
        header("Location: index.php?modal=register");
        exit();
    }

    // Insert new client
    $query = "INSERT INTO tbl_users (full_name, email, password, role, company_name) 
              VALUES ('$full_name', '$email', '$password', 'client', '$company')";

    if (mysqli_query($conn, $query)) {
        // FIX: use session flash message instead of JS alert, redirect to login modal
        $_SESSION['register_success'] = "Registration successful! You can now log in.";
        header("Location: index.php?modal=login");
    } else {
        $_SESSION['register_error'] = "Registration failed: " . mysqli_error($conn);
        header("Location: index.php?modal=register");
    }
    exit();
} else {
    header("Location: index.php");
    exit();
}
?>