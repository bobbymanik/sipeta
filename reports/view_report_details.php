<?php
require_once '../includes/functions.php';
requireAuth(['Leader', 'Admin', 'Technician']);

$table = $_GET['table'] ?? '';
$id = $_GET['id'] ?? '';

if (!$table || !$id) {
    echo '<p>Invalid request</p>';
    exit;
}

$db = getDB();

// Validate table name for security
$allowed_tables = ['broadcasting_logbook', 'downtime_dvb', 'transmission_problems'];
if (!in_array($table, $allowed_tables)) {
    echo '<p>Invalid table</p>';
    exit;
}

// Get report data
$stmt = $db->prepare("SELECT * FROM {$table} WHERE id = ?");
$stmt->execute([$id]);
$report = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$report) {
    echo '<p>Report not found</p>';
    exit;
}

// Check if technician can only view their own reports
if ($_SESSION['user_level'] === 'Technician' && $report['created_by'] != $_SESSION['user_id']) {
    echo '<p>Access denied</p>';
    exit;
}

// Get creator info
$creator = getUserInfo($report['created_by']);

// Get options for display
$staff_options = getStaffOptions('shift_staff');
$report_staff = getStaffOptions('report_staff');
$national_programs = getProgramOptions('national');
$local_programs = getProgramOptions('local');
$transmission_units = getTransmissionUnits();

echo '<h2>Rincian Laporan</h2>';

switch ($table) {
    case 'broadcasting_logbook':
        echo '<h3>Laporan Harian</h3>';
        echo '<div class="report-details">';
        echo '<p><strong>Hari:</strong> ' . htmlspecialchars($report['day_of_week']) . '</p>';
        echo '<p><strong>Tanggal:</strong> ' . htmlspecialchars($report['report_date']) . '</p>';
        echo '<p><strong>Shift:</strong> ' . htmlspecialchars($report['shift']) . '</p>';
        echo '<p><strong>Pegawai:</strong> ' . formatJsonArray($report['shift_staff'], $staff_options) . '</p>';
        echo '<p><strong>Program Nasional:</strong> ' . formatJsonArray($report['national_programs'], $national_programs) . '</p>';
        echo '<p><strong>Program Lokal:</strong> ' . formatJsonArray($report['local_programs'], $local_programs) . '</p>';
        echo '<p><strong>Kualitas Audio:</strong> ' . htmlspecialchars($report['audio_quality']) . '</p>';
        echo '<p><strong>Kualitas Video:</strong> ' . htmlspecialchars($report['video_quality']) . '</p>';
        echo '<p><strong>Durasi Masalah:</strong> ' . htmlspecialchars($report['problem_duration']) . '</p>';
        echo '<p><strong>Catatan:</strong> ' . nl2br(htmlspecialchars($report['notes'])) . '</p>';
        echo '</div>';
        break;
        
    case 'downtime_dvb':
        echo '<h3>Laporan Downtime DVB T2 TX</h3>';
        echo '<div class="report-details">';
        echo '<p><strong>Tanggal:</strong> ' . htmlspecialchars($report['report_date']) . '</p>';
        echo '<p><strong>Unit Transmisi:</strong> ' . formatJsonArray($report['transmission_units'], $transmission_units) . '</p>';
        echo '<p><strong>Downtime Start:</strong> ' . htmlspecialchars($report['downtime_start']) . '</p>';
        echo '<p><strong>Downtime Finish:</strong> ' . htmlspecialchars($report['downtime_finish']) . '</p>';
        echo '<p><strong>Total Downtime:</strong> ' . htmlspecialchars($report['total_downtime_minutes']) . ' minutes</p>';
        echo '<p><strong>Masalah:</strong> ' . htmlspecialchars($report['downtime_problem']) . '</p>';
        echo '<p><strong>Pelapor:</strong> ' . formatJsonArray($report['report_name'], $report_staff) . '</p>';
        echo '</div>';
        break;
        
    case 'transmission_problems':
        echo '<h3>Transmission Problem Report</h3>';
        echo '<div class="report-details">';
        echo '<p><strong>Tanggal:</strong> ' . htmlspecialchars($report['report_date']) . '</p>';
        echo '<p><strong>Waktu:</strong> ' . htmlspecialchars($report['problem_time']) . '</p>';
        echo '<p><strong>Shift:</strong> ' . htmlspecialchars($report['shift']) . '</p>';
        echo '<p><strong>Pegawai:</strong> ' . formatJsonArray($report['shift_staff'], $staff_options) . '</p>';
        
        // Get transmission unit name
        $unit_name = '';
        foreach ($transmission_units as $unit) {
            if ($unit['id'] == $report['transmission_unit']) {
                $unit_name = $unit['name'];
                break;
            }
        }
        echo '<p><strong>Unit Transmisi:</strong> ' . htmlspecialchars($unit_name) . '</p>';
        echo '<p><strong>Deskripsi Masalah:</strong> ' . nl2br(htmlspecialchars($report['problem_description'])) . '</p>';
        echo '<p><strong>Solusi:</strong> ' . nl2br(htmlspecialchars($report['solution_description'])) . '</p>';
        echo '</div>';
        break;
}

echo '<div class="report-meta">';
echo '<p><strong>Pelapor:</strong> ' . htmlspecialchars($creator['full_name']) . '</p>';
echo '<p><strong>Tanggal:</strong> ' . date('M j, Y H:i:s', strtotime($report['created_at'])) . '</p>';
if ($report['updated_at'] != $report['created_at']) {
    echo '<p><strong>Last Updated:</strong> ' . date('M j, Y H:i:s', strtotime($report['updated_at'])) . '</p>';
}
echo '</div>';
?>