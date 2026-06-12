<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/auth_check.php';

// Get current page file name for active styling
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . SITE_NAME : SITE_NAME . ' | Best Online Kabadiwala - Doorstep Scrap Pickup'; ?></title>
    
    <!-- SEO Optimization Meta Tags -->
    <meta name="description" content="<?php echo isset($meta_description) ? htmlspecialchars($meta_description) : 'Maile Kabari (mailekabari.in) is India\'s premium online kabadiwala service. Schedule a doorstep scrap pickup for iron, plastic, cardboard, paper, copper, and zinc. Get instant cash and accurate digital weights.'; ?>">
    <meta name="keywords" content="<?php echo isset($meta_keywords) ? htmlspecialchars($meta_keywords) : 'online kabadiwala, sell scrap online, scrap buyer online, kabadiwala near me, doorstep scrap collection, scrap metal price India, sell paper cardboard, scrap collector'; ?>">
    <meta name="author" content="Maile Kabari">
    <meta name="robots" content="index, follow">
    
    <!-- Canonical Link to prevent duplicate content -->
    <link rel="canonical" href="<?php echo isset($meta_canonical) ? htmlspecialchars($meta_canonical) : 'https://mailekabari.in' . $_SERVER['REQUEST_URI']; ?>">
    
    <!-- Open Graph / Facebook Meta Tags -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://mailekabari.in<?php echo $_SERVER['REQUEST_URI']; ?>">
    <meta property="og:title" content="<?php echo isset($page_title) ? $page_title . ' - ' . SITE_NAME : SITE_NAME; ?>">
    <meta property="og:description" content="<?php echo isset($meta_description) ? htmlspecialchars($meta_description) : 'Schedule a doorstep scrap pickup in India with Maile Kabari. Accurate weights, fair prices, and instant offline cash/UPI payments.'; ?>">
    <meta property="og:image" content="https://mailekabari.in/assets/images/og-share-banner.jpg">
    
    <!-- Twitter Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="https://mailekabari.in<?php echo $_SERVER['REQUEST_URI']; ?>">
    <meta name="twitter:title" content="<?php echo isset($page_title) ? $page_title . ' - ' . SITE_NAME : SITE_NAME; ?>">
    <meta name="twitter:description" content="<?php echo isset($meta_description) ? htmlspecialchars($meta_description) : 'Sell your household and business scrap online. Easy schedule, digital scale weighing, and instant cash payments.'; ?>">
    
    <!-- Mobile App Properties -->
    <meta name="theme-color" content="#10b981">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon.png" />
    
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Leaflet.js Map CSS (Interactive OpenStreetMap) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    
    <!-- Core Application CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
</head>
<body>

    <!-- Main Navigation Bar -->
    <header class="app-header">
        <div class="container header-wrap">
            <a href="<?php echo BASE_URL; ?>" class="logo">
                <i class="fa-solid fa-recycle"></i>
                Maile <span>Kabari</span>
            </a>
            
            <nav class="nav-links">
                <?php if (is_logged_in()): ?>
                    <?php if (has_role('admin')): ?>
                        <!-- Admin Navigation -->
                        <a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="<?php echo $current_page == 'dashboard.php' && strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? 'active' : ''; ?>">
                            <i class="fa-solid fa-chart-line"></i> Dashboard
                        </a>
                        <a href="<?php echo BASE_URL; ?>admin/staff.php" class="<?php echo $current_page == 'staff.php' ? 'active' : ''; ?>">
                            <i class="fa-solid fa-users"></i> Staff
                        </a>
                        <a href="<?php echo BASE_URL; ?>admin/rates.php" class="<?php echo $current_page == 'rates.php' ? 'active' : ''; ?>">
                            <i class="fa-solid fa-indian-rupee-sign"></i> Rates
                        </a>
                    <?php elseif (has_role('staff')): ?>
                        <!-- Staff Navigation -->
                        <a href="<?php echo BASE_URL; ?>staff/dashboard.php" class="<?php echo $current_page == 'dashboard.php' && strpos($_SERVER['PHP_SELF'], '/staff/') !== false ? 'active' : ''; ?>">
                            <i class="fa-solid fa-truck-ramp-box"></i> Tasks
                        </a>
                    <?php else: ?>
                        <!-- Customer Navigation -->
                        <a href="<?php echo BASE_URL; ?>customer/dashboard.php" class="<?php echo $current_page == 'dashboard.php' && strpos($_SERVER['PHP_SELF'], '/customer/') !== false ? 'active' : ''; ?>">
                            <i class="fa-solid fa-house"></i> Home
                        </a>
                        <a href="<?php echo BASE_URL; ?>customer/new_request.php" class="<?php echo $current_page == 'new_request.php' ? 'active' : ''; ?>">
                            <i class="fa-solid fa-plus-circle"></i> Sell Scrap
                        </a>
                        <a href="<?php echo BASE_URL; ?>customer/profile.php" class="<?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                            <i class="fa-solid fa-user"></i> Profile
                        </a>
                    <?php endif; ?>
                    
                    <!-- Shared Logout Button -->
                    <a href="<?php echo BASE_URL; ?>logout.php" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.85rem;">
                        <i class="fa-solid fa-right-from-bracket"></i> Logout
                    </a>
                <?php else: ?>
                    <!-- Guest Navigation -->
                    <a href="<?php echo BASE_URL; ?>login.php" class="btn btn-primary" style="padding: 8px 16px; font-size: 0.9rem;">
                        <i class="fa-solid fa-right-to-bracket"></i> Login / Register
                    </a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="flex-grow">
