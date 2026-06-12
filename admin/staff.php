<?php
$page_title = "Manage Staff";
require_once __DIR__ . '/../includes/header.php';

// Check admin authorization
if (!is_logged_in() || !has_role('admin')) {
    redirect('login.php');
}

try {
    // Fetch all staff members and count their completed collections
    $stmt = $pdo->query("
        SELECT u.id, u.name, u.phone, u.status, COUNT(o.id) AS completed_pickups 
        FROM users u 
        LEFT JOIN orders o ON u.id = o.assigned_staff_id AND o.status = 'collected' 
        WHERE u.role = 'staff' 
        GROUP BY u.id 
        ORDER BY u.created_at DESC
    ");
    $staff_list = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<div class="container" style="padding-top: 30px; padding-bottom: 40px;">
    <!-- Back to Dashboard Header -->
    <div style="margin-bottom: 24px;">
        <a href="<?php echo BASE_URL; ?>admin/dashboard.php" style="color: var(--text-secondary); font-size: 0.9rem; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 12px;">
            <i class="fa-solid fa-arrow-left"></i> Back to Console
        </a>
        <h1 style="font-size: 1.8rem; font-weight: 800;">Staff Management</h1>
        <p style="color: var(--text-secondary);">Register and manage scrap collectors who buy scrap from customers.</p>
    </div>

    <div class="dashboard-grid">
        <!-- Add Staff Form -->
        <div class="card" style="height: fit-content;">
            <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                <i class="fa-solid fa-user-plus" style="color: var(--primary);"></i> Add New Staff
            </h3>
            
            <form id="add-staff-form" onsubmit="addStaff(event)">
                <div class="form-group">
                    <label for="staff-name">Full Name</label>
                    <input type="text" id="staff-name" name="name" class="form-control" placeholder="e.g. Ram Kumar" required>
                </div>
                <div class="form-group">
                    <label for="staff-phone">Phone Number (Login username)</label>
                    <input type="tel" id="staff-phone" name="phone" class="form-control" placeholder="10-digit phone number" required pattern="[0-9]{10}">
                </div>
                <div class="form-group">
                    <label for="staff-password">Access Password</label>
                    <input type="password" id="staff-password" name="password" class="form-control" placeholder="Password for login" required minlength="6">
                </div>
                
                <button type="submit" id="add-btn" class="btn btn-primary btn-block" style="margin-top: 10px;">
                    Register Collector <i class="fa-solid fa-plus"></i>
                </button>
            </form>
        </div>

        <!-- Staff List Grid -->
        <div class="card">
            <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                <i class="fa-solid fa-people-carry-box" style="color: var(--primary);"></i> Registered Staff
            </h3>
            
            <?php if (empty($staff_list)): ?>
                <div style="text-align: center; color: var(--text-secondary); padding: 30px 10px;">
                    <i class="fa-solid fa-users-slash" style="font-size: 2rem; color: var(--border-color); margin-bottom: 10px;"></i>
                    <p style="font-size: 0.9rem;">No staff registered yet.</p>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <?php foreach ($staff_list as $st): ?>
                        <div style="background: rgba(255,255,255,0.01); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 16px; display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <strong style="font-size: 1rem; color: var(--text-primary);"><?php echo htmlspecialchars($st['name']); ?></strong><br>
                                <span style="font-size: 0.8rem; color: var(--text-secondary);"><i class="fa-solid fa-phone"></i> <?php echo $st['phone']; ?></span><br>
                                <span style="font-size: 0.75rem; color: var(--text-muted);"><i class="fa-solid fa-check-double"></i> Completed Pickups: <?php echo $st['completed_pickups']; ?></span>
                            </div>
                            
                            <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 8px;">
                                <span class="badge <?php echo $st['status'] === 'active' ? 'badge-collected' : 'badge-cancelled'; ?>" style="font-size: 0.7rem; padding: 2px 8px;">
                                    <?php echo $st['status']; ?>
                                </span>
                                
                                <button onclick="toggleStaffStatus(<?php echo $st['id']; ?>, '<?php echo $st['status']; ?>')" class="btn btn-secondary" style="padding: 4px 8px; font-size: 0.75rem; border-radius: 4px;">
                                    <?php echo $st['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
/**
 * Create a new staff collector profile via AJAX
 */
function addStaff(event) {
    event.preventDefault();
    
    const form = document.getElementById('add-staff-form');
    const formData = new FormData(form);
    formData.append('action', 'add');
    
    const btn = document.getElementById('add-btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Adding...';
    
    fetch('<?php echo BASE_URL; ?>api/manage_staff.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast('Staff collector registered successfully!', 'success');
            form.reset();
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast(data.message, 'error');
            btn.disabled = false;
            btn.innerHTML = 'Register Collector <i class="fa-solid fa-plus"></i>';
        }
    })
    .catch(() => {
        showToast('Registration failed. Try again.', 'error');
        btn.disabled = false;
        btn.innerHTML = 'Register Collector <i class="fa-solid fa-plus"></i>';
    });
}

/**
 * Toggle staff active/inactive state
 */
function toggleStaffStatus(id, currentStatus) {
    const nextStatus = currentStatus === 'active' ? 'inactive' : 'active';
    if (!confirm(`Are you sure you want to ${nextStatus === 'active' ? 'activate' : 'deactivate'} this staff member?`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'toggle_status');
    formData.append('staff_id', id);
    formData.append('status', nextStatus);
    
    fetch('<?php echo BASE_URL; ?>api/manage_staff.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast('Staff status updated successfully.', 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(() => {
        showToast('Failed to update status.', 'error');
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
