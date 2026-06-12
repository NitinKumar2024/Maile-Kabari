<?php
$page_title = "Collect Scrap";
require_once __DIR__ . '/../includes/header.php';

// Check staff authorization
if (!is_logged_in() || !has_role('staff')) {
    redirect('login.php');
}

$order_id = intval($_GET['order_id'] ?? 0);
$staff_id = $_SESSION['user_id'];

try {
    // 1. Fetch order details to verify assignment
    $orderStmt = $pdo->prepare("
        SELECT o.*, u.name AS customer_name, u.phone AS customer_phone 
        FROM `orders` o 
        JOIN `users` u ON o.user_id = u.id 
        WHERE o.id = ? AND o.assigned_staff_id = ? LIMIT 1
    ");
    $orderStmt->execute([$order_id, $staff_id]);
    $order = $orderStmt->fetch();
    
    if (!$order) {
        redirect('staff/dashboard.php');
    }
    
    // Redirect if already completed
    if ($order['status'] !== 'assigned') {
        redirect('staff/dashboard.php');
    }
    
    // 2. Fetch order items
    $itemsStmt = $pdo->prepare("
        SELECT oi.*, sc.name AS category_name, sc.unit 
        FROM `order_items` oi 
        JOIN `scrap_categories` sc ON oi.category_id = sc.id 
        WHERE oi.order_id = ?
    ");
    $itemsStmt->execute([$order_id]);
    $items = $itemsStmt->fetchAll();
    
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<div class="mobile-container" style="padding-top: 20px;">
    <!-- Header -->
    <div style="margin-bottom: 20px;">
        <a href="<?php echo BASE_URL; ?>staff/dashboard.php" style="color: var(--text-secondary); font-size: 0.9rem; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 12px;">
            <i class="fa-solid fa-arrow-left"></i> Back to Tasks
        </a>
        <h2 style="font-size: 1.4rem; font-weight: 800;">Collect Scrap: #MK-<?php echo $order_id; ?></h2>
    </div>

    <!-- Customer Card -->
    <div class="card" style="margin-bottom: 20px; padding: 16px;">
        <h4 style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 8px; border-bottom: 1px solid var(--border-color); padding-bottom: 4px;">Customer Info</h4>
        <div style="font-size: 0.95rem; line-height: 1.5;">
            <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong><br>
            <a href="tel:<?php echo $order['customer_phone']; ?>" style="font-size: 0.85rem;"><i class="fa-solid fa-phone"></i> <?php echo $order['customer_phone']; ?></a><br>
            <span style="color: var(--text-secondary); font-size: 0.85rem;"><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($order['pickup_address']); ?></span>
        </div>
    </div>

    <!-- Collection Form -->
    <form id="collect-form" onsubmit="preventSubmit(event)">
        <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
        <input type="hidden" name="status" value="collected">
        
        <h3 style="font-size: 1.05rem; font-weight: 700; margin-bottom: 12px; color: var(--text-primary);">Input Measured Weights</h3>
        
        <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 24px;">
            <?php foreach ($items as $item): ?>
                <div class="card" style="padding: 16px; background: rgba(255,255,255,0.015);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <div>
                            <strong style="font-size: 0.95rem; color: var(--text-primary);"><?php echo htmlspecialchars($item['category_name']); ?></strong>
                            <span style="font-size: 0.8rem; color: var(--text-secondary);"> (₹<?php echo $item['rate_at_order']; ?>/<?php echo $item['unit']; ?>)</span>
                        </div>
                        <span style="font-size: 0.75rem; color: var(--text-muted);">Est: <?php echo $item['estimated_quantity']; ?> <?php echo $item['unit']; ?></span>
                    </div>
                    
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px;">
                        <!-- Actual weight input -->
                        <div style="display: flex; align-items: center; gap: 8px; flex: 2;">
                            <input type="number" step="0.05" min="0" class="form-control actual-weight-input" id="act-weight-<?php echo $item['category_id']; ?>" name="actual_weights[<?php echo $item['category_id']; ?>]" value="<?php echo $item['estimated_quantity']; ?>" required style="height: 38px; padding: 6px 12px; font-weight: 700; text-align: right;" oninput="updateItemSubtotal(<?php echo $item['category_id']; ?>, <?php echo $item['rate_at_order']; ?>)">
                            <span style="font-size: 0.85rem; color: var(--text-secondary);"><?php echo $item['unit']; ?></span>
                        </div>
                        
                        <!-- Calculated subtotal -->
                        <div style="text-align: right; flex: 1;">
                            <span style="font-size: 0.75rem; color: var(--text-muted);">Payout</span><br>
                            <span id="subtotal-<?php echo $item['category_id']; ?>" style="font-weight: 700; color: var(--primary); font-size: 1.05rem;">
                                ₹<?php echo number_format($item['rate_at_order'] * $item['estimated_quantity'], 2); ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Total Payout Banner -->
        <div class="card" style="background: rgba(16, 185, 129, 0.05); border-color: rgba(16, 185, 129, 0.2); text-align: center; margin-bottom: 20px; padding: 16px;">
            <span style="font-size: 0.85rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">Total Offline Cash To Pay</span>
            <div id="collect-total-payout" style="font-size: 1.8rem; font-weight: 800; color: var(--primary); margin-top: 4px;">
                ₹0.00
            </div>
        </div>
        
        <!-- Field Notes -->
        <div class="form-group">
            <label for="staff_notes">Field Notes / Remarks (Optional)</label>
            <input type="text" id="staff_notes" name="staff_notes" class="form-control" placeholder="e.g. Customer was very helpful, accurate weights">
        </div>
        
        <!-- Swipe/Slide to Confirm Slider Button -->
        <h4 style="font-size: 0.8rem; text-transform: uppercase; color: var(--text-muted); text-align: center; margin-top: 24px; letter-spacing: 0.5px;">Slide to complete trade</h4>
        <div class="slider-container" id="swipe-slider">
            <div class="slider-text">SLIDE RIGHT TO CONFIRM</div>
            <div class="slider-button" id="swipe-btn"></div>
        </div>
    </form>
</div>

<script>
/**
 * Prevent traditional form submit since we use the swipe slider
 */
function preventSubmit(e) {
    e.preventDefault();
}

/**
 * Update individual item subtotal on weight input
 */
function updateItemSubtotal(catId, rate) {
    const weightInput = document.getElementById('act-weight-' + catId);
    const weight = parseFloat(weightInput.value) || 0;
    const subtotal = rate * weight;
    
    document.getElementById('subtotal-' + catId).innerText = '₹' + subtotal.toFixed(2);
    calculateTotalPayout();
}

/**
 * Calculate the overall sum of payouts from inputs
 */
function calculateTotalPayout() {
    let total = 0;
    const inputs = document.querySelectorAll('.actual-weight-input');
    
    inputs.forEach(input => {
        const catId = input.id.replace('act-weight-', '');
        const subtotalText = document.getElementById('subtotal-' + catId).innerText;
        const subtotalVal = parseFloat(subtotalText.replace('₹', '')) || 0;
        total += subtotalVal;
    });
    
    document.getElementById('collect-total-payout').innerText = '₹' + total.toFixed(2);
}

// Calculate initial total on page load
document.addEventListener('DOMContentLoaded', () => {
    calculateTotalPayout();
    initSwipeSlider();
});

/**
 * Swipe to confirm logic (Mouse + Touch drag)
 */
function initSwipeSlider() {
    const slider = document.getElementById('swipe-slider');
    const btn = document.getElementById('swipe-btn');
    
    let isDragging = false;
    let startX = 0;
    let maxDrag = slider.clientWidth - btn.clientWidth - 6; // Max bounds
    
    // Mouse events
    btn.addEventListener('mousedown', startDrag);
    window.addEventListener('mousemove', drag);
    window.addEventListener('mouseup', endDrag);
    
    // Touch events
    btn.addEventListener('touchstart', startDrag);
    window.addEventListener('touchmove', drag, { passive: false });
    window.addEventListener('touchend', endDrag);
    
    // Resize adjuster
    window.addEventListener('resize', () => {
        maxDrag = slider.clientWidth - btn.clientWidth - 6;
    });
    
    function startDrag(e) {
        isDragging = true;
        startX = (e.type === 'touchstart') ? e.touches[0].clientX : e.clientX;
        btn.style.transition = 'none';
    }
    
    function drag(e) {
        if (!isDragging) return;
        
        // Prevent scrolling on mobile drag
        if (e.type === 'touchmove') e.preventDefault();
        
        const clientX = (e.type === 'touchmove') ? e.touches[0].clientX : e.clientX;
        let deltaX = clientX - startX;
        
        // Bound checks
        if (deltaX < 0) deltaX = 0;
        if (deltaX > maxDrag) deltaX = maxDrag;
        
        btn.style.left = (deltaX + 3) + 'px';
        
        // Check for completed swipe
        if (deltaX >= maxDrag - 5) {
            triggerCollectionSubmit();
            isDragging = false;
        }
    }
    
    function endDrag() {
        if (!isDragging) return;
        isDragging = false;
        
        // If not completed, snap back to start
        btn.style.transition = 'left 0.2s ease-out';
        btn.style.left = '3px';
    }
}

/**
 * AJAX Submit collection data when swiped successfully
 */
function triggerCollectionSubmit() {
    const form = document.getElementById('collect-form');
    const formData = new FormData(form);
    
    // Disable inputs
    document.querySelectorAll('.actual-weight-input').forEach(input => input.disabled = true);
    document.getElementById('staff_notes').disabled = true;
    
    const slider = document.getElementById('swipe-slider');
    slider.style.background = 'rgba(16, 185, 129, 0.15)';
    document.querySelector('.slider-text').innerText = 'COMPLETING TRADE...';
    document.getElementById('swipe-btn').style.background = 'var(--success)';
    
    fetch('<?php echo BASE_URL; ?>api/update_order.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast('Order completed successfully! Payout saved.', 'success');
            setTimeout(() => {
                window.location.href = 'dashboard.php';
            }, 1200);
        } else {
            showToast(data.message, 'error');
            resetSlider();
        }
    })
    .catch(() => {
        showToast('Submission failed. Try again.', 'error');
        resetSlider();
    });
}

/**
 * Reset slider UI back to initial state
 */
function resetSlider() {
    document.querySelectorAll('.actual-weight-input').forEach(input => input.disabled = false);
    document.getElementById('staff_notes').disabled = false;
    
    const slider = document.getElementById('swipe-slider');
    slider.style.background = 'rgba(255, 255, 255, 0.03)';
    document.querySelector('.slider-text').innerText = 'SLIDE RIGHT TO CONFIRM';
    
    const btn = document.getElementById('swipe-btn');
    btn.style.background = 'var(--primary)';
    btn.style.transition = 'left 0.2s ease-out';
    btn.style.left = '3px';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
