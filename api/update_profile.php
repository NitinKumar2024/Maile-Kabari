<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/app.php';

// Allow POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(['success' => false, 'message' => 'Invalid request method.'], 405);
}

// Check auth state
if (!is_logged_in()) {
    send_json(['success' => false, 'message' => 'Unauthorized access. Please login.']);
}

$user_id = $_SESSION['user_id'];
$type = clean_input($_POST['type'] ?? '');

try {
    if ($type === 'personal') {
        $name = clean_input($_POST['name'] ?? '');
        $email = clean_input($_POST['email'] ?? null);
        
        if (empty($name)) {
            send_json(['success' => false, 'message' => 'Full name is required.']);
        }
        
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            send_json(['success' => false, 'message' => 'Please enter a valid email address.']);
        }
        
        // Update user
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
        $stmt->execute([$name, empty($email) ? null : $email, $user_id]);
        
        // Update session name
        $_SESSION['user_name'] = $name;
        
        send_json(['success' => true, 'message' => 'Profile updated successfully!']);
        
    } elseif ($type === 'password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        
        if (empty($current_password) || empty($new_password)) {
            send_json(['success' => false, 'message' => 'All password fields are required.']);
        }
        
        if (strlen($new_password) < 6) {
            send_json(['success' => false, 'message' => 'New password must be at least 6 characters long.']);
        }
        
        // Verify current password
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($current_password, $user['password_hash'])) {
            send_json(['success' => false, 'message' => 'Current password is incorrect.']);
        }
        
        // Hash new password and update
        $newHash = password_hash($new_password, PASSWORD_DEFAULT);
        $updateStmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $updateStmt->execute([$newHash, $user_id]);
        
        send_json(['success' => true, 'message' => 'Password changed successfully!']);
        
    } else {
        send_json(['success' => false, 'message' => 'Invalid update type.']);
    }
} catch (PDOException $e) {
    send_json(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
