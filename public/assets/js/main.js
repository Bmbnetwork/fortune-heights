// ================================================================
// FORTUNE HEIGHTS - MAIN JAVASCRIPT
// ================================================================

document.addEventListener('DOMContentLoaded', function() {
    initSidebar();
    initDropdowns();
    initAlerts();
    initForms();
    initTooltips();
});

// Sidebar Toggle
function initSidebar() {
    const toggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    if (toggle && sidebar) {
        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('show');
        });
    }
    
    if (overlay) {
        overlay.addEventListener('click', () => {
            sidebar.classList.remove('show');
        });
    }
}

// Dropdowns
function initDropdowns() {
    const notifBtn = document.getElementById('notifBtn');
    const notifDropdown = document.getElementById('notifDropdown');
    
    if (notifBtn && notifDropdown) {
        notifBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            notifDropdown.classList.toggle('show');
        });
        
        document.addEventListener('click', (e) => {
            if (!notifDropdown.contains(e.target)) {
                notifDropdown.classList.remove('show');
            }
        });
    }
}

// Auto-dismiss alerts
function initAlerts() {
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
}

// Form validation
function initForms() {
    document.querySelectorAll('form[data-validate]').forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
    });
}

function validateForm(form) {
    let valid = true;
    form.querySelectorAll('[required]').forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            valid = false;
        } else {
            input.classList.remove('is-invalid');
        }
    });
    return valid;
}

// Tooltips
function initTooltips() {
    document.querySelectorAll('[data-tooltip]').forEach(el => {
        el.addEventListener('mouseenter', function() {
            const tooltip = document.createElement('div');
            tooltip.className = 'custom-tooltip';
            tooltip.textContent = this.dataset.tooltip;
            tooltip.style.cssText = `
                position: absolute;
                background: #1f2937;
                color: white;
                padding: 5px 10px;
                border-radius: 4px;
                font-size: 12px;
                z-index: 9999;
                pointer-events: none;
            `;
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.style.left = rect.left + 'px';
            tooltip.style.top = (rect.top - 30) + 'px';
            
            this._tooltip = tooltip;
        });
        
        el.addEventListener('mouseleave', function() {
            if (this._tooltip) {
                this._tooltip.remove();
                this._tooltip = null;
            }
        });
    });
}

// Confirm delete
function confirmDelete(message = 'Are you sure you want to delete this item?') {
    return confirm(message);
}

// Format number
function formatNumber(num) {
    return new Intl.NumberFormat().format(num);
}

// AJAX helper
async function ajaxRequest(url, method = 'GET', data = null) {
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    };
    
    if (data && method !== 'GET') {
        options.body = JSON.stringify(data);
    }
    
    try {
        const response = await fetch(url, options);
        return await response.json();
    } catch (error) {
        console.error('AJAX Error:', error);
        return { success: false, message: error.message };
    }
}

// Real-time notification polling (every 30 seconds)
setInterval(async () => {
    try {
        const response = await fetch('index.php?page=notifications&ajax=1');
        const data = await response.json();
        
        if (data.unread_count > 0) {
            const badge = document.querySelector('#notifBtn .badge-dot');
            if (badge) badge.style.display = 'block';
        }
    } catch (e) {
        // Silent fail
    }
}, 30000);

// Print function
function printPage() {
    window.print();
}

// Export to PDF (using browser print)
function exportToPDF() {
    window.print();
}

// Search functionality
function initTableSearch(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    
    if (!input || !table) return;
    
    input.addEventListener('keyup', function() {
        const filter = this.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
}

// Auto-refresh data
function setupAutoRefresh(callback, interval = 60000) {
    setInterval(callback, interval);
}

// Console welcome
console.log('%c🏫 Fortune Heights Montessori School', 'color: #1e40af; font-size: 20px; font-weight: bold;');
console.log('%cParent-Teacher Communication System', 'color: #f59e0b; font-size: 14px;');
console.log('%cIjokodo, Ibadan, Nigeria', 'color: #6b7280; font-size: 12px;');