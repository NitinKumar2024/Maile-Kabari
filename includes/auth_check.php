<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/app.php';

// Check if user is already logged in via session
if (!is_logged_in()) {
    // If not, check if they have a persistent auto-login cookie
    if (isset($_COOKIE['remember_me'])) {
        $parts = explode(':', $_COOKIE['remember_me'], 2);
        
        if (count($parts) === 2) {
            $selector = $parts[0];
            $validator = $parts[1];
            
            try {
                // Find token in database
                $stmt = $pdo->prepare("SELECT * FROM `user_tokens` WHERE `selector` = ? AND `expires_at` > NOW() LIMIT 1");
                $stmt->execute([$selector]);
                $token = $stmt->fetch();
                
                if ($token) {
                    // Hash the validator from the cookie and verify against the db hash
                    $validator_hash = hash('sha256', $validator);
                    
                    if (hash_equals($token['validator_hash'], $validator_hash)) {
                        // Success! Retrieve the user details
                        $userStmt = $pdo->prepare("SELECT `id`, `name`, `phone`, `role`, `status` FROM `users` WHERE `id` = ? AND `status` = 'active' LIMIT 1");
                        $userStmt->execute([$token['user_id']]);
                        $user = $userStmt->fetch();
                        
                        if ($user) {
                            // Log the user in by populating session variables
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['user_name'] = $user['name'];
                            $_SESSION['user_phone'] = $user['phone'];
                            $_SESSION['user_role'] = $user['role'];
                            
                            // Rotate token: Generate a new validator for the next request
                            $newValidator = bin2hex(random_bytes(16));
                            $newValidatorHash = hash('sha256', $newValidator);
                            
                            // Update database validator hash and expiration (extending it by another 30 days)
                            $newExpires = date('Y-m-d H:i:s', time() + (86400 * 30));
                            $updateStmt = $pdo->prepare("UPDATE `user_tokens` SET `validator_hash` = ?, `expires_at` = ? WHERE `id` = ?");
                            $updateStmt->execute([$newValidatorHash, $newExpires, $token['id']]);
                            
                            // Send updated cookie to browser
                            setcookie(
                                'remember_me',
                                $selector . ':' . $newValidator,
                                [
                                    'expires' => time() + (86400 * 30),
                                    'path' => '/',
                                    'domain' => '',
                                    'secure' => false, // Set to true if running over HTTPS
                                    'httponly' => true,
                                    'samesite' => 'Lax'
                                ]
                            );
                        } else {
                            // User is inactive or doesn't exist anymore, clear token
                            $clearStmt = $pdo->prepare("DELETE FROM `user_tokens` WHERE `user_id` = ?");
                            $clearStmt->execute([$token['user_id']]);
                            setcookie('remember_me', '', time() - 3600, '/');
                        }
                    } else {
                        // SECURITY RISK: Validator does not match!
                        // This suggests a potential session hijack or cookie theft attempt.
                        // For safety, delete all remember tokens for this user.
                        $clearStmt = $pdo->prepare("DELETE FROM `user_tokens` WHERE `user_id` = ?");
                        $clearStmt->execute([$token['user_id']]);
                        setcookie('remember_me', '', time() - 3600, '/');
                    }
                }
            } catch (Exception $e) {
                // Silently ignore or log auth errors
            }
        }
    }
}
?>
