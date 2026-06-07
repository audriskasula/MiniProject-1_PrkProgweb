<?php
session_start();
require 'config/koneksi.php';

// Cek login donatur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'donatur') {
    $_SESSION['error_msg'] = "Anda harus login sebagai donatur untuk melakukan donasi.";
    header("Location: auth/login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$kampanye_id = (int) $_GET['id'];
$user_id = $_SESSION['user_id'];

// Ambil data kampanye
$stmt = $conn->prepare("SELECT judul_kampanye, target_dana, dana_terkumpul, rekening_donasi FROM kampanye WHERE id = ?");
$stmt->bind_param("i", $kampanye_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "Kampanye tidak ditemukan.";
    exit();
}
$k = $result->fetch_assoc();

// Ambil data user
$stmt_user = $conn->prepare("SELECT nama_lengkap, email FROM users WHERE id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user = $stmt_user->get_result()->fetch_assoc();

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nominal = (float) $_POST['nominal'];
    $metode_pembayaran = $_POST['metode_pembayaran'];
    $pesan_dukungan = $_POST['pesan_dukungan'];

    // Validasi
    if ($nominal < 10000) {
        $error = "Minimal donasi adalah Rp 10.000";
    } elseif (!isset($_FILES['bukti_transfer']) || $_FILES['bukti_transfer']['error'] != 0 || $_FILES['bukti_transfer']['size'] == 0) {
        $error = "Bukti transfer wajib diupload (format JPG).";
    } else {
        // Upload file
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_name = time() . '_' . basename($_FILES["bukti_transfer"]["name"]);
        $target_file = $target_dir . $file_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $allowed_types = array("jpg", "jpeg");

        if (in_array($imageFileType, $allowed_types)) {
            if (move_uploaded_file($_FILES["bukti_transfer"]["tmp_name"], $target_file)) {
                // Insert ke database
                $stmt_insert = $conn->prepare("INSERT INTO donasi (kampanye_id, donatur_id, nominal, metode_pembayaran, pesan_dukungan, bukti_transfer) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt_insert->bind_param("iidsss", $kampanye_id, $user_id, $nominal, $metode_pembayaran, $pesan_dukungan, $target_file);

                if ($stmt_insert->execute()) {
                    $success = "Donasi berhasil disubmit dan sedang menunggu verifikasi.";
                } else {
                    $error = "Terjadi kesalahan saat menyimpan data.";
                }
            } else {
                $error = "Maaf, terjadi kesalahan saat mengupload file.";
            }
        } else {
            $error = "Hanya file JPG yang diperbolehkan.";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donasi - PeduliSemua</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <header>
        <a href="index.php" class="logo"><span class="logo-icon">❇</span> PeduliSemua</a>
        <nav>
            <ul>
                <li><a href="index.php">Kembali</a></li>
                <li><a href="riwayat_donasi.php">Riwayat Donasi</a></li>
            </ul>
        </nav>
    </header>

    <div class="container center-card-layout">
        <div class="glass-card form-container">
            <h2 style="margin-bottom: 1.5rem; text-align: center;">Mulai Donasi</h2>

            <?php if ($error): ?>
                <div
                    style="background-color: #fee2e2; color: #dc2626; padding: 10px; border-radius: 8px; margin-bottom: 1rem;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div
                    style="background-color: #dcfce7; color: #16a34a; padding: 10px; border-radius: 8px; margin-bottom: 1rem; text-align: center;">
                    <?php echo $success; ?><br><br>
                    <a href="riwayat_donasi.php" class="btn-primary">Lihat Riwayat Donasi</a>
                </div>
            <?php else: ?>

                <div style="background: rgba(255,255,255,0.05); padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem;">
                    <h3 style="margin-bottom: 0.5rem; font-size: 1.1rem;">
                        <?php echo htmlspecialchars($k['judul_kampanye']); ?></h3>
                    <p style="color: var(--text-muted); font-size: 0.9rem;">
                        Terkumpul: Rp <?php echo number_format($k['dana_terkumpul'], 0, ',', '.'); ?> dari Target: Rp
                        <?php echo number_format($k['target_dana'], 0, ',', '.'); ?>
                    </p>
                    <p style="color: var(--primary); font-weight: bold; margin-top: 10px;">
                        Transfer ke: <?php echo htmlspecialchars($k['rekening_donasi']); ?>
                    </p>
                </div>

                <form action="donasi.php?id=<?php echo $kampanye_id; ?>" method="POST" enctype="multipart/form-data">

                    <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input type="text" class="form-control"
                            value="<?php echo htmlspecialchars($user['nama_lengkap']); ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>"
                            readonly>
                    </div>

                    <div class="form-group">
                        <label>Nominal Donasi (Min. Rp 10.000)</label>
                        <input type="number" name="nominal" class="form-control" placeholder="10000" min="10000" required>
                    </div>

                    <div class="form-group">
                        <label>Metode Pembayaran Asal</label>
                        <select name="metode_pembayaran" class="form-control" required>
                            <option value="">Pilih Metode</option>
                            <option value="BCA">Transfer BCA</option>
                            <option value="Mandiri">Transfer Mandiri</option>
                            <option value="BNI">Transfer BNI</option>
                            <option value="BRI">Transfer BRI</option>
                            <option value="Gopay">GoPay</option>
                            <option value="OVO">OVO</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Pesan Dukungan (Opsional)</label>
                        <textarea name="pesan_dukungan" class="form-control" rows="3"
                            placeholder="Tuliskan doa atau dukungan anda..."></textarea>
                    </div>

                    <div class="form-group">
                        <label>Bukti Transfer (JPG)</label>
                        <input type="file" name="bukti_transfer" class="form-control" accept=".jpg,.jpeg"
                            required>
                    </div>

                    <button type="submit" class="btn-primary" style="margin-top: 1rem;">Kirim Donasi</button>
                </form>

            <?php endif; ?>
        </div>
    </div>

    <script src="script.js"></script>
</body>

</html>