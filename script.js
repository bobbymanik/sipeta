// TVRI Broadcasting Reporting System JavaScript

// Global variables
let currentUser = null;

// DOM Content Loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

// Initialize Application
function initializeApp() {
    // Check if we're on login page
    if (document.getElementById('loginForm')) {
        initializeLogin();
    }
    
    // Initialize form validations
    initializeFormValidations();
    
    // Initialize tooltips and interactions
    initializeInteractions();
    
    // Auto-hide alerts after 5 seconds
    setTimeout(hideAlerts, 5000);
}

// Login functionality
function initializeLogin() {
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }
}

async function handleLogin(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const email = formData.get('email');
    const password = formData.get('password');
    
    if (!email || !password) {
        showMessage('Silahkan masukkan email dan kata sandi', 'error');
        return;
    }
    
    try {
        showLoading(true);
        
        const response = await fetch('auth/login.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showMessage('Login Berhasil', 'success');
            setTimeout(() => {
                window.location.href = result.redirect;
            }, 1000);
        } else {
            showMessage(result.message || 'Login Gagal', 'error');
        }
    } catch (error) {
        showMessage('ERROR!', 'error');
    } finally {
        showLoading(false);
    }
}

// Password reset functionality
function showResetPassword() {
    document.getElementById('loginForm').parentElement.style.display = 'none';
    document.getElementById('resetPasswordForm').style.display = 'block';
}

function showLoginForm() {
    document.getElementById('loginForm').parentElement.style.display = 'block';
    document.getElementById('resetPasswordForm').style.display = 'none';
}

async function sendResetEmail() {
    const email = document.getElementById('resetEmail').value;
    
    if (!email) {
        showMessage('Silahkan masukkan alamat email', 'error');
        return;
    }
    
    if (!isValidEmail(email)) {
        showMessage('Silahkan masukkan alamat email yang valid', 'error');
        return;
    }
    
    try {
        showLoading(true);
        
        // Simulate API call for demo
        await new Promise(resolve => setTimeout(resolve, 1500));
        
        showMessage('Tautan pengaturan ulang kata sandi telah dikirim ke email Anda!', 'success');
        setTimeout(() => {
            showLoginForm();
        }, 2000);
    } catch (error) {
        showMessage('Gagal mengirim email reset. Silakan coba lagi.', 'error');
    } finally {
        showLoading(false);
    }
}

// Form validation functions
function initializeFormValidations() {
    // Email validation
    const emailInputs = document.querySelectorAll('input[type="email"]');
    emailInputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateEmail(this);
        });
    });
    
    // Password validation
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    passwordInputs.forEach(input => {
        input.addEventListener('input', function() {
            validatePassword(this);
        });
    });
    
    // Required field validation
    const requiredInputs = document.querySelectorAll('input[required], select[required], textarea[required]');
    requiredInputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateRequired(this);
        });
    });
    
    // Date validation
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        input.addEventListener('change', function() {
            validateDate(this);
        });
    });
    
    // Time validation
    const timeInputs = document.querySelectorAll('input[type="time"]');
    timeInputs.forEach(input => {
        input.addEventListener('change', function() {
            validateTime(this);
        });
    });
}

function validateEmail(input) {
    const email = input.value.trim();
    const isValid = isValidEmail(email);
    
    toggleFieldValidation(input, isValid, 'Silahkan masukkan alamat email yang valid');
    return isValid;
}

function validatePassword(input) {
    const password = input.value;
    const isValid = password.length >= 6;
    
    toggleFieldValidation(input, isValid, 'Kata sandi harus terdiri dari minimal 6 karakter');
    return isValid;
}

function validateRequired(input) {
    const value = input.value.trim();
    const isValid = value.length > 0;
    
    toggleFieldValidation(input, isValid, '*wajib diisi');
    return isValid;
}

function validateDate(input) {
    const date = new Date(input.value);
    const today = new Date();
    const isValid = date <= today;
    
    toggleFieldValidation(input, isValid, 'Tanggal tidak boleh di masa mendatang');
    return isValid;
}

function validateTime(input) {
    const time = input.value;
    const isValid = /^([01]?[0-9]|2[0-3]):[0-5][0-9]$/.test(time);
    
    toggleFieldValidation(input, isValid, 'Harap masukkan waktu yang valid');
    return isValid;
}

function toggleFieldValidation(input, isValid, errorMessage) {
    const existingError = input.parentElement.querySelector('.field-error');
    
    if (existingError) {
        existingError.remove();
    }
    
    if (!isValid) {
        input.style.borderColor = '#e74c3c';
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.style.color = '#e74c3c';
        errorDiv.style.fontSize = '12px';
        errorDiv.style.marginTop = '5px';
        errorDiv.textContent = errorMessage;
        input.parentElement.appendChild(errorDiv);
    } else {
        input.style.borderColor = '#27ae60';
    }
}

// Utility functions
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function showMessage(message, type = 'info') {
    const messageDiv = document.getElementById('message') || createMessageDiv();
    messageDiv.textContent = message;
    messageDiv.className = `message ${type}`;
    messageDiv.style.display = 'block';
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        messageDiv.style.display = 'none';
    }, 5000);
}

function createMessageDiv() {
    const messageDiv = document.createElement('div');
    messageDiv.id = 'message';
    messageDiv.className = 'message';
    document.body.appendChild(messageDiv);
    return messageDiv;
}

function hideAlerts() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        alert.style.opacity = '0';
        setTimeout(() => {
            alert.style.display = 'none';
        }, 300);
    });
}

function showLoading(show) {
    const buttons = document.querySelectorAll('button[type="submit"]');
    buttons.forEach(button => {
        if (show) {
            button.disabled = true;
            button.textContent = 'Memuat...';
        } else {
            button.disabled = false;
            // Restore original text (you might want to store this)
            if (button.textContent === 'Memuat...') {
                button.textContent = 'Masuk';
            }
        }
    });
}

// Interactive features
function initializeInteractions() {
    // Add smooth scrolling to anchor links
    const anchorLinks = document.querySelectorAll('a[href^="#"]');
    anchorLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Add hover effects to cards
    const cards = document.querySelectorAll('.stats-card, .action-card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // Add confirmation dialogs for delete actions
    const deleteButtons = document.querySelectorAll('.delete-btn, button[name="delete_report"]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Yakin ingin menghapus item ini? Tindakan ini tidak dapat dibatalkan.')) {
                e.preventDefault();
                return false;
            }
        });
    });
    
    // Add auto-save functionality for forms (optional)
    initializeAutoSave();
    
    // Initialize table sorting
    initializeTableSorting();
    
    // Initialize search functionality
    initializeSearch();
}

// Auto-save functionality
function initializeAutoSave() {
    const forms = document.querySelectorAll('.report-form');
    forms.forEach(form => {
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                saveFormData(form);
            });
        });
        
        // Load saved data on page load
        loadFormData(form);
    });
}

function saveFormData(form) {
    const formData = new FormData(form);
    const data = {};
    
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }
    
    const formId = form.id || 'default-form';
    localStorage.setItem(`form-${formId}`, JSON.stringify(data));
}

function loadFormData(form) {
    const formId = form.id || 'default-form';
    const savedData = localStorage.getItem(`form-${formId}`);
    
    if (savedData) {
        try {
            const data = JSON.parse(savedData);
            
            for (let [key, value] of Object.entries(data)) {
                const input = form.querySelector(`[name="${key}"]`);
                if (input && input.type !== 'hidden') {
                    input.value = value;
                }
            }
        } catch (e) {
            console.log('Terjadi kesalahan saat memuat data formulir yang disimpan:', e);
        }
    }
}

function clearFormData(form) {
    const formId = form.id || 'default-form';
    localStorage.removeItem(`form-${formId}`);
}

// Table sorting functionality
function initializeTableSorting() {
    const tables = document.querySelectorAll('.reports-table, .users-table, .audit-table');
    tables.forEach(table => {
        const headers = table.querySelectorAll('th');
        headers.forEach((header, index) => {
            if (!header.querySelector('.no-sort')) {
                header.style.cursor = 'pointer';
                header.addEventListener('click', () => sortTable(table, index));
            }
        });
    });
}

function sortTable(table, columnIndex) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    if (rows.length === 0) return;
    
    const isAscending = table.dataset.sortDirection !== 'asc';
    table.dataset.sortDirection = isAscending ? 'asc' : 'desc';
    
    rows.sort((a, b) => {
        const aValue = a.cells[columnIndex].textContent.trim();
        const bValue = b.cells[columnIndex].textContent.trim();
        
        // Try to parse as numbers first
        const aNum = parseFloat(aValue);
        const bNum = parseFloat(bValue);
        
        if (!isNaN(aNum) && !isNaN(bNum)) {
            return isAscending ? aNum - bNum : bNum - aNum;
        }
        
        // Try to parse as dates
        const aDate = new Date(aValue);
        const bDate = new Date(bValue);
        
        if (!isNaN(aDate.getTime()) && !isNaN(bDate.getTime())) {
            return isAscending ? aDate - bDate : bDate - aDate;
        }
        
        // Default to string comparison
        return isAscending ? 
            aValue.localeCompare(bValue) : 
            bValue.localeCompare(aValue);
    });
    
    // Reorder the rows
    rows.forEach(row => tbody.appendChild(row));
    
    // Update header indicators
    const headers = table.querySelectorAll('th');
    headers.forEach(header => header.classList.remove('sort-asc', 'sort-desc'));
    headers[columnIndex].classList.add(isAscending ? 'sort-asc' : 'sort-desc');
}

// Search functionality
function initializeSearch() {
    const searchInputs = document.querySelectorAll('.search-input');
    searchInputs.forEach(input => {
        input.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const targetTable = document.querySelector(this.dataset.target);
            
            if (targetTable) {
                filterTable(targetTable, searchTerm);
            }
        });
    });
}

function filterTable(table, searchTerm) {
    const rows = table.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const shouldShow = text.includes(searchTerm);
        row.style.display = shouldShow ? '' : 'none';
    });
}

// Export functionality helpers
function exportTableToCSV(table, filename) {
    const rows = table.querySelectorAll('tr');
    const csv = [];
    
    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const rowData = [];
        
        cols.forEach(col => {
            // Clean the text content
            let text = col.textContent.trim();
            text = text.replace(/"/g, '""'); // Escape quotes
            rowData.push(`"${text}"`);
        });
        
        csv.push(rowData.join(','));
    });
    
    // Create and download the file
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    
    const a = document.createElement('a');
    a.href = url;
    a.download = filename || 'export.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}

// Print functionality
function printTable(table) {
    const printWindow = window.open('', '_blank');
    const tableHTML = table.outerHTML;
    
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Sistem Pelaporan TVRI</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                table { width: 100%; border-collapse: collapse; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; }
                .no-print { display: none; }
            </style>
        </head>
        <body>
            <h1>TVRI Broadcasting Reports</h1>
            <p>Generated on ${new Date().toLocaleDateString()}</p>
            ${tableHTML}
        </body>
        </html>
    `);
    
    printWindow.document.close();
    printWindow.print();
}

// Form submission helpers
function submitFormWithAjax(form, successCallback, errorCallback) {
    const formData = new FormData(form);
    
    fetch(form.action || window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (successCallback) successCallback(data);
            showMessage(data.message || 'Proses Berhasil', 'success');
        } else {
            if (errorCallback) errorCallback(data);
            showMessage(data.message || 'Proses Gagal', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (errorCallback) errorCallback(error);
        showMessage('Network error occurred', 'error');
    });
}

// Date and time utilities
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function formatDateTime(dateTimeString) {
    const date = new Date(dateTimeString);
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function getCurrentDate() {
    const today = new Date();
    return today.toISOString().split('T')[0];
}

function getCurrentTime() {
    const now = new Date();
    return now.toTimeString().slice(0, 5);
}

// Local storage utilities
function saveToLocalStorage(key, data) {
    try {
        localStorage.setItem(key, JSON.stringify(data));
        return true;
    } catch (e) {
        console.error('Terjadi kesalahan saat menyimpan ke penyimpanan lokal:', e);
        return false;
    }
}

function loadFromLocalStorage(key) {
    try {
        const data = localStorage.getItem(key);
        return data ? JSON.parse(data) : null;
    } catch (e) {
        console.error('Terjadi kesalahan saat memuat dari penyimpanan lokal:', e);
        return null;
    }
}

function removeFromLocalStorage(key) {
    try {
        localStorage.removeItem(key);
        return true;
    } catch (e) {
        console.error('Terjadi kesalahan saat menghapus dari penyimpanan lokal:', e);
        return false;
    }
}

// Performance optimization
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    }
}

// Initialize everything when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeApp);
} else {
    initializeApp();
}

// Global error handler
window.addEventListener('error', function(e) {
    console.error('Global error:', e.error);
    showMessage('Terjadi kesalahan tak terduga. Harap segarkan halaman dan coba lagi.', 'error');
});

// Handle unhandled promise rejections
window.addEventListener('unhandledrejection', function(e) {
    console.error('Unhandled promise rejection:', e.reason);
    showMessage('Terjadi kesalahan jaringan. Silakan periksa koneksi Anda dan coba lagi.', 'error');
});