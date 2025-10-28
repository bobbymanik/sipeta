<?php
require_once '../includes/functions.php';
requireAuth(['Leader', 'Admin']);

$db = getDB();

// Get filter parameters
$table_filter = $_GET['table'] ?? '';
$action_filter = $_GET['action'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if ($table_filter) {
    $where_conditions[] = "table_name = ?";
    $params[] = $table_filter;
}

if ($action_filter) {
    $where_conditions[] = "action = ?";
    $params[] = $action_filter;
}

if ($date_from) {
    $where_conditions[] = "DATE(changed_at) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $where_conditions[] = "DATE(changed_at) <= ?";
    $params[] = $date_to;
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get audit trail records
$sql = "
    SELECT at.*, u.full_name as changed_by_name 
    FROM audit_trail at 
    JOIN users u ON at.changed_by = u.id 
    {$where_clause}
    ORDER BY at.changed_at DESC 
    LIMIT 100
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$audit_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat - Sistem Pelaporan TVRI</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <div class="header-left">
                <img src="https://upload.wikimedia.org/wikipedia/commons/e/e0/TVRI_Riau.svg" alt="TVRI Logo" class="header-logo">
                <h1>Riwayat</h1>
            </div>
            <div class="header-right">
                <span class="user-info"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <a href="../dashboard/<?php echo strtolower($_SESSION['user_level']); ?>.php" class="back-btn">Kembali ke Beranda</a>
            </div>
        </header>

        <main class="audit-main">
            <!-- Filters -->
<div class="filters-section">
    <form method="GET" class="filters-form">
        <div class="filter-group">
            <label for="table">Jenis Laporan:</label>
            <select id="table" name="table">
                <option value="">Semua Laporan</option>
                <option value="broadcasting_logbook" <?php echo $table_filter === 'broadcasting_logbook' ? 'selected' : ''; ?>>Laporan Harian</option>
                <option value="downtime_dvb" <?php echo $table_filter === 'downtime_dvb' ? 'selected' : ''; ?>>Laporan Downtime DVB</option>
                <option value="transmission_problems" <?php echo $table_filter === 'transmission_problems' ? 'selected' : ''; ?>>Laporan Masalah Transmisi</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label for="action">Aksi:</label>
            <select id="action" name="action">
                <option value="">Semua Aksi</option>
                <option value="INSERT" <?php echo $action_filter === 'INSERT' ? 'selected' : ''; ?>>INSERT</option>
                <option value="UPDATE" <?php echo $action_filter === 'UPDATE' ? 'selected' : ''; ?>>UPDATE</option>
                <option value="DELETE" <?php echo $action_filter === 'DELETE' ? 'selected' : ''; ?>>DELETE</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label for="date_from">Dari Tanggal:</label>
            <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
        </div>
        
        <div class="filter-group">
            <label for="date_to">Hingga Tanggal:</label>
            <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
        </div>

        <!-- Tombol filter & hapus filter dibungkus bersama -->
        <div class="filter-actions">
            <button type="submit" class="filter-btn">Filter</button>
            <a href="audit_trail.php" class="clear-btn">Bersihkan</a>
        </div>
    </form>
</div>

            <!-- Audit Records -->
            <div class="table-container">
                <table class="audit-table">
                    <thead>
                        <tr>
                            <th>Tanggal / Waktu</th>
                            <th>Jenis Laporan</th>
                            <th>ID Pelapor</th>
                            <th>Aksi</th>
                            <th>Pelapor</th>
                            <th>Rincian</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($audit_records)): ?>
                        <tr>
                            <td colspan="6" class="no-data">Tidak ada riwayat ditemukan</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($audit_records as $record): ?>
                        <tr>
                            <td><?php echo date('M j, Y H:i:s', strtotime($record['changed_at'])); ?></td>
                            <td><?php echo htmlspecialchars($record['table_name']); ?></td>
                            <td><?php echo htmlspecialchars($record['record_id']); ?></td>
                            <td><span class="action-badge action-<?php echo strtolower($record['action']); ?>"><?php echo $record['action']; ?></span></td>
                            <td><?php echo htmlspecialchars($record['changed_by_name']); ?></td>
                            <td>
                                <button onclick="viewAuditDetails(<?php echo htmlspecialchars(json_encode($record)); ?>)" class="details-btn">Lihat</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Modal for audit details -->
    <div id="auditModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div id="auditDetails"></div>
        </div>
    </div>

    <script>
        function viewAuditDetails(record) {
            let html = '<h2>Audit Details</h2>';
            html += '<div class="audit-details">';
            html += '<p><strong>Table:</strong> ' + record.table_name + '</p>';
            html += '<p><strong>Record ID:</strong> ' + record.record_id + '</p>';
            html += '<p><strong>Action:</strong> ' + record.action + '</p>';
            html += '<p><strong>Changed By:</strong> ' + record.changed_by_name + '</p>';
            html += '<p><strong>Date/Time:</strong> ' + new Date(record.changed_at).toLocaleString() + '</p>';
            
            if (record.old_values) {
                html += '<h3>Old Values:</h3>';
                html += '<pre>' + JSON.stringify(JSON.parse(record.old_values), null, 2) + '</pre>';
            }
            
            if (record.new_values) {
                html += '<h3>New Values:</h3>';
                html += '<pre>' + JSON.stringify(JSON.parse(record.new_values), null, 2) + '</pre>';
            }
            
            html += '</div>';
            
            document.getElementById('auditDetails').innerHTML = html;
            document.getElementById('auditModal').style.display = 'block';
        }

        // Close modal
        document.querySelector('.close').onclick = function() {
            document.getElementById('auditModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('auditModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>