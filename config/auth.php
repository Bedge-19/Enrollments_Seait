<?php
// config/auth.php - Session Authentication and Role Guards

require_once __DIR__ . '/db.php';

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['role']);
}

/**
 * Get current logged in user array
 */
function currentUser(): ?array {
    if (!isLoggedIn()) return null;
    return [
        'id'         => $_SESSION['user_id'],
        'username'   => $_SESSION['username'],
        'role'       => $_SESSION['role'],
        'staff_id'   => $_SESSION['staff_id'] ?? null,
        'student_id' => $_SESSION['student_id'] ?? null,
        'name'       => $_SESSION['full_name'] ?? $_SESSION['username']
    ];
}

/**
 * Enforce login
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        setFlash('warning', 'Please log in to continue.');
        header("Location: " . BASE_URL . "/login.php");
        exit;
    }
}

/**
 * Require specific role or roles
 * @param string|array $allowedRoles
 */
function requireRole(string|array $allowedRoles): void {
    requireLogin();
    
    $allowed = is_array($allowedRoles) ? $allowedRoles : [$allowedRoles];
    $userRole = $_SESSION['role'] ?? '';

    // Admin has access to administrative pages, but office-specific roles are checked
    if (!in_array($userRole, $allowed, true) && $userRole !== 'Admin') {
        setFlash('danger', 'Access denied. You do not have permission to view that page.');
        header("Location: " . getRoleDashboardUrl($userRole));
        exit;
    }
}

/**
 * Get standard home URL for a role
 */
function getRoleDashboardUrl(?string $role): string {
    return match ($role) {
        'Student'         => BASE_URL . '/student/index.php',
        'Department'      => BASE_URL . '/department/index.php',
        'Registrar'       => BASE_URL . '/registrar/index.php',
        'Accounting'      => BASE_URL . '/accounting/index.php',
        'Clinic'          => BASE_URL . '/clinic/index.php',
        'Security Office' => BASE_URL . '/security/index.php',
        'Admin'           => BASE_URL . '/admin/index.php',
        default           => BASE_URL . '/login.php'
    };
}

/**
 * CSRF Token Generator
 */
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * CSRF Token Validator
 */
function verifyCsrfToken(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Render hidden CSRF input field
 */
function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '">';
}
