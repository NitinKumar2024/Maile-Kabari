<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/app.php';

// Check auth state
if (!is_logged_in()) {
    send_json(['success' => false, 'message' => 'Unauthorized access. Please login.'], 401);
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$user_name = $_SESSION['user_name'];

// ==========================================
// HANDLE GET: FETCH DETAILS
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = clean_input($_GET['action'] ?? '');
    $order_id = intval($_GET['order_id'] ?? 0);
    
    if ($action === 'details' && $order_id > 0) {
        try {
            // Check access (Admins can view all, Staff can view assigned, Customers can view their own)
            $orderQuery = "
                SELECT o.*, uc.name AS customer_name, uc.phone AS customer_phone 
                FROM `orders` o 
                JOIN `users` uc ON o.user_id = uc.id 
                WHERE o.id = ? LIMIT 1
            ";
            $stmt = $pdo->prepare($orderQuery);
            $stmt->execute([$order_id]);
            $order = $stmt->fetch();
            
            if (!$order) {
                send_json(['success' => false, 'message' => 'Order not found.']);
            }
            
            // Check roles
            if ($user_role === 'customer' && $order['user_id'] != $user_id) {
                send_json(['success' => false, 'message' => 'Access denied.']);
            }
            if ($user_role === 'staff' && $order['assigned_staff_id'] != $user_id) {
                send_json(['success' => false, 'message' => 'Access denied.']);
            }
            
            // Fetch order items
            $itemsQuery = "
                SELECT oi.*, sc.name AS category_name, sc.unit 
                FROM `order_items` oi 
                JOIN `scrap_categories` sc ON oi.category_id = sc.id 
                WHERE oi.order_id = ?
            ";
            $itemsStmt = $pdo->prepare($itemsQuery);
            $itemsStmt->execute([$order_id]);
            $items = $itemsStmt->fetchAll();
            
            send_json([
                'success' => true,
                'order' => $order,
                'items' => $items
            ]);
            
        } catch (PDOException $e) {
            send_json(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }
    
    send_json(['success' => false, 'message' => 'Invalid GET parameters.']);
}

// ==========================================
// HANDLE POST: UPDATE DETAILS
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = clean_input($_POST['action'] ?? '');
    
    // Action: Read all notifications for active user
    if ($action === 'read_all') {
        try {
            $stmt = $pdo->prepare("UPDATE `notifications` SET `is_read` = 1 WHERE `user_id` = ?");
            $stmt->execute([$user_id]);
            send_json(['success' => true]);
        } catch (PDOException $e) {
            send_json(['success' => false, 'message' => 'Database error.']);
        }
    }
    
    // Action: Standard Order Update (Status, Assignment, Notes, Payouts)
    $order_id = intval($_POST['order_id'] ?? 0);
    $status = clean_input($_POST['status'] ?? '');
    
    if ($order_id <= 0 || empty($status)) {
        send_json(['success' => false, 'message' => 'Missing required fields.']);
    }
    
    try {
        // Fetch current order state
        $stmt = $pdo->prepare("SELECT * FROM `orders` WHERE id = ? LIMIT 1");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch();
        
        if (!$order) {
            send_json(['success' => false, 'message' => 'Order not found.']);
        }
        
        // Authorization check by role
        if ($user_role === 'customer') {
            // Customers can only transition their OWN orders to CANCELLED, and only if status is PENDING
            if ($order['user_id'] != $user_id) {
                send_json(['success' => false, 'message' => 'Unauthorized operation.']);
            }
            if ($status !== 'cancelled' || $order['status'] !== 'pending') {
                send_json(['success' => false, 'message' => 'Cannot cancel order in current state.']);
            }
        }
        
        if ($user_role === 'staff') {
            // Staff can only transition assigned orders to COLLECTED
            if ($order['assigned_staff_id'] != $user_id) {
                send_json(['success' => false, 'message' => 'Order is not assigned to you.']);
            }
            if ($status !== 'collected') {
                send_json(['success' => false, 'message' => 'Invalid status change request.']);
            }
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        // ------------------------------------------
        // CASE: CUSTOMER CANCEL
        // ------------------------------------------
        if ($user_role === 'customer' && $status === 'cancelled') {
            $updateStmt = $pdo->prepare("UPDATE `orders` SET `status` = 'cancelled' WHERE `id` = ?");
            $updateStmt->execute([$order_id]);
            
            // Notify admins
            $admins = $pdo->query("SELECT id FROM users WHERE role = 'admin' AND status = 'active'")->fetchAll();
            $notif = $pdo->prepare("INSERT INTO `notifications` (user_id, title, message) VALUES (?, ?, ?)");
            foreach ($admins as $ad) {
                $notif->execute([
                    $ad['id'],
                    "Order Cancelled #MK-" . $order_id,
                    "Customer " . $user_name . " cancelled their scheduled pickup."
                ]);
            }
        }
        
        // ------------------------------------------
        // CASE: STAFF COLLECTION (Doorstep completion)
        // ------------------------------------------
        elseif ($user_role === 'staff' && $status === 'collected') {
            $staff_notes = clean_input($_POST['staff_notes'] ?? null);
            $actual_weights = $_POST['actual_weights'] ?? []; // Map of category_id => actual weight
            
            $total_actual_price = 0.00;
            
            // Update individual order items with measured weights
            $updateItem = $pdo->prepare("UPDATE `order_items` SET `actual_quantity` = ? WHERE `order_id` = ? AND `category_id` = ?");
            
            foreach ($actual_weights as $cat_id => $act_weight) {
                $cat_id = intval($cat_id);
                $act_weight = floatval($act_weight);
                
                if ($act_weight < 0) {
                    throw new Exception("Weight measurements cannot be negative.");
                }
                
                // Fetch rate
                $rateStmt = $pdo->prepare("SELECT rate_at_order FROM `order_items` WHERE `order_id` = ? AND `category_id` = ? LIMIT 1");
                $rateStmt->execute([$order_id, $cat_id]);
                $rate = $rateStmt->fetchColumn() ?: 0.00;
                
                $total_actual_price += ($rate * $act_weight);
                
                $updateItem->execute([$act_weight, $order_id, $cat_id]);
            }
            
            // Update order header
            $updateOrder = $pdo->prepare("
                UPDATE `orders` 
                SET `status` = 'collected', `total_actual_price` = ?, `staff_notes` = ? 
                WHERE `id` = ?
            ");
            $updateOrder->execute([$total_actual_price, $staff_notes, $order_id]);
            
            // Notify customer
            $notifCustomer = $pdo->prepare("INSERT INTO `notifications` (user_id, title, message) VALUES (?, ?, ?)");
            $notifCustomer->execute([
                $order['user_id'],
                "Pickup Completed #MK-" . $order_id,
                "Your scrap has been weighed. Payout received: ₹" . number_format($total_actual_price, 2) . ". Thank you for recycling!"
            ]);
            
            // Notify admins
            $admins = $pdo->query("SELECT id FROM users WHERE role = 'admin' AND status = 'active'")->fetchAll();
            $notifAdmin = $pdo->prepare("INSERT INTO `notifications` (user_id, title, message) VALUES (?, ?, ?)");
            foreach ($admins as $ad) {
                $notifAdmin->execute([
                    $ad['id'],
                    "Order Completed #MK-" . $order_id,
                    "Staff " . $user_name . " collected scrap and completed order. Payout: ₹" . number_format($total_actual_price, 2)
                ]);
            }
        }
        
        // ------------------------------------------
        // CASE: ADMIN UPDATES (Assignment / Notes / Status overrides)
        // ------------------------------------------
        elseif ($user_role === 'admin') {
            $assigned_staff_id = !empty($_POST['assigned_staff_id']) ? intval($_POST['assigned_staff_id']) : null;
            $admin_notes = clean_input($_POST['admin_notes'] ?? null);
            
            // Validate staff existence
            if ($assigned_staff_id !== null) {
                $chkStaff = $pdo->prepare("SELECT COUNT(*) FROM users WHERE id = ? AND role = 'staff' AND status = 'active'");
                $chkStaff->execute([$assigned_staff_id]);
                if ($chkStaff->fetchColumn() == 0) {
                    throw new Exception("Selected staff collector is invalid or inactive.");
                }
                
                // If staff is assigned, auto-advance status to 'assigned' if it was pending
                if ($status === 'pending') {
                    $status = 'assigned';
                }
            } else {
                // If staff is removed, auto-revert to 'pending' if it was assigned
                if ($status === 'assigned') {
                    $status = 'pending';
                }
            }
            
            // Update order details
            $updateOrder = $pdo->prepare("
                UPDATE `orders` 
                SET `status` = ?, `assigned_staff_id` = ?, `admin_notes` = ? 
                WHERE `id` = ?
            ");
            $updateOrder->execute([$status, $assigned_staff_id, $admin_notes, $order_id]);
            
            // Handle notification dispatches based on change
            $notif = $pdo->prepare("INSERT INTO `notifications` (user_id, title, message) VALUES (?, ?, ?)");
            
            if ($status === 'assigned' && $assigned_staff_id !== null && $order['assigned_staff_id'] != $assigned_staff_id) {
                // Get staff details
                $stStmt = $pdo->prepare("SELECT name FROM users WHERE id = ? LIMIT 1");
                $stStmt->execute([$assigned_staff_id]);
                $staff_name_selected = $stStmt->fetchColumn();
                
                // Notify Staff member
                $notif->execute([
                    $assigned_staff_id,
                    "New Pickup Task #MK-" . $order_id,
                    "You have been assigned a pickup task. View dashboard for location details."
                ]);
                
                // Notify Customer
                $notif->execute([
                    $order['user_id'],
                    "Collector Assigned #MK-" . $order_id,
                    "Collector " . $staff_name_selected . " has been assigned to pick up your scrap."
                ]);
            }
            
            if ($status === 'cancelled' && $order['status'] !== 'cancelled') {
                // Notify Customer of admin cancellation
                $notif->execute([
                    $order['user_id'],
                    "Request Cancelled #MK-" . $order_id,
                    "Your pickup request has been cancelled by the administrator."
                ]);
            }
        }
        
        $pdo->commit();
        send_json(['success' => true, 'message' => 'Order updated successfully.']);
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        send_json(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>
