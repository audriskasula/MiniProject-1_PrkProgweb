<?php
session_start();
require '../config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'pengelola') {
    header("Location: ../auth/login.php");
    exit();
}

$pengelola_id = $_SESSION['user_id'];
$success = "";
$error = "";

// Handle Verification
if (isset($_GET['action']) && isset($_GET['id'])) {
    $donasi_id = (int) $_GET['id'];
    $action = $_GET['action']; // 'verify' or 'reject'

    // Pastikan donasi ini milik kampanye yang dikelola oleh user ini
    $stmt_cek = $conn->prepare("
        SELECT d.id, d.nominal, d.status, k.id as kampanye_id 
        FROM donasi d 
        JOIN kampanye k ON d.kampanye_id = k.id 
        WHERE d.id = ? AND k.pengelola_id = ?
    ");
    $stmt_cek->bind_param("ii", $donasi_id, $pengelola_id);
    $stmt_cek->execute();
    $res = $stmt_cek->get_result();

    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();

        if ($row['status'] == 'PENDING') {
            if ($action == 'verify') {
                $new_status = 'VERIFIED';
                $conn->begin_transaction();
                try {
                    // Update donasi
                    $stmt_upd = $conn->prepare("UPDATE donasi SET status = ? WHERE id = ?");
                    $stmt_upd->bind_param("si", $new_status, $donasi_id);
                    $stmt_upd->execute();

                    // Update dana terkumpul kampanye
                    $stmt_dana = $conn->prepare("UPDATE kampanye SET dana_terkumpul = dana_terkumpul + ? WHERE id = ?");
                    $stmt_dana->bind_param("di", $row['nominal'], $row['kampanye_id']);
                    $stmt_dana->execute();

                    $conn->commit();
                    $success = "Donasi berhasil diverifikasi. Dana telah ditambahkan ke kampanye.";
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = "Terjadi kesalahan saat memverifikasi donasi.";
                }
            } else if ($action == 'reject') {
                $new_status = 'REJECTED';
                $stmt_upd = $conn->prepare("UPDATE donasi SET status = ? WHERE id = ?");
                $stmt_upd->bind_param("si", $new_status, $donasi_id);
                if ($stmt_upd->execute()) {
                    $success = "Donasi telah ditolak.";
                } else {
                    $error = "Terjadi kesalahan.";
                }
            }
        } else {
            $error = "Status donasi tidak dapat diubah lagi.";
        }
    } else {
        $error = "Donasi tidak ditemukan atau Anda tidak memiliki akses.";
    }
}

// Get all campaigns for this pengelola
$stmt_kamp = $conn->prepare("SELECT id, judul_kampanye, dana_terkumpul FROM kampanye WHERE pengelola_id = ? ORDER BY batas_waktu DESC");
$stmt_kamp->bind_param("i", $pengelola_id);
$stmt_kamp->execute();
$kampanye_list = $stmt_kamp->get_result()->fetch_all(MYSQLI_ASSOC);

// Get all donations for this pengelola's campaigns, grouped by kampanye
$stmt = $conn->prepare("
    SELECT d.*, k.judul_kampanye, k.id as kid, u.nama_lengkap as nama_donatur 
    FROM donasi d 
    JOIN kampanye k ON d.kampanye_id = k.id 
    JOIN users u ON d.donatur_id = u.id 
    WHERE k.pengelola_id = ? 
    ORDER BY k.id ASC, d.tanggal_donasi DESC
");
$stmt->bind_param("i", $pengelola_id);
$stmt->execute();
$all_donasi = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Group donations by campaign
$grouped = [];
foreach ($all_donasi as $d) {
    $kid = $d['kid'];
    if (!isset($grouped[$kid])) {
        $grouped[$kid] = [
            'judul' => $d['judul_kampanye'],
            'donasi' => [],
            'summary' => [
                'VERIFIED' => ['count' => 0, 'total' => 0],
                'PENDING' => ['count' => 0, 'total' => 0],
                'REJECTED' => ['count' => 0, 'total' => 0],
            ]
        ];
    }
    $grouped[$kid]['donasi'][] = $d;
    $grouped[$kid]['summary'][$d['status']]['count']++;
    $grouped[$kid]['summary'][$d['status']]['total'] += $d['nominal'];
}

// Calculate overall totals
$overall_verified = 0;
$overall_pending = 0;
$overall_rejected = 0;
$overall_verified_count = 0;
$overall_pending_count = 0;
$overall_rejected_count = 0;

foreach ($grouped as $g) {
    $overall_verified += $g['summary']['VERIFIED']['total'];
    $overall_pending += $g['summary']['PENDING']['total'];
    $overall_rejected += $g['summary']['REJECTED']['total'];
    $overall_verified_count += $g['summary']['VERIFIED']['count'];
    $overall_pending_count += $g['summary']['PENDING']['count'];
    $overall_rejected_count += $g['summary']['REJECTED']['count'];
}

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Donasi - PeduliSemua</title>
    <link rel="stylesheet" href="../style.css">
</head>

<body>

    <header>
        <a href="../index.php" class="logo"><span class="logo-icon">❇</span> PeduliSemua</a>
        <nav>
            <ul>
                <li><a href="dashboard_pengelola.php">Dashboard Kampanye</a></li>
                <li><a href="../auth/logout.php" class="btn-login"
                        style="background:var(--error); border-color:var(--error); color:white;">Logout</a></li>
            </ul>
        </nav>
    </header>

    <div class="container" style="max-width: 1100px;">
        <h2 style="margin-bottom: 2rem;">Verifikasi Donasi Masuk</h2>

        <?php if ($success): ?>
            <div style="background-color: #dcfce7; color: #16a34a; padding: 10px; border-radius: 8px; margin-bottom: 1rem;">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div style="background-color: #fee2e2; color: #dc2626; padding: 10px; border-radius: 8px; margin-bottom: 1rem;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Overall Summary — Rubric #32 -->
        <div class="summary-grid">
            <div class="summary-card" style="border-bottom: 4px solid #16a34a;">
                <h4>Total Verified</h4>
                <div class="summary-value" style="color: #16a34a;">Rp <?php echo number_format($overall_verified, 0, ',', '.'); ?></div>
                <div class="summary-count">(<?php echo $overall_verified_count; ?> donasi)</div>
            </div>
            <div class="summary-card" style="border-bottom: 4px solid #eab308;">
                <h4>Total Pending</h4>
                <div class="summary-value" style="color: #a16207;">Rp <?php echo number_format($overall_pending, 0, ',', '.'); ?></div>
                <div class="summary-count">(<?php echo $overall_pending_count; ?> donasi)</div>
            </div>
            <div class="summary-card" style="border-bottom: 4px solid #dc2626;">
                <h4>Total Rejected</h4>
                <div class="summary-value" style="color: #dc2626;">Rp <?php echo number_format($overall_rejected, 0, ',', '.'); ?></div>
                <div class="summary-count">(<?php echo $overall_rejected_count; ?> donasi)</div>
            </div>
        </div>

        <!-- Per-campaign sections — Rubric #22, #30, #31 -->
        <?php if (count($grouped) > 0): ?>
            <?php foreach ($grouped as $kid => $camp): ?>
                <div class="campaign-section">
                    <div class="glass-card" style="margin-bottom: 1.5rem;">
                        <div class="campaign-section-header">
                            <h3>📋 <?php echo htmlspecialchars($camp['judul']); ?></h3>
                            <div class="campaign-section-stats">
                                <span style="background: #dcfce7; color: #16a34a;">
                                    ✅ Verified: Rp <?php echo number_format($camp['summary']['VERIFIED']['total'], 0, ',', '.'); ?>
                                    (<?php echo $camp['summary']['VERIFIED']['count']; ?>)
                                </span>
                                <span style="background: #fef9c3; color: #a16207;">
                                    ⏳ Pending: Rp <?php echo number_format($camp['summary']['PENDING']['total'], 0, ',', '.'); ?>
                                    (<?php echo $camp['summary']['PENDING']['count']; ?>)
                                </span>
                                <span style="background: #fee2e2; color: #dc2626;">
                                    ❌ Rejected: Rp <?php echo number_format($camp['summary']['REJECTED']['total'], 0, ',', '.'); ?>
                                    (<?php echo $camp['summary']['REJECTED']['count']; ?>)
                                </span>
                            </div>
                        </div>

                        <div style="padding: 0; overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead style="background: rgba(255,255,255,0.05);">
                                    <tr>
                                        <th style="padding: 12px 15px; text-align: left; border-bottom: 1px solid var(--border);">Tanggal</th>
                                        <th style="padding: 12px 15px; text-align: left; border-bottom: 1px solid var(--border);">Donatur</th>
                                        <th style="padding: 12px 15px; text-align: left; border-bottom: 1px solid var(--border);">Nominal</th>
                                        <th style="padding: 12px 15px; text-align: center; border-bottom: 1px solid var(--border);">Bukti</th>
                                        <th style="padding: 12px 15px; text-align: center; border-bottom: 1px solid var(--border);">Status</th>
                                        <th style="padding: 12px 15px; text-align: center; border-bottom: 1px solid var(--border);">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($camp['donasi'] as $d):
                                        $status_class = '';
                                        if ($d['status'] == 'VERIFIED')
                                            $status_class = 'status-verified';
                                        else if ($d['status'] == 'PENDING')
                                            $status_class = 'status-pending';
                                        else
                                            $status_class = 'status-rejected';
                                        ?>
                                        <tr>
                                            <td style="padding: 12px 15px; border-bottom: 1px solid var(--border);">
                                                <?php echo date('d/m/Y H:i', strtotime($d['tanggal_donasi'])); ?>
                                            </td>
                                            <td style="padding: 12px 15px; border-bottom: 1px solid var(--border);">
                                                <?php echo htmlspecialchars($d['nama_donatur']); ?>
                                            </td>
                                            <td style="padding: 12px 15px; border-bottom: 1px solid var(--border);">Rp
                                                <?php echo number_format($d['nominal'], 0, ',', '.'); ?>
                                            </td>
                                            <td style="padding: 12px 15px; border-bottom: 1px solid var(--border); text-align: center;">
                                                <a href="../<?php echo htmlspecialchars($d['bukti_transfer']); ?>" target="_blank"
                                                    style="color: var(--primary);">Lihat Bukti</a>
                                            </td>
                                            <td style="padding: 12px 15px; border-bottom: 1px solid var(--border); text-align: center;">
                                                <span class="status-badge <?php echo $status_class; ?>"><?php echo $d['status']; ?></span>
                                            </td>
                                            <td style="padding: 12px 15px; border-bottom: 1px solid var(--border); text-align: center;">
                                                <?php if ($d['status'] == 'PENDING'): ?>
                                                    <a href="kelola_donasi.php?action=verify&id=<?php echo $d['id']; ?>" class="btn-primary"
                                                        style="padding: 5px 10px; font-size: 0.8rem; margin-right: 5px;">Terima</a>
                                                    <a href="kelola_donasi.php?action=reject&id=<?php echo $d['id']; ?>" class="btn-primary"
                                                        style="background: var(--error); border-color: var(--error); padding: 5px 10px; font-size: 0.8rem;"
                                                        onclick="return confirm('Tolak donasi ini?');">Tolak</a>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="glass-card" style="text-align: center; padding: 3rem;">
                <p>Belum ada donasi masuk untuk kampanye Anda.</p>
            </div>
        <?php endif; ?>
    </div>

</body>

</html>