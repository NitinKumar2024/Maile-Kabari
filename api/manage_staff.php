<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/app.php';

// Allow POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(['success' => false, 'message' => 'Invalid request method.'], 405);
}

// Check admin role authorization
if (!is_logged_in() || !has_role('admin')) {
    send_json(['success' => false, 'message' => 'Unauthorized access. Please login.']);
}

$action = clean_input($_POST['action'] ?? '');

try {
    if ($action === 'add') {
        $name = clean_input($_POST['name'] ?? '');
        $phone = clean_input($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($name) || empty($phone) || empty($password)) {
            send_json(['success' => false, 'message' => 'All fields are required.']);
        }
        
        if (!preg_match('/^[0-9]{10}$/', $phone)) {
            send_json(['success' => false, 'message' => 'Phone number must be a valid 10-digit number.']);
        }
        
        if (strlen($password) < 6) {
            send_json(['success' => false, 'message' => 'Password must be at least 6 characters long.']);
        }
        
        // Check if phone number is already registered in DB
        $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ? LIMIT 1");
        $stmt->execute([$phone]);
        if ($stmt->fetch()) {
            send_json(['success' => false, 'message' => 'This phone number is already registered.']);
        }
        
        // Hash password
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert staff user
        $insertStmt = $pdo->prepare("INSERT INTO users (name, phone, password_hash, role, status) VALUES (?, ?, ?, 'staff', 'active')");
        $insertStmt->execute([$name, $phone, $passwordHash]);
        
        send_json(['success' => true, 'message' => 'Staff collector registered successfully!']);
        
    } elseif ($action === 'toggle_status') {
        $staff_id = intval($_POST['staff_id'] ?? 0);
        $status = clean_input($_POST['status'] ?? '');
        
        if ($staff_id <= 0 || !in_array($status, ['active', 'inactive'])) {
            send_json(['success' => false, 'message' => 'Invalid parameters.']);
        }
        
        // Update status
        $updateStmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ? AND role = 'staff'");
        $updateStmt->execute([$status, $staff_id]);
        
        // If deactivating staff, invalidate all active login tokens to log them out instantly
        if ($status === 'inactive') {
            $clearStmt = $pdo->prepare("DELETE FROM user_tokens WHERE user_id = ?");
            $clearStmt->execute([$staff_id]);
        }
        
        send_json(['success' => true, 'message' => 'Staff status updated successfully.']);
        
    } else {
        send_json(['success' => false, 'message' => 'Invalid action.']);
    }
} catch (PDOException $e) {
    send_json(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
