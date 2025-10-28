<?php
require_once 'config/database.php';
$db = new Database();
$conn = $db->getConnection();

if ($conn) {
    echo "✅ Koneksi database berhasil.";
} else {
    echo "❌ Koneksi database gagal.";
}
?>
