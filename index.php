<?php
$page_title = "Online Kabadiwala Bihar | Doorstep Scrap Pickup & Selling";
$meta_description = "Bihar's trusted online kabadiwala - Maile Kabari (mailekabari.in). Doorstep scrap collection in Maile, Bhairopur, Vaishali, Bihar. Sell iron, plastic, books, cardboard, copper, and zinc for instant offline cash/UPI.";
$meta_keywords = "online kabadiwala Bihar, sell scrap online Vaishali, kabadiwala near me Bihar, scrap buyer Bhairopur, doorstep scrap collection Bihar, scrap prices Bihar, sell books Vaishali";
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
  "telephone": "+919999999999",
  "priceRange": "₹",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "Maile, Bhairopur",
    "addressLocality": "Vaishali",
    "addressRegion": "BR",
    "postalCode": "844101",
    "addressCountry": "IN"
  },
  "geo": {
    "@type": "GeoCoordinates",
    "latitude": 25.6833,
    "longitude": 85.2167
  },
  "description": "Bihar's trusted online scrap collection and doorstep pickup service operating from Vaishali. Sell metals, plastic, paper, cardboard, copper, and zinc.",
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
<div class="mobile-container" style="padding-top: 30px; padding-bottom: 30px;">
    <div style="text-align: center; margin-bottom: 32px;">
        <div style="display: inline-flex; align-items: center; justify-content: center; width: 80px; height: 80px; border-radius: 50%; background: var(--bg-card); border: 2px solid var(--primary); margin-bottom: 20px; font-size: 2.5rem; color: var(--primary); box-shadow: 0 0 20px var(--primary-glow);">
            <i class="fa-solid fa-recycle"></i>
        </div>
        <h1 style="font-size: 2.1rem; font-weight: 800; line-height: 1.25; margin-bottom: 12px; letter-spacing: -0.5px;">
            Bihar's Own<br>Online <span style="color: var(--primary);">Kabadiwala</span>
        </h1>
        <p style="color: var(--text-secondary); font-size: 1rem; margin-bottom: 24px;">
            Proudly serving **Maile, Bhairopur, Vaishali** and neighboring areas in Bihar. Schedule a doorstep pickup and get paid instantly!
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
                Book Pickup Now <i class="fa-solid fa-arrow-right"></i>
            </a>
        <?php endif; ?>
    </div>

    <!-- Quick Stats Grid -->
    <div class="card" style="margin-bottom: 24px; padding: 20px 10px;">
        <div style="display: flex; justify-content: space-around; text-align: center;">
            <div>
                <h3 style="color: var(--primary); font-size: 1.5rem; font-weight: 800;">10K+</h3>
                <p style="color: var(--text-secondary); font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px;">KG Recycled</p>
            </div>
            <div style="border-left: 1px solid var(--border-color); height: 40px; align-self: center;"></div>
            <div>
                <h3 style="color: var(--primary); font-size: 1.5rem; font-weight: 800;">3K+</h3>
                <p style="color: var(--text-secondary); font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px;">Pickups Done</p>
            </div>
            <div style="border-left: 1px solid var(--border-color); height: 40px; align-self: center;"></div>
            <div>
                <h3 style="color: var(--primary); font-size: 1.5rem; font-weight: 800;">100%</h3>
                <p style="color: var(--text-secondary); font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px;">Bihar Trust</p>
            </div>
        </div>
    </div>

    <!-- Scrap Items List -->
    <h3 style="margin-bottom: 12px; font-weight: 700; font-size: 1.15rem;">What We Buy</h3>
    <div class="card" style="margin-bottom: 24px; padding: 16px;">
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
            <div style="display: flex; align-items: center; gap: 10px; background: rgba(255,255,255,0.01); border: 1px solid var(--border-color); padding: 10px; border-radius: 8px;">
                <i class="fa-solid fa-weight-hanging" style="color: var(--primary);"></i>
                <span style="font-weight:500; font-size:0.85rem;">Iron / Metal</span>
            </div>
            <div style="display: flex; align-items: center; gap: 10px; background: rgba(255,255,255,0.01); border: 1px solid var(--border-color); padding: 10px; border-radius: 8px;">
                <i class="fa-solid fa-bottle-water" style="color: var(--primary);"></i>
                <span style="font-weight:500; font-size:0.85rem;">Plastic Bottles</span>
            </div>
            <div style="display: flex; align-items: center; gap: 10px; background: rgba(255,255,255,0.01); border: 1px solid var(--border-color); padding: 10px; border-radius: 8px;">
                <i class="fa-solid fa-box-open" style="color: var(--primary);"></i>
                <span style="font-weight:500; font-size:0.85rem;">Cardboard</span>
            </div>
            <div style="display: flex; align-items: center; gap: 10px; background: rgba(255,255,255,0.01); border: 1px solid var(--border-color); padding: 10px; border-radius: 8px;">
                <i class="fa-solid fa-book" style="color: var(--primary);"></i>
                <span style="font-weight:500; font-size:0.85rem;">Books / Papers</span>
            </div>
            <div style="display: flex; align-items: center; gap: 10px; background: rgba(255,255,255,0.01); border: 1px solid var(--border-color); padding: 10px; border-radius: 8px;">
                <i class="fa-solid fa-window-maximize" style="color: var(--primary);"></i>
                <span style="font-weight:500; font-size:0.85rem;">Aluminium</span>
            </div>
            <div style="display: flex; align-items: center; gap: 10px; background: rgba(255,255,255,0.01); border: 1px solid var(--border-color); padding: 10px; border-radius: 8px;">
                <i class="fa-solid fa-bolt" style="color: var(--primary);"></i>
                <span style="font-weight:500; font-size:0.85rem;">Copper / Zinc</span>
            </div>
        </div>
    </div>

    <!-- How It Works Section -->
    <h3 style="margin-bottom: 12px; font-weight: 700; font-size: 1.15rem;">How It Works</h3>
    <div class="card" style="padding: 20px 16px; margin-bottom: 24px;">
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
                    <h4 style="font-weight: 600; font-size: 0.95rem; margin-bottom: 2px;">Doorstep Verification</h4>
                    <p style="color: var(--text-secondary); font-size: 0.85rem;">Our staff collector arrives, weighs items on digital scales, and updates actual weight.</p>
                </div>
            </div>
            <div style="display: flex; gap: 16px; align-items: flex-start;">
                <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--primary-glow); border: 1px solid var(--primary); display: flex; align-items: center; justify-content: center; font-weight: bold; color: var(--primary); flex-shrink: 0;">3</div>
                <div>
                    <h4 style="font-weight: 600; font-size: 0.95rem; margin-bottom: 2px;">Instant Cash / UPI Payout</h4>
                    <p style="color: var(--text-secondary); font-size: 0.85rem;">Get paid instantly in hand via cash or UPI transfer. Direct and offline.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Shop Address & Contact Details (Bihar Specific) -->
    <h3 style="margin-bottom: 12px; font-weight: 700; font-size: 1.15rem;">Visit Our Local Shop</h3>
    <div class="card" style="padding: 20px 16px; border-left: 4px solid var(--primary); margin-bottom: 10px;">
        <div style="display: flex; flex-direction: column; gap: 14px;">
            <div style="display: flex; gap: 12px; align-items: flex-start;">
                <div style="font-size: 1.2rem; color: var(--primary); width: 24px; text-align: center;"><i class="fa-solid fa-store"></i></div>
                <div>
                    <h4 style="font-weight: 600; font-size: 0.95rem; margin-bottom: 2px; color: var(--text-primary);">Rohit Kabari Shop</h4>
                    <p style="color: var(--text-secondary); font-size: 0.85rem; line-height: 1.4;">
                        Maile, Bhairopur, Vaishali,<br>Bihar - 844101
                    </p>
                </div>
            </div>
            
            <div style="display: flex; gap: 12px; align-items: flex-start;">
                <div style="font-size: 1.2rem; color: var(--primary); width: 24px; text-align: center;"><i class="fa-solid fa-phone-volume"></i></div>
                <div>
                    <h4 style="font-weight: 600; font-size: 0.95rem; margin-bottom: 2px; color: var(--text-primary);">Call Us Directly</h4>
                    <p style="color: var(--text-secondary); font-size: 0.85rem; display: flex; flex-wrap: wrap; gap: 10px; margin-top: 4px;">
                        <a href="tel:+919999999999" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                            <i class="fa-solid fa-phone"></i> +91 99999 99999
                        </a>
                        <a href="tel:+919876543210" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                            <i class="fa-solid fa-phone"></i> +91 98765 43210
                        </a>
                    </p>
                </div>
            </div>

            <div style="display: flex; gap: 12px; align-items: flex-start;">
                <div style="font-size: 1.2rem; color: var(--primary); width: 24px; text-align: center;"><i class="fa-solid fa-map-location-dot"></i></div>
                <div style="width: 100%;">
                    <h4 style="font-weight: 600; font-size: 0.95rem; margin-bottom: 4px; color: var(--text-primary);">Find Us On Map</h4>
                    <a href="https://www.openstreetmap.org/?mlat=25.6833&mlon=85.2167#map=15/25.6833/85.2167" target="_blank" class="btn btn-secondary btn-block" style="padding: 10px; font-size: 0.8rem; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; gap: 6px;">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i> Open Map Directions
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
