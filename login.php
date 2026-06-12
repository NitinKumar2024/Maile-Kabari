<?php
$page_title = "Login / Register";
$meta_description = "Log in or register an account on Maile Kabari (mailekabari.in) to sell scrap online. Get door-to-door waste collection services, digital weights, and fast cash payouts.";
$meta_keywords = "maile kabari login, register maile kabari, online kabadiwala account, sell scrap signup";
require_once __DIR__ . '/includes/header.php';

// If user is already logged in, redirect to correct dashboard
if (is_logged_in()) {
    if (has_role('admin')) {
        redirect('admin/dashboard.php');
    } elseif (has_role('staff')) {
        redirect('staff/dashboard.php');
    } else {
        redirect('customer/dashboard.php');
    }
}
?>

<div class="mobile-container" style="justify-content: center; min-height: calc(100vh - 120px);">
    <div class="card" style="text-align: center;">
        <h2 style="margin-bottom: 8px;">Welcome to Maile Kabari</h2>
        <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 24px;">Convert your scrap to instant cash</p>
        
        <!-- Auth Tab Handles -->
        <div class="auth-tabs">
            <div id="tab-login-btn" class="auth-tab active" onclick="switchTab('login')">
                <i class="fa-solid fa-right-to-bracket"></i> Login
            </div>
            <div id="tab-register-btn" class="auth-tab" onclick="switchTab('register')">
                <i class="fa-solid fa-user-plus"></i> Register
            </div>
        </div>
        
        <!-- Login Form Panel -->
        <div id="panel-login" class="auth-content-panel active">
            <form id="form-login" onsubmit="handleAuth(event, 'login')">
                <div class="form-group">
                    <label for="login-phone">Phone Number</label>
                    <input type="tel" id="login-phone" name="phone" class="form-control" placeholder="Enter 10-digit number" required pattern="[0-9]{10}">
                </div>
                <div class="form-group">
                    <label for="login-password">Password</label>
                    <input type="password" id="login-password" name="password" class="form-control" placeholder="Enter password" required>
                </div>
                <div class="form-group" style="flex-direction: row; gap: 8px; align-items: center; cursor: pointer;">
                    <input type="checkbox" id="login-remember" name="remember" checked style="accent-color: var(--primary); width: 18px; height: 18px;">
                    <label for="login-remember" style="margin-bottom: 0; cursor: pointer; user-select: none;">Keep me logged in</label>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block" style="margin-top: 10px;">
                    Login <i class="fa-solid fa-arrow-right-to-bracket"></i>
                </button>
            </form>
        </div>
        
        <!-- Register Form Panel -->
        <div id="panel-register" class="auth-content-panel">
            <form id="form-register" onsubmit="handleAuth(event, 'register')">
                <div class="form-group">
                    <label for="register-name">Full Name</label>
                    <input type="text" id="register-name" name="name" class="form-control" placeholder="Enter full name" required>
                </div>
                <div class="form-group">
                    <label for="register-phone">Phone Number</label>
                    <input type="tel" id="register-phone" name="phone" class="form-control" placeholder="Enter 10-digit phone" required pattern="[0-9]{10}">
                </div>
                <div class="form-group">
                    <label for="register-password">Password</label>
                    <input type="password" id="register-password" name="password" class="form-control" placeholder="Create strong password" required minlength="6">
                </div>
                
                <button type="submit" class="btn btn-primary btn-block" style="margin-top: 10px;">
                    Register & Login <i class="fa-solid fa-user-check"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
/**
 * Switch tabs between login and registration panels
 */
function switchTab(tab) {
    document.querySelectorAll('.auth-tab').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.auth-content-panel').forEach(el => el.classList.remove('active'));
    
    if (tab === 'login') {
        document.getElementById('tab-login-btn').classList.add('active');
        document.getElementById('panel-login').classList.add('active');
    } else {
        document.getElementById('tab-register-btn').classList.add('active');
        document.getElementById('panel-register').classList.add('active');
    }
}

/**
 * Handle authentication form submissions via AJAX
 */
function handleAuth(event, action) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    formData.append('action', action);
    
    // Disable submit buttons to prevent double click
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';
    
    fetch('<?php echo BASE_URL; ?>api/login_register.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            
            // Redirect to appropriate dashboard based on user role
            setTimeout(() => {
                if (data.role === 'admin') {
                    window.location.href = 'admin/dashboard.php';
                } else if (data.role === 'staff') {
                    window.location.href = 'staff/dashboard.php';
                } else {
                    window.location.href = 'customer/dashboard.php';
                }
            }, 1000);
        } else {
            showToast(data.message, 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Something went wrong. Please try again.', 'error');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
