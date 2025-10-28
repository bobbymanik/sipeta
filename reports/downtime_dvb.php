<?php
require_once '../includes/functions.php';
requireAuth(['Admin', 'Technician']);

$transmission_units = getTransmissionUnits();
$report_staff = getStaffOptions('report_staff');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token keamanan tidak valid. Silakan coba lagi.';
    } else {
        try {
            $db = getDB();
            
            $stmt = $db->prepare("
                INSERT INTO downtime_dvb 
                (transmission_units, downtime_start, downtime_finish, downtime_problem, report_name, report_date, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                json_encode($_POST['transmission_units'] ?? []),
                $_POST['downtime_start'],
                $_POST['downtime_finish'],
                $_POST['downtime_problem'],
                json_encode($_POST['report_name'] ?? []),
                $_POST['report_date'],
                $_SESSION['user_id']
            ]);
            
            if ($result) {
                $report_id = $db->lastInsertId();
                logAudit('downtime_dvb', $report_id, 'INSERT', null, $_POST);
                $success = 'Downtime DVB T2 TX report submitted successfully!';
            }
        } catch (Exception $e) {
            $error = 'Failed to submit report. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Downtime DVB T2 TX Riau - Sistem Pelaporan TVRI</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <div class="header-left">
                <img src="https://upload.wikimedia.org/wikipedia/commons/e/e0/TVRI_Riau.svg" alt="TVRI Logo" class="header-logo">
                <h1>Laporan Downtime DVB T2 TX Riau</h1>
            </div>
            <div class="header-right">
                <span class="user-info"><?php echo htmlspecialchars($_SESSION['full_name']); ?> (Teknisi)</span>
                <a href="../dashboard/<?php echo strtolower($_SESSION['user_level']); ?>.php" class="back-btn">Kembali ke Dashboard</a>
            </div>
        </header>

        <main class="form-main">
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" class="report-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="form-group">
                    <label for="report_date">Tanggal:</label>
                    <input type="date" id="report_date" name="report_date" required>
                </div>

                <div class="form-group">
                    <label>Unit Transmisi:</label>
                    <div class="checkbox-group">
                        <?php foreach ($transmission_units as $unit): ?>
                        <label class="checkbox-label">
                            <input type="checkbox" name="transmission_units[]" value="<?php echo $unit['id']; ?>">
                            <?php echo htmlspecialchars($unit['name']); ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="downtime_start">Downtime Start (A):</label>
                        <input type="time" id="downtime_start" name="downtime_start" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="downtime_finish">Downtime Finish (B):</label>
                        <input type="time" id="downtime_finish" name="downtime_finish" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="total_downtime">Total Downtime (Menit):</label>
                    <input type="number" id="total_downtime" name="total_downtime" readonly class="readonly-field">
                    <small>Dihitung secara otomatis dari waktu mulai dan selesai</small>
                </div>

                <div class="form-group">
                    <label for="downtime_problem">Masalah Downtime:</label>
                    <input type="text" id="downtime_problem" name="downtime_problem" required placeholder="Deskripsikan masalah...">
                </div>

                <div class="form-group">
                    <label>Nama Pelapor:</label>
                    <div class="checkbox-group">
                        <?php foreach ($report_staff as $staff): ?>
                        <label class="checkbox-label">
                            <input type="checkbox" name="report_name[]" value="<?php echo $staff['id']; ?>">
                            <?php echo htmlspecialchars($staff['name']); ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="submit-btn">Kirim Laporan</button>
                    <a href="../dashboard/<?php echo strtolower($_SESSION['user_level']); ?>.php" class="cancel-btn">Batal</a>
                </div>
            </form>
        </main>
    </div>

    <script>
        // Set today's date as default
        document.getElementById('report_date').valueAsDate = new Date();
        
        // Calculate total downtime automatically
        function calculateDowntime() {
            const startTime = document.getElementById('downtime_start').value;
            const finishTime = document.getElementById('downtime_finish').value;
            
            if (startTime && finishTime) {
                const start = new Date('2000-01-01 ' + startTime);
                const finish = new Date('2000-01-01 ' + finishTime);
                
                let diffMs = finish - start;
                
                // Handle case where finish time is next day
                if (diffMs < 0) {
                    diffMs += 24 * 60 * 60 * 1000; // Add 24 hours
                }
                
                const diffMinutes = Math.floor(diffMs / (1000 * 60));
                document.getElementById('total_downtime').value = diffMinutes;
            }
        }
        
        document.getElementById('downtime_start').addEventListener('change', calculateDowntime);
        document.getElementById('downtime_finish').addEventListener('change', calculateDowntime);
    </script>
</body>
</html>