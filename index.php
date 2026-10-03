<?php
// index.php - Main Entry Point Router
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

if (isLoggedIn()) {
    $role = $_SESSION['role'] ?? '';
    header("Location: " . getRoleDashboardUrl($role));
    exit;
} else {
    header("Location: " . BASE_URL . "/landing.php");
    exit;
}
