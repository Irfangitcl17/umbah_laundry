-- ========================================================
-- Database: db_laundry
-- Sistem Manajemen & Portal Pelanggan Umbah Laundry
-- ========================================================

CREATE DATABASE IF NOT EXISTS db_laundry CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_laundry;

SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- Struktur tabel `users`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','karyawan','pelanggan') NOT NULL DEFAULT 'pelanggan',
  `nama` varchar(100) NOT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data untuk tabel `users`
INSERT INTO `users` (`id`, `username`, `password`, `role`, `nama`, `no_hp`, `alamat`, `created_at`) VALUES
('1', 'admin', '$2y$10$4jeuBXaF8sCa3aiSWMGRCuOIrzQmmsCpiKQH9T9U6.poqhnkyB3.m', 'admin', 'Owner Umbah', '087715890651', NULL, '2026-09-14 07:19:52'),
('2', 'kasir1', '$2y$10$bLW9QB.N3fRFtv.drvGq0uEuAxtgszcIQSPccNfRNgdvRK0ny5a7K', 'karyawan', 'Kasir Umbah', '089876543210', NULL, '2026-09-14 07:19:52'),
('4', 'Irfankeren', '$2y$10$BkpdstUF0TxWZn7DXeVJUu.zb7Vg0PDCoblUeZuXqD0sz/0nmjyHq', 'karyawan', 'Mohammad Irfan Hariyono', '081234567888', NULL, '2026-09-14 08:24:51'),
('5', 'sitipelanggan', '$2y$10$bbSRiYIuxUH4jzrGymErl.UxYxZt8M.UKraEzTo4Ph0eqz8AoPdMC', 'pelanggan', 'Siti Rahayu', '081333444555', 'Jl. Telang Asri Blok B No. 4', '2026-09-14 08:43:36'),
('7', 'Reseki', '$2y$10$BwG2FqK.a8dWwbf5elaN1emRaN35P/rZgTG9qzF15sTUM/wm2Ix9K', 'pelanggan', 'Rezeki', '087999999999', 'Telang gang 4 rumah kuning', '2026-09-14 08:54:09');

-- --------------------------------------------------------
-- Struktur tabel `layanan`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `layanan`;
CREATE TABLE `layanan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_layanan` varchar(100) NOT NULL,
  `kategori` varchar(50) NOT NULL,
  `satuan` varchar(20) NOT NULL DEFAULT 'kg',
  `harga_per_satuan` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data untuk tabel `layanan`
INSERT INTO `layanan` (`id`, `nama_layanan`, `kategori`, `satuan`, `harga_per_satuan`, `created_at`) VALUES
('1', 'Cuci Basah', 'Kiloan', 'kg', '5000.00', '2026-09-14 07:19:52'),
('2', 'Cuci Kering', 'Kiloan', 'kg', '6500.00', '2026-09-14 07:19:52'),
('3', 'Cuci Lipat', 'Kiloan', 'kg', '7500.00', '2026-09-14 07:19:52'),
('4', 'Cuci Setrika', 'Kiloan', 'kg', '9000.00', '2026-09-14 07:19:52'),
('5', 'Setrika Saja', 'Kiloan', 'kg', '5000.00', '2026-09-14 07:19:52');

-- --------------------------------------------------------
-- Struktur tabel `pelanggan`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `pelanggan`;
CREATE TABLE `pelanggan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  `no_hp` varchar(20) NOT NULL,
  `alamat` text DEFAULT NULL,
  `jumlah_bonus` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `no_hp` (`no_hp`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data untuk tabel `pelanggan`
INSERT INTO `pelanggan` (`id`, `nama`, `no_hp`, `alamat`, `jumlah_bonus`, `created_at`) VALUES
('1', 'Budi Santoso', '081299887766', 'Jl. Telang No. 5', '0', '2026-09-14 08:14:29'),
('2', 'Siti Rahayu', '081333444555', 'Jl. Telang Asri Blok B No. 4', '0', '2026-09-14 08:43:36'),
('3', 'Risky Tri Novianto', '081234567777', 'Telang gang 4 rumah kuning', '0', '2026-09-14 08:50:32'),
('4', 'Rezeki', '087999999999', 'Telang gang 4 rumah kuning', '0', '2026-09-14 08:54:09');

-- --------------------------------------------------------
-- Struktur tabel `transaksi`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `transaksi`;
CREATE TABLE `transaksi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode_transaksi` varchar(30) NOT NULL,
  `id_pelanggan` int(11) NOT NULL,
  `id_layanan` int(11) NOT NULL,
  `id_karyawan` int(11) DEFAULT NULL,
  `berat_jumlah` decimal(5,2) NOT NULL,
  `total_harga` decimal(10,2) NOT NULL,
  `metode_pembayaran` enum('Tunai','QRIS','Transfer') DEFAULT 'Tunai',
  `status_pembayaran` enum('Belum Lunas','Lunas') DEFAULT 'Lunas',
  `status_cucian` enum('Antrian','Dalam Proses','Selesai','Sudah Diambil','Batal') DEFAULT 'Antrian',
  `catatan` text DEFAULT NULL,
  `tanggal_masuk` datetime DEFAULT current_timestamp(),
  `tanggal_selesai` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode_transaksi` (`kode_transaksi`),
  KEY `fk_tx_pelanggan` (`id_pelanggan`),
  KEY `fk_tx_layanan` (`id_layanan`),
  KEY `fk_tx_karyawan` (`id_karyawan`),
  CONSTRAINT `fk_tx_karyawan` FOREIGN KEY (`id_karyawan`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_tx_layanan` FOREIGN KEY (`id_layanan`) REFERENCES `layanan` (`id`),
  CONSTRAINT `fk_tx_pelanggan` FOREIGN KEY (`id_pelanggan`) REFERENCES `pelanggan` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data untuk tabel `transaksi`
INSERT INTO `transaksi` (`id`, `kode_transaksi`, `id_pelanggan`, `id_layanan`, `id_karyawan`, `berat_jumlah`, `total_harga`, `metode_pembayaran`, `status_pembayaran`, `status_cucian`, `catatan`, `tanggal_masuk`, `tanggal_selesai`) VALUES
('1', 'UMB-20260914-75FD', '1', '1', '1', '3.50', '17500.00', 'Tunai', 'Lunas', 'Selesai', NULL, '2026-09-14 08:14:29', '2026-09-14 08:14:29'),
('2', 'UMB-20260914-8648', '2', '4', NULL, '4.00', '36000.00', 'Tunai', 'Belum Lunas', 'Selesai', '[Minta Dijemput] Alamat: Jl. Telang Asri Blok B No. 4. Catatan: Pakaian kerja jangan kena pemutih', '2026-09-14 08:43:36', '2026-09-14 08:43:36'),
('3', 'UMB-20260914-89B3', '4', '1', NULL, '1.00', '5000.00', 'QRIS', 'Belum Lunas', 'Dalam Proses', '[Minta Dijemput] Alamat Jemput: Telang gang 4 rumah kuning. Catatan: Yang putih sendirikan yaa', '2026-09-14 09:33:33', NULL);

SET FOREIGN_KEY_CHECKS = 1;
