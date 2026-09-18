<?php
// Session Management & Role Security Middleware
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if a user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current logged in user array
 */
function get_logged_user() {
    if (is_logged_in()) {
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'] ?? 'User',
            'email' => $_SESSION['user_email'] ?? '',
            'role' => $_SESSION['user_role'] ?? 'student'
        ];
    }
    return null;
}

/**
 * Require specific role or redirect
 */
function require_role($allowed_roles) {
    if (!is_logged_in()) {
        header("Location: ../login.php?msg=please_login");
        exit();
    }

    if (is_string($allowed_roles)) {
        $allowed_roles = [$allowed_roles];
    }

    $user_role = $_SESSION['user_role'] ?? '';
    if (!in_array($user_role, $allowed_roles)) {
        // Redirect based on actual role
        if ($user_role === 'admin') {
            header("Location: ../admin/dashboard.php?err=unauthorized");
        } elseif ($user_role === 'owner') {
            header("Location: ../owner/dashboard.php?err=unauthorized");
        } else {
            header("Location: ../student/dashboard.php?err=unauthorized");
        }
        exit();
    }
}
?>
