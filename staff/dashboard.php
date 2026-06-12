<?php
$page_title = "Staff Dashboard";
require_once __DIR__ . '/../includes/header.php';

// Check staff authorization
if (!is_logged_in() || !has_role('staff')) {
    redirect('login.php');
}

$staff_id = $_SESSION['user_id'];
$staff_name = $_SESSION['user_name'];

try {
    // 1. Fetch assigned active pickups
    $activeStmt = $pdo->prepare("
        SELECT o.*, u.name AS customer_name, u.phone AS customer_phone 
        FROM `orders` o 
        JOIN `users` u ON o.user_id = u.id 
        WHERE o.assigned_staff_id = ? AND o.status = 'assigned' 
        ORDER BY o.pickup_date ASC, o.created_at ASC
    ");
    $activeStmt->execute([$staff_id]);
    $assigned_pickups = $activeStmt->fetchAll();
    
    // 2. Fetch completed pickups today
    $today = date('Y-m-d');
    $compStmt = $pdo->prepare("
        SELECT o.*, u.name AS customer_name 
        FROM `orders` o 
        JOIN `users` u ON o.user_id = u.id 
        WHERE o.assigned_staff_id = ? AND o.status = 'collected' AND DATE(o.updated_at) = ? 
        ORDER BY o.updated_at DESC
    ");
    $compStmt->execute([$staff_id, $today]);
    $completed_pickups = $compStmt->fetchAll();
    
    // Sum total collected payout today
    $today_payout = array_reduce($completed_pickups, function($carry, $item) {
        return $carry + $item['total_actual_price'];
    }, 0);
    
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<div class="mobile-container" style="padding-top: 20px;">
    <!-- Welcoming Header -->
    <div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h4 style="color: var(--text-secondary); font-weight: 500; font-size: 0.9rem;">Field Collector,</h4>
            <h2 style="font-size: 1.4rem; font-weight: 800;"><?php echo htmlspecialchars($staff_name); ?> 🚚</h2>
        </div>
        <div style="background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.2); padding: 8px 14px; border-radius: 8px; text-align: right;">
            <span style="font-size: 0.7rem; color: var(--text-secondary); text-transform: uppercase;">Paid Today</span>
            <div style="font-weight: 800; color: var(--primary); font-size: 1.15rem;">₹<?php echo number_format($today_payout, 0); ?></div>
        </div>
    </div>

    <!-- Assigned Pickups Section -->
    <div style="margin-bottom: 28px;">
        <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center;">
            <span>Assigned Pickups</span>
            <span class="badge badge-assigned" style="font-size: 0.7rem;"><?php echo count($assigned_pickups); ?> Active</span>
        </h3>
        
        <?php if (empty($assigned_pickups)): ?>
            <div class="card" style="text-align: center; padding: 40px 20px; color: var(--text-secondary);">
                <i class="fa-solid fa-clipboard-check" style="font-size: 2.5rem; color: var(--border-color); margin-bottom: 12px;"></i>
                <p style="font-size: 0.95rem; font-weight: 500;">All clear! No pending tasks.</p>
                <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">New bookings assigned by admin will appear here.</p>
            </div>
        <?php else: ?>
            <?php foreach ($assigned_pickups as $task): ?>
                <div class="order-list-item" style="border-left: 3px solid var(--info);">
                    <div class="order-header">
                        <span class="order-id">Order #MK-<?php echo $task['id']; ?></span>
                        <span class="badge badge-assigned"><?php echo $task['pickup_time_slot']; ?></span>
                    </div>
                    
                    <div style="font-size: 1.05rem; font-weight: 700; color: var(--text-primary); margin-top: 4px;">
                        <i class="fa-solid fa-user" style="color: var(--text-muted); margin-right: 6px;"></i> <?php echo htmlspecialchars($task['customer_name']); ?>
                    </div>
                    
                    <div class="order-details" style="font-size: 0.85rem; margin-top: 2px;">
                        <i class="fa-solid fa-location-dot" style="color: var(--primary);"></i> <?php echo htmlspecialchars($task['pickup_address']); ?>
                    </div>
                    
                    <!-- Customer phone and navigation CTA row -->
                    <div style="display: flex; gap: 8px; margin-top: 8px;">
                        <a href="tel:<?php echo $task['customer_phone']; ?>" class="btn btn-secondary" style="flex: 1; padding: 8px; font-size: 0.8rem; border-radius: 4px;">
                            <i class="fa-solid fa-phone"></i> Call Customer
                        </a>
                        
                        <?php if ($task['lat']): ?>
                            <a href="https://www.google.com/maps/search/?api=1&query=<?php echo $task['lat']; ?>,<?php echo $task['lng']; ?>" target="_blank" class="btn btn-secondary" style="flex: 1; padding: 8px; font-size: 0.8rem; border-radius: 4px;">
                                <i class="fa-solid fa-map-location-dot"></i> Navigate
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Customer Notes if any -->
                    <?php if ($task['customer_notes']): ?>
                        <div style="font-size: 0.8rem; background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: 4px; padding: 6px 10px; color: var(--text-secondary); margin-top: 4px;">
                            <strong>Note:</strong> "<?php echo htmlspecialchars($task['customer_notes']); ?>"
                        </div>
                    <?php endif; ?>
                    
                    <div class="order-footer" style="margin-top: 8px;">
                        <div>
                            <span style="font-size: 0.7rem; color: var(--text-muted);">Est. Cash Required</span>
                            <div class="order-payout" style="font-size: 1.05rem;">₹<?php echo number_format($task['total_estimated_price'], 2); ?></div>
                        </div>
                        
                        <a href="<?php echo BASE_URL; ?>staff/collect.php?order_id=<?php echo $task['id']; ?>" class="btn btn-primary" style="padding: 8px 16px; font-size: 0.85rem; border-radius: 4px;">
                            Collect Scrap <i class="fa-solid fa-chevron-right"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Completed History Today Section -->
    <div>
        <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 12px;">Completed Today</h3>
        
        <?php if (empty($completed_pickups)): ?>
            <div class="card" style="text-align: center; padding: 20px; color: var(--text-secondary); font-size: 0.9rem;">
                No pickups completed today yet.
            </div>
        <?php else: ?>
            <?php foreach ($completed_pickups as $comp): ?>
                <div class="order-list-item" style="opacity: 0.8; border-left: 3px solid var(--success);">
                    <div class="order-header">
                        <span class="order-id" style="color: var(--text-secondary);">Order #MK-<?php echo $comp['id']; ?></span>
                        <span class="badge badge-collected">Collected</span>
                    </div>
                    <div class="order-details" style="font-size: 0.85rem;">
                        <strong>Customer:</strong> <?php echo htmlspecialchars($comp['customer_name']); ?><br>
                        <strong>Time:</strong> <?php echo date('h:i A', strtotime($comp['updated_at'])); ?>
                    </div>
                    <div class="order-footer">
                        <div>
                            <span style="font-size: 0.7rem; color: var(--text-muted);">Amount Paid</span>
                            <div style="font-weight: 700; color: var(--success); font-size: 1rem;">₹<?php echo number_format($comp['total_actual_price'], 2); ?></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
