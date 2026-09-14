<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['user']['role'] === 'pelanggan') {
    header("Location: dashboard_pelanggan.php");
    exit;
}

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/classes/Layanan.php';

$db = (new Database())->getConnection();
$layananModel = new Layanan($db);

$successMsg = '';
$errorMsg   = '';

// Tambah Layanan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_layanan'])) {
    $nama     = trim($_POST['nama_layanan'] ?? '');
    $kategori = trim($_POST['kategori'] ?? '');
    $satuan   = trim($_POST['satuan'] ?? '');
    $harga    = (float)($_POST['harga_per_satuan'] ?? 0);

    if (!empty($nama) && !empty($kategori) && !empty($satuan) && $harga > 0) {
        $sukses = $layananModel->tambahLayanan($nama, $kategori, $satuan, $harga);
        if ($sukses) {
            $successMsg = "Layanan '{$nama}' berhasil ditambahkan!";
        } else {
            $errorMsg = "Gagal menambahkan layanan ke database.";
        }
    } else {
        $errorMsg = "Harap isi semua data layanan dengan benar.";
    }
}

// Hapus Layanan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_id'])) {
    $hapusId = (int)$_POST['hapus_id'];
    if ($layananModel->isLayananDigunakan($hapusId)) {
        $errorMsg = "Layanan tidak dapat dihapus karena sudah memiliki riwayat transaksi pelanggan.";
    } else {
        $hapusSukses = $layananModel->hapusLayanan($hapusId);
        if ($hapusSukses) {
            $successMsg = "Layanan berhasil dihapus.";
        } else {
            $errorMsg = "Gagal menghapus layanan.";
        }
    }
}

$services = $layananModel->getAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Kelola Layanan - Umbah Laundry</title>
    <link rel="stylesheet" href="assets/css/chingu-style.css">
</head>
<body>

<div class="app-container">
    <header class="top-header">
        <div class="brand-wrapper">
            <div class="brand-logo-icon">
                <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
            </div>
            <div>
                <a href="index.php" class="brand-title">KELOLA LAYANAN</a>
                <div class="brand-loc">Umbah Laundry • Madura</div>
            </div>
        </div>
        <div class="user-badge">
            <div class="user-badge-avatar"><?= strtoupper(substr($_SESSION['user']['nama'], 0, 1)) ?></div>
            <div class="user-badge-name"><?= htmlspecialchars($_SESSION['user']['nama']) ?></div>
        </div>
    </header>

    <div class="content">
        <?php if ($successMsg): ?>
            <div class="alert alert-success"><?= htmlspecialchars($successMsg) ?></div>
        <?php endif; ?>

        <?php if ($errorMsg): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($errorMsg) ?></div>
        <?php endif; ?>

        <!-- Form Tambah Layanan Baru -->
        <details class="collapse-card">
            <summary class="collapse-summary">
                <span><span class="badge-icon">+</span> Tambah Layanan Baru</span>
                <span>▼</span>
            </summary>
            <div class="collapse-body">
                <form method="POST" action="services.php">
                    <input type="hidden" name="tambah_layanan" value="1">
                    <div class="form-group">
                        <label>Nama Layanan</label>
                        <input type="text" name="nama_layanan" required placeholder="cth. Cuci Kering Setrika Premium">
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                        <div class="form-group">
                            <label>Kategori</label>
                            <select name="kategori" required>
                                <option value="Kiloan">Kiloan</option>
                                <option value="Satuan">Satuan</option>
                                <option value="Express">Express</option>
                                <option value="Sepatu/Tas">Sepatu/Tas</option>
                                <option value="Bedcover">Bedcover</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Satuan</label>
                            <input type="text" name="satuan" required placeholder="kg / pcs / pasang" value="kg">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Harga per Satuan (Rp)</label>
                        <input type="number" name="harga_per_satuan" required placeholder="cth. 9000" min="1000" step="500">
                    </div>
                    <button type="submit" class="btn-primary">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Simpan Layanan Baru
                    </button>
                </form>
            </div>
        </details>

        <!-- Daftar Layanan Aktif -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
            <h4 style="font-size: 0.95rem; font-weight: 700;">Daftar Layanan Tersedia (<?= count($services) ?>)</h4>
        </div>

        <div class="order-list">
            <?php if (empty($services)): ?>
                <div style="text-align: center; color: var(--text-muted); font-size: 0.85rem; padding: 2.5rem 1rem;">
                    Belum ada layanan terdaftar.
                </div>
            <?php else: ?>
                <?php foreach ($services as $srv): ?>
                    <div class="order-card" style="padding: 1rem 1.15rem;">
                        <div class="order-card-header">
                            <div>
                                <span class="order-code"><?= htmlspecialchars($srv['kategori']) ?></span>
                                <h5><?= htmlspecialchars($srv['nama_layanan']) ?></h5>
                                <div class="order-card-meta">
                                    Satuan: <strong><?= htmlspecialchars($srv['satuan']) ?></strong>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div class="order-price">
                                    Rp <?= number_format($srv['harga_per_satuan'], 0, ',', '.') ?>
                                </div>
                                <div style="font-size: 0.72rem; color: var(--text-muted);">
                                    / <?= htmlspecialchars($srv['satuan']) ?>
                                </div>
                            </div>
                        </div>

                        <div class="order-card-actions">
                            <span style="font-size: 0.75rem; color: var(--text-muted);">
                                ID Layanan: #<?= $srv['id'] ?>
                            </span>
                            <form method="POST" action="services.php" onsubmit="return confirm('Hapus layanan <?= htmlspecialchars(addslashes($srv['nama_layanan'])) ?>?')">
                                <input type="hidden" name="hapus_id" value="<?= $srv['id'] ?>">
                                <button type="submit" class="btn-sm btn-sm-danger">
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bottom Navigation Bar dengan SVG Icons -->
    <nav class="bottom-nav">
        <a href="kasir.php" class="nav-item">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            Kasir
        </a>
        <a href="services.php" class="nav-item active">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
            Layanan
        </a>
        <a href="laporan.php" class="nav-item">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            Laporan
        </a>
        <a href="logout.php" class="nav-item" onclick="return confirm('Apakah Anda yakin ingin keluar dari sistem?')">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
            Keluar
        </a>
    </nav>
</div>

</body>
</html>