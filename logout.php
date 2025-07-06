<?php
session_start();

// Check which type of user is logged in before destroying the session.
$was_admin = isset($_SESSION['admin_id']);

// Unset all of the session variables.
session_unset();

// Destroy the session.
session_destroy();

// Redirect to the appropriate login page.
if ($was_admin) {
    header('Location: admin/login.php');
} else {
    header('Location: student/login.php');
}
exit;
?>
