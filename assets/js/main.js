// Global Application JavaScript

document.addEventListener('DOMContentLoaded', () => {
    console.log('Maile Kabari App Initialized.');
    
    // Close modals on overlay click
    const overlays = document.querySelectorAll('.modal-overlay');
    overlays.forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                overlay.classList.remove('active');
            }
        });
    });
});

/**
 * Open a modal dialog
 * @param {string} modalId 
 */
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
    }
}

/**
 * Close a modal dialog
 * @param {string} modalId 
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
    }
}

/**
 * Helper to show toast messages (In-app notifications)
 * @param {string} message 
 * @param {string} type ('success', 'error', 'warning', 'info')
 */
function showToast(message, type = 'info') {
    // Check if toast container exists, create if not
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.style.position = 'fixed';
        container.style.bottom = '20px';
        container.style.right = '20px';
        container.style.zIndex = '9999';
        container.style.display = 'flex';
        container.style.flexDirection = 'column';
        container.style.gap = '10px';
        document.body.appendChild(container);
    }
    
    const toast = document.createElement('div');
    toast.className = 'card';
    toast.style.padding = '12px 20px';
    toast.style.borderRadius = '8px';
    toast.style.fontSize = '0.9rem';
    toast.style.fontWeight = '500';
    toast.style.boxShadow = 'var(--shadow-md)';
    toast.style.minWidth = '250px';
    toast.style.display = 'flex';
    toast.style.alignItems = 'center';
    toast.style.gap = '10px';
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(20px)';
    toast.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
    
    // Icon and colors based on type
    let icon = '<i class="fa-solid fa-info-circle"></i>';
    let color = 'var(--text-primary)';
    let border = '1px solid var(--border-color)';
    
    if (type === 'success') {
        icon = '<i class="fa-solid fa-check-circle" style="color: var(--success)"></i>';
        border = '1px solid rgba(16, 185, 129, 0.3)';
    } else if (type === 'error') {
        icon = '<i class="fa-solid fa-triangle-exclamation" style="color: var(--danger)"></i>';
        border = '1px solid rgba(239, 68, 68, 0.3)';
    } else if (type === 'warning') {
        icon = '<i class="fa-solid fa-circle-exclamation" style="color: var(--warning)"></i>';
        border = '1px solid rgba(245, 158, 11, 0.3)';
    }
    
    toast.innerHTML = `${icon} <span>${message}</span>`;
    toast.style.border = border;
    toast.style.background = 'var(--bg-secondary)';
    
    container.appendChild(toast);
    
    // Force reflow
    toast.offsetHeight;
    
    // Animate in
    toast.style.opacity = '1';
    toast.style.transform = 'translateY(0)';
    
    // Remove after 4 seconds
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-20px)';
        setTimeout(() => {
            toast.remove();
        }, 300);
    }, 4000);
}
