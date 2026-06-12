<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/app.php';

// Allow POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(['success' => false, 'message' => 'Invalid request method.'], 405);
}

// Check authorization
if (!is_logged_in() || !has_role('customer')) {
    send_json(['success' => false, 'message' => 'Unauthorized access. Please login.']);
}

$user_id = $_SESSION['user_id'];
$customer_name = $_SESSION['user_name'];

$categories = $_POST['categories'] ?? [];
$weights = $_POST['weights'] ?? [];
$address = clean_input($_POST['address'] ?? '');
$lat = !empty($_POST['lat']) ? floatval($_POST['lat']) : null;
$lng = !empty($_POST['lng']) ? floatval($_POST['lng']) : null;
$pickup_date = clean_input($_POST['pickup_date'] ?? '');
$pickup_time_slot = clean_input($_POST['pickup_time_slot'] ?? '');
$customer_notes = clean_input($_POST['customer_notes'] ?? null);

// Validations
if (empty($categories)) {
    send_json(['success' => false, 'message' => 'Please select at least one scrap item.']);
}
if (empty($address)) {
    send_json(['success' => false, 'message' => 'Pickup address is required.']);
}
if (empty($pickup_date)) {
    send_json(['success' => false, 'message' => 'Pickup date is required.']);
}
if (empty($pickup_time_slot)) {
    send_json(['success' => false, 'message' => 'Preferred time slot is required.']);
}

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // 1. Calculate estimated price and check input validity
    $total_estimated_price = 0.00;
    $items_to_insert = [];
    
    foreach ($categories as $cat_id) {
        $cat_id = intval($cat_id);
        
        // Fetch category rate
        $catStmt = $pdo->prepare("SELECT name, rate_per_unit, unit FROM scrap_categories WHERE id = ? LIMIT 1");
        $catStmt->execute([$cat_id]);
        $category = $catStmt->fetch();
        
        if (!$category) {
            throw new Exception("Invalid scrap category selected.");
        }
        
        $est_weight = isset($weights[$cat_id]) ? floatval($weights[$cat_id]) : 0;
        if ($est_weight <= 0) {
            throw new Exception("Please enter a valid weight for " . $category['name']);
        }
        
        $rate = floatval($category['rate_per_unit']);
        $subtotal = $rate * $est_weight;
        $total_estimated_price += $subtotal;
        
        $items_to_insert[] = [
            'category_id' => $cat_id,
            'estimated_quantity' => $est_weight,
            'rate_at_order' => $rate
        ];
    }
    
    // 2. Insert Order Header
    $orderStmt = $pdo->prepare("
        INSERT INTO `orders` 
        (user_id, pickup_address, lat, lng, pickup_date, pickup_time_slot, total_estimated_price, customer_notes, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
    ");
    $orderStmt->execute([
        $user_id,
        $address,
        $lat,
        $lng,
        $pickup_date,
        $pickup_time_slot,
        $total_estimated_price,
        $customer_notes
    ]);
    
    $order_id = $pdo->lastInsertId();
    
    // 3. Insert Order Items
    $itemStmt = $pdo->prepare("
        INSERT INTO `order_items` 
        (order_id, category_id, estimated_quantity, rate_at_order) 
        VALUES (?, ?, ?, ?)
    ");
    
    foreach ($items_to_insert as $item) {
        $itemStmt->execute([
            $order_id,
            $item['category_id'],
            $item['estimated_quantity'],
            $item['rate_at_order']
        ]);
    }
    
    // 4. Create Notification for Customer
    $notifCustomer = $pdo->prepare("INSERT INTO `notifications` (user_id, title, message) VALUES (?, ?, ?)");
    $notifCustomer->execute([
        $user_id,
        "Pickup Scheduled #MK-" . $order_id,
        "Your pickup request for " . date('d M Y', strtotime($pickup_date)) . " (" . $pickup_time_slot . ") has been placed. Estimated value: ₹" . number_format($total_estimated_price, 2)
    ]);
    
    // 5. Create Notification for Admin users
    $adminStmt = $pdo->prepare("SELECT id FROM users WHERE role = 'admin' AND status = 'active'");
    $adminStmt->execute();
    $admins = $adminStmt->fetchAll();
    
    $notifAdmin = $pdo->prepare("INSERT INTO `notifications` (user_id, title, message) VALUES (?, ?, ?)");
    foreach ($admins as $admin) {
        $notifAdmin->execute([
            $admin['id'],
            "New Order #MK-" . $order_id,
            "Customer " . $customer_name . " placed a new scrap request for ₹" . number_format($total_estimated_price, 2)
        ]);
    }
    
    // Commit transaction
    $pdo->commit();
    
    send_json([
        'success' => true,
        'message' => 'Pickup request scheduled successfully!',
        'order_id' => $order_id
    ]);
    
} catch (Exception $e) {
    // Rollback on any failure
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    send_json(['success' => false, 'message' => $e->getMessage()]);
}
?>
