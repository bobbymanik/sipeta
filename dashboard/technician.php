<?php
require_once '../includes/functions.php';
requireAuth('Technician');

$db = getDB();

// Get user's report statistics
$user_id = $_SESSION['user_id'];
$broadcasting_count = $db->prepare("SELECT COUNT(*) FROM broadcasting_logbook WHERE created_by = ?");
$broadcasting_count->execute([$user_id]);
$broadcasting_total = $broadcasting_count->fetchColumn();

$downtime_count = $db->prepare("SELECT COUNT(*) FROM downtime_dvb WHERE created_by = ?");
$downtime_count->execute([$user_id]);
$downtime_total = $downtime_count->fetchColumn();

$transmission_count = $db->prepare("SELECT COUNT(*) FROM transmission_problems WHERE created_by = ?");
$transmission_count->execute([$user_id]);
$transmission_total = $transmission_count->fetchColumn();

// Get recent reports by this user
$recent_reports = $db->prepare("
    SELECT 'Broadcasting Logbook' as type, report_date as date, created_at, id
    FROM broadcasting_logbook WHERE created_by = ?
    UNION ALL
    SELECT 'Downtime DVB' as type, report_date as date, created_at, id
    FROM downtime_dvb WHERE created_by = ?
    UNION ALL
    SELECT 'Transmission Problem' as type, report_date as date, created_at, id
    FROM transmission_problems WHERE created_by = ?
    ORDER BY created_at DESC LIMIT 5
");
$recent_reports->execute([$user_id, $user_id, $user_id]);
$user_reports = $recent_reports->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda Teknisi - Sistem Pelaporan TVRI</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <div class="header-left">
                <img src="https://upload.wikimedia.org/wikipedia/commons/e/e0/TVRI_Riau.svg" alt="TVRI Logo" class="header-logo">
                <h1>Sistem Pelaporan TTVRI</h1>
            </div>
            <div class="header-right">
                <span class="user-info">Selamat Datang, <?php echo htmlspecialchars($_SESSION['full_name']); ?> (Teknisi)</span>
                <a href="../auth/logout.php" class="logout-btn">Keluar</a>
            </div>
        </header>

        <nav class="dashboard-nav">
            <a href="#" class="nav-item active">Beranda</a>
            <a href="../reports/broadcasting_logbook.php" class="nav-item">Laporan Harian</a>
            <a href="../reports/downtime_dvb.php" class="nav-item">Laporan Downtime DVB T2</a>
            <a href="../reports/transmission_problem.php" class="nav-item">Laporan Masalah Transmisi</a>
            <a href="../reports/view_reports.php" class="nav-item">Laporan Saya</a>
        </nav>

        <main class="dashboard-main">
            <div class="dashboard-grid">
                <div class="stats-card">
                    <h3>Laporan Harian</h3>
                    <div class="stat-number"><?php echo $broadcasting_total; ?></div>
                    <p>Laporan terkirim</p>
                </div>

                <div class="stats-card">
                    <h3>Laporan Downtime</h3>
                    <div class="stat-number"><?php echo $downtime_total; ?></div>
                    <p>Laporan terkirim</p>
                </div>

                <div class="stats-card">
                    <h3>Laporan Masalah Transmisi</h3>
                    <div class="stat-number"><?php echo $transmission_total; ?></div>
                    <p>Laporan terkirim</p>
                </div>
            </div>

            <div class="create-reports">
                <h2>Buat Laporan Baru</h2>
                <div class="action-grid">
                    <a href="../reports/broadcasting_logbook.php" class="action-card">
                        <h3>📺 Laporan Harian</h3>
                        <p>Merekam laporan aktivitas harian</p>
                    </a>
                    
                    <a href="../reports/downtime_dvb.php" class="action-card">
                        <h3>📡 Laporan Downtime DVB T2 TX</h3>
                        <p>Melaporkan masalah downtime transmisi</p>
                    </a>
                    
                    <a href="../reports/transmission_problem.php" class="action-card">
                        <h3>⚠️ Laporan Masalah Transmisi</h3>
                        <p>Melaporkan masalah yang ada di transmisi</p>
                    </a>
                </div>
            </div>

            <div class="recent-reports">
                <h2>Laporan Terbaru Saya</h2>
                <div class="table-container">
                    <table class="reports-table">
                        <thead>
                            <tr>
                                <th>Jenis Laporan</th>
                                <th>Tanggal</th>
                                <th>Waktu</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($user_reports as $report): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($report['type']); ?></td>
                                <td><?php echo htmlspecialchars($report['date']); ?></td>
                                <td><?php echo date('M j, Y H:i', strtotime($report['created_at'])); ?></td>
                                <td><span class="status-badge status-submitted">Terkirim</span></td>
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