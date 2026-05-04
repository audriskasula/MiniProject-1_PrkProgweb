<?php
session_start();
require 'koneksi.php';

// Pagination setup
$limit = 6;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search parameters
$q = isset($_GET['q']) ? $_GET['q'] : '';
$kategori = isset($_GET['kategori']) ? $_GET['kategori'] : '';
$lokasi = isset($_GET['lokasi']) ? $_GET['lokasi'] : '';

// Base query
$whereClause = "WHERE batas_waktu > NOW()";
$params = [];
$types = "";

if (!empty($q)) {
    $whereClause .= " AND judul_kampanye LIKE ?";
    $params[] = "%$q%";
    $types .= "s";
}
if (!empty($kategori)) {
    $whereClause .= " AND kategori = ?";
    $params[] = $kategori;
    $types .= "s";
}
if (!empty($lokasi)) {
    $whereClause .= " AND lokasi = ?";
    $params[] = $lokasi;
    $types .= "s";
}

// Count total rows for pagination
$countQuery = "SELECT COUNT(id) as total FROM kampanye $whereClause";
$stmtCount = $conn->prepare($countQuery);
if ($types) {
    $stmtCount->bind_param($types, ...$params);
}
$stmtCount->execute();
$totalRows = $stmtCount->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

// Fetch data
// "Daftar kampanye akan ditampilkan dari deadline yang paling dekat serta dana paling kecil"
$query = "SELECT kampanye.*, users.nama_lengkap as nama_penyelenggara 
          FROM kampanye 
          JOIN users ON kampanye.pengelola_id = users.id 
          $whereClause 
          ORDER BY batas_waktu ASC, target_dana ASC 
          LIMIT ?, ?";
$stmt = $conn->prepare($query);
$params[] = $offset;
$params[] = $limit;
$types .= "ii";
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$kampanyes = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PeduliSemua - Sistem Crowdfunding Sosial</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 2rem;
            gap: 10px;
        }
        .pagination a, .pagination span {
            padding: 8px 16px;
            border-radius: 8px;
            background: var(--surface);
            color: var(--text);
            text-decoration: none;
            font-weight: 500;
        }
        .pagination a.active {
            background: var(--primary);
            color: white;
        }
        .pagination a:hover:not(.active) {
            background: rgba(99, 102, 241, 0.1);
        }
    </style>
</head>

<body>
    <header>
        <a href="index.php" class="logo"><span>⚡</span> PeduliSemua</a>
        <nav>
            <ul>
                <li><a href="index.php">Beranda</a></li>
                <li><a href="#kampanye">Kampanye</a></li>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <?php if($_SESSION['role'] == 'pengelola'): ?>
                        <li><a href="dashboard_pengelola.php">Dashboard</a></li>
                    <?php else: ?>
                        <li><a href="riwayat_donasi.php">Riwayat Donasi</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php" class="btn-login" style="background:var(--error); border-color:var(--error); color:white;">Logout (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a></li>
                <?php else: ?>
                    <li><a href="login.php" class="btn-login">Login</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <section class="hero">
        <h1 class="hero-title">Bersama Kita Bisa <br> Mengubah Dunia</h1>
        <p>Platform crowdfunding modern untuk membantu sesama. Temukan kampanye sosial pilihan dan mulai berbagi kebaikan hari ini.</p>
    </section>

    <div class="container">
        <form action="index.php" method="GET" class="search-filter">
            <input type="text" name="q" placeholder="Cari Judul Kampanye..." value="<?php echo htmlspecialchars($q); ?>">
            <select name="kategori">
                <option value="">Semua Kategori</option>
                <option value="Bencana Alam" <?php if($kategori == 'Bencana Alam') echo 'selected'; ?>>Bencana Alam</option>
                <option value="Pendidikan" <?php if($kategori == 'Pendidikan') echo 'selected'; ?>>Pendidikan</option>
                <option value="Kesehatan" <?php if($kategori == 'Kesehatan') echo 'selected'; ?>>Kesehatan</option>
                <option value="Lingkungan" <?php if($kategori == 'Lingkungan') echo 'selected'; ?>>Lingkungan</option>
            </select>
            <select name="lokasi">
                <option value="">Lokasi</option>
                <option value="Jawa" <?php if($lokasi == 'Jawa') echo 'selected'; ?>>Jawa</option>
                <option value="Luar Jawa" <?php if($lokasi == 'Luar Jawa') echo 'selected'; ?>>Luar Jawa</option>
            </select>
            <button type="submit" class="btn-primary search-btn">Cari</button>
        </form>

        <div class="campaign-grid" id="kampanye">
            <?php if (count($kampanyes) > 0): ?>
                <?php foreach ($kampanyes as $k): 
                    $progress = ($k['target_dana'] > 0) ? ($k['dana_terkumpul'] / $k['target_dana']) * 100 : 0;
                    if ($progress > 100) $progress = 100;

                    $date1 = new DateTime();
                    $date2 = new DateTime($k['batas_waktu']);
                    $interval = $date1->diff($date2);
                    $days_left = $interval->format('%a');
                ?>
                <div class="campaign-card">
                    <img src="<?php echo htmlspecialchars($k['gambar']); ?>" alt="Kampanye" class="card-img">
                    <div class="card-body">
                        <span class="card-category"><?php echo htmlspecialchars($k['kategori']); ?></span>
                        <h3 class="card-title"><?php echo htmlspecialchars($k['judul_kampanye']); ?></h3>
                        <p class="card-organizer">Oleh: <?php echo htmlspecialchars($k['nama_penyelenggara']); ?></p>

                        <div class="progress-container">
                            <div class="progress-fill" style="width: <?php echo $progress; ?>%;"></div>
                        </div>

                        <div class="card-stats">
                            <h4>Terkumpul: <strong>Rp <?php echo number_format($k['dana_terkumpul'], 0, ',', '.'); ?></strong></h4>
                            <h4>Sisa: <strong><?php echo $days_left; ?> Hari</strong></h4>
                        </div>
                        <div class="card-stats card-stats-target">
                            <span>Target: Rp <?php echo number_format($k['target_dana'], 0, ',', '.'); ?></span>
                        </div>

                    </div>
                    <div class="button-campaign">
                        <a href="detail.php?id=<?php echo $k['id']; ?>" class="btn-primary">Lihat Detail</a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Tidak ada kampanye yang ditemukan.</p>
            <?php endif; ?>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&q=<?php echo urlencode($q); ?>&kategori=<?php echo urlencode($kategori); ?>&lokasi=<?php echo urlencode($lokasi); ?>" class="<?php echo ($i == $page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>

    </div>

    <footer>
        <p>&copy; 2026 PeduliSemua - Sistem Crowdfunding Sosial. All rights reserved.</p>
    </footer>

</body>
</html>
