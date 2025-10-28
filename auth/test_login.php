<?php
// auth/test_login.php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// PERBAIKI PATH - gunakan path yang benar dari lokasi file ini
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

echo "<h2>Login System Debug</h2>";

// Test database connection
echo "<h3>1. Database Connection Test:</h3>";
$db = getDB();
if ($db) {
    echo "✓ Database connection successful<br>";
    
    // Test users table
    try {
        $stmt = $db->query("SELECT COUNT(*) as count FROM users");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✓ Users table exists with " . $result['count'] . " records<br>";
        
        // Show sample users (without passwords)
        $stmt = $db->query("SELECT id, email, user_level, is_active FROM users LIMIT 5");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>2. Sample Users:</h3>";
        if (count($users) > 0) {
            foreach ($users as $user) {
                echo "ID: {$user['id']}, Email: {$user['email']}, Level: {$user['user_level']}, Active: {$user['is_active']}<br>";
            }
        } else {
            echo "No users found in database<br>";
        }
    } catch (Exception $e) {
        echo "✗ Error accessing users table: " . $e->getMessage() . "<br>";
    }
} else {
    echo "✗ Database connection failed<br>";
    echo "Please check your database configuration in config/database.php<br>";
}

// Test session
echo "<h3>3. Session Test:</h3>";
echo "Session ID: " . session_id() . "<br>";
echo "Session status: " . session_status() . "<br>";

// Test functions
echo "<h3>4. Functions Test:</h3>";
echo "CSRF Token: " . generateCSRFToken() . "<br>";
echo "isLoggedIn(): " . (isLoggedIn() ? 'true' : 'false') . "<br>";

echo "<h3>5. Manual Login Test:</h3>";
echo '<form method="POST" action="login.php">
    Email: <input type="email" name="email" value=""><br>
    Password: <input type="password" name="password" value=""><br>
    <input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">
    <button type="submit">Test Login</button>
</form>';

// Test file paths
echo "<h3>6. File Path Check:</h3>";
echo "Current directory: " . __DIR__ . "<br>";
echo "Database file exists: " . (file_exists(__DIR__ . '/../config/database.php') ? 'Yes' : 'No') . "<br>";
echo "Functions file exists: " . (file_exists(__DIR__ . '/../includes/functions.php') ? 'Yes' : 'No') . "<br>";
?>