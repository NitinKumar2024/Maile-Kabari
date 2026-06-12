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

$category_id = intval($_POST['category_id'] ?? 0);
$rate = floatval($_POST['rate'] ?? -1);

if ($category_id <= 0 || $rate < 0) {
    send_json(['success' => false, 'message' => 'Invalid scrap category or rate value.']);
}

try {
    // Update rate in database
    $stmt = $pdo->prepare("UPDATE `scrap_categories` SET `rate_per_unit` = ?, `updated_at` = CURRENT_TIMESTAMP WHERE `id` = ?");
    $stmt->execute([$rate, $category_id]);
    
    send_json(['success' => true, 'message' => 'Scrap category rate updated successfully!']);
} catch (PDOException $e) {
    send_json(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
