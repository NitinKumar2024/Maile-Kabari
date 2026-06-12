<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/app.php';

// Handle POST requests only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(['success' => false, 'message' => 'Invalid request method.'], 405);
}

$action = clean_input($_POST['action'] ?? '');
$phone = clean_input($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';

// Basic validations
if (empty($phone) || empty($password)) {
    send_json(['success' => false, 'message' => 'Phone number and password are required.']);
}

if (!preg_match('/^[0-9]{10}$/', $phone)) {
    send_json(['success' => false, 'message' => 'Phone number must be a valid 10-digit number.']);
}

try {
    if ($action === 'register') {
        $name = clean_input($_POST['name'] ?? '');
        
        if (empty($name)) {
            send_json(['success' => false, 'message' => 'Full name is required.']);
        }
        
        // Check if phone already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ? LIMIT 1");
        $stmt->execute([$phone]);
        if ($stmt->fetch()) {
            send_json(['success' => false, 'message' => 'This phone number is already registered.']);
        }
        
        // Hash password
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user (default role: customer)
        $insertStmt = $pdo->prepare("INSERT INTO users (name, phone, password_hash, role, status) VALUES (?, ?, ?, 'customer', 'active')");
        $insertStmt->execute([$name, $phone, $passwordHash]);
        
        $userId = $pdo->lastInsertId();
        
        // Set session
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_phone'] = $phone;
        $_SESSION['user_role'] = 'customer';
        
        // Generate persistent remember token (Auto-login by default)
        setup_remember_cookie($pdo, $userId);
        
        send_json([
            'success' => true,
            'message' => 'Registration successful!',
            'role' => 'customer'
        ]);
        
    } elseif ($action === 'login') {
        // Find user by phone
        $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ? LIMIT 1");
        $stmt->execute([$phone]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($password, $user['password_hash'])) {
            send_json(['success' => false, 'message' => 'Invalid phone number or password.']);
        }
        
        if ($user['status'] !== 'active') {
            send_json(['success' => false, 'message' => 'Your account is deactivated. Please contact admin.']);
        }
        
        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_phone'] = $user['phone'];
        $_SESSION['user_role'] = $user['role'];
        
        // Generate persistent remember token (Auto-login by default)
        setup_remember_cookie($pdo, $user['id']);
        
        send_json([
            'success' => true,
            'message' => 'Login successful!',
            'role' => $user['role']
        ]);
        
    } else {
        send_json(['success' => false, 'message' => 'Invalid action.']);
    }
} catch (PDOException $e) {
    send_json(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

/**
 * Setup remember-me persistent cookie and save tokens in database
 */
function setup_remember_cookie($pdo, $userId) {
    // Generate secure random values
    $selector = bin2hex(random_bytes(8)); // 16 characters
    $validator = bin2hex(random_bytes(16)); // 32 characters
    $validatorHash = hash('sha256', $validator);
    
    // Set expiry date (30 days from now)
    $expires = date('Y-m-d H:i:s', time() + (86400 * 30));
    
    // Insert into user_tokens
    $stmt = $pdo->prepare("INSERT INTO user_tokens (user_id, selector, validator_hash, expires_at) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, $selector, $validatorHash, $expires]);
    
    // Set cookie on client
    setcookie(
        'remember_me',
        $selector . ':' . $validator,
        [
            'expires' => time() + (86400 * 30),
            'path' => '/',
            'domain' => '',
            'secure' => false, // Set to true if running over HTTPS
            'httponly' => true,
            'samesite' => 'Lax'
        ]
    );
}
?>
