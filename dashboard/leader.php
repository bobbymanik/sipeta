<?php
require_once '../includes/functions.php';
requireAuth('Leader');

$db = getDB();

// Get recent reports summary
$broadcasting_count = $db->query("SELECT COUNT(*) FROM broadcasting_logbook WHERE created_at >= NOW() - INTERVAL 7 DAY")->fetchColumn();
$downtime_count = $db->query("SELECT COUNT(*) FROM downtime_dvb WHERE created_at >= NOW() - INTERVAL 7 DAY")->fetchColumn();
$transmission_count = $db->query("SELECT COUNT(*) FROM transmission_problems WHERE created_at >= NOW() - INTERVAL 7 DAY")->fetchColumn();

// Get recent reports
$recent_reports = $db->query("
    SELECT 'Broadcasting Logbook' as type, report_date as date, bl.created_at, u.full_name as created_by
    FROM broadcasting_logbook bl 
    JOIN users u ON bl.created_by = u.id 
    UNION ALL
    SELECT 'Downtime DVB' as type, report_date as date, dd.created_at, u.full_name as created_by
    FROM downtime_dvb dd 
    JOIN users u ON dd.created_by = u.id
    UNION ALL
    SELECT 'Transmission Problem' as type, report_date as date, tp.created_at, u.full_name as created_by
    FROM transmission_problems tp 
    JOIN users u ON tp.created_by = u.id
    ORDER BY created_at DESC LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda Pimpinan - Sistem Pelaporan TVRI</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <div class="header-left">
                <img src="https://upload.wikimedia.org/wikipedia/commons/e/e0/TVRI_Riau.svg" alt="TVRI Logo" class="header-logo">
                <h1>Sistem Pelaporan TVRI</h1>
            </div>
            <div class="header-right">
                <span class="user-info">Selamat Datang, <?php echo htmlspecialchars($_SESSION['full_name']); ?> (Pimpinan)</span>
                <a href="../auth/logout.php" class="logout-btn">Keluar</a>
            </div>
        </header>

        <nav class="dashboard-nav">
            <a href="#" class="nav-item active">Beranda</a>
            <a href="../reports/view_reports.php" class="nav-item">Tinjau Laporan</a>
            <a href="../admin/audit_trail.php" class="nav-item">Riwayat</a>
        </nav>

        <main class="dashboard-main">
            <div class="dashboard-grid">
                <div class="stats-card">
                    <h3>Laporan Harian</h3>
                    <div class="stat-number"><?php echo $broadcasting_count; ?></div>
                    <p>Laporan minggu ini</p>
                </div>

                <div class="stats-card">
                    <h3>Laporan Downtime</h3>
                    <div class="stat-number"><?php echo $downtime_count; ?></div>
                    <p>Laporan minggu ini</p>
                </div>

                <div class="stats-card">
                    <h3>Laporan Masalah Transmisi</h3>
                    <div class="stat-number"><?php echo $transmission_count; ?></div>
                    <p>Laporan minggu ini</p>
                </div>
            </div>

            <div class="recent-reports">
                <h2>Laporan Terkini</h2>
                <div class="table-container">
                    <table class="reports-table">
                        <thead>
                            <tr>
                                <th>Jenis Laporan</th>
                                <th>Tanggal</th>
                                <th>Pelapor</th>
                                <th>Waktu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_reports as $report): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($report['type']); ?></td>
                                <td><?php echo htmlspecialchars($report['date']); ?></td>
                                <td><?php echo htmlspecialchars($report['created_by']); ?></td>
                                <td><?php echo date('M j, Y H:i', strtotime($report['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>