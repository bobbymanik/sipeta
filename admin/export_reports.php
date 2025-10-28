<?php
require_once '../includes/functions.php';
requireAuth('Admin');

$db = getDB();

// Get parameters
$format = $_GET['format'] ?? 'pdf';
$report_type = $_GET['type'] ?? 'all';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query conditions
$where_conditions = [];
$params = [];

if ($date_from) {
    $where_conditions[] = "report_date >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $where_conditions[] = "report_date <= ?";
    $params[] = $date_to;
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get reports data
$reports = [];

if ($report_type === 'all' || $report_type === 'broadcasting') {
    $sql = "SELECT 'Broadcasting Logbook' as type, bl.*, u.full_name as created_by_name FROM broadcasting_logbook bl JOIN users u ON bl.created_by = u.id {$where_clause}";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $reports = array_merge($reports, $stmt->fetchAll(PDO::FETCH_ASSOC));
}

if ($report_type === 'all' || $report_type === 'downtime') {
    $sql = "SELECT 'Downtime DVB' as type, dd.*, u.full_name as created_by_name FROM downtime_dvb dd JOIN users u ON dd.created_by = u.id {$where_clause}";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $reports = array_merge($reports, $stmt->fetchAll(PDO::FETCH_ASSOC));
}

if ($report_type === 'all' || $report_type === 'transmission') {
    $sql = "SELECT 'Transmission Problem' as type, tp.*, u.full_name as created_by_name FROM transmission_problems tp JOIN users u ON tp.created_by = u.id {$where_clause}";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $reports = array_merge($reports, $stmt->fetchAll(PDO::FETCH_ASSOC));
}

// Sort by date
usort($reports, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

if ($format === 'excel') {
    // Export to CSV (Excel compatible)
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="tvri_reports_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // CSV headers
    fputcsv($output, ['Report Type', 'Report Date', 'Created By', 'Created At', 'Details']);
    
    foreach ($reports as $report) {
        $details = '';
        switch ($report['type']) {
            case 'Broadcasting Logbook':
                $details = "Day: {$report['day_of_week']}, Shift: {$report['shift']}, Audio: {$report['audio_quality']}, Video: {$report['video_quality']}";
                break;
            case 'Downtime DVB':
                $details = "Start: {$report['downtime_start']}, Finish: {$report['downtime_finish']}, Duration: {$report['total_downtime_minutes']} min";
                break;
            case 'Transmission Problem':
                $details = "Time: {$report['problem_time']}, Unit: {$report['transmission_unit']}, Shift: {$report['shift']}";
                break;
        }
        
        fputcsv($output, [
            $report['type'],
            $report['report_date'],
            $report['created_by_name'],
            $report['created_at'],
            $details
        ]);
    }
    
    fclose($output);
    exit;
}

// PDF Export (simplified HTML to PDF)
if ($format === 'pdf') {
    // Gunakan HTML normal agar tidak error
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: inline; filename="tvri_reports_' . date('Y-m-d') . '.html"');
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>TVRI Reports Export</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .header { text-align: center; margin-bottom: 30px; }
            .logo { width: 100px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
            .report-details { font-size: 12px; }
            @media print { 
                body { margin: 0; }
                .no-print { display: none; }
            }
        </style>
    </head>
    <body>
        <div class="header">
            <img src="https://upload.wikimedia.org/wikipedia/commons/e/e0/TVRI_Riau.svg" alt="TVRI Logo" class="logo">
            <h1>Sistem Pelaporan TVRI</h1>
            <p>Generated on <?php echo date('F j, Y'); ?></p>
            <?php if ($date_from || $date_to): ?>
            <p>Period: <?php echo $date_from ?: 'All time'; ?> to <?php echo $date_to ?: 'Present'; ?></p>
            <?php endif; ?>
        </div>
        
        <div class="no-print">
            <p><strong>Note:</strong> Tekan <em>Ctrl + P</em> lalu pilih <em>Save as PDF</em> untuk mengunduh laporan ini.</p>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Jenis Laporan</th>
                    <th>Tanggal</th>
                    <th>Dibuat Oleh</th>
                    <th>Rincian</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reports as $report): ?>
                <tr>
                    <td><?php echo htmlspecialchars($report['type']); ?></td>
                    <td><?php echo htmlspecialchars($report['report_date']); ?></td>
                    <td><?php echo htmlspecialchars($report['created_by_name']); ?></td>
                    <td class="report-details">
                        <?php
                        switch ($report['type']) {
                            case 'Broadcasting Logbook':
                                echo "Day: {$report['day_of_week']}<br>";
                                echo "Shift: {$report['shift']}<br>";
                                echo "Audio: {$report['audio_quality']}, Video: {$report['video_quality']}";
                                if ($report['problem_duration']) {
                                    echo "<br>Problem Duration: {$report['problem_duration']}";
                                }
                                break;
                            case 'Downtime DVB':
                                echo "Start: {$report['downtime_start']}<br>";
                                echo "Finish: {$report['downtime_finish']}<br>";
                                echo "Duration: {$report['total_downtime_minutes']} minutes<br>";
                                echo "Problem: " . htmlspecialchars($report['downtime_problem']);
                                break;
                            case 'Transmission Problem':
                                echo "Time: {$report['problem_time']}<br>";
                                echo "Shift: {$report['shift']}<br>";
                                echo "Problem: " . htmlspecialchars(substr($report['problem_description'], 0, 100)) . '...';
                                break;
                        }
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <script>
            window.onload = () => {
                setTimeout(() => window.print(), 1000);
            };
        </script>
    </body>
    </html>
    <?php
    exit;
}


// Default: Show export options page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ekspor Laporan - Sistem Pelaporan TVRI</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <div class="header-left">
                <img src="https://upload.wikimedia.org/wikipedia/commons/e/e0/TVRI_Riau.svg" alt="TVRI Logo" class="header-logo">
                <h1>Ekspor Laporan</h1>
            </div>
            <div class="header-right">
                <span class="user-info"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <a href="../dashboard/admin.php" class="back-btn">Kembali ke Beranda</a>
            </div>
        </header>

        <main class="export-main">
            <div class="export-form">
                <h2>Ekspor Konfigurasi</h2>
                <form method="GET">
                    <div class="form-group">
                        <label for="type">Jenis Laporan:</label>
                        <select id="type" name="type">
                            <option value="all">Semua Laporan</option>
                            <option value="broadcasting">Laporan Harian</option>
                            <option value="downtime">Laporan Downtime DVB</option>
                            <option value="transmission">Laporan Masalah Transmisi</option>
                        </select>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="date_from">Dari Tanggal:</label>
                            <input type="date" id="date_from" name="date_from">
                        </div>
                        
                        <div class="form-group">
                            <label for="date_to">Hingga Tanggal:</label>
                            <input type="date" id="date_to" name="date_to">
                        </div>
                    </div>
                    
                    <div class="export-buttons">
                        <button type="submit" name="format" value="pdf" class="export-btn pdf">Ekspor ke PDF</button>
                        <button type="submit" name="format" value="excel" class="export-btn excel">Ekspor ke Excel</button>
                    </div>
                </form>
            </div>
            
            <div class="export-info">
                <h3>Ekspor Informasi</h3>
                <ul>
                    <li><strong>Ekspor PDF:</strong> Menghasilkan laporan PDF yang dapat dicetak dengan semua data yang dipilih</li>
                    <li><strong>Ekspor Excel:</strong> Membuat file CSV yang dapat dibuka di Excel atau aplikasi spreadsheet lainnya</li>
                    <li><strong>Rentang Tanggal:</strong> Biarkan tanggal kosong untuk mengekspor semua laporan</li>
                    <li><strong>Jenis Laporan:</strong> Pilih jenis laporan tertentu atau pilih "Semua Laporan" untuk ekspor lengkap</li>
                </ul>
            </div>
        </main>
    </div>
</body>
</html>