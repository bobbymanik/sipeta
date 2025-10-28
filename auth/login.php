<?php
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metode tidak diizinkan']);
    exit();
}

$email = sanitizeInput($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Email dan kata sandi diperlukan']);
    exit();
}

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_level'] = $user['user_level'];
        $_SESSION['full_name'] = $user['full_name'];
        
        // Determine redirect URL based on user level
        $redirect_url = 'dashboard/' . strtolower($user['user_level']) . '.php';
        
        echo json_encode([
            'success' => true, 
            'message' => 'Login berhasil',
            'redirect' => $redirect_url,
            'user_level' => $user['user_level']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Email atau kata sandi tidak valid']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Login gagal. Silakan coba lagi.']);
}
?>