<?php
// Load environment variables
require_once __DIR__ . '/config/env.php';

// Database credentials matching db.php
define('SETUP_HOST', env('DB_HOST', '127.0.0.1'));
define('SETUP_USER', env('DB_USER', 'root'));
define('SETUP_PASS', env('DB_PASS', ''));
define('SETUP_DB', env('DB_NAME', 'rohit_kabari'));

echo "=== Maile Kabari Database Setup ===\n<br>";

try {
    // 1. Connect to MySQL without specifying a database
    $pdo = new PDO("mysql:host=" . SETUP_HOST . ";charset=utf8mb4", SETUP_USER, SETUP_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    // 2. Create the Database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . SETUP_DB . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    echo "Database '" . SETUP_DB . "' verified/created successfully.\n<br>";
    
    // 3. Connect to the specific database
    $pdo->exec("USE `" . SETUP_DB . "`");
    
    // 4. Read the schema.sql file
    $schemaFile = __DIR__ . '/config/schema.sql';
    if (!file_exists($schemaFile)) {
        die("Error: schema.sql file not found at $schemaFile");
    }
    
    $sql = file_get_contents($schemaFile);
    
    // Remove comments
    $sql = preg_replace('/--.*\n/', '', $sql);
    
    // Split queries by semicolon (simple parser)
    $queries = explode(';', $sql);
    
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            $pdo->exec($query);
        }
    }
    echo "Database schema tables and default categories imported successfully.\n<br>";
    
    // 5. Seed a default admin user if none exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `users` WHERE `role` = 'admin'");
    $stmt->execute();
    $adminCount = $stmt->fetchColumn();
    
    if ($adminCount == 0) {
        $adminName = "Rohit Admin";
        $adminPhone = "9876543210";
        $adminPass = "admin123";
        $passwordHash = password_hash($adminPass, PASSWORD_DEFAULT);
        
        $insertStmt = $pdo->prepare("INSERT INTO `users` (`name`, `phone`, `password_hash`, `role`, `status`) VALUES (?, ?, ?, 'admin', 'active')");
        $insertStmt->execute([$adminName, $adminPhone, $passwordHash]);
        
        echo "Default Administrator account created:\n<br>";
        echo "Name: $adminName\n<br>";
        echo "Phone: $adminPhone\n<br>";
        echo "Password: $adminPass\n<br>";
    } else {
        echo "Administrator account already exists.\n<br>";
    }
    
    echo "\n<br>=== Setup Completed Successfully! ===\n<br>";
    
} catch (PDOException $e) {
    die("Setup failed: " . $e->getMessage());
}
?>
