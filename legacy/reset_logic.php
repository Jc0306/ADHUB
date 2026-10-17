<?php
session_start();
$_SESSION['login_error'] = "Password reset is not available yet. Please contact the admin.";
header("Location: index.php?modal=login");
exit();
?>