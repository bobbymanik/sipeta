<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Check user role
function hasRole($required_role) {
    if (!isLoggedIn()) return false;
    
    if (is_array($required_role)) {
        return in_array($_SESSION['user_level'], $required_role);
    }
    
    return $_SESSION['user_level'] === $required_role;
}

// Redirect if not authorized
function requireAuth($required_role = null) {
    if (!isLoggedIn()) {
        header('Location: index.html');
        exit();
    }
    
    if ($required_role && !hasRole($required_role)) {
        header('Location: dashboard/' . strtolower($_SESSION['user_level']) . '.php');
        exit();
    }
}

// Get database connection
function getDB() {
    $database = new Database();
    return $database->getConnection();
}

// Log audit trail
function logAudit($table_name, $record_id, $action, $old_values = null, $new_values = null) {
    if (!isLoggedIn()) return;
    
    $db = getDB();
    $stmt = $db->prepare("
        INSERT INTO audit_trail (table_name, record_id, action, old_values, new_values, changed_by) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $table_name,
        $record_id,
        $action,
        $old_values ? json_encode($old_values) : null,
        $new_values ? json_encode($new_values) : null,
        $_SESSION['user_id']
    ]);
}

// Get staff options
function getStaffOptions($type = 'shift_staff') {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM staff_options WHERE type = ? AND is_active = 1 ORDER BY name");
    $stmt->execute([$type]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get program options
function getProgramOptions($type = null) {
    $db = getDB();
    $sql = "SELECT * FROM program_options WHERE is_active = 1";
    $params = [];
    
    if ($type) {
        $sql .= " AND type = ?";
        $params[] = $type;
    }
    
    $sql .= " ORDER BY name";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get transmission units
function getTransmissionUnits() {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM transmission_units WHERE is_active = 1 ORDER BY name");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Format JSON array for display
function formatJsonArray($json_string, $options_array, $key_field = 'id', $value_field = 'name') {
    if (empty($json_string)) return '';
    
    $ids = json_decode($json_string, true);
    if (!is_array($ids)) return '';
    
    $names = [];
    foreach ($options_array as $option) {
        if (in_array($option[$key_field], $ids)) {
            $names[] = $option[$value_field];
        }
    }
    
    return implode(', ', $names);
}

// Send email notification
function sendEmailNotification($to, $subject, $message) {
    // In a real implementation, you would use PHPMailer or similar
    // For now, we'll log the email to database
    $db = getDB();
    $stmt = $db->prepare("
        INSERT INTO email_notifications (recipient_email, subject, message, status) 
        VALUES (?, ?, ?, 'sent')
    ");
    $stmt->execute([$to, $subject, $message]);
    
    // Return true for demo purposes
    return true;
}

// Get user info
function getUserInfo($user_id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Sanitize input
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>