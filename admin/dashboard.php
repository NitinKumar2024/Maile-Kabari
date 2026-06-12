<?php
$page_title = "Admin Dashboard";
require_once __DIR__ . '/../includes/header.php';

// Check admin authorization
if (!is_logged_in() || !has_role('admin')) {
    redirect('login.php');
}

$status_filter = clean_input($_GET['status'] ?? 'all');

try {
    // 1. Fetch Metrics Data
    $totalCollected = $pdo->query("SELECT SUM(total_actual_price) FROM orders WHERE status = 'collected'")->fetchColumn() ?: 0;
    $activeCount = $pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('pending', 'assigned')")->fetchColumn();
    $customerCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
    $staffCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'staff' AND status = 'active'")->fetchColumn();
    
    // 2. Fetch Orders based on status filter
    $queryStr = "
        SELECT o.*, uc.name AS customer_name, uc.phone AS customer_phone, us.name AS staff_name 
        FROM `orders` o 
        JOIN `users` uc ON o.user_id = uc.id 
        LEFT JOIN `users` us ON o.assigned_staff_id = us.id
    ";
    
    if ($status_filter !== 'all') {
        $queryStr .= " WHERE o.status = :status";
    }
    
    $queryStr .= " ORDER BY o.created_at DESC";
    
    $stmt = $pdo->prepare($queryStr);
    if ($status_filter !== 'all') {
        $stmt->bindValue(':status', $status_filter);
    }
    $stmt->execute();
    $orders = $stmt->fetchAll();
    
    // 3. Fetch active staff list for the assign dropdown
    $staffStmt = $pdo->query("SELECT id, name FROM users WHERE role = 'staff' AND status = 'active' ORDER BY name ASC");
    $staff_list = $staffStmt->fetchAll();
    
    // 4. Fetch admin in-app notifications
    $notifStmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $notifStmt->execute([$_SESSION['user_id']]);
    $notifications = $notifStmt->fetchAll();
    
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<div class="container" style="padding-top: 30px; padding-bottom: 40px;">
    <!-- Welcome Header & Notifications -->
    <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 30px;">
        <div>
            <h1 style="font-size: 1.8rem; font-weight: 800;">Admin Console</h1>
            <p style="color: var(--text-secondary);">Manage scrap orders, allocate staff collectors, and track business statistics.</p>
        </div>
        
        <!-- Notifications Bell dropdown -->
        <div style="position: relative;">
            <button class="btn btn-secondary" onclick="document.getElementById('notif-panel').classList.toggle('active')" style="position: relative; padding: 10px 14px;">
                <i class="fa-solid fa-bell"></i> Notifications
                <?php 
                    $unreadCount = array_reduce($notifications, function($carry, $item) {
                        return $carry + (!$item['is_read'] ? 1 : 0);
                    }, 0);
                    if ($unreadCount > 0):
                ?>
                    <span style="position: absolute; top: -5px; right: -5px; background: var(--danger); color: white; border-radius: 50%; font-size: 0.7rem; width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; font-weight: bold; border: 2px solid var(--bg-primary);"><?php echo $unreadCount; ?></span>
                <?php endif; ?>
            </button>
            
            <div id="notif-panel" class="card" style="display: none; position: absolute; right: 0; top: 50px; width: 320px; z-index: 500; padding: 12px; box-shadow: var(--shadow-lg);">
                <h4 style="margin-bottom: 10px; border-bottom: 1px solid var(--border-color); padding-bottom: 6px;">Recent Alerts</h4>
                <div style="display:flex; flex-direction:column; gap: 8px; max-height: 250px; overflow-y:auto;">
                    <?php if (empty($notifications)): ?>
                        <div style="text-align:center; color: var(--text-secondary); font-size:0.85rem; padding: 10px;">No alerts.</div>
                    <?php else: ?>
                        <?php foreach ($notifications as $n): ?>
                            <div style="font-size: 0.85rem; border-bottom: 1px solid var(--border-color); padding-bottom: 6px; <?php echo !$n['is_read'] ? 'border-left: 2px solid var(--primary); padding-left: 6px;' : ''; ?>">
                                <strong><?php echo htmlspecialchars($n['title']); ?></strong>
                                <p style="color: var(--text-secondary);"><?php echo htmlspecialchars($n['message']); ?></p>
                                <span style="font-size:0.7rem; color: var(--text-muted);"><?php echo date('d M, h:i A', strtotime($n['created_at'])); ?></span>
                            </div>
                        <?php endforeach; ?>
                        <button class="btn btn-secondary btn-block" style="padding: 6px; font-size: 0.8rem; margin-top: 6px;" onclick="markAllNotificationsRead()">Mark all read</button>
                    <?php endif; ?>
                </div>
            </div>
            <style>
                #notif-panel.active { display: block !important; }
            </style>
        </div>
    </div>

    <!-- Analytics Dashboard Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div class="card" style="display: flex; align-items: center; gap: 16px; padding: 20px;">
            <div style="width: 50px; height: 50px; border-radius: var(--radius-sm); background: rgba(16, 185, 129, 0.1); border: 1px solid var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: var(--primary);">
                <i class="fa-solid fa-indian-rupee-sign"></i>
            </div>
            <div>
                <span style="font-size: 0.85rem; color: var(--text-secondary);">Total Payouts</span>
                <h3 style="font-size: 1.5rem; font-weight: 800;">₹<?php echo number_format($totalCollected, 2); ?></h3>
            </div>
        </div>
        
        <div class="card" style="display: flex; align-items: center; gap: 16px; padding: 20px;">
            <div style="width: 50px; height: 50px; border-radius: var(--radius-sm); background: rgba(245, 158, 11, 0.1); border: 1px solid var(--warning); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: var(--warning);">
                <i class="fa-solid fa-truck-pickup"></i>
            </div>
            <div>
                <span style="font-size: 0.85rem; color: var(--text-secondary);">Active Pickups</span>
                <h3 style="font-size: 1.5rem; font-weight: 800;"><?php echo $activeCount; ?></h3>
            </div>
        </div>

        <div class="card" style="display: flex; align-items: center; gap: 16px; padding: 20px;">
            <div style="width: 50px; height: 50px; border-radius: var(--radius-sm); background: rgba(59, 130, 246, 0.1); border: 1px solid var(--info); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: var(--info);">
                <i class="fa-solid fa-user-group"></i>
            </div>
            <div>
                <span style="font-size: 0.85rem; color: var(--text-secondary);">Total Customers</span>
                <h3 style="font-size: 1.5rem; font-weight: 800;"><?php echo $customerCount; ?></h3>
            </div>
        </div>

        <div class="card" style="display: flex; align-items: center; gap: 16px; padding: 20px;">
            <div style="width: 50px; height: 50px; border-radius: var(--radius-sm); background: rgba(16, 185, 129, 0.1); border: 1px solid var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: var(--primary);">
                <i class="fa-solid fa-people-carry-box"></i>
            </div>
            <div>
                <span style="font-size: 0.85rem; color: var(--text-secondary);">Active Collectors</span>
                <h3 style="font-size: 1.5rem; font-weight: 800;"><?php echo $staffCount; ?></h3>
            </div>
        </div>
    </div>

    <!-- Filter Buttons -->
    <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 20px;">
        <a href="?status=all" class="btn <?php echo $status_filter == 'all' ? 'btn-primary' : 'btn-secondary'; ?>" style="padding: 8px 16px; font-size: 0.85rem;">All Orders</a>
        <a href="?status=pending" class="btn <?php echo $status_filter == 'pending' ? 'btn-primary' : 'btn-secondary'; ?>" style="padding: 8px 16px; font-size: 0.85rem;">Pending</a>
        <a href="?status=assigned" class="btn <?php echo $status_filter == 'assigned' ? 'btn-primary' : 'btn-secondary'; ?>" style="padding: 8px 16px; font-size: 0.85rem;">Assigned</a>
        <a href="?status=collected" class="btn <?php echo $status_filter == 'collected' ? 'btn-primary' : 'btn-secondary'; ?>" style="padding: 8px 16px; font-size: 0.85rem;">Collected</a>
        <a href="?status=cancelled" class="btn <?php echo $status_filter == 'cancelled' ? 'btn-primary' : 'btn-secondary'; ?>" style="padding: 8px 16px; font-size: 0.85rem;">Cancelled</a>
    </div>

    <!-- Orders Data Grid Table -->
    <div class="card" style="padding: 0; overflow: hidden;">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Pickup Date/Slot</th>
                        <th>Address</th>
                        <th>Est. Price</th>
                        <th>Actual Price</th>
                        <th>Assigned Staff</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; color: var(--text-secondary); padding: 30px;">
                                No orders matching the current filter.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td style="font-weight: 700; color: var(--text-primary);">#MK-<?php echo $order['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong><br>
                                    <span style="font-size: 0.75rem; color: var(--text-secondary);"><?php echo $order['customer_phone']; ?></span>
                                </td>
                                <td>
                                    <?php echo date('d M Y', strtotime($order['pickup_date'])); ?><br>
                                    <span style="font-size:0.75rem; color: var(--text-secondary);"><?php echo $order['pickup_time_slot']; ?></span>
                                </td>
                                <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?php echo htmlspecialchars($order['pickup_address']); ?>
                                </td>
                                <td>₹<?php echo number_format($order['total_estimated_price'], 2); ?></td>
                                <td style="font-weight: 600; color: var(--success);">
                                    <?php echo $order['total_actual_price'] !== null ? '₹' . number_format($order['total_actual_price'], 2) : '-'; ?>
                                </td>
                                <td>
                                    <?php echo $order['staff_name'] ? htmlspecialchars($order['staff_name']) : '<span style="color: var(--text-muted); font-style:italic;">Unassigned</span>'; ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $order['status']; ?>"><?php echo $order['status']; ?></span>
                                </td>
                                <td>
                                    <button class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem; border-radius:4px;" onclick="loadOrderDetails(<?php echo $order['id']; ?>)">
                                        Manage
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Manage Order Modal Drawer -->
<div id="manage-modal" class="modal-overlay">
    <div class="modal-container" style="max-width: 550px;">
        <div class="modal-header">
            <h3 id="modal-title" style="font-weight: 800; font-size: 1.3rem;">Order Details</h3>
            <span class="modal-close" onclick="closeModal('manage-modal')">&times;</span>
        </div>
        
        <div id="modal-body-content" style="max-height: 400px; overflow-y: auto; padding-right: 4px; display: flex; flex-direction: column; gap: 16px;">
            <!-- Loaded dynamically via JS -->
            <div style="text-align:center; padding: 20px; color: var(--text-secondary);"><i class="fa-solid fa-spinner fa-spin"></i> Loading...</div>
        </div>
        
        <div style="border-top: 1px solid var(--border-color); padding-top: 16px; margin-top: 20px;">
            <form id="order-update-form" onsubmit="submitOrderUpdate(event)">
                <input type="hidden" id="modal-order-id" name="order_id">
                
                <div class="form-group">
                    <label for="assign_staff">Assign Scrap Collector</label>
                    <select id="assign_staff" name="assigned_staff_id" class="form-control">
                        <option value="">Choose Staff Collector</option>
                        <?php foreach ($staff_list as $st): ?>
                            <option value="<?php echo $st['id']; ?>"><?php echo htmlspecialchars($st['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="order_status">Modify Status</label>
                    <select id="order_status" name="status" class="form-control" required>
                        <option value="pending">Pending</option>
                        <option value="assigned">Assigned</option>
                        <option value="collected">Collected (Completed)</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="admin_notes">Admin Internal Notes (Optional)</label>
                    <input type="text" id="admin_notes" name="admin_notes" class="form-control" placeholder="Internal remarks">
                </div>
                
                <button type="submit" id="modal-submit-btn" class="btn btn-primary btn-block">
                    Update Booking Details <i class="fa-solid fa-check"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
/**
 * Load detailed order parameters into modal drawer via AJAX
 */
function loadOrderDetails(orderId) {
    openModal('manage-modal');
    
    const bodyContent = document.getElementById('modal-body-content');
    bodyContent.innerHTML = '<div style="text-align:center; padding: 30px; color: var(--text-secondary);"><i class="fa-solid fa-spinner fa-spin fa-2x"></i><br><br>Retrieving order details...</div>';
    
    document.getElementById('modal-order-id').value = orderId;
    
    // Fetch details
    fetch(`<?php echo BASE_URL; ?>api/update_order.php?action=details&order_id=${orderId}`)
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Populate form
            document.getElementById('assign_staff').value = data.order.assigned_staff_id || '';
            document.getElementById('order_status').value = data.order.status;
            document.getElementById('admin_notes').value = data.order.admin_notes || '';
            
            // Build items HTML
            let itemsHtml = '<table style="width:100%; font-size:0.85rem; border-collapse:collapse; margin-top: 10px;">';
            itemsHtml += '<tr style="border-bottom:1px solid var(--border-color); font-weight:600; color:var(--text-secondary);"><th style="padding: 6px 0;">Item</th><th style="text-align:right;">Est. Quantity</th><th style="text-align:right;">Actual Qty</th><th style="text-align:right;">Rate</th></tr>';
            
            data.items.forEach(it => {
                itemsHtml += `<tr style="border-bottom:1px solid var(--border-color);">
                    <td style="padding: 8px 0; font-weight:500;">${it.category_name}</td>
                    <td style="text-align:right;">${it.estimated_quantity} ${it.unit}</td>
                    <td style="text-align:right; font-weight:600; color:var(--primary);">${it.actual_quantity !== null ? it.actual_quantity + ' ' + it.unit : '-'}</td>
                    <td style="text-align:right;">₹${it.rate_at_order}</td>
                </tr>`;
            });
            itemsHtml += '</table>';
            
            // Generate details HTML
            bodyContent.innerHTML = `
                <div>
                    <strong>Customer:</strong> ${escapeHtml(data.order.customer_name)} (${data.order.customer_phone})<br>
                    <strong>Scheduled Date:</strong> ${data.order.pickup_date} (${data.order.pickup_time_slot})<br>
                    <strong>Address:</strong> ${escapeHtml(data.order.pickup_address)}
                    ${data.order.lat ? `<br><a href="https://www.openstreetmap.org/?mlat=${data.order.lat}&mlon=${data.order.lng}#map=17/${data.order.lat}/${data.order.lng}" target="_blank" style="font-size:0.8rem; display:inline-flex; align-items:center; gap:4px; margin-top:4px;"><i class="fa-solid fa-map-location-dot"></i> View on OpenStreetMap</a>` : ''}
                </div>
                
                ${data.order.customer_notes ? `
                    <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); padding: 8px; border-radius: 4px; font-size: 0.85rem;">
                        <strong>Customer Instructions:</strong><br>${escapeHtml(data.order.customer_notes)}
                    </div>
                ` : ''}
                
                ${data.order.staff_notes ? `
                    <div style="background: rgba(16, 185, 129, 0.05); border: 1px solid rgba(16, 185, 129, 0.2); padding: 8px; border-radius: 4px; font-size: 0.85rem;">
                        <strong>Collector Field Notes:</strong><br>${escapeHtml(data.order.staff_notes)}
                    </div>
                ` : ''}
                
                <div>
                    <strong>Scrap Items Booked:</strong>
                    ${itemsHtml}
                </div>
            `;
            
            document.getElementById('modal-title').innerText = `Manage Order #MK-${orderId}`;
        } else {
            showToast(data.message, 'error');
            closeModal('manage-modal');
        }
    })
    .catch(err => {
        console.error(err);
        showToast('Failed to load details.', 'error');
        closeModal('manage-modal');
    });
}

/**
 * Submit order status updates & assignments
 */
function submitOrderUpdate(e) {
    e.preventDefault();
    
    const form = document.getElementById('order-update-form');
    const formData = new FormData(form);
    
    const btn = document.getElementById('modal-submit-btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving changes...';
    
    fetch('<?php echo BASE_URL; ?>api/update_order.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast('Order details updated successfully.', 'success');
            closeModal('manage-modal');
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast(data.message, 'error');
            btn.disabled = false;
            btn.innerHTML = 'Update Booking Details <i class="fa-solid fa-check"></i>';
        }
    })
    .catch(err => {
        console.error(err);
        showToast('Failed to update details.', 'error');
        btn.disabled = false;
        btn.innerHTML = 'Update Booking Details <i class="fa-solid fa-check"></i>';
    });
}

/**
 * Mark all notifications for this admin as read
 */
function markAllNotificationsRead() {
    const formData = new FormData();
    formData.append('action', 'read_all');
    
    fetch('<?php echo BASE_URL; ?>api/update_order.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast('All notifications marked as read.', 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }
    });
}

/**
 * Simple HTML Escaper
 */
function escapeHtml(text) {
    if (!text) return '';
    return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
