<?php
$page_title = "Customer Dashboard";
require_once __DIR__ . '/../includes/header.php';

// Check customer authorization
if (!is_logged_in() || !has_role('customer')) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

try {
    // 1. Fetch current scrap rates
    $ratesStmt = $pdo->prepare("SELECT * FROM `scrap_categories` ORDER BY `name` ASC");
    $ratesStmt->execute();
    $rates = $ratesStmt->fetchAll();
    
    // 2. Fetch active requests (pending & assigned)
    $activeStmt = $pdo->prepare("
        SELECT o.*, u.name AS staff_name, u.phone AS staff_phone 
        FROM `orders` o 
        LEFT JOIN `users` u ON o.assigned_staff_id = u.id 
        WHERE o.user_id = ? AND o.status IN ('pending', 'assigned') 
        ORDER BY o.created_at DESC
    ");
    $activeStmt->execute([$user_id]);
    $active_orders = $activeStmt->fetchAll();
    
    // 3. Fetch past requests (collected & cancelled)
    $pastStmt = $pdo->prepare("
        SELECT o.*, u.name AS staff_name 
        FROM `orders` o 
        LEFT JOIN `users` u ON o.assigned_staff_id = u.id 
        WHERE o.user_id = ? AND o.status IN ('collected', 'cancelled') 
        ORDER BY o.updated_at DESC LIMIT 10
    ");
    $pastStmt->execute([$user_id]);
    $past_orders = $pastStmt->fetchAll();
    
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<div class="mobile-container" style="padding-top: 20px;">
    <!-- Welcome Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <h4 style="color: var(--text-secondary); font-weight: 500; font-size: 0.9rem;">Welcome Back,</h4>
            <h2 style="font-size: 1.5rem; font-weight: 800;"><?php echo $user_name; ?> 👋</h2>
        </div>
        <a href="<?php echo BASE_URL; ?>customer/profile.php" style="width: 44px; height: 44px; border-radius: 50%; background: var(--bg-card); border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; color: var(--primary);">
            <i class="fa-solid fa-user-gear"></i>
        </a>
    </div>

    <!-- Live Rates Horizontal Feed -->
    <div style="margin-bottom: 28px;">
        <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center;">
            <span>Live Buying Rates</span>
            <span style="font-size: 0.75rem; color: var(--primary); font-weight: 500; text-transform: uppercase;">Updated Live <i class="fa-solid fa-circle-nodes fa-pulse"></i></span>
        </h3>
        
        <div class="rates-feed">
            <?php foreach ($rates as $rate): ?>
                <?php
                    // Map icon keys to FontAwesome classes
                    $icon_class = 'fa-solid fa-box';
                    switch ($rate['slug']) {
                        case 'iron': $icon_class = 'fa-solid fa-weight-hanging'; break;
                        case 'plastic': $icon_class = 'fa-solid fa-bottle-water'; break;
                        case 'cardboard': $icon_class = 'fa-solid fa-box-open'; break;
                        case 'books': $icon_class = 'fa-solid fa-book'; break;
                        case 'aluminium': $icon_class = 'fa-solid fa-window-maximize'; break;
                        case 'copper': $icon_class = 'fa-solid fa-bolt'; break;
                        case 'zinc': $icon_class = 'fa-solid fa-shield-halved'; break;
                    }
                ?>
                <div class="rate-card">
                    <div class="rate-icon" style="color: var(--primary);">
                        <i class="<?php echo $icon_class; ?>"></i>
                    </div>
                    <div class="rate-name"><?php echo $rate['name']; ?></div>
                    <div class="rate-price">₹<?php echo number_format($rate['rate_per_unit'], 0); ?>/<?php echo $rate['unit']; ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Quick Action Banner -->
    <a href="<?php echo BASE_URL; ?>customer/new_request.php" class="btn btn-primary btn-block" style="padding: 16px; border-radius: var(--radius-md); box-shadow: 0 4px 15px var(--primary-glow); margin-bottom: 30px;">
        <i class="fa-solid fa-truck-pickup"></i> Book A Scrap Pickup
    </a>

    <!-- Active Requests Section -->
    <div style="margin-bottom: 28px;">
        <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 12px;">Active Bookings</h3>
        
        <?php if (empty($active_orders)): ?>
            <div class="card" style="text-align: center; padding: 30px 20px; color: var(--text-secondary);">
                <i class="fa-solid fa-calendar-xmark" style="font-size: 2rem; color: var(--border-color); margin-bottom: 12px;"></i>
                <p style="font-size: 0.9rem;">No active pickup requests.</p>
                <a href="<?php echo BASE_URL; ?>customer/new_request.php" style="font-size: 0.85rem; display: inline-block; margin-top: 10px;">Create one now</a>
            </div>
        <?php else: ?>
            <?php foreach ($active_orders as $order): ?>
                <div class="order-list-item">
                    <div class="order-header">
                        <span class="order-id">Order #MK-<?php echo $order['id']; ?></span>
                        <span class="badge badge-<?php echo $order['status']; ?>"><?php echo $order['status']; ?></span>
                    </div>
                    <div class="order-date">
                        <i class="fa-solid fa-calendar-day"></i> Scheduled: <?php echo date('d M Y', strtotime($order['pickup_date'])); ?> (<?php echo $order['pickup_time_slot']; ?>)
                    </div>
                    <div class="order-details">
                        <i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($order['pickup_address']); ?>
                    </div>
                    
                    <?php if ($order['status'] === 'assigned'): ?>
                        <div style="background: rgba(59, 130, 246, 0.08); border: 1px solid rgba(59, 130, 246, 0.2); border-radius: 6px; padding: 10px; font-size: 0.85rem; display: flex; align-items: center; justify-content: space-between; margin-top: 4px;">
                            <div>
                                <strong style="color: var(--text-primary);"><i class="fa-solid fa-user-check"></i> Assigned Collector:</strong><br>
                                <span style="color: var(--text-secondary);"><?php echo $order['staff_name']; ?></span>
                            </div>
                            <a href="tel:<?php echo $order['staff_phone']; ?>" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem; border-radius: 4px;">
                                <i class="fa-solid fa-phone"></i> Call
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <div class="order-footer">
                        <div>
                            <span style="font-size: 0.75rem; color: var(--text-muted);">Est. Cash Value</span>
                            <div class="order-payout">₹<?php echo number_format($order['total_estimated_price'], 2); ?></div>
                        </div>
                        
                        <?php if ($order['status'] === 'pending'): ?>
                            <button onclick="cancelOrder(<?php echo $order['id']; ?>)" class="btn btn-danger" style="padding: 6px 12px; font-size: 0.8rem; border-radius: 4px;">
                                <i class="fa-solid fa-xmark"></i> Cancel
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Past Trades History -->
    <div>
        <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 12px;">Past Trades</h3>
        
        <?php if (empty($past_orders)): ?>
            <div class="card" style="text-align: center; padding: 20px; color: var(--text-secondary); font-size: 0.9rem;">
                No trade history available.
            </div>
        <?php else: ?>
            <?php foreach ($past_orders as $order): ?>
                <div class="order-list-item" style="opacity: 0.85;">
                    <div class="order-header">
                        <span class="order-id" style="color: var(--text-secondary);">Order #MK-<?php echo $order['id']; ?></span>
                        <span class="badge badge-<?php echo $order['status']; ?>"><?php echo $order['status']; ?></span>
                    </div>
                    <div class="order-date">Completed: <?php echo date('d M Y, h:i A', strtotime($order['updated_at'])); ?></div>
                    <div class="order-footer">
                        <?php if ($order['status'] === 'collected'): ?>
                            <div>
                                <span style="font-size: 0.75rem; color: var(--text-muted);">Payout Received</span>
                                <div style="font-weight: 700; color: var(--success); font-size: 1.1rem;">₹<?php echo number_format($order['total_actual_price'], 2); ?></div>
                            </div>
                            <span style="font-size: 0.8rem; color: var(--text-muted);">Collector: <?php echo $order['staff_name']; ?></span>
                        <?php else: ?>
                            <span style="color: var(--danger); font-size: 0.85rem; font-weight: 500;">Request Cancelled</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
/**
 * Cancel a pending order request via AJAX
 */
function cancelOrder(orderId) {
    if (!confirm('Are you sure you want to cancel this pickup request?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('order_id', orderId);
    formData.append('status', 'cancelled');
    
    fetch('<?php echo BASE_URL; ?>api/update_order.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Pickup request cancelled successfully.', 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Failed to cancel request. Please try again.', 'error');
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
