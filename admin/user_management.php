<?php
require_once '../includes/functions.php';
requireAuth('Admin');

$db = getDB();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token keamanan tidak valid. Silakan coba lagi.';
    } else {
        if (isset($_POST['add_user'])) {
            // Tambah pengguna
            try {
                $stmt = $db->prepare("INSERT INTO users (email, password, user_level, full_name) VALUES (?, ?, ?, ?)");
                $result = $stmt->execute([
                    $_POST['email'],
                    password_hash($_POST['password'], PASSWORD_DEFAULT),
                    $_POST['user_level'],
                    $_POST['full_name']
                ]);
                if ($result) $success = 'Pengguna berhasil ditambahkan!';
            } catch (Exception $e) {
                $error = 'Gagal menambahkan pengguna. Email mungkin sudah terdaftar.';
            }
        } elseif (isset($_POST['update_user'])) {
            // Ubah pengguna
            try {
                $sql = "UPDATE users SET full_name = ?, user_level = ?, is_active = ?";
                $params = [$_POST['full_name'], $_POST['user_level'], $_POST['is_active']];
                if (!empty($_POST['password'])) {
                    $sql .= ", password = ?";
                    $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
                }
                $sql .= " WHERE id = ?";
                $params[] = $_POST['user_id'];
                $stmt = $db->prepare($sql);
                if ($stmt->execute($params)) $success = 'Data pengguna berhasil diperbarui!';
            } catch (Exception $e) {
                $error = 'Gagal memperbarui data pengguna.';
            }
        } elseif (isset($_POST['delete_user'])) {
            // Hapus pengguna
            $user_id = $_POST['user_id'];
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $old_values = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            if ($stmt->execute([$user_id])) {
                logAudit('users', $user_id, 'DELETE', $old_values, null);
                $success = 'Pengguna berhasil dihapus!';
            } else {
                $error = 'Gagal menghapus pengguna.';
            }
        }
    }
}

// Ambil semua pengguna
$users = $db->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pengguna - Sistem Pelaporan TVRI</title>
    <link rel="stylesheet" href="../style.css">

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        .delete-btn {
            background-color: #f44336;
            color: #fff;
            border: none;
            padding: 6px 10px;
            border-radius: 4px;
            cursor: pointer;
        }
        .delete-btn:hover { background-color: #d32f2f; }

        .edit-btn {
            background-color: #2196F3;
            color: white;
            border: none;
            padding: 6px 10px;
            border-radius: 4px;
            cursor: pointer;
        }
        .edit-btn:hover { background-color: #1976D2; }

        .alert {
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .alert-success { background: #e7f5e8; color: #2e7d32; }
        .alert-error { background: #fdecea; color: #c62828; }
    </style>
</head>

<body>
<div class="dashboard-container">
    <header class="dashboard-header">
        <div class="header-left">
            <img src="https://upload.wikimedia.org/wikipedia/commons/e/e0/TVRI_Riau.svg" alt="TVRI Logo" class="header-logo">
            <h1>Manajemen Pengguna</h1>
        </div>
        <div class="header-right">
            <span class="user-info"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
            <a href="../dashboard/admin.php" class="back-btn">Kembali ke Beranda</a>
        </div>
    </header>

    <main class="admin-main">
        <?php if (isset($success)): ?>
            <script>
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: '<?php echo addslashes($success); ?>',
                    timer: 2000,
                    showConfirmButton: false
                });
            </script>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <script>
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: '<?php echo addslashes($error); ?>',
                });
            </script>
        <?php endif; ?>

        <!-- Form Tambah Pengguna -->
        <div class="section">
            <h2>Tambahkan Pengguna</h2>
            <form method="POST" class="user-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="full_name">Nama Lengkap:</label>
                        <input type="text" id="full_name" name="full_name" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Kata Sandi:</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="user_level">Peran:</label>
                        <select id="user_level" name="user_level" required>
                            <option value="Technician">Teknisi</option>
                            <option value="Admin">Admin</option>
                            <option value="Leader">Pimpinan</option>
                        </select>
                    </div>
                </div>

                <button type="submit" name="add_user" class="submit-btn">Tambahkan Pengguna</button>
            </form>
        </div>

        <!-- Daftar Pengguna -->
        <div class="section">
            <h2>Daftar Pengguna</h2>
            <div class="table-container">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>Nama Lengkap</th>
                            <th>Email</th>
                            <th>Peran</th>
                            <th>Status</th>
                            <th>Dibuat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><span class="level-badge level-<?php echo strtolower($user['user_level']); ?>"><?php echo $user['user_level']; ?></span></td>
                            <td><span class="status-badge status-<?php echo $user['is_active'] ? 'active' : 'inactive'; ?>"><?php echo $user['is_active'] ? 'Aktif' : 'Nonaktif'; ?></span></td>
                            <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <button onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)" class="edit-btn">Ubah</button>
                                <form method="POST" class="delete-form" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="button" class="delete-btn" onclick="confirmDelete(this)">Hapus</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- Modal Edit User -->
<div id="editUserModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Ubah Pengguna</h2>
        <form method="POST" id="editUserForm">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="user_id" id="edit_user_id">

            <div class="form-group">
                <label for="edit_full_name">Nama Lengkap:</label>
                <input type="text" id="edit_full_name" name="full_name" required>
            </div>

            <div class="form-group">
                <label for="edit_user_level">Peran:</label>
                <select id="edit_user_level" name="user_level" required>
                    <option value="Technician">Teknisi</option>
                    <option value="Admin">Admin</option>
                    <option value="Leader">Pimpinan</option>
                </select>
            </div>

            <div class="form-group">
                <label for="edit_is_active">Status:</label>
                <select id="edit_is_active" name="is_active" required>
                    <option value="1">Aktif</option>
                    <option value="0">Nonaktif</option>
                </select>
            </div>

            <div class="form-group">
                <label for="edit_password">Kata Sandi Baru (Opsional):</label>
                <input type="password" id="edit_password" name="password">
            </div>

            <div class="form-actions">
                <button type="submit" name="update_user" class="submit-btn">Perbarui</button>
                <button type="button" onclick="closeEditModal()" class="cancel-btn">Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
function editUser(user) {
    document.getElementById('edit_user_id').value = user.id;
    document.getElementById('edit_full_name').value = user.full_name;
    document.getElementById('edit_user_level').value = user.user_level;
    document.getElementById('edit_is_active').value = user.is_active;
    document.getElementById('edit_password').value = '';
    document.getElementById('editUserModal').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('editUserModal').style.display = 'none';
}

// 🔥 SWEETALERT2 KONFIRMASI HAPUS
function confirmDelete(button) {
    const form = button.closest('form');
    Swal.fire({
        title: 'Yakin ingin menghapus?',
        text: 'Tindakan ini tidak dapat dibatalkan!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'delete_user';
            hidden.value = '1';
            form.appendChild(hidden);
            form.submit();
        }
    });
}

window.onclick = function(event) {
    const editModal = document.getElementById('editUserModal');
    if (event.target === editModal) closeEditModal();
};
document.querySelector('.close').onclick = closeEditModal;
</script>
</body>
</html>
