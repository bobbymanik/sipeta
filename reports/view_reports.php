<?php
require_once '../includes/functions.php';
requireAuth(['Leader', 'Admin', 'Technician']);

$db = getDB();
$user_level = $_SESSION['user_level'];
$user_id = $_SESSION['user_id'];

// Handle report deletion (Admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_report']) && $user_level === 'Admin') {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $table = $_POST['table'];
        $report_id = $_POST['report_id'];

        // Get old values for audit
        $stmt = $db->prepare("SELECT * FROM {$table} WHERE id = ?");
        $stmt->execute([$report_id]);
        $old_values = $stmt->fetch(PDO::FETCH_ASSOC);

        // Delete the report
        $stmt = $db->prepare("DELETE FROM {$table} WHERE id = ?");
        if ($stmt->execute([$report_id])) {
            logAudit($table, $report_id, 'DELETE', $old_values, null);
            // Gunakan JSON untuk mengirim status ke JavaScript
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'success', 'message' => 'Laporan berhasil dihapus.']);
                exit;
            } else {
                $success = 'Laporan berhasil dihapus.';
            }
        } else {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus laporan.']);
                exit;
            }
        }
    } else {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Token keamanan tidak valid.']);
            exit;
        }
    }
}

// Get filter parameters
$report_type = $_GET['type'] ?? 'all';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query based on user level and filters
$where_conditions = [];
$params = [];

if ($user_level === 'Technician') {
    $where_conditions[] = "created_by = ?";
    $params[] = $user_id;
}

if ($date_from) {
    $where_conditions[] = "report_date >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $where_conditions[] = "report_date <= ?";
    $params[] = $date_to;
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get reports based on type
$reports = [];
if ($report_type === 'all' || $report_type === 'broadcasting') {
    $sql = "SELECT 'broadcasting_logbook' as table_name, 'Broadcasting Logbook' as type, id, report_date, created_at, created_by FROM broadcasting_logbook {$where_clause}";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $reports = array_merge($reports, $stmt->fetchAll(PDO::FETCH_ASSOC));
}

if ($report_type === 'all' || $report_type === 'downtime') {
    $sql = "SELECT 'downtime_dvb' as table_name, 'Downtime DVB' as type, id, report_date, created_at, created_by FROM downtime_dvb {$where_clause}";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $reports = array_merge($reports, $stmt->fetchAll(PDO::FETCH_ASSOC));
}

if ($report_type === 'all' || $report_type === 'transmission') {
    $sql = "SELECT 'transmission_problems' as table_name, 'Transmission Problem' as type, id, report_date, created_at, created_by FROM transmission_problems {$where_clause}";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $reports = array_merge($reports, $stmt->fetchAll(PDO::FETCH_ASSOC));
}

// Sort by created_at descending
usort($reports, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});


// Get user names for display
$user_names = [];
if (!empty($reports)) {
    // 1. Ambil ID unik
    $user_ids = array_unique(array_column($reports, 'created_by'));

    // 2. FILTER ID YANG TIDAK VALID (0, NULL, atau non-numerik)
    $user_ids = array_filter($user_ids, function($id) {
        return is_numeric($id) && $id > 0;
    });

    // 3. PASTIKAN ARRAY TIDAK KOSONG SEBELUM MEMBANGUN QUERY
    if (!empty($user_ids)) {
        // Cara paling aman membuat placeholder: ?,?,?
        // Ini memastikan jumlah '?' sama persis dengan jumlah ID
        $placeholders = implode(',', array_fill(0, count($user_ids), '?'));

        $stmt = $db->prepare("SELECT id, full_name FROM users WHERE id IN ({$placeholders})");

        // Menggunakan array_values() untuk reset indeks array, ini praktik terbaik PDO
        // Baris 89 yang baru (dijamin aman)
        $stmt->execute(array_values($user_ids));

        $user_names = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tinjau Laporan - Sistem Pelaporan TVRI</title>
    <link rel="stylesheet" href="../style.css">
    <!-- Tambahkan SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>
<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <div class="header-left">
                <img src="https://upload.wikimedia.org/wikipedia/commons/e/e0/TVRI_Riau.svg  " alt="TVRI Logo" class="header-logo">
                <h1><?php echo $user_level === 'Technician' ? 'Laporan Saya' : 'Semua Laporan'; ?></h1>
            </div>
            <div class="header-right">
                <span class="user-info"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <a href="../dashboard/<?php echo strtolower($_SESSION['user_level']); ?>.php" class="back-btn">Kembali ke Beranda</a>
            </div>
        </header>

        <main class="reports-main">
            <!-- Alert div ini akan digunakan oleh SweetAlert2 untuk pesan sukses dari PHP lama -->
            <?php if (isset($success)): ?>
                <div class="alert alert-success" style="display: none;" id="success-message"><?php echo $success; ?></div>
            <?php endif; ?>

            <!-- Filters -->
            <div class="filters-section">
                <form method="GET" class="filters-form">
                    <div class="filter-group">
                        <label for="type">Jenis Laporan:</label>
                        <select id="type" name="type">
                            <option value="all" <?php echo $report_type === 'all' ? 'selected' : ''; ?>>Semua Laporan</option>
                            <option value="broadcasting" <?php echo $report_type === 'broadcasting' ? 'selected' : ''; ?>>Laporan Harian</option>
                            <option value="downtime" <?php echo $report_type === 'downtime' ? 'selected' : ''; ?>>Laporan Downtime DVB</option>
                            <option value="transmission" <?php echo $report_type === 'transmission' ? 'selected' : ''; ?>>Laporan Masalah Transmisi</option>
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

                    <button type="submit" class="filter-btn">Filter</button>
                    <a href="view_reports.php" class="clear-btn">Bersihkan</a>
                </form>
            </div>

            <!-- Reports Table -->
            <div class="table-container">
                <table class="reports-table">
                    <thead>
                        <tr>
                            <th>Jenis Laporan</th>
                            <th>Tanggal</th>
                            <th>Pelapor</th>
                            <th>Waktu</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reports)): ?>
                        <tr>
                            <td colspan="5" class="no-data">Tidak ada laporan ditemukan</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($reports as $report): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($report['type']); ?></td>
                            <td><?php echo htmlspecialchars($report['report_date']); ?></td>
                            <td><?php echo htmlspecialchars($user_names[$report['created_by']] ?? 'Unknown'); ?></td>
                            <td><?php echo date('M j, Y H:i', strtotime($report['created_at'])); ?></td>
                            <td class="actions">
                                <button onclick="viewReport('<?php echo $report['table_name']; ?>', <?php echo $report['id']; ?>)" class="view-btn">Lihat</button>
                                <?php if ($user_level === 'Admin'): ?>
                                <form method="POST" class="delete-form" style="display: inline;" data-table="<?php echo $report['table_name']; ?>" data-id="<?php echo $report['id']; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    <input type="hidden" name="table" value="<?php echo $report['table_name']; ?>">
                                    <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                                    <button type="button" class="delete-btn">Hapus</button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($user_level === 'Admin'): ?>
            <div class="export-section">
                <h3>Ekspor</h3>
                <a href="../admin/export_reports.php?format=pdf&type=<?php echo $report_type; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>" class="export-btn" target="_blank">Ekspor ke PDF</a>
                <a href="../admin/export_reports.php?format=excel&type=<?php echo $report_type; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>" class="export-btn">Ekspor ke Excel</a>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Modal for viewing report details -->
    <div id="reportModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div id="reportDetails"></div>
        </div>
    </div>

    <!-- Tambahkan SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script>
        // Fungsi SweetAlert untuk pesan sukses dari PHP lama (jika ada)
        <?php if (isset($success)): ?>
            document.addEventListener('DOMContentLoaded', function() {
                const successDiv = document.getElementById('success-message');
                if (successDiv) {
                    Swal.fire({
                        title: 'Berhasil!',
                        text: successDiv.textContent,
                        icon: 'success',
                        confirmButtonText: 'OK'
                    });
                    // Hilangkan div setelah ditampilkan
                    successDiv.remove();
                }
            });
        <?php endif; ?>


        // Fungsi untuk melihat laporan (tetap sama)
        function viewReport(table, id) {
            // AJAX call to get report details
            fetch(`view_report_details.php?table=${table}&id=${id}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('reportDetails').innerHTML = html;
                    document.getElementById('reportModal').style.display = 'block';
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error!',
                        text: 'Terjadi kesalahan saat memuat detail laporan.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                });
        }

        // Close modal
        document.querySelector('.close').onclick = function() {
            document.getElementById('reportModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('reportModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }

        // Event delegation untuk tombol hapus
        document.querySelectorAll('.delete-form').forEach(form => {
            const deleteBtn = form.querySelector('.delete-btn');
            deleteBtn.addEventListener('click', function(e) {
                e.preventDefault(); // Mencegah submit form biasa

                const table = form.getAttribute('data-table');
                const id = form.getAttribute('data-id');
                const csrfToken = form.querySelector('input[name="csrf_token"]').value;

                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Laporan ini akan dihapus secara permanen!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Kirim permintaan AJAX
                        fetch('', { // Kirim ke halaman ini sendiri
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                                'X-Requested-With': 'XMLHttpRequest' // Tandai sebagai AJAX
                            },
                            body: new URLSearchParams({
                                'delete_report': '1',
                                'table': table,
                                'report_id': id,
                                'csrf_token': csrfToken
                            })
                        })
                        .then(response => response.json()) // Expect JSON response
                        .then(data => {
                            if (data.status === 'success') {
                                Swal.fire({
                                    title: 'Dihapus!',
                                    text: data.message,
                                    icon: 'success',
                                    confirmButtonText: 'OK'
                                }).then(() => {
                                    // Reload halaman atau hapus baris dari DOM
                                    location.reload(); // Refresh untuk menampilkan data terbaru
                                });
                            } else {
                                Swal.fire({
                                    title: 'Gagal!',
                                    text: data.message || 'Terjadi kesalahan saat menghapus laporan.',
                                    icon: 'error',
                                    confirmButtonText: 'OK'
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire({
                                title: 'Error!',
                                text: 'Terjadi kesalahan jaringan.',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        });
                    }
                });
            });
        });

    </script>
</body>
</html>