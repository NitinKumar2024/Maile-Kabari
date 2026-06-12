<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/app.php';

// If user has remember-me cookie, remove it from database to invalidate it
if (isset($_COOKIE['remember_me'])) {
    $parts = explode(':', $_COOKIE['remember_me'], 2);
    if (count($parts) === 2) {
        $selector = $parts[0];
        try {
            // Delete selector from DB
            $stmt = $pdo->prepare("DELETE FROM `user_tokens` WHERE `selector` = ?");
            $stmt->execute([$selector]);
        } catch (Exception $e) {
            // Ignore database failures during logout
        }
    }
    
    // Clear remember-me cookie on the browser (expire in past)
    setcookie('remember_me', '', time() - 3600, '/');
}

// Clear and destroy session variables
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// Redirect back to home/login
redirect('login.php');
?>
