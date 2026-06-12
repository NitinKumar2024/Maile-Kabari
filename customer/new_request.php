<?php
$page_title = "Schedule Scrap Pickup";
require_once __DIR__ . '/../includes/header.php';

// Check customer authorization
if (!is_logged_in() || !has_role('customer')) {
    redirect('login.php');
}

try {
    // Fetch active categories
    $stmt = $pdo->prepare("SELECT * FROM `scrap_categories` ORDER BY `name` ASC");
    $stmt->execute();
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<div class="mobile-container" style="padding-top: 20px;">
    <!-- Step Header -->
    <div style="margin-bottom: 20px;">
        <a href="<?php echo BASE_URL; ?>customer/dashboard.php" style="color: var(--text-secondary); font-size: 0.9rem; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 16px;">
            <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
        </a>
        <h2 style="font-size: 1.5rem; font-weight: 800;">Schedule Pickup</h2>
    </div>

    <!-- Step Progress Indicator -->
    <div class="step-indicator">
        <div id="dot-1" class="step-dot active">1</div>
        <div id="dot-2" class="step-dot">2</div>
        <div id="dot-3" class="step-dot">3</div>
    </div>

    <form id="pickup-form" onsubmit="submitPickup(event)">
        <!-- STEP 1: SELECT ITEMS -->
        <div id="step-1" class="step-panel active">
            <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 12px; color: var(--text-primary);">
                What scrap do you want to sell?
            </h3>
            
            <div class="scrap-input-grid" style="margin-bottom: 24px;">
                <?php foreach ($categories as $cat): ?>
                    <div class="scrap-input-item" id="scrap-item-<?php echo $cat['id']; ?>" onclick="toggleScrapItem(<?php echo $cat['id']; ?>)">
                        <input type="checkbox" class="scrap-checkbox" id="check-<?php echo $cat['id']; ?>" name="categories[]" value="<?php echo $cat['id']; ?>" data-rate="<?php echo $cat['rate_per_unit']; ?>" data-name="<?php echo $cat['name']; ?>" data-unit="<?php echo $cat['unit']; ?>" onclick="event.stopPropagation(); toggleScrapItem(<?php echo $cat['id']; ?>)">
                        
                        <div style="font-size: 1.8rem; color: var(--primary); margin-bottom: 4px;">
                            <?php
                                $icon_class = 'fa-solid fa-box';
                                switch ($cat['slug']) {
                                    case 'iron': $icon_class = 'fa-solid fa-weight-hanging'; break;
                                    case 'plastic': $icon_class = 'fa-solid fa-bottle-water'; break;
                                    case 'cardboard': $icon_class = 'fa-solid fa-box-open'; break;
                                    case 'books': $icon_class = 'fa-solid fa-book'; break;
                                    case 'aluminium': $icon_class = 'fa-solid fa-window-maximize'; break;
                                    case 'copper': $icon_class = 'fa-solid fa-bolt'; break;
                                    case 'zinc': $icon_class = 'fa-solid fa-shield-halved'; break;
                                }
                            ?>
                            <i class="<?php echo $icon_class; ?>"></i>
                        </div>
                        <div style="font-weight: 600; font-size: 0.85rem;"><?php echo $cat['name']; ?></div>
                        <div style="font-size: 0.75rem; color: var(--text-secondary);">₹<?php echo number_format($cat['rate_per_unit'], 0); ?>/<?php echo $cat['unit']; ?></div>
                        
                        <!-- Weight field (hidden initially) -->
                        <div class="scrap-input-fields" onclick="event.stopPropagation();">
                            <input type="number" step="0.1" min="0.1" class="form-control est-weight" style="padding: 6px; font-size: 0.85rem; height: 32px;" placeholder="Weight" id="weight-<?php echo $cat['id']; ?>" name="weights[<?php echo $cat['id']; ?>]" oninput="calculateEstimate()">
                            <span style="font-size: 0.8rem; color: var(--text-secondary);"><?php echo $cat['unit']; ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <button type="button" class="btn btn-primary btn-block" onclick="goToStep(2)">
                Continue <i class="fa-solid fa-arrow-right"></i>
            </button>
        </div>

        <!-- STEP 2: ADDRESS & SCHEDULE -->
        <div id="step-2" class="step-panel">
            <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 12px; color: var(--text-primary);">
                Pickup Location & Date
            </h3>
            
            <!-- Address input -->
            <div class="form-group">
                <label for="address">Pickup Address</label>
                <textarea id="address" name="address" class="form-control" rows="3" placeholder="Enter complete doorstep address" required></textarea>
            </div>
            
            <!-- GPS & Map integration -->
            <div style="display: flex; gap: 10px; margin-bottom: 16px;">
                <button type="button" class="btn btn-secondary" style="flex: 1; padding: 10px; font-size: 0.85rem;" onclick="detectLocation()">
                    <i class="fa-solid fa-location-crosshairs" style="color: var(--primary);"></i> Detect Location
                </button>
                <button type="button" class="btn btn-secondary" style="flex: 1; padding: 10px; font-size: 0.85rem;" onclick="toggleMap()">
                    <i class="fa-solid fa-map-marked-alt" style="color: var(--primary);"></i> Pin on Map
                </button>
            </div>
            
            <!-- Hidden coordinates fields -->
            <input type="hidden" id="lat" name="lat">
            <input type="hidden" id="lng" name="lng">
            
            <!-- Map Container (collapsible) -->
            <div id="map-container" style="display: none; margin-bottom: 20px;">
                <p style="font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 6px;"><i class="fa-solid fa-info-circle"></i> Drag or click to place the marker on your location.</p>
                <div id="map"></div>
            </div>
            
            <!-- Schedule inputs -->
            <div class="form-group">
                <label for="pickup_date">Preferred Date</label>
                <input type="date" id="pickup_date" name="pickup_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
            </div>
            
            <div class="form-group">
                <label for="pickup_time_slot">Preferred Time Slot</label>
                <select id="pickup_time_slot" name="pickup_time_slot" class="form-control" required>
                    <option value="">Select a time slot</option>
                    <option value="09:00 AM - 12:00 PM">Morning (09:00 AM - 12:00 PM)</option>
                    <option value="12:00 PM - 03:00 PM">Afternoon (12:00 PM - 03:00 PM)</option>
                    <option value="03:00 PM - 06:00 PM">Evening (03:00 PM - 06:00 PM)</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="customer_notes">Additional Instructions (Optional)</label>
                <input type="text" id="customer_notes" name="customer_notes" class="form-control" placeholder="e.g. Near Mother Dairy booth, call before arrival">
            </div>
            
            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="button" class="btn btn-secondary" style="flex: 1;" onclick="goToStep(1)">
                    <i class="fa-solid fa-arrow-left"></i> Back
                </button>
                <button type="button" class="btn btn-primary" style="flex: 2;" onclick="goToStep(3)">
                    Review Order <i class="fa-solid fa-arrow-right"></i>
                </button>
            </div>
        </div>

        <!-- STEP 3: REVIEW & CONFIRM -->
        <div id="step-3" class="step-panel">
            <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 16px; color: var(--text-primary);">
                Confirm Pickup Request
            </h3>
            
            <div class="card" style="margin-bottom: 24px; padding: 20px;">
                <h4 style="border-bottom: 1px solid var(--border-color); padding-bottom: 8px; margin-bottom: 12px; font-size: 0.95rem; color: var(--text-secondary);">Selected Items</h4>
                <div id="review-items-list" style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 16px;">
                    <!-- Dynamically populated -->
                </div>
                
                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px dashed var(--border-color); padding-top: 12px; margin-bottom: 20px;">
                    <strong style="font-size: 0.95rem;">Total Estimated Value</strong>
                    <span id="review-total-price" style="font-size: 1.4rem; font-weight: 800; color: var(--primary);">₹0.00</span>
                </div>
                
                <h4 style="border-bottom: 1px solid var(--border-color); padding-bottom: 8px; margin-bottom: 12px; font-size: 0.95rem; color: var(--text-secondary);">Pickup Details</h4>
                <div style="display: flex; flex-direction: column; gap: 8px; font-size: 0.9rem;">
                    <div><strong><i class="fa-solid fa-location-dot" style="color: var(--primary); margin-right: 6px;"></i> Address:</strong> <span id="review-address">-</span></div>
                    <div><strong><i class="fa-solid fa-calendar-day" style="color: var(--primary); margin-right: 6px;"></i> Date:</strong> <span id="review-date">-</span></div>
                    <div><strong><i class="fa-solid fa-clock" style="color: var(--primary); margin-right: 6px;"></i> Time Slot:</strong> <span id="review-slot">-</span></div>
                    <div id="review-notes-container" style="display:none;"><strong><i class="fa-solid fa-note-sticky" style="color: var(--primary); margin-right: 6px;"></i> Instructions:</strong> <span id="review-notes">-</span></div>
                </div>
            </div>
            
            <div style="display: flex; gap: 12px;">
                <button type="button" class="btn btn-secondary" style="flex: 1;" onclick="goToStep(2)">
                    <i class="fa-solid fa-arrow-left"></i> Back
                </button>
                <button type="submit" id="submit-btn" class="btn btn-primary" style="flex: 2; box-shadow: 0 4px 15px var(--primary-glow);">
                    Schedule Pickup <i class="fa-solid fa-check"></i>
                </button>
            </div>
        </div>
    </form>
</div>

<script>
let map = null;
let marker = null;
let currentStep = 1;

/**
 * Handle card click and select/deselect scrap item
 */
function toggleScrapItem(id) {
    const card = document.getElementById('scrap-item-' + id);
    const checkbox = document.getElementById('check-' + id);
    const weightInput = document.getElementById('weight-' + id);
    
    if (checkbox.checked) {
        checkbox.checked = false;
        card.classList.remove('selected');
        weightInput.value = '';
        weightInput.required = false;
    } else {
        checkbox.checked = true;
        card.classList.add('selected');
        weightInput.required = true;
        weightInput.focus();
    }
    calculateEstimate();
}

/**
 * Calculate the estimated reward dynamically in client JS
 */
function calculateEstimate() {
    let total = 0;
    const checkboxes = document.querySelectorAll('.scrap-checkbox:checked');
    
    checkboxes.forEach(chk => {
        const id = chk.value;
        const rate = parseFloat(chk.getAttribute('data-rate'));
        const weightInput = document.getElementById('weight-' + id);
        const weight = parseFloat(weightInput.value) || 0;
        total += (rate * weight);
    });
    
    document.getElementById('review-total-price').innerText = '₹' + total.toFixed(2);
    return total;
}

/**
 * Handle wizard step navigation and form check validations
 */
function goToStep(step) {
    if (step === 2) {
        // Validate Step 1 selection: At least one item selected and weights filled
        const selectedCount = document.querySelectorAll('.scrap-checkbox:checked').length;
        if (selectedCount === 0) {
            showToast('Please select at least one scrap item to sell.', 'warning');
            return;
        }
        
        let weightsValid = true;
        document.querySelectorAll('.scrap-checkbox:checked').forEach(chk => {
            const id = chk.value;
            const weightInput = document.getElementById('weight-' + id);
            if (!weightInput.value || parseFloat(weightInput.value) <= 0) {
                weightsValid = false;
                weightInput.focus();
            }
        });
        
        if (!weightsValid) {
            showToast('Please enter a valid estimated weight for all selected items.', 'warning');
            return;
        }
    }
    
    if (step === 3) {
        // Validate Step 2 inputs
        const address = document.getElementById('address').value.trim();
        const pickupDate = document.getElementById('pickup_date').value;
        const timeSlot = document.getElementById('pickup_time_slot').value;
        
        if (!address) {
            showToast('Please enter your pickup address.', 'warning');
            document.getElementById('address').focus();
            return;
        }
        if (!pickupDate) {
            showToast('Please select a pickup date.', 'warning');
            document.getElementById('pickup_date').focus();
            return;
        }
        if (!timeSlot) {
            showToast('Please choose a preferred time slot.', 'warning');
            document.getElementById('pickup_time_slot').focus();
            return;
        }
        
        // Populate Step 3 Review details
        populateReview();
    }
    
    // Switch active panels
    document.querySelectorAll('.step-panel').forEach(el => el.classList.remove('active'));
    document.getElementById('step-' + step).classList.add('active');
    
    // Update progress dots
    document.querySelectorAll('.step-dot').forEach((dot, index) => {
        const dotNum = index + 1;
        dot.className = 'step-dot';
        if (dotNum === step) {
            dot.classList.add('active');
        } else if (dotNum < step) {
            dot.classList.add('completed');
            dot.innerHTML = '<i class="fa-solid fa-check"></i>';
        } else {
            dot.innerText = dotNum;
        }
    });
    
    currentStep = step;
    window.scrollTo(0, 0);
}

/**
 * Populate confirmation screen details
 */
function populateReview() {
    const listContainer = document.getElementById('review-items-list');
    listContainer.innerHTML = '';
    
    const checkboxes = document.querySelectorAll('.scrap-checkbox:checked');
    checkboxes.forEach(chk => {
        const id = chk.value;
        const name = chk.getAttribute('data-name');
        const unit = chk.getAttribute('data-unit');
        const rate = parseFloat(chk.getAttribute('data-rate'));
        const weight = parseFloat(document.getElementById('weight-' + id).value) || 0;
        
        const subtotal = rate * weight;
        
        const row = document.createElement('div');
        row.style.display = 'flex';
        row.style.justify = 'space-between';
        row.style.fontSize = '0.9rem';
        row.innerHTML = `
            <span>${name} (${weight} ${unit} &times; ₹${rate})</span>
            <span style="font-weight:500;">₹${subtotal.toFixed(2)}</span>
        `;
        listContainer.appendChild(row);
    });
    
    document.getElementById('review-address').innerText = document.getElementById('address').value.trim();
    
    // Date formatting
    const dVal = document.getElementById('pickup_date').value;
    const dateObj = new Date(dVal);
    document.getElementById('review-date').innerText = dateObj.toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' });
    
    document.getElementById('review-slot').innerText = document.getElementById('pickup_time_slot').value;
    
    // Notes
    const notes = document.getElementById('customer_notes').value.trim();
    const notesContainer = document.getElementById('review-notes-container');
    if (notes) {
        document.getElementById('review-notes').innerText = notes;
        notesContainer.style.display = 'block';
    } else {
        notesContainer.style.display = 'none';
    }
    
    calculateEstimate();
}

/**
 * HTML5 Geolocation automatic coordinates capture
 */
function detectLocation() {
    if (!navigator.geolocation) {
        showToast('Geolocation is not supported by your browser.', 'error');
        return;
    }
    
    showToast('Fetching your location coordinates...', 'info');
    
    navigator.geolocation.getCurrentPosition(
        (position) => {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            
            document.getElementById('lat').value = lat;
            document.getElementById('lng').value = lng;
            
            showToast('Coordinates captured successfully!', 'success');
            
            // Reverse geocode via OpenStreetMap Nominatim
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
            .then(res => res.json())
            .then(data => {
                if (data.display_name) {
                    document.getElementById('address').value = data.display_name;
                    showToast('Address filled automatically!', 'success');
                }
            })
            .catch(() => {
                // Ignore geocode failures, text address remains editable
            });
            
            // If map is initialized, move marker
            if (map) {
                const loc = [lat, lng];
                map.setView(loc, 16);
                marker.setLatLng(loc);
            }
        },
        (error) => {
            console.error(error);
            showToast('Unable to fetch location. Please enter manually.', 'warning');
        },
        { enableHighAccuracy: true, timeout: 8000 }
    );
}

/**
 * Initialize / toggle Leaflet interactive OSM map
 */
function toggleMap() {
    const mapDiv = document.getElementById('map-container');
    
    if (mapDiv.style.display === 'none') {
        mapDiv.style.display = 'block';
        
        let startLat = parseFloat(document.getElementById('lat').value) || 28.6139; // Delhi center default
        let startLng = parseFloat(document.getElementById('lng').value) || 77.2090;
        
        if (!map) {
            // Create map
            map = L.map('map').setView([startLat, startLng], 13);
            
            // Add OpenStreetMap tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);
            
            // Add draggable marker
            marker = L.marker([startLat, startLng], { draggable: true }).addTo(map);
            
            // Event on marker dragend
            marker.on('dragend', function(e) {
                const position = marker.getLatLng();
                updateCoordsAndAddress(position.lat, position.lng);
            });
            
            // Event on map click
            map.on('click', function(e) {
                marker.setLatLng(e.latlng);
                updateCoordsAndAddress(e.latlng.lat, e.latlng.lng);
            });
        } else {
            // Relocate to current input coordinate values
            map.setView([startLat, startLng], 13);
            marker.setLatLng([startLat, startLng]);
        }
        
        // Leaflet resize invalidation helper
        setTimeout(() => { map.invalidateSize(); }, 200);
    } else {
        mapDiv.style.display = 'none';
    }
}

/**
 * Update coord values and fetch reverse address
 */
function updateCoordsAndAddress(lat, lng) {
    document.getElementById('lat').value = lat.toFixed(8);
    document.getElementById('lng').value = lng.toFixed(8);
    
    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
    .then(res => res.json())
    .then(data => {
        if (data.display_name) {
            document.getElementById('address').value = data.display_name;
        }
    })
    .catch(() => {});
}

/**
 * Submit booked pickup request via AJAX
 */
function submitPickup(event) {
    event.preventDefault();
    
    const form = document.getElementById('pickup-form');
    const formData = new FormData(form);
    
    const submitBtn = document.getElementById('submit-btn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Scheduling...';
    
    fetch('<?php echo BASE_URL; ?>api/submit_order.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Pickup scheduled successfully!', 'success');
            setTimeout(() => {
                window.location.href = 'dashboard.php';
            }, 1200);
        } else {
            showToast(data.message, 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Schedule Pickup <i class="fa-solid fa-check"></i>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Something went wrong. Please submit again.', 'error');
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Schedule Pickup <i class="fa-solid fa-check"></i>';
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
