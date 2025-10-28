<?php
require_once '../includes/functions.php';
requireAuth(['Admin', 'Technician']);

$staff_options = getStaffOptions('shift_staff');
$transmission_units = getTransmissionUnits();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token keamanan tidak valid. Silakan coba lagi.';
    } else {
        try {
            $db = getDB();
            
            $stmt = $db->prepare("
                INSERT INTO transmission_problems 
                (report_date, shift, shift_staff, transmission_unit, problem_time, problem_description, solution_description, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $_POST['report_date'],
                $_POST['shift'],
                json_encode($_POST['shift_staff'] ?? []),
                $_POST['transmission_unit'],
                $_POST['problem_time'],
                $_POST['problem_description'],
                $_POST['solution_description'],
                $_SESSION['user_id']
            ]);
            
            if ($result) {
                $report_id = $db->lastInsertId();
                logAudit('transmission_problems', $report_id, 'INSERT', null, $_POST);
                
                // Send email notification to admin
                $admin_emails = $db->query("SELECT email FROM users WHERE user_level = 'Admin' AND is_active = 1")->fetchAll(PDO::FETCH_COLUMN);
                foreach ($admin_emails as $email) {
                    sendEmailNotification(
                        $email, 
                        'Laporan Masalah Transmisi Baru', 
                        "Laporan masalah transmisi baru telah diserahkan oleh " . $_SESSION['full_name'] . " on " . $_POST['report_date']
                    );
                }
                
                $success = 'Laporan masalah transmisi berhasil dikirim! Admin telah diberi tahu.';
            }
        } catch (Exception $e) {
            $error = 'Gagal mengirim laporan. Silakan coba lagi.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Masalah Transmisi - Sistem Pelaporan TVRI</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <div class="header-left">
                <img src="https://upload.wikimedia.org/wikipedia/commons/e/e0/TVRI_Riau.svg" alt="TVRI Logo" class="header-logo">
                <h1>Laporan Masalah Transmisi</h1>
            </div>
            <div class="header-right">
                <span class="user-info"><?php echo htmlspecialchars($_SESSION['full_name']); ?> (Teknisi)</span>
                <a href="../dashboard/<?php echo strtolower($_SESSION['user_level']); ?>.php" class="back-btn">Kembali Ke Beranda</a>
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

            <div class="form-row">
                <div class="form-group">
                    <label for="report_date">Tanggal:</label>
                    <input type="date" id="report_date" name="report_date" required>
                </div>
                <div class="form-group">
                    <label for="problem_time">Waktu:</label>
                    <input type="time" id="problem_time" name="problem_time" required>
                </div>
            </div>

            <div class="form-group">
                <label>Shift:</label>
                <div class="radio-group">
                    <label><input type="radio" name="shift" value="Shift 1 (00.00-08.00)" required> Shift 1 (00.00-08.00)</label>
                    <label><input type="radio" name="shift" value="Shift 2 (08.00-16.00)" required> Shift 2 (08.00-16.00)</label>
                    <label><input type="radio" name="shift" value="Shift 3 (16.00-00.00)" required> Shift 3 (16.00-00.00)</label>
                </div>
            </div>

            <div class="form-group">
                <label>Pegawai Shift:</label>
                <div class="checkbox-group">
                    <?php foreach ($staff_options as $staff): ?>
                    <label><input type="checkbox" name="shift_staff[]" value="<?php echo $staff['id']; ?>"> <?php echo htmlspecialchars($staff['name']); ?></label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="transmission_unit">Unit Transmisi:</label>
                <select id="transmission_unit" name="transmission_unit" required>
                    <option value="">Pilih Unit Transmisi</option>
                    <?php foreach ($transmission_units as $unit): ?>
                    <option value="<?php echo $unit['id']; ?>"><?php echo htmlspecialchars($unit['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="problem_description">Deskripsikan Masalah:</label>
                <textarea id="problem_description" name="problem_description" rows="4" required placeholder="Deskripsikan masalah transmisi secara rinci..."></textarea>
            </div>

            <div class="form-group">
                <label for="solution_description">Deskripsikan Solusi:</label>
                <textarea id="solution_description" name="solution_description" rows="4" required placeholder="Deskripsikan solusi atau tindakan yang diambil..."></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="submit-btn">Kirim Laporan</button>
                <a href="../dashboard/<?php echo strtolower($_SESSION['user_level']); ?>.php" class="cancel-btn">Batal</a>
            </div>
        </form>
    </main>
</div>

<script>
    document.getElementById('report_date').valueAsDate = new Date();
    const now = new Date();
    const timeString = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
    document.getElementById('problem_time').value = timeString;
</script>

</body>
</html>