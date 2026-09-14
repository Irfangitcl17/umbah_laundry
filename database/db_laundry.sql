CREATE DATABASE IF NOT EXISTS db_laundry CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_laundry;

-- Tabel Pengguna (Admin, Karyawan, Pelanggan)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'karyawan', 'pelanggan') NOT NULL DEFAULT 'pelanggan',
    nama VARCHAR(100) NOT NULL,
    no_hp VARCHAR(20) NULL,
    alamat TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabel Layanan Laundry
CREATE TABLE IF NOT EXISTS layanan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_layanan VARCHAR(100) NOT NULL,
    kategori VARCHAR(50) NOT NULL,
    satuan VARCHAR(20) NOT NULL DEFAULT 'kg',
    harga_per_satuan DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabel Pelanggan
CREATE TABLE IF NOT EXISTS pelanggan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    no_hp VARCHAR(20) UNIQUE NOT NULL,
    alamat TEXT NULL,
    jumlah_bonus INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabel Transaksi Cucian
CREATE TABLE IF NOT EXISTS transaksi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_transaksi VARCHAR(30) UNIQUE NOT NULL,
    id_pelanggan INT NOT NULL,
    id_layanan INT NOT NULL,
    id_karyawan INT NULL DEFAULT NULL,
    berat_jumlah DECIMAL(5,2) NOT NULL,
    total_harga DECIMAL(10,2) NOT NULL,
    metode_pembayaran ENUM('Tunai', 'QRIS', 'Transfer') DEFAULT 'Tunai',
    status_pembayaran ENUM('Belum Lunas', 'Lunas') DEFAULT 'Lunas',
    status_cucian ENUM('Antrian', 'Dalam Proses', 'Selesai', 'Sudah Diambil', 'Batal') DEFAULT 'Antrian',
    catatan TEXT NULL,
    tanggal_masuk DATETIME DEFAULT CURRENT_TIMESTAMP,
    tanggal_selesai DATETIME NULL,
    CONSTRAINT fk_tx_pelanggan FOREIGN KEY (id_pelanggan) REFERENCES pelanggan(id) ON DELETE CASCADE,
    CONSTRAINT fk_tx_layanan FOREIGN KEY (id_layanan) REFERENCES layanan(id) ON DELETE RESTRICT,
    CONSTRAINT fk_tx_karyawan FOREIGN KEY (id_karyawan) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Seed Data (Password default: admin -> admin123, kasir1 -> kasir123)
INSERT INTO users (username, password, role, nama, no_hp) VALUES
('admin', '$2y$10$N2aWWw4TRspSiZWD/jK2k.4fg7SLzlEwRvowpi5FSYDINfT3SICFS', 'admin', 'Owner Umbah', '087715890651'),
('kasir1', '$2y$10$G7vbTKjpvu9U8ez3OL7q8eDUiys05YOqiN1K5ZEjW6/IUZyY3qPV2', 'karyawan', 'Kasir Umbah', '089876543210');

INSERT INTO layanan (nama_layanan, kategori, satuan, harga_per_satuan) VALUES
('Cuci Basah', 'Kiloan', 'kg', 5000.00),
('Cuci Kering', 'Kiloan', 'kg', 6500.00),
('Cuci Lipat', 'Kiloan', 'kg', 7500.00),
('Cuci Setrika', 'Kiloan', 'kg', 9000.00),
('Setrika Saja', 'Kiloan', 'kg', 5000.00);