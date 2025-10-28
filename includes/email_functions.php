<?php
// Email notification functions for TVRI Broadcasting System

function sendTransmissionProblemNotification($report_data) {
    $db = getDB();
    
    // Get admin emails
    $stmt = $db->prepare("SELECT email FROM users WHERE user_level = 'Admin' AND is_active = 1");
    $stmt->execute();
    $admin_emails = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $subject = "New Transmission Problem Report - TVRI Broadcasting";
    $message = "A new transmission problem report has been submitted.\n\n";
    $message .= "Report Details:\n";
    $message .= "Date: " . $report_data['report_date'] . "\n";
    $message .= "Time: " . $report_data['problem_time'] . "\n";
    $message .= "Shift: " . $report_data['shift'] . "\n";
    $message .= "Problem: " . $report_data['problem_description'] . "\n";
    $message .= "Solution: " . $report_data['solution_description'] . "\n";
    $message .= "\nPlease log in to the system to view the complete report.";
    
    foreach ($admin_emails as $email) {
        sendEmailNotification($email, $subject, $message);
    }
}

function sendUserAccountNotification($user_data, $action = 'created') {
    $subject = "TVRI Broadcasting System - Account " . ucfirst($action);
    $message = "Your account has been {$action} in the TVRI Broadcasting System.\n\n";
    $message .= "Account Details:\n";
    $message .= "Name: " . $user_data['full_name'] . "\n";
    $message .= "Email: " . $user_data['email'] . "\n";
    $message .= "User Level: " . $user_data['user_level'] . "\n";
    
    if ($action === 'created') {
        $message .= "Password: " . ($user_data['temp_password'] ?? 'Please contact administrator') . "\n";
        $message .= "\nPlease change your password after first login.";
    }
    
    $message .= "\nSystem URL: " . $_SERVER['HTTP_HOST'];
    
    sendEmailNotification($user_data['email'], $subject, $message);
}

function sendSystemMaintenanceNotification($maintenance_data) {
    $db = getDB();
    
    // Get all active users
    $stmt = $db->prepare("SELECT email FROM users WHERE is_active = 1");
    $stmt->execute();
    $user_emails = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $subject = "System Maintenance Notification - TVRI Broadcasting";
    $message = "The TVRI Broadcasting Reporting System will undergo maintenance.\n\n";
    $message .= "Maintenance Details:\n";
    $message .= "Start Time: " . $maintenance_data['start_time'] . "\n";
    $message .= "End Time: " . $maintenance_data['end_time'] . "\n";
    $message .= "Description: " . $maintenance_data['description'] . "\n";
    $message .= "\nThe system may be unavailable during this period.";
    
    foreach ($user_emails as $email) {
        sendEmailNotification($email, $subject, $message);
    }
}

function sendWeeklyReportSummary() {
    $db = getDB();
    
    // Get admin and leader emails
    $stmt = $db->prepare("SELECT email FROM users WHERE user_level IN ('Admin', 'Leader') AND is_active = 1");
    $stmt->execute();
    $recipient_emails = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get weekly statistics
    $week_start = date('Y-m-d', strtotime('monday this week'));
    $week_end = date('Y-m-d', strtotime('sunday this week'));
    
    $broadcasting_count = $db->prepare("SELECT COUNT(*) FROM broadcasting_logbook WHERE report_date BETWEEN ? AND ?");
    $broadcasting_count->execute([$week_start, $week_end]);
    $broadcasting_total = $broadcasting_count->fetchColumn();
    
    $downtime_count = $db->prepare("SELECT COUNT(*) FROM downtime_dvb WHERE report_date BETWEEN ? AND ?");
    $downtime_count->execute([$week_start, $week_end]);
    $downtime_total = $downtime_count->fetchColumn();
    
    $transmission_count = $db->prepare("SELECT COUNT(*) FROM transmission_problems WHERE report_date BETWEEN ? AND ?");
    $transmission_count->execute([$week_start, $week_end]);
    $transmission_total = $transmission_count->fetchColumn();
    
    $subject = "Weekly Report Summary - TVRI Broadcasting System";
    $message = "Weekly Report Summary for " . date('M j', strtotime($week_start)) . " - " . date('M j, Y', strtotime($week_end)) . "\n\n";
    $message .= "Report Statistics:\n";
    $message .= "Broadcasting Logbook Reports: " . $broadcasting_total . "\n";
    $message .= "Downtime DVB Reports: " . $downtime_total . "\n";
    $message .= "Transmission Problem Reports: " . $transmission_total . "\n";
    $message .= "Total Reports: " . ($broadcasting_total + $downtime_total + $transmission_total) . "\n\n";
    $message .= "Please log in to the system for detailed reports.";
    
    foreach ($recipient_emails as $email) {
        sendEmailNotification($email, $subject, $message);
    }
}

// Email template functions
function getEmailTemplate($type, $data = []) {
    $templates = [
        'welcome' => [
            'subject' => 'Selamat Datang di Sistem Pelaporan TVRI',
            'body' => 'Terima kasih telah bergabung dengan Sistem Pelaporan TVRI. Kami senang memiliki Anda sebagai bagian dari tim kami.'
        ],
        'password_reset' => [
            'subject' => 'Permintaan Reset Kata Sandi',
            'body' => 'Anda telah meminta untuk mereset kata sandi Anda. Silakan gunakan tautan berikut untuk mengatur ulang kata sandi Anda: {reset_link}'
        ],
        'report_submitted' => [
            'subject' => 'Laporan Dikirim Berhasil',
            'body' => 'Laporan Anda telah berhasil dikirim. Terima kasih atas kontribusi Anda.'
        ]
    ];
    
    return $templates[$type] ?? null;
}

// Email queue management (for high-volume scenarios)
function queueEmail($recipient, $subject, $message, $priority = 'normal') {
    $db = getDB();
    
    $stmt = $db->prepare("
        INSERT INTO email_queue (recipient_email, subject, message, priority, status) 
        VALUES (?, ?, ?, ?, 'pending')
    ");
    
    return $stmt->execute([$recipient, $subject, $message, $priority]);
}

function processEmailQueue($limit = 10) {
    $db = getDB();
    
    // Get pending emails
    $stmt = $db->prepare("
        SELECT * FROM email_queue 
        WHERE status = 'pending' 
        ORDER BY priority DESC, created_at ASC 
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    $emails = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($emails as $email) {
        $success = sendEmailNotification($email['recipient_email'], $email['subject'], $email['message']);
        
        // Update status
        $status = $success ? 'sent' : 'failed';
        $update_stmt = $db->prepare("UPDATE email_queue SET status = ?, sent_at = NOW() WHERE id = ?");
        $update_stmt->execute([$status, $email['id']]);
    }
    
    return count($emails);
}
?>