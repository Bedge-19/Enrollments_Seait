<?php
// logout.php - Session Termination
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

if (isLoggedIn()) {
    logActivity('USER_LOGOUT', 'Authentication', 'users', $_SESSION['user_id'] ?? null, "User logged out");
}

session_unset();
session_destroy();

session_start();
setFlash('info', 'You have been successfully logged out.');
header("Location: " . BASE_URL . "/login.php");
exit;
