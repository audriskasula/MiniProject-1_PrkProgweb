<?php
session_start();
require 'koneksi.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'pengelola') {
        header("Location: dashboard_pengelola.php");
    } else {
        header("Location: index.php");
    }
    exit();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, username, password, role, nama_lengkap FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        // Update to handle password_verify for new registered users and plaintext for old users
        if (password_verify($password, $user['password']) || $password === $user['password']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];

            if ($user['role'] == 'pengelola') {
                header("Location: dashboard_pengelola.php");
            } else {
                header("Location: index.php");
            }
            exit();
        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Username tidak ditemukan!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PeduliSemua</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <header>
        <a href="index.php" class="logo"><span class="logo-icon">❇</span> PeduliSemua</a>
    </header>

    <div class="container center-card-layout">
        <div class="glass-card form-container" style="max-width: 400px; text-align: center;">
            <h2 style="margin-bottom: 0.5rem;">Selamat Datang Kembali</h2>
            <p style="color: var(--text-muted); margin-bottom: 2rem;">Silakan masuk untuk mengelola kampanye atau donasi Anda.</p>

            <?php if ($error): ?>
                <div style="background-color: #fee2e2; color: #dc2626; padding: 10px; border-radius: 8px; margin-bottom: 1rem;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST" style="text-align: left;">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control" placeholder="Masukkan username" required>
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Masukkan Password" required>
                </div>

                <button type="submit" class="btn-primary" style="margin-top: 1rem; width: 100%;">Login</button>
            </form>
            
            <p style="margin-top: 1rem; color: var(--text-muted); font-size: 0.9rem;">
                Belum punya akun? <a href="register.php" style="color: var(--primary); text-decoration: none; font-weight: bold;">Daftar di sini</a>
            </p>
            <p style="margin-top: 1rem; color: var(--text-muted); font-size: 0.9rem;">
                <a href="index.php" style="color: var(--primary); text-decoration: none;">&larr; Kembali ke Halaman Utama</a>
            </p>
        </div>
    </div>

</body>
</html>
