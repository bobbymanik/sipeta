<?php
require_once '../includes/functions.php';
requireAuth('Admin');

$db = getDB();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token keamanan tidak valid. Silakan coba lagi.';
    } else {
        if (isset($_POST['add_staff'])) {
            // Add staff option
            try {
                $stmt = $db->prepare("INSERT INTO staff_options (name, type) VALUES (?, ?)");
                $result = $stmt->execute([$_POST['staff_name'], $_POST['staff_type']]);
                if ($result) $success = 'Opsi staf berhasil ditambahkan!';
            } catch (Exception $e) {
                $error = 'Gagal menambahkan opsi staf.';
            }
        } elseif (isset($_POST['add_program'])) {
            // Add program option
            try {
                $stmt = $db->prepare("INSERT INTO program_options (name, type) VALUES (?, ?)");
                $result = $stmt->execute([$_POST['program_name'], $_POST['program_type']]);
                if ($result) $success = 'Opsi program berhasil ditambahkan!';
            } catch (Exception $e) {
                $error = 'Gagal menambahkan opsi program.';
            }
        } elseif (isset($_POST['add_unit'])) {
            // Add transmission unit
            try {
                $stmt = $db->prepare("INSERT INTO transmission_units (name) VALUES (?)");
                $result = $stmt->execute([$_POST['unit_name']]);
                if ($result) $success = 'Unit transmisi berhasil ditambahkan!';
            } catch (Exception $e) {
                $error = 'Gagal menambahkan unit transmisi.';
            }
        } elseif (isset($_POST['toggle_active'])) {
            // Toggle active status
            $table = $_POST['table'];
            $id = $_POST['id'];
            $new_status = $_POST['new_status'];
            
            $allowed_tables = ['staff_options', 'program_options', 'transmission_units'];
            if (in_array($table, $allowed_tables)) {
                $stmt = $db->prepare("UPDATE {$table} SET is_active = ? WHERE id = ?");
                $result = $stmt->execute([$new_status, $id]);
                if ($result) $success = 'Status berhasil diperbarui!';
            }
        }
    }
}

// Get all options
$staff_options = $db->query("SELECT * FROM staff_options ORDER BY type, name")->fetchAll(PDO::FETCH_ASSOC);
$program_options = $db->query("SELECT * FROM program_options ORDER BY type, name")->fetchAll(PDO::FETCH_ASSOC);
$transmission_units = $db->query("SELECT * FROM transmission_units ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Opsi - Sistem Pelaporan TVRI</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <div class="header-left">
                <img src="https://upload.wikimedia.org/wikipedia/commons/e/e0/TVRI_Riau.svg" alt="TVRI Logo" class="header-logo">
                <h1>Kelola Opsi</h1>
            </div>
            <div class="header-right">
                <span class="user-info"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <a href="../dashboard/admin.php" class="back-btn">Kembali ke Beranda</a>
            </div>
        </header>

        <main class="admin-main">
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Staff Options -->
            <div class="section">
                <h2>Pegawai</h2>
                <form method="POST" class="add-form">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <div class="form-row">
                        <input type="text" name="staff_name" placeholder="Nama Pegawai" required>
                        <select name="staff_type" required>
                            <option value="shift_staff">Pegawai Shift</option>
                            <option value="report_staff">Pegawai Pelapor</option>
                        </select>
                        <button type="submit" name="add_staff" class="add-btn">Tambah Pegawai</button>
                    </div>
                </form>
                
                <div class="options-grid">
                    <?php foreach ($staff_options as $staff): ?>
                    <div class="option-item">
                        <span class="option-name"><?php echo htmlspecialchars($staff['name']); ?></span>
                        <span class="option-type"><?php echo ucfirst(str_replace('_', ' ', $staff['type'])); ?></span>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="table" value="staff_options">
                            <input type="hidden" name="id" value="<?php echo $staff['id']; ?>">
                            <input type="hidden" name="new_status" value="<?php echo $staff['is_active'] ? 0 : 1; ?>">
                            <button type="submit" name="toggle_active" class="toggle-btn <?php echo $staff['is_active'] ? 'active' : 'inactive'; ?>">
                                <?php echo $staff['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                            </button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Program Options -->
            <div class="section">
                <h2>Program</h2>
                <form method="POST" class="add-form">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <div class="form-row">
                        <input type="text" name="program_name" placeholder="Nama Program" required>
                        <select name="program_type" required>
                            <option value="national">Nasional</option>
                            <option value="local">Lokal</option>
                        </select>
                        <button type="submit" name="add_program" class="add-btn">Tambah Program</button>
                    </div>
                </form>
                
                <div class="options-grid">
                    <?php foreach ($program_options as $program): ?>
                    <div class="option-item">
                        <span class="option-name"><?php echo htmlspecialchars($program['name']); ?></span>
                        <span class="option-type"><?php echo ucfirst($program['type']); ?></span>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="table" value="program_options">
                            <input type="hidden" name="id" value="<?php echo $program['id']; ?>">
                            <input type="hidden" name="new_status" value="<?php echo $program['is_active'] ? 0 : 1; ?>">
                            <button type="submit" name="toggle_active" class="toggle-btn <?php echo $program['is_active'] ? 'active' : 'inactive'; ?>">
                                <?php echo $program['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                            </button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Transmission Units -->
            <div class="section">
                <h2>Unit Transmisi</h2>
                <form method="POST" class="add-form">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <div class="form-row">
                        <input type="text" name="unit_name" placeholder="Nama Unit Transmisi" required>
                        <button type="submit" name="add_unit" class="add-btn">Tambah Unit</button>
                    </div>
                </form>
                
                <div class="options-grid">
                    <?php foreach ($transmission_units as $unit): ?>
                    <div class="option-item">
                        <span class="option-name"><?php echo htmlspecialchars($unit['name']); ?></span>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="table" value="transmission_units">
                            <input type="hidden" name="id" value="<?php echo $unit['id']; ?>">
                            <input type="hidden" name="new_status" value="<?php echo $unit['is_active'] ? 0 : 1; ?>">
                            <button type="submit" name="toggle_active" class="toggle-btn <?php echo $unit['is_active'] ? 'active' : 'inactive'; ?>">
                                <?php echo $unit['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                            </button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>