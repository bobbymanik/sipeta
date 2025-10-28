<?php
require_once '../includes/functions.php';
requireAuth(['Admin', 'Technician']);

$staff_options = getStaffOptions('shift_staff');
$national_programs = getProgramOptions('national');
$local_programs = getProgramOptions('local');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        try {
            $db = getDB();
            
            $stmt = $db->prepare("
                INSERT INTO broadcasting_logbook 
                (day_of_week, report_date, shift, shift_staff, national_programs, local_programs, 
                 audio_quality, video_quality, problem_duration, notes, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $_POST['day_of_week'],
                $_POST['report_date'],
                $_POST['shift'],
                json_encode($_POST['shift_staff'] ?? []),
                json_encode($_POST['national_programs'] ?? []),
                json_encode($_POST['local_programs'] ?? []),
                $_POST['audio_quality'],
                $_POST['video_quality'],
                $_POST['problem_duration'] ?? '',
                $_POST['notes'] ?? '',
                $_SESSION['user_id']
            ]);
            
            if ($result) {
                $report_id = $db->lastInsertId();
                logAudit('broadcasting_logbook', $report_id, 'INSERT', null, $_POST);
                $success = 'Broadcasting logbook report submitted successfully!';
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
    <title>Laporan Harian - Sistem Pelaporan TVRI</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <div class="header-left">
                <img src="https://upload.wikimedia.org/wikipedia/commons/e/e0/TVRI_Riau.svg" alt="TVRI Logo" class="header-logo">
                <h1>Laporan Harian</h1>
            </div>
            <div class="header-right">
                <span class="user-info"><?php echo htmlspecialchars($_SESSION['full_name']); ?> (Teknisi)</span>
                <a href="../dashboard/<?php echo strtolower($_SESSION['user_level']); ?>.php" class="back-btn">Kembali ke Beranda</a>
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
                        <label for="day_of_week">Hari:</label>
                        <select id="day_of_week" name="day_of_week" required>
                            <option value="">Pilih Hari</option>
                            <option value="Monday">Senin</option>
                            <option value="Tuesday">Selasa</option>
                            <option value="Wednesday">Rabu</option>
                            <option value="Thursday">Kamis</option>
                            <option value="Friday">Jumat</option>
                            <option value="Saturday">Sabtu</option>
                            <option value="Sunday">Minggu</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="report_date">Tanggal:</label>
                        <input type="date" id="report_date" name="report_date" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Shift:</label>
                    <div class="radio-group">
                        <label class="radio-label">
                            <input type="radio" name="shift" value="Shift 1 (00.00-08.00)" required>
                            Shift 1 (00.00-08.00)
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="shift" value="Shift 2 (08.00-16.00)" required>
                            Shift 2 (08.00-16.00)
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="shift" value="Shift 3 (16.00-00.00)" required>
                            Shift 3 (16.00-00.00)
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label>Pegawai Shift:</label>
                    <div class="checkbox-group">
                        <?php foreach ($staff_options as $staff): ?>
                        <label class="checkbox-label">
                            <input type="checkbox" name="shift_staff[]" value="<?php echo $staff['id']; ?>">
                            <?php echo htmlspecialchars($staff['name']); ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label>Program Nasionals:</label>
                    <div class="checkbox-group">
                        <?php foreach ($national_programs as $program): ?>
                        <label class="checkbox-label">
                            <input type="checkbox" name="national_programs[]" value="<?php echo $program['id']; ?>">
                            <?php echo htmlspecialchars($program['name']); ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label>Program Lokals:</label>
                    <div class="checkbox-group">
                        <?php foreach ($local_programs as $program): ?>
                        <label class="checkbox-label">
                            <input type="checkbox" name="local_programs[]" value="<?php echo $program['id']; ?>">
                            <?php echo htmlspecialchars($program['name']); ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Kualitas Audio:</label>
                        <div class="radio-group">
                            <label class="radio-label">
                                <input type="radio" name="audio_quality" value="Normal" required>
                                Normal
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="audio_quality" value="Problem" required>
                                Bermasalah
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Kualitas Video:</label>
                        <div class="radio-group">
                            <label class="radio-label">
                                <input type="radio" name="video_quality" value="Normal" required>
                                Normal
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="video_quality" value="Problem" required>
                                Bermasalah
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="problem_duration">Durasi Masalah:</label>
                    <input type="text" id="problem_duration" name="problem_duration" placeholder="contoh: 30 menit">
                </div>

                <div class="form-group">
                    <label for="notes">Catatan:</label>
                    <textarea id="notes" name="notes" rows="4" placeholder="Catatan atau pengamatan tambahan..."></textarea>
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
        
        // Set current day as default
        const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        const today = days[new Date().getDay()];
        document.getElementById('day_of_week').value = today;
    </script>
</body>
</html>