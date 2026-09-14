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
require_once __DIR__ . '/classes/Transaksi.php';

$db = (new Database())->getConnection();
$transaksiModel = new Transaksi($db);

$preset = $_GET['preset'] ?? 'bulan_ini';
$tglMulai = $_GET['tgl_mulai'] ?? '';
$tglSelesai = $_GET['tgl_selesai'] ?? '';

$today = date('Y-m-d');

if ($preset === 'hari_ini') {
    $tglMulai = $today;
    $tglSelesai = $today;
} elseif ($preset === '7_hari') {
    $tglMulai = date('Y-m-d', strtotime('-6 days'));
    $tglSelesai = $today;
} elseif ($preset === 'bulan_ini') {
    $tglMulai = date('Y-m-01');
    $tglSelesai = date('Y-m-t');
} elseif ($preset === 'semua') {
    $tglMulai = '';
    $tglSelesai = '';
}

$statistik = $transaksiModel->getStatistikLaporan($tglMulai, $tglSelesai);

// Query transaksi sesuai filter tanggal
$sql = "SELECT t.*, p.nama AS nama_pelanggan, p.no_hp, l.nama_layanan, l.satuan 
        FROM transaksi t
        JOIN pelanggan p ON t.id_pelanggan = p.id
        JOIN layanan l ON t.id_layanan = l.id
        WHERE 1=1";
$params = [];

if (!empty($tglMulai)) {
    $sql .= " AND DATE(t.tanggal_masuk) >= :tglMulai";
    $params[':tglMulai'] = $tglMulai;
}
if (!empty($tglSelesai)) {
    $sql .= " AND DATE(t.tanggal_masuk) <= :tglSelesai";
    $params[':tglSelesai'] = $tglSelesai;
}

$sql .= " ORDER BY t.id DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$daftarTransaksi = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Laporan Keuangan & Operasional - Umbah Laundry</title>
    <link rel="stylesheet" href="assets/css/chingu-style.css">
</head>
<body>

<div class="app-container">
    <header class="top-header">
        <div class="brand-wrapper">
            <div class="brand-logo-icon">
                <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
            </div>
            <div>
                <a href="index.php" class="brand-title">LAPORAN KEUANGAN</a>
                <div class="brand-loc">Sistem Umbah Laundry</div>
            </div>
        </div>
        <div class="user-badge">
            <div class="user-badge-avatar"><?= strtoupper(substr($_SESSION['user']['nama'], 0, 1)) ?></div>
            <div class="user-badge-name"><?= htmlspecialchars($_SESSION['user']['nama']) ?></div>
        </div>
    </header>

    <div class="content">
        <!-- Preset Filter Tabs -->
        <div class="tabs">
            <a href="laporan.php?preset=hari_ini" class="tab-btn <?= $preset === 'hari_ini' ? 'active' : '' ?>">Hari Ini</a>
            <a href="laporan.php?preset=7_hari" class="tab-btn <?= $preset === '7_hari' ? 'active' : '' ?>">7 Hari Terakhir</a>
            <a href="laporan.php?preset=bulan_ini" class="tab-btn <?= $preset === 'bulan_ini' ? 'active' : '' ?>">Bulan Ini</a>
            <a href="laporan.php?preset=semua" class="tab-btn <?= $preset === 'semua' ? 'active' : '' ?>">Semua Data</a>
        </div>

        <!-- Filter Form Custom Rentang Tanggal -->
        <details class="collapse-card" style="margin-bottom: 1.25rem;">
            <summary class="collapse-summary">
                <span>Pilih Rentang Tanggal Khusus</span>
                <span>▼</span>
            </summary>
            <div class="collapse-body">
                <form method="GET" action="laporan.php">
                    <input type="hidden" name="preset" value="custom">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 0.75rem;">
                        <div class="form-group" style="margin-bottom:0;">
                            <label>Mulai Tanggal</label>
                            <input type="date" name="tgl_mulai" value="<?= htmlspecialchars($tglMulai) ?>" required>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label>Sampai Tanggal</label>
                            <input type="date" name="tgl_selesai" value="<?= htmlspecialchars($tglSelesai) ?>" required>
                        </div>
                    </div>
                    <button type="submit" class="btn-primary" style="min-height: 40px; padding: 0.5rem;">Terapkan Filter</button>
                </form>
            </div>
        </details>

        <!-- Ringkasan Statistik -->
        <div class="stat-card" style="margin-bottom: 1rem;">
            <h4>Total Omset (Lunas)</h4>
            <div class="amount">Rp <?= number_format($statistik['omset'], 0, ',', '.') ?></div>
            <div style="font-size: 0.8rem; opacity: 0.9; margin-top: 0.35rem;">
                Periode: <?= $tglMulai ? date('d M Y', strtotime($tglMulai)) : 'Awal' ?> s/d <?= $tglSelesai ? date('d M Y', strtotime($tglSelesai)) : 'Sekarang' ?>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.6rem; margin-bottom: 1.25rem;">
            <div class="stat-card-sm" style="text-align: center;">
                <div class="stat-label">Total Order</div>
                <div class="stat-val"><?= $statistik['total_transaksi'] ?></div>
            </div>
            <div class="stat-card-sm" style="text-align: center;">
                <div class="stat-label">Cucian Selesai</div>
                <div class="stat-val" style="color: #059669;"><?= $statistik['total_selesai'] ?></div>
            </div>
            <div class="stat-card-sm" style="text-align: center;">
                <div class="stat-label">Belum Lunas</div>
                <div class="stat-val" style="color: #e11d48;"><?= $statistik['total_belum_lunas'] ?></div>
            </div>
        </div>

        <!-- Tombol Cetak / Export Rekap -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.85rem;">
            <h4 style="font-size: 0.95rem; font-weight: 700;">Rincian Transaksi (<?= count($daftarTransaksi) ?>)</h4>
            <button type="button" class="btn-sm btn-sm-nota" onclick="window.print()" style="padding: 0.45rem 0.85rem;">
                Cetak Laporan
            </button>
        </div>

        <!-- Daftar Transaksi Pada Laporan -->
        <div class="order-list">
            <?php if (empty($daftarTransaksi)): ?>
                <div style="text-align: center; color: var(--text-muted); font-size: 0.85rem; padding: 2.5rem 1rem; background: var(--surface); border: 1px solid var(--border); border-radius: 12px;">
                    Tidak ada transaksi pada periode yang dipilih.
                </div>
            <?php else: ?>
                <?php foreach ($daftarTransaksi as $trx): ?>
                    <div class="order-card" style="padding: 0.85rem 1rem;">
                        <div class="order-card-header">
                            <div>
                                <span class="order-code"><?= htmlspecialchars($trx['kode_transaksi']) ?></span>
                                <h5 style="margin-top: 0.25rem;"><?= htmlspecialchars($trx['nama_pelanggan']) ?></h5>
                                <div class="order-card-meta">
                                    <?= htmlspecialchars($trx['nama_layanan']) ?> (<?= $trx['berat_jumlah'] ?> <?= htmlspecialchars($trx['satuan']) ?>)
                                    • <?= date('d/m/Y H:i', strtotime($trx['tanggal_masuk'])) ?>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div class="order-price">Rp <?= number_format($trx['total_harga'], 0, ',', '.') ?></div>
                                <div style="margin-top: 0.35rem;">
                                    <?php if ($trx['status_pembayaran'] === 'Lunas'): ?>
                                        <span class="badge badge-lunas">Lunas</span>
                                    <?php else: ?>
                                        <span class="badge badge-belum-lunas">Belum Lunas</span>
                                    <?php endif; ?>
                                </div>
                            </div>
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
        <a href="services.php" class="nav-item">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
            Layanan
        </a>
        <a href="laporan.php" class="nav-item active">
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
