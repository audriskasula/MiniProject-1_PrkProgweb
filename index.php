<?php
session_start();
require 'config/koneksi.php';

// Pagination setup
$limit = 6;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search parameters
$q = isset($_GET['q']) ? $_GET['q'] : '';
$kategori = isset($_GET['kategori']) ? $_GET['kategori'] : '';
$lokasi = isset($_GET['lokasi']) ? $_GET['lokasi'] : '';
$tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : '';

// Base query — JOIN users so we can search by penyelenggara name
$whereClause = "WHERE kampanye.batas_waktu > NOW()";
$params = [];
$types = "";

if (!empty($q)) {
    $whereClause .= " AND (kampanye.judul_kampanye LIKE ? OR users.nama_lengkap LIKE ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
    $types .= "ss";
}
if (!empty($kategori)) {
    $whereClause .= " AND kampanye.kategori = ?";
    $params[] = $kategori;
    $types .= "s";
}
if (!empty($lokasi)) {
    $whereClause .= " AND kampanye.lokasi = ?";
    $params[] = $lokasi;
    $types .= "s";
}
if (!empty($tanggal)) {
    $whereClause .= " AND kampanye.batas_waktu = ?";
    $params[] = $tanggal;
    $types .= "s";
}

// Count total rows for pagination
$countQuery = "SELECT COUNT(kampanye.id) as total FROM kampanye JOIN users ON kampanye.pengelola_id = users.id $whereClause";
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
    <title>PeduliSemua - Crowdfunding Platform</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <header>
        <a href="index.php" class="logo"><span class="logo-icon">❇</span> PeduliSemua</a>
        <nav>
            <ul>
                <li><a href="index.php">Beranda</a></li>
                <li><a href="#kampanye">Donasi</a></li>
                <li><a href="#how-it-works">Cara Kerja</a></li>
                <li><a href="#about-us">Tentang Kami</a></li>
            </ul>
        </nav>
        <div>
            <?php if (isset($_SESSION['user_id'])): ?>
                <span style="margin-right: 10px; font-weight: 500; color: #333;">Halo, <?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?></span>
                <?php if ($_SESSION['role'] == 'pengelola'): ?>
                    <a href="admin/dashboard_pengelola.php" class="btn-login"
                        style="margin-right: 10px; background: #fff; border: 1px solid #eaeaea;">Dashboard</a>
                <?php else: ?>
                    <a href="riwayat_donasi.php" class="btn-login"
                        style="margin-right: 10px; background: #fff; border: 1px solid #eaeaea;">Riwayat</a>
                <?php endif; ?>
                <a href="auth/logout.php" class="btn-login" style="background:#ff4d4f; color:#fff !important;">Keluar</a>
            <?php else: ?>
                <a href="auth/login.php" class="btn-login">Masuk</a>
            <?php endif; ?>
        </div>
    </header>

    <section class="hero-new container">
        <div class="hero-inner">
            <div class="hero-content">
                <div class="hero-title-container">
                    <div class="huge-fund" style="font-size: 5.5rem; letter-spacing: -2px;">Peduli<br>Semua</div>
                    <div class="help-others">Bantu<br>Sesama</div>
                </div>
                <a href="#kampanye" class="btn-primary">Mulai Galang Dana</a>
            </div>
        </div>
    </section>

    <section class="fast-flash container" id="how-it-works">
        <div class="section-heading">
            <h2>Cepat Seperti <em>Kilat</em></h2>
            <p>Galang dana secepat kilat! Tingkatkan kampanye Anda hanya dalam semenit dengan platform penggalangan dana
                kami.</p>
        </div>
        <div class="flash-grid">
            <div class="flash-card">
                <div class="icon">🚀</div>
                <h3>Nyalakan Dampak</h3>
                <p>Bagikan tujuan Anda dan dampak positif yang dibawanya. Jelaskan dengan jelas bagaimana kontribusi
                    akan membawa perubahan berarti.</p>
            </div>
            <div class="flash-card">
                <div class="icon">⚡</div>
                <h3>Sebarkan Kebaikan</h3>
                <p>Manfaatkan kecepatan media sosial dan jaringan online. Bagikan kampanye penggalangan dana Anda dengan
                    cepat ke berbagai platform.</p>
            </div>
            <div class="flash-card">
                <div class="icon">🌍</div>
                <h3>Terhubung Secara Global</h3>
                <p>Bangun jaringan sosial yang kuat di sekitar tujuan Anda. Dorong pendukung untuk membagikan kampanye
                    di komunitas lokal mereka.</p>
            </div>
        </div>
    </section>

    <section class="urgent-fund container" id="kampanye">
        <div class="section-heading">
            <h2>Penggalangan Dana Mendesak!</h2>
            <p>Waktu sangat berharga! Bergabunglah dengan misi kami SEKARANG untuk memberikan dampak langsung. Setiap
                detik sangat berarti!</p>
        </div>

        <form action="index.php#kampanye" method="GET" class="search-filter" style="flex-wrap: wrap;">
            <input type="text" name="q" placeholder="Cari kampanye atau nama penyelenggara..." value="<?php echo htmlspecialchars($q); ?>">
            <select name="kategori">
                <option value="">Semua Kategori</option>
                <option value="Bencana Alam" <?php if ($kategori == 'Bencana Alam')
                    echo 'selected'; ?>>Bencana Alam
                </option>
                <option value="Pendidikan" <?php if ($kategori == 'Pendidikan')
                    echo 'selected'; ?>>Pendidikan</option>
                <option value="Kesehatan" <?php if ($kategori == 'Kesehatan')
                    echo 'selected'; ?>>Kesehatan</option>
                <option value="Lingkungan" <?php if ($kategori == 'Lingkungan')
                    echo 'selected'; ?>>Lingkungan</option>
            </select>
            <select name="lokasi">
                <option value="">Lokasi</option>
                <option value="Jawa" <?php if ($lokasi == 'Jawa')
                    echo 'selected'; ?>>Jawa</option>
                <option value="Luar Jawa" <?php if ($lokasi == 'Luar Jawa')
                    echo 'selected'; ?>>Luar Jawa</option>
            </select>
            <input type="date" name="tanggal" value="<?php echo htmlspecialchars($tanggal); ?>" placeholder="Batas Waktu" style="max-width: 180px;">
            <button type="submit" class="btn-primary" style="padding: 0.8rem 1.5rem;">Cari</button>
        </form>

        <div class="campaign-grid">
            <?php if (count($kampanyes) > 0): ?>
                <?php foreach ($kampanyes as $k):
                    $progress = ($k['target_dana'] > 0) ? ($k['dana_terkumpul'] / $k['target_dana']) * 100 : 0;
                    if ($progress > 100)
                        $progress = 100;
                    $date1 = new DateTime();
                    $date2 = new DateTime($k['batas_waktu']);
                    $interval = $date1->diff($date2);
                    $days_left = $interval->format('%a');
                    ?>
                    <a href="detail.php?id=<?php echo $k['id']; ?>" class="campaign-card">
                        <img src="<?php echo htmlspecialchars($k['gambar']); ?>"
                            alt="<?php echo htmlspecialchars($k['judul_kampanye']); ?>" class="card-img"
                            onerror="this.onerror=null;this.src='uploads/placeholder.svg';">
                        <div class="card-body">
                            <span class="card-category"><?php echo htmlspecialchars($k['kategori']); ?> <span
                                    class="check-icon">✔</span></span>
                            <h3 class="card-title"><?php echo htmlspecialchars($k['judul_kampanye']); ?></h3>

                            <div class="progress-container">
                                <div class="progress-fill" style="width: <?php echo $progress; ?>%;"></div>
                            </div>

                            <div class="card-stats-row">
                                <div class="stat-left">Rp <?php echo number_format($k['dana_terkumpul'], 0, ',', '.'); ?></div>
                                <div class="stat-right">sisa <?php echo $days_left; ?> hari</div>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Tidak ada kampanye yang ditemukan.</p>
            <?php endif; ?>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&q=<?php echo urlencode($q); ?>&kategori=<?php echo urlencode($kategori); ?>&lokasi=<?php echo urlencode($lokasi); ?>&tanggal=<?php echo urlencode($tanggal); ?>#kampanye"
                        class="<?php echo ($i == $page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="large-numbers container" id="about-us">
        <div class="numbers-wrapper">
            <p class="pre-title">Jadilah Bagian Dari Penggalang Dana Bersama Lebih Dari</p>
            <h2 class="huge-number">217,924+</h2>
            <p class="post-title">Orang Dari Seluruh Dunia Telah Bergabung</p>
            <a href="auth/login.php" class="btn-primary">Bergabunglah Sekarang!</a>
        </div>
    </section>

    <section class="faq-section container">
        <h2>Pertanyaan yang Sering<br>Diajukan.</h2>
        <div class="faq-list">
            <div class="faq-item">
                <span>Bagaimana Saya Bisa Berdonasi?</span>
                <span class="faq-plus">+</span>
            </div>
            <div class="faq-item">
                <span>Apakah Donasi Saya Bebas Pajak?</span>
                <span class="faq-plus">+</span>
            </div>
            <div class="faq-item">
                <span>Bisakah Saya Berdonasi Atas Nama Seseorang?</span>
                <span class="faq-plus">+</span>
            </div>
            <div class="faq-item">
                <span>Bagaimana Donasi Saya Akan Digunakan?</span>
                <span class="faq-plus">+</span>
            </div>
            <div class="faq-item">
                <span>Bisakah Saya Mengatur Donasi Rutin?</span>
                <span class="faq-plus">+</span>
            </div>
        </div>
    </section>

    <footer class="main-footer">
        <div class="footer-container">
            <div class="footer-left">
                <a href="#" class="logo footer-logo"><span class="logo-icon">❇</span> PeduliSemua</a>
                <p>Meningkatkan Pengalaman & Membantu<br>Mereka yang Membutuhkan</p>
            </div>
            <div class="footer-links">
                <div class="footer-col">
                    <h4>Donasi</h4>
                    <a href="#">Pendidikan</a>
                    <a href="#">Sosial</a>
                    <a href="#">Kesehatan</a>
                    <a href="#">Bencana</a>
                </div>
                <div class="footer-col">
                    <h4>Bantuan</h4>
                    <a href="#">FAQ</a>
                    <a href="#">Kebijakan Privasi</a>
                    <a href="#">Aksesibilitas</a>
                    <a href="#">Hubungi Kami</a>
                </div>
                <div class="footer-col">
                    <h4>Perusahaan</h4>
                    <a href="#">Tentang Kami</a>
                    <a href="#">Karir</a>
                    <a href="#">Layanan</a>
                    <a href="#">Harga</a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; PeduliSemua Inc. 2026. Hak Cipta Dilindungi.</p>
            <div class="social-pills">
                <a href="#"><i class="icon">📷</i> Instagram</a>
                <a href="#"><i class="icon">f</i> Facebook</a>
                <a href="#"><i class="icon">𝕏</i> Twitter</a>
                <a href="#"><i class="icon">in</i> LinkedIn</a>
            </div>
        </div>
    </footer>
    <script src="script.js"></script>
</body>

</html>