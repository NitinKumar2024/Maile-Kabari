<?php
$page_title = "My Profile";
require_once __DIR__ . '/../includes/header.php';

// Check customer authorization
if (!is_logged_in() || !has_role('customer')) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

try {
    // Fetch latest user details from DB
    $stmt = $pdo->prepare("SELECT name, phone, email FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        redirect('logout.php');
    }
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<div class="mobile-container" style="padding-top: 20px;">
    <!-- Back to Dashboard Header -->
    <div style="margin-bottom: 20px;">
        <a href="<?php echo BASE_URL; ?>customer/dashboard.php" style="color: var(--text-secondary); font-size: 0.9rem; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 16px;">
            <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
        </a>
        <h2 style="font-size: 1.5rem; font-weight: 800;">Profile Settings</h2>
    </div>

    <!-- Edit Profile Card -->
    <div class="card" style="margin-bottom: 24px;">
        <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
            <i class="fa-solid fa-user-pen" style="color: var(--primary);"></i> Personal Details
        </h3>
        
        <form id="profile-form" onsubmit="updateProfile(event)">
            <input type="hidden" name="type" value="personal">
            
            <div class="form-group">
                <label>Phone Number (Username)</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>" disabled style="opacity: 0.6; cursor: not-allowed; background: rgba(0,0,0,0.2);">
                <span style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;"><i class="fa-solid fa-lock"></i> Phone number cannot be changed.</span>
            </div>
            
            <div class="form-group">
                <label for="profile-name">Full Name</label>
                <input type="text" id="profile-name" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="profile-email">Email Address (Optional)</label>
                <input type="email" id="profile-email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" placeholder="Add your email address">
            </div>
            
            <button type="submit" id="profile-btn" class="btn btn-primary btn-block">
                Save Changes <i class="fa-solid fa-floppy-disk"></i>
            </button>
        </form>
    </div>

    <!-- Change Password Card -->
    <div class="card">
        <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
            <i class="fa-solid fa-key" style="color: var(--primary);"></i> Change Password
        </h3>
        
        <form id="password-form" onsubmit="changePassword(event)">
            <input type="hidden" name="type" value="password">
            
            <div class="form-group">
                <label for="curr-pass">Current Password</label>
                <input type="password" id="curr-pass" name="current_password" class="form-control" required placeholder="Enter current password">
            </div>
            
            <div class="form-group">
                <label for="new-pass">New Password</label>
                <input type="password" id="new-pass" name="new_password" class="form-control" required placeholder="Min 6 characters" minlength="6">
            </div>
            
            <div class="form-group">
                <label for="conf-pass">Confirm New Password</label>
                <input type="password" id="conf-pass" name="confirm_password" class="form-control" required placeholder="Repeat new password">
            </div>
            
            <button type="submit" id="password-btn" class="btn btn-primary btn-block">
                Update Password <i class="fa-solid fa-key"></i>
            </button>
        </form>
    </div>
</div>

<script>
/**
 * Update personal profile details
 */
function updateProfile(event) {
    event.preventDefault();
    const form = document.getElementById('profile-form');
    const formData = new FormData(form);
    
    const btn = document.getElementById('profile-btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';
    
    fetch('<?php echo BASE_URL; ?>api/update_profile.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            // Wait brief moment then reload to see changes
            setTimeout(() => { window.location.reload(); }, 1000);
        } else {
            showToast(data.message, 'error');
            btn.disabled = false;
            btn.innerHTML = 'Save Changes <i class="fa-solid fa-floppy-disk"></i>';
        }
    })
    .catch(() => {
        showToast('Profile update failed. Try again.', 'error');
        btn.disabled = false;
        btn.innerHTML = 'Save Changes <i class="fa-solid fa-floppy-disk"></i>';
    });
}

/**
 * Handle password changes
 */
function changePassword(event) {
    event.preventDefault();
    
    const currPass = document.getElementById('curr-pass').value;
    const newPass = document.getElementById('new-pass').value;
    const confPass = document.getElementById('conf-pass').value;
    
    if (newPass !== confPass) {
        showToast('New passwords do not match.', 'error');
        return;
    }
    
    const form = document.getElementById('password-form');
    const formData = new FormData(form);
    
    const btn = document.getElementById('password-btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Updating...';
    
    fetch('<?php echo BASE_URL; ?>api/update_profile.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            form.reset();
            btn.disabled = false;
            btn.innerHTML = 'Update Password <i class="fa-solid fa-key"></i>';
        } else {
            showToast(data.message, 'error');
            btn.disabled = false;
            btn.innerHTML = 'Update Password <i class="fa-solid fa-key"></i>';
        }
    })
    .catch(() => {
        showToast('Password update failed. Try again.', 'error');
        btn.disabled = false;
        btn.innerHTML = 'Update Password <i class="fa-solid fa-key"></i>';
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
