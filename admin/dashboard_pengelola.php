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

// Flash messages dari upload_gambar.php
if (isset($_SESSION['flash_success'])) {
    $success = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}
if (isset($_SESSION['flash_error'])) {
    $error = $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id_to_delete = (int) $_GET['delete'];

    // Cek dana terkumpul
    $stmt_cek = $conn->prepare("SELECT dana_terkumpul, gambar FROM kampanye WHERE id = ? AND pengelola_id = ?");
    $stmt_cek->bind_param("ii", $id_to_delete, $pengelola_id);
    $stmt_cek->execute();
    $res = $stmt_cek->get_result();

    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        if ($row['dana_terkumpul'] >= 10000) {
            $error = "Kampanye tidak dapat dihapus karena dana terkumpul sudah >= Rp 10.000.";
        } else {
            $stmt_del = $conn->prepare("DELETE FROM kampanye WHERE id = ?");
            $stmt_del->bind_param("i", $id_to_delete);
            if ($stmt_del->execute()) {
                $file_path = "../" . $row['gambar'];
                if (file_exists($file_path) && $row['gambar'] != 'uploads/placeholder.svg') {
                    unlink($file_path);
                }
                $success = "Kampanye berhasil dihapus.";
            } else {
                $error = "Gagal menghapus kampanye.";
            }
        }
    }
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $judul = $_POST['judul_kampanye'];
    $kategori = $_POST['kategori'];
    $lokasi = $_POST['lokasi'];
    $deskripsi = $_POST['deskripsi'];
    $target_dana = (float) $_POST['target_dana'];
    $batas_waktu = $_POST['batas_waktu'];
    $rekening = $_POST['rekening_donasi'];

    $gambar = "";
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        $target_dir = "../uploads/";
        if (!is_dir($target_dir))
            mkdir($target_dir, 0777, true);
        $file_name = time() . '_' . basename($_FILES["gambar"]["name"]);
        $target_file = $target_dir . $file_name;
        if (move_uploaded_file($_FILES["gambar"]["tmp_name"], $target_file)) {
            $gambar = "uploads/" . $file_name;
        }
    }

    if ($id > 0) {
        // Edit
        if ($gambar != "") {
            $stmt = $conn->prepare("UPDATE kampanye SET judul_kampanye=?, kategori=?, lokasi=?, deskripsi=?, target_dana=?, batas_waktu=?, rekening_donasi=?, gambar=? WHERE id=? AND pengelola_id=?");
            $stmt->bind_param("ssssdsssii", $judul, $kategori, $lokasi, $deskripsi, $target_dana, $batas_waktu, $rekening, $gambar, $id, $pengelola_id);
        } else {
            $stmt = $conn->prepare("UPDATE kampanye SET judul_kampanye=?, kategori=?, lokasi=?, deskripsi=?, target_dana=?, batas_waktu=?, rekening_donasi=? WHERE id=? AND pengelola_id=?");
            $stmt->bind_param("ssssdssii", $judul, $kategori, $lokasi, $deskripsi, $target_dana, $batas_waktu, $rekening, $id, $pengelola_id);
        }

        if ($stmt->execute())
            $success = "Kampanye berhasil diupdate.";
        else
            $error = "Gagal mengupdate kampanye.";
    } else {
        // Tambah baru
        if ($gambar == "")
            $gambar = "uploads/placeholder.svg"; // placeholder default
        $stmt = $conn->prepare("INSERT INTO kampanye (pengelola_id, judul_kampanye, kategori, lokasi, deskripsi, target_dana, batas_waktu, gambar, rekening_donasi) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssdsss", $pengelola_id, $judul, $kategori, $lokasi, $deskripsi, $target_dana, $batas_waktu, $gambar, $rekening);
        if ($stmt->execute())
            $success = "Kampanye baru berhasil ditambahkan.";
        else
            $error = "Gagal menambahkan kampanye.";
    }
}

// Get Data for Table
$stmt = $conn->prepare("SELECT * FROM kampanye WHERE pengelola_id = ? ORDER BY batas_waktu DESC");
$stmt->bind_param("i", $pengelola_id);
$stmt->execute();
$kampanyes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// For Edit form pre-fill
$edit_data = null;
if (isset($_GET['edit'])) {
    $edit_id = (int) $_GET['edit'];
    $stmt_edit = $conn->prepare("SELECT * FROM kampanye WHERE id = ? AND pengelola_id = ?");
    $stmt_edit->bind_param("ii", $edit_id, $pengelola_id);
    $stmt_edit->execute();
    $res = $stmt_edit->get_result();
    if ($res->num_rows > 0) {
        $edit_data = $res->fetch_assoc();
    }
}

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pengelola - PeduliSemua</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .form-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .full-width {
            grid-column: 1 / -1;
        }
    </style>
</head>

<body>

    <header>
        <a href="../index.php" class="logo"><span class="logo-icon">❇</span> PeduliSemua</a>
        <nav>
            <ul>
                <li><a href="../index.php">Lihat Web</a></li>
                <li><a href="kelola_donasi.php">Verifikasi Donasi</a></li>
                <li><a href="../auth/logout.php" class="btn-login"
                        style="background:var(--error); border-color:var(--error); color:white;">Logout</a></li>
            </ul>
        </nav>
    </header>

    <div class="container" style="max-width: 1000px;">
        <h2 style="margin-bottom: 2rem;">Manajemen Kampanye</h2>

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

        <div class="glass-card" style="margin-bottom: 3rem;">
            <h3><?php echo $edit_data ? 'Edit Kampanye' : 'Buat Kampanye Baru'; ?></h3>
            <form action="dashboard_pengelola.php" method="POST" enctype="multipart/form-data"
                style="margin-top: 1rem;">
                <input type="hidden" name="id" value="<?php echo $edit_data ? $edit_data['id'] : ''; ?>">

                <div class="form-layout">
                    <div class="form-group full-width">
                        <label>Judul Kampanye</label>
                        <input type="text" name="judul_kampanye" class="form-control" required
                            value="<?php echo $edit_data ? htmlspecialchars($edit_data['judul_kampanye']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Kategori</label>
                        <select name="kategori" class="form-control" required>
                            <option value="Bencana Alam" <?php if ($edit_data && $edit_data['kategori'] == 'Bencana Alam')
                                echo 'selected'; ?>>Bencana Alam</option>
                            <option value="Pendidikan" <?php if ($edit_data && $edit_data['kategori'] == 'Pendidikan')
                                echo 'selected'; ?>>Pendidikan</option>
                            <option value="Kesehatan" <?php if ($edit_data && $edit_data['kategori'] == 'Kesehatan')
                                echo 'selected'; ?>>Kesehatan</option>
                            <option value="Lingkungan" <?php if ($edit_data && $edit_data['kategori'] == 'Lingkungan')
                                echo 'selected'; ?>>Lingkungan</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Lokasi</label>
                        <input type="text" name="lokasi" class="form-control" required
                            value="<?php echo $edit_data ? htmlspecialchars($edit_data['lokasi']) : ''; ?>">
                    </div>

                    <div class="form-group full-width">
                        <label>Deskripsi</label>
                        <textarea name="deskripsi" class="form-control" rows="4"
                            required><?php echo $edit_data ? htmlspecialchars($edit_data['deskripsi']) : ''; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Target Dana (Rp)</label>
                        <input type="number" name="target_dana" class="form-control" required
                            value="<?php echo $edit_data ? (int) $edit_data['target_dana'] : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Batas Waktu</label>
                        <input type="date" name="batas_waktu" class="form-control" required
                            value="<?php echo $edit_data ? $edit_data['batas_waktu'] : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Rekening Donasi Tujuan</label>
                        <input type="text" name="rekening_donasi" class="form-control" required
                            value="<?php echo $edit_data ? htmlspecialchars($edit_data['rekening_donasi']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Gambar / Poster
                            <?php echo $edit_data ? '(Kosongkan jika tidak ingin mengubah)' : '(Opsional, default: placeholder)'; ?></label>
                        <input type="file" name="gambar" class="form-control" accept="image/*">
                        <?php if ($edit_data && $edit_data['gambar']): ?>
                            <div style="margin-top: 8px;">
                                <img src="../<?php echo htmlspecialchars($edit_data['gambar']); ?>" alt="Preview"
                                    style="max-height: 80px; border-radius: 6px; border: 1px solid var(--border);">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="margin-top: 1rem;">
                    <button type="submit"
                        class="btn-primary"><?php echo $edit_data ? 'Simpan Perubahan' : 'Tambah Kampanye'; ?></button>
                    <?php if ($edit_data): ?>
                        <a href="dashboard_pengelola.php" class="btn-primary"
                            style="background: var(--text-muted); border-color: var(--text-muted);">Batal</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <h3 style="margin-bottom: 1rem;">Daftar Kampanye Anda</h3>
        <div class="glass-card" style="padding: 0; overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background: rgba(255,255,255,0.05);">
                    <tr>
                        <th style="padding: 15px; text-align: left; border-bottom: 1px solid var(--border);">Gambar</th>
                        <th style="padding: 15px; text-align: left; border-bottom: 1px solid var(--border);">Judul</th>
                        <th style="padding: 15px; text-align: left; border-bottom: 1px solid var(--border);">Target</th>
                        <th style="padding: 15px; text-align: left; border-bottom: 1px solid var(--border);">Terkumpul
                        </th>
                        <th style="padding: 15px; text-align: left; border-bottom: 1px solid var(--border);">Batas Waktu
                        </th>
                        <th style="padding: 15px; text-align: center; border-bottom: 1px solid var(--border);">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($kampanyes) > 0): ?>
                        <?php foreach ($kampanyes as $k): ?>
                            <tr>
                                <td style="padding: 10px; border-bottom: 1px solid var(--border); width: 120px;">
                                    <div style="position: relative;">
                                        <img src="../<?php echo htmlspecialchars($k['gambar']); ?>" alt="Gambar"
                                            style="width: 90px; height: 60px; object-fit: cover; border-radius: 6px; border: 1px solid var(--border); display: block;">
                                        <?php if ($k['gambar'] == 'uploads/placeholder.svg'): ?>
                                            <span
                                                style="display: inline-block; margin-top: 4px; font-size: 0.7rem; color: var(--text-muted); background: rgba(99,102,241,0.1); padding: 2px 6px; border-radius: 4px;">Placeholder</span>
                                        <?php endif; ?>
                                    </div>
                                    <!-- <form action="upload_gambar.php" method="POST" enctype="multipart/form-data" style="margin-top: 6px;">
                                        <input type="hidden" name="kampanye_id" value="<?php echo $k['id']; ?>">
                                        <input type="file" name="gambar" accept="image/*" style="font-size: 0.7rem; width: 100px;" onchange="this.form.submit()" required>
                                    </form> -->
                                </td>
                                <td style="padding: 15px; border-bottom: 1px solid var(--border);">
                                    <?php echo htmlspecialchars($k['judul_kampanye']); ?>
                                </td>
                                <td style="padding: 15px; border-bottom: 1px solid var(--border);">Rp
                                    <?php echo number_format($k['target_dana'], 0, ',', '.'); ?>
                                </td>
                                <td style="padding: 15px; border-bottom: 1px solid var(--border);">Rp
                                    <?php echo number_format($k['dana_terkumpul'], 0, ',', '.'); ?>
                                </td>
                                <td style="padding: 15px; border-bottom: 1px solid var(--border);">
                                    <?php echo $k['batas_waktu']; ?>
                                </td>
                                <td style="padding: 15px; border-bottom: 1px solid var(--border); text-align: center;">
                                    <a href="dashboard_pengelola.php?edit=<?php echo $k['id']; ?>"
                                        style="color: var(--primary); margin-right: 10px;">Edit</a>
                                    <a href="dashboard_pengelola.php?delete=<?php echo $k['id']; ?>"
                                        style="color: var(--error);"
                                        onclick="return confirm('Yakin hapus kampanye ini?');">Hapus</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="padding: 15px; text-align: center;">Belum ada kampanye.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>

</html>