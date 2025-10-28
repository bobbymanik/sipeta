<?php
// Authentication check middleware for TVRI Broadcasting System

session_start();

function checkAuthentication() {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        redirectToLogin();
    }
    
    // Check if session is expired (optional - set session timeout)
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600)) {
        // Session expired after 1 hour of inactivity
        session_destroy();
        redirectToLogin();
    }
    
    $_SESSION['last_activity'] = time();
}

function checkRole($required_roles) {
    checkAuthentication();
    
    if (!is_array($required_roles)) {
        $required_roles = [$required_roles];
    }
    
    if (!in_array($_SESSION['user_level'], $required_roles)) {
        redirectToDashboard();
    }
}

function redirectToLogin() {
    $login_url = '/index.html';
    if (!headers_sent()) {
        header('Location: ' . $login_url);
    } else {
        echo '<script>window.location.href = "' . $login_url . '";</script>';
    }
    exit();
}

function redirectToDashboard() {
    $dashboard_url = '/dashboard/' . strtolower($_SESSION['user_level']) . '.php';
    if (!headers_sent()) {
        header('Location: ' . $dashboard_url);
    } else {
        echo '<script>window.location.href = "' . $dashboard_url . '";</script>';
    }
    exit();
}

function getCurrentUser() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    require_once 'functions.php';
    return getUserInfo($_SESSION['user_id']);
}

function hasPermission($permission) {
    $permissions = [
        'Leader' => ['view_reports', 'view_audit'],
        'Admin' => ['view_reports', 'create_reports', 'delete_reports', 'manage_users', 'export_reports', 'view_audit', 'manage_options'],
        'Technician' => ['view_own_reports', 'create_reports']
    ];
    
    $user_level = $_SESSION['user_level'] ?? '';
    $user_permissions = $permissions[$user_level] ?? [];
    
    return in_array($permission, $user_permissions);
}

function requirePermission($permission) {
    if (!hasPermission($permission)) {
        http_response_code(403);
        die('Akses ditolak. Anda tidak memiliki izin untuk melakukan tindakan ini.');
    }
}

// Rate limiting for login attempts
function checkRateLimit($identifier, $max_attempts = 5, $window_minutes = 15) {
    $cache_key = 'rate_limit_' . md5($identifier);
    
    // In a real application, you would use Redis or Memcached
    // For demo purposes, we'll use file-based storage
    $rate_limit_file = sys_get_temp_dir() . '/' . $cache_key;
    
    $attempts = [];
    if (file_exists($rate_limit_file)) {
        $attempts = json_decode(file_get_contents($rate_limit_file), true) ?: [];
    }
    
    // Remove old attempts outside the window
    $cutoff_time = time() - ($window_minutes * 60);
    $attempts = array_filter($attempts, function($timestamp) use ($cutoff_time) {
        return $timestamp > $cutoff_time;
    });
    
    if (count($attempts) >= $max_attempts) {
        return false; // Rate limit exceeded
    }
    
    // Add current attempt
    $attempts[] = time();
    file_put_contents($rate_limit_file, json_encode($attempts));
    
    return true; // Within rate limit
}

// Security headers
function setSecurityHeaders() {
    if (!headers_sent()) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Only set HTTPS headers if using HTTPS
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }
}

// Call security headers on every request
setSecurityHeaders();
?>