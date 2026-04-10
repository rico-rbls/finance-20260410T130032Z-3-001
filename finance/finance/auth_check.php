<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Ensures the user is logged in. 
 * If not, redirects to login.
 */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

/**
 * Restricts access based on user roles.
 * Usage: restrictTo(['admin', 'finance_officer']);
 */
function restrictTo($allowed_roles) {
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
        // You can customize this error page or redirect to dashboard
        die("
            <div style='font-family: sans-serif; text-align: center; margin-top: 100px;'>
                <h1 style='color: #dc3545;'>403 - Access Denied</h1>
                <p>You do not have permission to access this module.</p>
                <a href='dashboard.php' style='color: #0d6efd;'>Return to Dashboard</a>
            </div>
        ");
    }
}
?>