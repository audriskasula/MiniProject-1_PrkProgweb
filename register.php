<?php
session_start();
require 'koneksi.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $no_telepon = trim($_POST['no_telepon']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Basic Validation
    if ($password !== $confirm_password) {
        $error = "Password dan Konfirmasi Password tidak cocok!";
    } else {
        // Cek apakah username atau email sudah ada
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt_check->bind_param("ss", $username, $email);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $error = "Username atau Email sudah terdaftar!";
        } else {
            // Hash password untuk keamanan (mencegah SQL injection dan aman jika db bocor)
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'donatur'; // Default role untuk register publik

            $stmt_insert = $conn->prepare("INSERT INTO users (username, password, role, nama_lengkap, email, no_telepon) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_insert->bind_param("ssssss", $username, $hashed_password, $role, $nama_lengkap, $email, $no_telepon);
            
            if ($stmt_insert->execute()) {
                $success = "Registrasi berhasil! Silakan login.";
            } else {
                $error = "Terjadi kesalahan saat menyimpan data.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - PeduliSemua</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <header>
        <a href="index.php" class="logo"><span class="logo-icon">❇</span> PeduliSemua</a>
    </header>

    <div class="container center-card-layout">
        <div class="glass-card form-container" style="max-width: 500px;">
            <h2 style="margin-bottom: 0.5rem; text-align: center;">Buat Akun Baru</h2>
            <p style="color: var(--text-muted); margin-bottom: 2rem; text-align: center;">Bergabunglah bersama kami untuk mulai berdonasi.</p>

            <?php if ($error): ?>
                <div style="background-color: #fee2e2; color: #dc2626; padding: 10px; border-radius: 8px; margin-bottom: 1rem;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div style="background-color: #dcfce7; color: #16a34a; padding: 10px; border-radius: 8px; margin-bottom: 1rem; text-align: center;">
                    <?php echo $success; ?><br><br>
                    <a href="login.php" class="btn-primary" style="display: inline-block;">Pergi ke Halaman Login</a>
                </div>
            <?php else: ?>

            <form action="register.php" method="POST" id="registerForm">
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" class="form-control" placeholder="Masukkan nama lengkap Anda" required>
                </div>

                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control" placeholder="Pilih username" required>
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" placeholder="Masukkan email aktif" required>
                </div>
                
                <div class="form-group">
                    <label>Nomor Telepon</label>
                    <input type="text" name="no_telepon" class="form-control" placeholder="08xxxxxxxxxx" required>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Buat password" required>
                </div>

                <div class="form-group">
                    <label>Konfirmasi Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Ulangi password" required>
                </div>
                
                <p id="passwordError" style="color: #dc2626; font-size: 0.85rem; margin-top: -10px; margin-bottom: 10px; display: none;">Password tidak cocok!</p>

                <button type="submit" class="btn-primary" style="margin-top: 1rem; width: 100%;">Daftar</button>
            </form>
            
            <?php endif; ?>
            
            <p style="margin-top: 1.5rem; color: var(--text-muted); font-size: 0.9rem; text-align: center;">
                Sudah punya akun? <a href="login.php" style="color: var(--primary); text-decoration: none; font-weight: bold;">Login di sini</a>
            </p>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const form = document.getElementById("registerForm");
            const password = document.getElementById("password");
            const confirmPassword = document.getElementById("confirm_password");
            const passwordError = document.getElementById("passwordError");

            if (form) {
                // Real-time validation
                confirmPassword.addEventListener('input', function() {
                    if (password.value !== confirmPassword.value) {
                        passwordError.style.display = 'block';
                        confirmPassword.style.borderColor = '#dc2626';
                    } else {
                        passwordError.style.display = 'none';
                        confirmPassword.style.borderColor = '#c4f06b';
                    }
                });

                // On Submit validation
                form.addEventListener("submit", function(e) {
                    if (password.value !== confirmPassword.value) {
                        e.preventDefault(); // Mencegah form di-submit
                        passwordError.style.display = 'block';
                        confirmPassword.style.borderColor = '#dc2626';
                        confirmPassword.focus();
                    }
                });
            }
        });
    </script>
</body>
</html>
