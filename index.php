<?php
$page_title = "Online Kabadiwala | Doorstep Scrap Pickup & Selling";
$meta_description = "Sell scrap online in India at Maile Kabari (mailekabari.in). We provide doorstep scrap collection for iron, plastic, cardboard, books, papers, aluminium, copper, and zinc. Check live scrap rates, schedule a pickup, and get instant offline payouts.";
$meta_keywords = "online kabadiwala, sell scrap online, kabadiwala near me, scrap buyer online, doorstep scrap collection, scrap iron price per kg, sell old books cardboard, scrap metal price India";
$meta_canonical = "https://mailekabari.in/";
require_once __DIR__ . '/includes/header.php';
?>

<!-- JSON-LD LocalBusiness Schema Markup for Google SEO -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "RecyclingCenter",
  "name": "Maile Kabari",
  "image": "https://mailekabari.in/assets/images/og-share-banner.jpg",
  "@id": "https://mailekabari.in/",
  "url": "https://mailekabari.in/",
  "telephone": "+919876543210",
  "priceRange": "₹",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "Doorstep Service Areas",
    "addressLocality": "New Delhi",
    "addressRegion": "DL",
    "postalCode": "110001",
    "addressCountry": "IN"
  },
  "geo": {
    "@type": "GeoCoordinates",
    "latitude": 28.6139,
    "longitude": 77.2090
  },
  "description": "Premium online scrap collection and doorstep pickup service. Sell iron, metals, plastic, paper, cardboard, copper, and zinc for instant cash.",
  "openingHoursSpecification": {
    "@type": "OpeningHoursSpecification",
    "dayOfWeek": [
      "Monday",
      "Tuesday",
      "Wednesday",
      "Thursday",
      "Friday",
      "Saturday",
      "Sunday"
    ],
    "opens": "09:00",
    "closes": "18:00"
  }
}
</script>

<!-- Hero Section -->
<div class="mobile-container" style="padding-top: 40px; padding-bottom: 40px;">
    <div style="text-align: center; margin-bottom: 40px;">
        <div style="display: inline-flex; align-items: center; justify-content: center; width: 80px; height: 80px; border-radius: 50%; background: var(--bg-card); border: 2px solid var(--primary); margin-bottom: 20px; font-size: 2.5rem; color: var(--primary); box-shadow: 0 0 20px var(--primary-glow);">
            <i class="fa-solid fa-recycle"></i>
        </div>
        <h1 style="font-size: 2.2rem; font-weight: 800; line-height: 1.2; margin-bottom: 12px; letter-spacing: -0.5px;">
            Sell Your Scrap,<br>Get Paid <span style="color: var(--primary);">Instantly</span>
        </h1>
        <p style="color: var(--text-secondary); font-size: 1.05rem; margin-bottom: 30px;">
            Schedule a doorstep scrap pickup for iron, plastic, cardboard, papers, copper, and more in just three clicks.
        </p>
        
        <?php if (is_logged_in()): ?>
            <?php 
                $dashboard_link = 'customer/dashboard.php';
                if (has_role('admin')) $dashboard_link = 'admin/dashboard.php';
                elseif (has_role('staff')) $dashboard_link = 'staff/dashboard.php';
            ?>
            <a href="<?php echo BASE_URL . $dashboard_link; ?>" class="btn btn-primary btn-block" style="padding: 16px;">
                Go to Dashboard <i class="fa-solid fa-arrow-right"></i>
            </a>
        <?php else: ?>
            <a href="<?php echo BASE_URL; ?>login.php" class="btn btn-primary btn-block" style="padding: 16px;">
                Get Started Now <i class="fa-solid fa-arrow-right"></i>
            </a>
        <?php endif; ?>
    </div>

    <!-- Quick Stats Grid -->
    <div class="card" style="margin-bottom: 30px; padding: 20px 10px;">
        <div style="display: flex; justify-content: space-around; text-align: center;">
            <div>
                <h3 style="color: var(--primary); font-size: 1.6rem; font-weight: 800;">10K+</h3>
                <p style="color: var(--text-secondary); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">KG Recycled</p>
            </div>
            <div style="border-left: 1px solid var(--border-color); height: 40px; align-self: center;"></div>
            <div>
                <h3 style="color: var(--primary); font-size: 1.6rem; font-weight: 800;">3K+</h3>
                <p style="color: var(--text-secondary); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Pickups Completed</p>
            </div>
            <div style="border-left: 1px solid var(--border-color); height: 40px; align-self: center;"></div>
            <div>
                <h3 style="color: var(--primary); font-size: 1.6rem; font-weight: 800;">100%</h3>
                <p style="color: var(--text-secondary); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Fair Weights</p>
            </div>
        </div>
    </div>

    <!-- Scrap Items List -->
    <h3 style="margin-bottom: 16px; font-weight: 700; font-size: 1.2rem;">What We Buy</h3>
    <div class="card" style="margin-bottom: 30px; padding: 16px;">
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
            <div style="display: flex; align-items: center; gap: 10px; background: rgba(255,255,255,0.01); border: 1px solid var(--border-color); padding: 10px; border-radius: 8px;">
                <i class="fa-solid fa-weight-hanging" style="color: var(--primary);"></i>
                <span style="font-weight:500; font-size:0.9rem;">Iron / Metal</span>
            </div>
            <div style="display: flex; align-items: center; gap: 10px; background: rgba(255,255,255,0.01); border: 1px solid var(--border-color); padding: 10px; border-radius: 8px;">
                <i class="fa-solid fa-bottle-water" style="color: var(--primary);"></i>
                <span style="font-weight:500; font-size:0.9rem;">Plastic Bottle</span>
            </div>
            <div style="display: flex; align-items: center; gap: 10px; background: rgba(255,255,255,0.01); border: 1px solid var(--border-color); padding: 10px; border-radius: 8px;">
                <i class="fa-solid fa-box-open" style="color: var(--primary);"></i>
                <span style="font-weight:500; font-size:0.9rem;">Cardboard</span>
            </div>
            <div style="display: flex; align-items: center; gap: 10px; background: rgba(255,255,255,0.01); border: 1px solid var(--border-color); padding: 10px; border-radius: 8px;">
                <i class="fa-solid fa-book" style="color: var(--primary);"></i>
                <span style="font-weight:500; font-size:0.9rem;">Books / Papers</span>
            </div>
            <div style="display: flex; align-items: center; gap: 10px; background: rgba(255,255,255,0.01); border: 1px solid var(--border-color); padding: 10px; border-radius: 8px;">
                <i class="fa-solid fa-window-maximize" style="color: var(--primary);"></i>
                <span style="font-weight:500; font-size:0.9rem;">Aluminium</span>
            </div>
            <div style="display: flex; align-items: center; gap: 10px; background: rgba(255,255,255,0.01); border: 1px solid var(--border-color); padding: 10px; border-radius: 8px;">
                <i class="fa-solid fa-bolt" style="color: var(--primary);"></i>
                <span style="font-weight:500; font-size:0.9rem;">Copper / Zinc</span>
            </div>
        </div>
    </div>

    <!-- How It Works Section -->
    <h3 style="margin-bottom: 16px; font-weight: 700; font-size: 1.2rem;">How It Works</h3>
    <div class="card" style="padding: 20px 16px;">
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <div style="display: flex; gap: 16px; align-items: flex-start;">
                <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--primary-glow); border: 1px solid var(--primary); display: flex; align-items: center; justify-content: center; font-weight: bold; color: var(--primary); flex-shrink: 0;">1</div>
                <div>
                    <h4 style="font-weight: 600; font-size: 0.95rem; margin-bottom: 2px;">Schedule a Pickup</h4>
                    <p style="color: var(--text-secondary); font-size: 0.85rem;">Select your items, enter weights, pin your location, and pick a time.</p>
                </div>
            </div>
            <div style="display: flex; gap: 16px; align-items: flex-start;">
                <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--primary-glow); border: 1px solid var(--primary); display: flex; align-items: center; justify-content: center; font-weight: bold; color: var(--primary); flex-shrink: 0;">2</div>
                <div>
                    <h4 style="font-weight: 600; font-size: 0.95rem; margin-bottom: 2px;">Collector Doorstep Visit</h4>
                    <p style="color: var(--text-secondary); font-size: 0.85rem;">Our staff collector arrives, weighs items on digital scales, and updates actual weight.</p>
                </div>
            </div>
            <div style="display: flex; gap: 16px; align-items: flex-start;">
                <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--primary-glow); border: 1px solid var(--primary); display: flex; align-items: center; justify-content: center; font-weight: bold; color: var(--primary); flex-shrink: 0;">3</div>
                <div>
                    <h4 style="font-weight: 600; font-size: 0.95rem; margin-bottom: 2px;">Instant Cash / UPI Payment</h4>
                    <p style="color: var(--text-secondary); font-size: 0.85rem;">Get paid instantly in hand via cash or UPI transfer. Direct and offline.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
