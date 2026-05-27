<?php
session_start();
require 'config/koneksi.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = (int) $_GET['id'];
$stmt = $conn->prepare("SELECT kampanye.*, users.nama_lengkap as nama_penyelenggara, users.email as email_penyelenggara 
                        FROM kampanye 
                        JOIN users ON kampanye.pengelola_id = users.id 
                        WHERE kampanye.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "Kampanye tidak ditemukan.";
    exit();
}

$k = $result->fetch_assoc();

$progress = ($k['target_dana'] > 0) ? ($k['dana_terkumpul'] / $k['target_dana']) * 100 : 0;
if ($progress > 100)
    $progress = 100;

$date1 = new DateTime();
$date2 = new DateTime($k['batas_waktu']);
$interval = $date1->diff($date2);
$days_left = $interval->format('%a');
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Kampanye - PeduliSemua</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <header>
        <a href="index.php" class="logo"><span class="logo-icon">❇</span> PeduliSemua</a>
        <nav>
            <ul>
                <li><a href="index.php">Kembali ke Beranda</a></li>
            </ul>
        </nav>
    </header>

    <div class="container">
        <div class="detail-layout">
            <div class="detail-content">
                <img src="<?php echo htmlspecialchars($k['gambar']); ?>"
                    alt="<?php echo htmlspecialchars($k['judul_kampanye']); ?>" class="detail-img"
                    onerror="this.onerror=null;this.src='uploads/placeholder.svg';">

                <div class="detail-header-badges">
                    <span class="badge"><?php echo htmlspecialchars($k['kategori']); ?></span>
                    <span class="badge">📍 <?php echo htmlspecialchars($k['lokasi']); ?></span>
                </div>

                <h1><?php echo htmlspecialchars($k['judul_kampanye']); ?></h1>
                <p class="detail-organizer-box">
                    <span class="text-lg">👤</span> Diselenggarakan oleh:
                    <?php echo htmlspecialchars($k['nama_penyelenggara']); ?>
                    (<a
                        href="mailto:<?php echo htmlspecialchars($k['email_penyelenggara']); ?>"><?php echo htmlspecialchars($k['email_penyelenggara']); ?></a>)
                </p>

                <div class="detail-desc">
                    <?php echo nl2br(htmlspecialchars($k['deskripsi'])); ?>
                </div>
            </div>

            <div class="detail-sidebar">
                <div class="glass-card sticky-sidebar">
                    <h3>Progres Donasi</h3>

                    <div class="progress-container" style="height: 12px; margin: 1.5rem 0;">
                        <div class="progress-fill" style="width: <?php echo $progress; ?>%;"></div>
                    </div>

                    <div class="donation-stats">
                        <div class="stat-item">
                            <span class="stat-label">Terkumpul</span>
                            <span class="stat-value">Rp
                                <?php echo number_format($k['dana_terkumpul'], 0, ',', '.'); ?></span>
                        </div>
                        <div class="stat-item" style="text-align: right;">
                            <span class="stat-label">Sisa Waktu</span>
                            <span class="stat-value"><?php echo $days_left; ?> Hari</span>
                        </div>
                    </div>

                    <div class="stat-item"
                        style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border);">
                        <span class="stat-label">Target Dana</span>
                        <span class="stat-value" style="color: var(--text);">Rp
                            <?php echo number_format($k['target_dana'], 0, ',', '.'); ?></span>
                    </div>

                    <div class="stat-item" style="margin-top: 1rem;">
                        <span class="stat-label">Transfer Ke</span>
                        <span class="stat-value"
                            style="color: var(--primary); font-size: 1rem;"><?php echo htmlspecialchars($k['rekening_donasi']); ?></span>
                    </div>

                    <a href="donasi.php?id=<?php echo $k['id']; ?>" class="btn-primary"
                        style="display: block; text-align: center; margin-top: 2rem;">Donasi Sekarang</a>
                </div>
            </div>
        </div>
    </div>

</body>

</html>