<?php
$page_title = "Manage Scrap Rates";
require_once __DIR__ . '/../includes/header.php';

// Check admin authorization
if (!is_logged_in() || !has_role('admin')) {
    redirect('login.php');
}

try {
    // Fetch all categories
    $stmt = $pdo->query("SELECT * FROM scrap_categories ORDER BY name ASC");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<div class="container" style="padding-top: 30px; padding-bottom: 40px;">
    <!-- Back to Console Header -->
    <div style="margin-bottom: 24px;">
        <a href="<?php echo BASE_URL; ?>admin/dashboard.php" style="color: var(--text-secondary); font-size: 0.9rem; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 12px;">
            <i class="fa-solid fa-arrow-left"></i> Back to Console
        </a>
        <h1 style="font-size: 1.8rem; font-weight: 800;">Scrap Rates Control</h1>
        <p style="color: var(--text-secondary);">Manage the buying rates per unit for different categories. Updates reflect on customer dashboards instantly.</p>
    </div>

    <!-- Rates Editor Card -->
    <div class="card" style="max-width: 650px; margin: 0 auto; padding: 0; overflow: hidden;">
        <div class="table-responsive">
            <table class="admin-table" style="width: 100%;">
                <thead>
                    <tr>
                        <th style="width: 80px; text-align: center;">Icon</th>
                        <th>Scrap Category</th>
                        <th style="width: 120px;">Unit</th>
                        <th style="width: 180px;">Rate (in ₹)</th>
                        <th style="width: 100px; text-align: center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $cat): ?>
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
                        <tr id="rate-row-<?php echo $cat['id']; ?>">
                            <!-- Icon -->
                            <td style="text-align: center; font-size: 1.6rem; color: var(--primary);">
                                <i class="<?php echo $icon_class; ?>"></i>
                            </td>
                            
                            <!-- Category Name -->
                            <td>
                                <strong style="color: var(--text-primary);"><?php echo htmlspecialchars($cat['name']); ?></strong>
                            </td>
                            
                            <!-- Unit -->
                            <td>
                                <span style="font-size: 0.85rem; background: rgba(255,255,255,0.03); border:1px solid var(--border-color); padding: 4px 10px; border-radius: 4px;">
                                    per <?php echo htmlspecialchars($cat['unit']); ?>
                                </span>
                            </td>
                            
                            <!-- Rate Input -->
                            <td>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span style="font-weight: 600; color: var(--text-secondary);">₹</span>
                                    <input type="number" step="0.01" min="0" class="form-control rate-input" id="input-rate-<?php echo $cat['id']; ?>" value="<?php echo number_format($cat['rate_per_unit'], 2, '.', ''); ?>" style="padding: 6px 10px; font-size: 0.95rem; font-weight: 700; height: 36px; text-align: right;">
                                </div>
                            </td>
                            
                            <!-- Action Button -->
                            <td style="text-align: center;">
                                <button onclick="saveRate(<?php echo $cat['id']; ?>)" id="save-btn-<?php echo $cat['id']; ?>" class="btn btn-primary" style="padding: 6px 12px; font-size: 0.8rem; border-radius: 4px;">
                                    <i class="fa-solid fa-floppy-disk"></i> Save
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
/**
 * Save updated scrap rate via AJAX
 */
function saveRate(catId) {
    const rateInput = document.getElementById('input-rate-' + catId);
    const newRate = parseFloat(rateInput.value);
    
    if (isNaN(newRate) || newRate < 0) {
        showToast('Please enter a valid positive price rate.', 'error');
        rateInput.focus();
        return;
    }
    
    const saveBtn = document.getElementById('save-btn-' + catId);
    const originalHtml = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
    
    const formData = new FormData();
    formData.append('category_id', catId);
    formData.append('rate', newRate);
    
    fetch('<?php echo BASE_URL; ?>api/update_rates.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast('Rate updated successfully.', 'success');
            // Flash row border to indicate success
            const row = document.getElementById('rate-row-' + catId);
            const originalBorder = row.style.borderColor;
            row.style.border = '1px solid var(--primary)';
            setTimeout(() => {
                row.style.border = originalBorder;
            }, 1000);
        } else {
            showToast(data.message, 'error');
        }
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalHtml;
    })
    .catch(() => {
        showToast('Failed to save rate. Please try again.', 'error');
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalHtml;
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
