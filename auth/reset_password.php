<?php
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metode tidak diizinkan']);
    exit();
}

$email = sanitizeInput($_POST['email'] ?? '');

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Email diperlukan']);
    exit();
}

try {
    $db = getDB();
    
    // Check if user exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND is_active = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // Don't reveal if email exists or not for security
        echo json_encode(['success' => true, 'message' => 'Jika email tersebut ada, tautan pengaturan ulang telah dikirim.']);
        exit();
    }
    
    // Generate reset token
    $token = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Store reset token
    $stmt = $db->prepare("INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$user['id'], $token, $expires_at]);
    
    // Send reset email (simulated)
    $reset_link = "http://localhost/reset_password_form.php?token=" . $token;
    $subject = "Permintaan Reset Kata Sandi - Sistem Pelaporan TVRI";
    $message = "Klik tautan berikut untuk mengatur ulang kata sandi Anda: " . $reset_link . "\n\nTautan ini akan kedaluwarsa dalam 1 jam.";
    
    sendEmailNotification($email, $subject, $message);
    
    echo json_encode(['success' => true, 'message' => 'Tautan pengaturan ulang kata sandi telah dikirim ke email Anda.']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Gagal memproses permintaan pengaturan ulang. Silahkan coba lagi!']);
}
?>