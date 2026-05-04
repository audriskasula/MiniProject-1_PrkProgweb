CREATE DATABASE IF NOT EXISTS db_crowdfunding;
USE db_crowdfunding;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('donatur','pengelola') NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `no_telepon` varchar(20) NOT NULL,
  `alamat` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `users` (`username`, `password`, `role`, `nama_lengkap`, `email`, `no_telepon`, `alamat`) VALUES
('donatur1', '12345', 'donatur', 'Budi Santoso', 'budi@example.com', '081234567890', NULL),
('donatur2', '12345', 'donatur', 'Siti Aminah', 'siti@example.com', '089876543210', NULL),
('pengelola1', '12345', 'pengelola', 'Yayasan Peduli Anak', 'peduli@yayasan.com', '0215551234', 'Jl. Sudirman No 1, Jakarta'),
('pengelola2', '12345', 'pengelola', 'Komunitas Hijau', 'hijau@komunitas.com', '0274123456', 'Jl. Kaliurang KM 5, Yogyakarta');

CREATE TABLE `kampanye` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pengelola_id` int(11) NOT NULL,
  `judul_kampanye` varchar(150) NOT NULL,
  `kategori` enum('Bencana Alam','Pendidikan','Kesehatan','Lingkungan') NOT NULL,
  `lokasi` varchar(100) NOT NULL,
  `deskripsi` text NOT NULL,
  `target_dana` decimal(15,2) NOT NULL,
  `dana_terkumpul` decimal(15,2) DEFAULT 0.00,
  `batas_waktu` date NOT NULL,
  `gambar` varchar(255) NOT NULL,
  `rekening_donasi` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `pengelola_id` (`pengelola_id`),
  CONSTRAINT `kampanye_ibfk_1` FOREIGN KEY (`pengelola_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `kampanye` (`pengelola_id`, `judul_kampanye`, `kategori`, `lokasi`, `deskripsi`, `target_dana`, `dana_terkumpul`, `batas_waktu`, `gambar`, `rekening_donasi`) VALUES
(3, 'Bantuan Seragam Sekolah Anak Yatim', 'Pendidikan', 'Jawa', 'Mari bantu anak-anak panti asuhan mendapatkan seragam baru untuk tahun ajaran baru.', 15000000.00, 5000000.00, DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?auto=format&fit=crop&q=80&w=800', 'BCA 123456789 a/n Yayasan Peduli Anak'),
(4, 'Penanaman 1000 Pohon Mangrove di Pesisir', 'Lingkungan', 'Luar Jawa', 'Program rehabilitasi hutan mangrove di pesisir utara.', 20000000.00, 15000000.00, DATE_ADD(CURDATE(), INTERVAL 14 DAY), 'https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?auto=format&fit=crop&q=80&w=800', 'Mandiri 987654321 a/n Komunitas Hijau'),
(3, 'Bantuan Darurat Korban Banjir', 'Bencana Alam', 'Jawa', 'Bantuan segera untuk korban banjir bandang di daerah X.', 50000000.00, 45000000.00, DATE_ADD(CURDATE(), INTERVAL 3 DAY), 'https://images.unsplash.com/photo-1524661135-423995f22d0b?auto=format&fit=crop&q=80&w=800', 'BRI 111222333 a/n Yayasan Peduli Anak');

CREATE TABLE `donasi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kampanye_id` int(11) NOT NULL,
  `donatur_id` int(11) NOT NULL,
  `nominal` decimal(15,2) NOT NULL,
  `metode_pembayaran` varchar(50) NOT NULL,
  `pesan_dukungan` text DEFAULT NULL,
  `bukti_transfer` varchar(255) NOT NULL,
  `status` enum('PENDING','VERIFIED','REJECTED') DEFAULT 'PENDING',
  `tanggal_donasi` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `kampanye_id` (`kampanye_id`),
  KEY `donatur_id` (`donatur_id`),
  CONSTRAINT `donasi_ibfk_1` FOREIGN KEY (`kampanye_id`) REFERENCES `kampanye` (`id`) ON DELETE CASCADE,
  CONSTRAINT `donasi_ibfk_2` FOREIGN KEY (`donatur_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
