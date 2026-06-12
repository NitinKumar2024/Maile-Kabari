<?php
// Load environment variables
require_once __DIR__ . '/env.php';

// Error Reporting (adjust for production)
if (env('APP_DEBUG', false)) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

// Start PHP Session securely
if (session_status() === PHP_SESSION_NONE) {
    // Session cookie parameters for security
    session_set_cookie_params([
        'lifetime' => 0, // Session cookie expires when browser closes
        'path' => '/',
        'domain' => '',
        'secure' => env('SESSION_SECURE', false), // Set to true if running over HTTPS
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Global App constants
define('SITE_NAME', env('SITE_NAME', 'Maile Kabari'));
define('BASE_URL', env('BASE_URL', '/maile_kabari/'));

/**
 * Sanitize user input data
 */
function clean_input($data) {
    if ($data === null) return '';
    return htmlspecialchars(stripslashes(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Send JSON response and exit
 */
function send_json($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

/**
 * Redirect user to a page relative to BASE_URL
 */
function redirect($path) {
    header("Location: " . BASE_URL . ltrim($path, '/'));
    exit;
}

/**
 * Check if a user has a specific role
 */
function has_role($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

/**
 * Check if the user is authenticated
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}
?>
