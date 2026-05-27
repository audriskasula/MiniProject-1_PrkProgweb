<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'donatur') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get summary
$stmt_summary = $conn->prepare("
    SELECT 
        status, 
        COUNT(id) as total_donasi, 
        SUM(nominal) as total_nominal 
    FROM donasi 
    WHERE donatur_id = ? 
    GROUP BY status
");
$stmt_summary->bind_param("i", $user_id);
$stmt_summary->execute();
$summary_result = $stmt_summary->get_result();

$summary = [
    'VERIFIED' => ['count' => 0, 'nominal' => 0],
    'PENDING' => ['count' => 0, 'nominal' => 0],
    'REJECTED' => ['count' => 0, 'nominal' => 0]
];

while ($row = $summary_result->fetch_assoc()) {
    $summary[$row['status']]['count'] = $row['total_donasi'];
    $summary[$row['status']]['nominal'] = $row['total_nominal'];
}

// Get history
$stmt = $conn->prepare("
    SELECT donasi.*, kampanye.judul_kampanye 
    FROM donasi 
    JOIN kampanye ON donasi.kampanye_id = kampanye.id 
    WHERE donatur_id = ? 
    ORDER BY tanggal_donasi DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Donasi - PeduliSemua</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <header>
        <a href="index.php" class="logo"><span class="logo-icon">❇</span> PeduliSemua</a>
        <nav>
            <ul>
                <li><a href="index.php">Beranda</a></li>
                <li><a href="logout.php" class="btn-login" style="background:var(--error); border-color:var(--error); color:white;">Logout</a></li>
            </ul>
        </nav>
    </header>

    <div class="container" style="max-width: 800px;">
        <h2 style="margin-bottom: 2rem;">Ringkasan Donasi</h2>
        
        <div class="campaign-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 3rem;">
            <div class="glass-card" style="text-align: center; border-bottom: 4px solid #16a34a;">
                <h3 style="color: #16a34a; margin-bottom: 10px;">Verified</h3>
                <p style="font-size: 1.5rem; font-weight: bold;">Rp <?php echo number_format($summary['VERIFIED']['nominal'], 0, ',', '.'); ?></p>
                <p style="color: var(--text-muted);">(<?php echo $summary['VERIFIED']['count']; ?> donasi)</p>
            </div>
            <div class="glass-card" style="text-align: center; border-bottom: 4px solid #eab308;">
                <h3 style="color: #eab308; margin-bottom: 10px;">Pending</h3>
                <p style="font-size: 1.5rem; font-weight: bold;">Rp <?php echo number_format($summary['PENDING']['nominal'], 0, ',', '.'); ?></p>
                <p style="color: var(--text-muted);">(<?php echo $summary['PENDING']['count']; ?> donasi)</p>
            </div>
            <div class="glass-card" style="text-align: center; border-bottom: 4px solid #dc2626;">
                <h3 style="color: #dc2626; margin-bottom: 10px;">Ditolak</h3>
                <p style="font-size: 1.5rem; font-weight: bold;">Rp <?php echo number_format($summary['REJECTED']['nominal'], 0, ',', '.'); ?></p>
                <p style="color: var(--text-muted);">(<?php echo $summary['REJECTED']['count']; ?> donasi)</p>
            </div>
        </div>

        <h2 style="margin-bottom: 1.5rem;">Riwayat Donasi</h2>
        
        <div class="glass-card" style="padding: 0; overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background: rgba(255,255,255,0.05);">
                    <tr>
                        <th style="padding: 15px; text-align: left; border-bottom: 1px solid var(--border);">Tanggal</th>
                        <th style="padding: 15px; text-align: left; border-bottom: 1px solid var(--border);">Kampanye</th>
                        <th style="padding: 15px; text-align: left; border-bottom: 1px solid var(--border);">Nominal</th>
                        <th style="padding: 15px; text-align: left; border-bottom: 1px solid var(--border);">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($history) > 0): ?>
                        <?php foreach ($history as $h): 
                            $status_class = '';
                            if ($h['status'] == 'VERIFIED') $status_class = 'status-verified';
                            else if ($h['status'] == 'PENDING') $status_class = 'status-pending';
                            else $status_class = 'status-rejected';
                        ?>
                        <tr>
                            <td style="padding: 15px; border-bottom: 1px solid var(--border);"><?php echo date('d M Y H:i', strtotime($h['tanggal_donasi'])); ?></td>
                            <td style="padding: 15px; border-bottom: 1px solid var(--border);"><?php echo htmlspecialchars($h['judul_kampanye']); ?></td>
                            <td style="padding: 15px; border-bottom: 1px solid var(--border);">Rp <?php echo number_format($h['nominal'], 0, ',', '.'); ?></td>
                            <td style="padding: 15px; border-bottom: 1px solid var(--border);">
                                <span class="status-badge <?php echo $status_class; ?>"><?php echo $h['status']; ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="padding: 15px; text-align: center;">Belum ada riwayat donasi.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>
