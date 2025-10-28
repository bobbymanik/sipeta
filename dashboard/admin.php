<?php
require_once '../includes/functions.php';
requireAuth('Admin');

$db = getDB();

// Get statistics
$total_users = $db->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn();
$total_reports = $db->query("
    SELECT COUNT(*) FROM (
        SELECT id FROM broadcasting_logbook 
        UNION ALL 
        SELECT id FROM downtime_dvb 
        UNION ALL 
        SELECT id FROM transmission_problems
    ) as all_reports
")->fetchColumn();
$recent_changes = $db->query("SELECT COUNT(*) FROM audit_trail WHERE DATE(changed_at) = CURDATE()")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda Admin - Sistem Pelaporan TVRI</title>
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
                <span class="user-info">Selamat Datang, <?php echo htmlspecialchars($_SESSION['full_name']); ?> (Admin)</span>
                <a href="../auth/logout.php" class="logout-btn">Keluar</a>
            </div>
        </header>

        <nav class="dashboard-nav">
            <a href="#" class="nav-item active">Beranda</a>
            <a href="../reports/view_reports.php" class="nav-item">Tinjau Laporan</a>
            <a href="../admin/user_management.php" class="nav-item">Manajemen Pengguna</a>
            <a href="../admin/manage_options.php" class="nav-item">Kelola Opsi </a>
            <a href="../admin/export_reports.php" class="nav-item">Ekspor Laporan</a>
            <a href="../admin/audit_trail.php" class="nav-item">Riwayat</a>
        </nav>

        <main class="dashboard-main">
            <div class="dashboard-grid">
                <div class="stats-card">
                    <h3>Total Pengguna</h3>
                    <div class="stat-number"><?php echo $total_users; ?></div>
                    <p>Pengguna Aktif</p>
                </div>

                <div class="stats-card">
                    <h3>Total Laporan</h3>
                    <div class="stat-number"><?php echo $total_reports; ?></div>
                    <p>Semua Laporan</p>
                </div>

                <div class="stats-card">
                    <h3>Laporan Hari Ini</h3>
                    <div class="stat-number"><?php echo $recent_changes; ?></div>
                    <p>Laporan Masuk</p>
                </div>
            </div>

            <div class="quick-actions">
                <h2>Menu Utama</h2>
                <div class="action-grid">
                    <a href="../admin/user_management.php" class="action-card">
                        <h3>👥 Manajemen Pengguna</h3>
                        <p>Menambah, mengubah, atau menonaktifkan akun pengguna</p>
                    </a>
                    
                    <a href="../reports/view_reports.php" class="action-card">
                        <h3>📊 Tinjau Laporan</h3>
                        <p>Meninjau dan mengelola seluruh laporan</p>
                    </a>
                    
                    <a href="../admin/export_reports.php" class="action-card">
                        <h3>📥 Ekspor Laporan</h3>
                        <p>Mengekspor laporan dalam format PDF atau Excel</p>
                    </a>
                    
                    <a href="../admin/manage_options.php" class="action-card">
                        <h3>⚙️ Kelola Opsi</h3>
                        <p>Mengelola data pegawai, program, dan transmisi</p>
                    </a>
                </div>
            </div>
        </main>
    </div>
</body>
</html>