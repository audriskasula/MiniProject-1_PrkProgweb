<?php
session_start();
require '../config/koneksi.php';

// Hanya pengelola yang bisa upload gambar
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'pengelola') {
    header("Location: ../auth/login.php");
    exit();
}

$pengelola_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['kampanye_id'])) {
    $kampanye_id = (int) $_POST['kampanye_id'];

    // Verifikasi kampanye milik pengelola ini
    $stmt = $conn->prepare("SELECT id, gambar FROM kampanye WHERE id = ? AND pengelola_id = ?");
    $stmt->bind_param("ii", $kampanye_id, $pengelola_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows == 0) {
        $_SESSION['flash_error'] = "Kampanye tidak ditemukan atau bukan milik Anda.";
        header("Location: dashboard_pengelola.php");
        exit();
    }

    $row = $res->fetch_assoc();

    // Cek apakah ada file yang diupload
    if (!isset($_FILES['gambar']) || $_FILES['gambar']['error'] != 0) {
        $_SESSION['flash_error'] = "Silakan pilih file gambar untuk diupload.";
        header("Location: dashboard_pengelola.php");
        exit();
    }

    // Validasi tipe file
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
    $file_type = $_FILES['gambar']['type'];
    if (!in_array($file_type, $allowed_types)) {
        $_SESSION['flash_error'] = "Tipe file tidak diizinkan. Gunakan JPG, PNG, GIF, WEBP, atau SVG.";
        header("Location: dashboard_pengelola.php");
        exit();
    }

    // Validasi ukuran (max 5MB)
    if ($_FILES['gambar']['size'] > 5 * 1024 * 1024) {
        $_SESSION['flash_error'] = "Ukuran file terlalu besar. Maksimal 5MB.";
        header("Location: dashboard_pengelola.php");
        exit();
    }

    // Upload file baru
    $target_dir = "../uploads/";
    if (!is_dir($target_dir))
        mkdir($target_dir, 0777, true);

    $ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
    $file_name = 'kampanye_' . $kampanye_id . '_' . time() . '.' . $ext;
    $target_file = $target_dir . $file_name;

    if (move_uploaded_file($_FILES['gambar']['tmp_name'], $target_file)) {
        // Hapus gambar lama jika bukan placeholder
        $old_gambar = $row['gambar'];
        $old_file_path = "../" . $old_gambar;
        if ($old_gambar && $old_gambar != 'uploads/placeholder.svg' && file_exists($old_file_path)) {
            unlink($old_file_path);
        }

        // Update DB
        $db_gambar = "uploads/" . $file_name;
        $stmt_update = $conn->prepare("UPDATE kampanye SET gambar = ? WHERE id = ?");
        $stmt_update->bind_param("si", $db_gambar, $kampanye_id);

        if ($stmt_update->execute()) {
            $_SESSION['flash_success'] = "Gambar kampanye berhasil diperbarui.";
        } else {
            $_SESSION['flash_error'] = "Gagal memperbarui gambar di database.";
        }
    } else {
        $_SESSION['flash_error'] = "Gagal mengupload file gambar.";
    }

    header("Location: dashboard_pengelola.php");
    exit();
}

// Jika bukan POST, redirect
header("Location: dashboard_pengelola.php");
exit();
?>
