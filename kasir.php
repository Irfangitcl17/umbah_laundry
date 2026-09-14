<?php
session_start();

// Proteksi akses staf: hanya admin & karyawan
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['user']['role'] === 'pelanggan') {
    header("Location: dashboard_pelanggan.php");
    exit;
}

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/classes/Pelanggan.php';
require_once __DIR__ . '/classes/Layanan.php';
require_once __DIR__ . '/classes/Transaksi.php';

$db = (new Database())->getConnection();
$layananModel   = new Layanan($db);
$pelangganModel = new Pelanggan($db);
$transaksiModel = new Transaksi($db);

$successMsg = '';
$errorMsg   = '';

// 1. Aksi Tambah Transaksi Baru oleh Kasir
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buat_transaksi'])) {
    $nama        = trim($_POST['nama'] ?? '');
    $no_hp       = trim($_POST['no_hp'] ?? '');
    $id_layanan  = (int)($_POST['id_layanan'] ?? 0);
    $berat       = (float)($_POST['berat_jumlah'] ?? 0);
    $metode      = $_POST['metode'] ?? 'Tunai';
    $statusBayar = $_POST['status_pembayaran'] ?? 'Lunas';
    $catatan     = trim($_POST['catatan'] ?? '');

    if (!empty($nama) && !empty($no_hp) && $id_layanan > 0 && $berat > 0) {
        $pelangganId = $pelangganModel->findOrCreate($nama, $no_hp, $_POST['alamat'] ?? '');
        
        $kode = $transaksiModel->buatTransaksi(
            $pelangganId,
            $id_layanan,
            (int)$_SESSION['user']['id'],
            $berat,
            $metode,
            $statusBayar,
            $catatan
        );
        
        $successMsg = "Transaksi Berhasil Dibuat! No. Nota: {$kode}";
    } else {
        $errorMsg = "Harap lengkapi semua kolom transaksi dengan benar.";
    }
}

// 2. Aksi Update Status Cucian
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status_cucian'])) {
    $trxId     = (int)$_POST['transaksi_id'];
    $newStatus = $_POST['new_status'];
    if ($transaksiModel->updateStatus($trxId, $newStatus)) {
        $successMsg = "Status cucian berhasil diperbarui menjadi '{$newStatus}'!";
    } else {
        $errorMsg = "Gagal memperbarui status cucian.";
    }
}

// 3. Aksi Update Status Pembayaran (Belum Lunas -> Lunas)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status_bayar'])) {
    $trxId     = (int)$_POST['transaksi_id'];
    $newBayar  = $_POST['new_status_bayar'];
    if ($transaksiModel->updateStatusPembayaran($trxId, $newBayar)) {
        $successMsg = "Status pembayaran berhasil diubah menjadi '{$newBayar}'!";
    } else {
        $errorMsg = "Gagal mengubah status pembayaran.";
    }
}

$services = $layananModel->getAll();
$omset    = $transaksiModel->getOmsetHariIni();

$statusFilter = $_GET['status'] ?? '';
$searchQuery  = trim($_GET['q'] ?? '');

$daftarTransaksi = $transaksiModel->getDaftarTransaksi($statusFilter, $searchQuery);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Portal Kasir & POS - Umbah Laundry</title>
    <link rel="stylesheet" href="assets/css/chingu-style.css">
</head>
<body>

<div class="app-container">
    <!-- Header Kasir -->
    <header class="top-header">
        <div class="brand-wrapper">
            <div class="brand-logo-icon">🏪</div>
            <div>
                <a href="kasir.php" class="brand-title">PORTAL KASIR</a>
                <div class="brand-loc">Umbah Laundry • Telang Madura</div>
            </div>
        </div>
        <div class="user-badge">
            <div class="user-badge-avatar"><?= strtoupper(substr($_SESSION['user']['nama'], 0, 1)) ?></div>
            <div class="user-badge-name"><?= htmlspecialchars($_SESSION['user']['nama']) ?></div>
        </div>
    </header>

    <div class="content">
        <!-- Banner Info & Omset Hari Ini -->
        <div class="stat-card">
            <h4>Omset Hari Ini (Lunas)</h4>
            <div class="amount">Rp <?= number_format($omset, 0, ',', '.') ?></div>
            <div style="font-size: 0.78rem; opacity: 0.9; margin-top: 0.35rem; display: flex; justify-content: space-between;">
                <span>Petugas: <strong><?= htmlspecialchars($_SESSION['user']['nama']) ?></strong> (<?= ucfirst(htmlspecialchars($_SESSION['user']['role'])) ?>)</span>
                <a href="index.php" target="_blank" style="color: #fff; text-decoration: underline; font-weight: 600;">Lihat Landing Page ↗</a>
            </div>
        </div>

        <?php if ($successMsg): ?>
            <div class="alert alert-success">✅ <?= htmlspecialchars($successMsg) ?></div>
        <?php endif; ?>

        <?php if ($errorMsg): ?>
            <div class="alert alert-danger">⚠️ <?= htmlspecialchars($errorMsg) ?></div>
        <?php endif; ?>

        <!-- Form Input Transaksi Baru Kasir -->
        <details class="collapse-card" <?= (isset($_POST['buat_transaksi']) && $errorMsg) ? 'open' : '' ?>>
            <summary class="collapse-summary">
                <span><span class="badge-icon">+</span> Input Transaksi Cucian Kasir</span>
                <span>▼</span>
            </summary>
            <div class="collapse-body">
                <form method="POST" action="kasir.php">
                    <input type="hidden" name="buat_transaksi" value="1">
                    
                    <div class="form-group">
                        <label>Nomor WhatsApp Pelanggan</label>
                        <input type="tel" name="no_hp" required placeholder="08xxxxxxxxxx (untuk kirim nota WA)" value="<?= htmlspecialchars($_POST['no_hp'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label>Nama Pelanggan</label>
                        <input type="text" name="nama" required placeholder="Nama lengkap pelanggan" value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label>Alamat Pelanggan (Opsional)</label>
                        <input type="text" name="alamat" placeholder="cth. Perum Telang Indah Gg 3" value="<?= htmlspecialchars($_POST['alamat'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label>Pilih Layanan</label>
                        <select name="id_layanan" id="selectLayanan" required>
                            <option value="">-- Pilih Layanan Cucian --</option>
                            <?php foreach ($services as $srv): ?>
                                <option value="<?= $srv['id'] ?>" data-harga="<?= $srv['harga_per_satuan'] ?>" data-satuan="<?= htmlspecialchars($srv['satuan']) ?>">
                                    <?= htmlspecialchars($srv['nama_layanan']) ?> (Rp <?= number_format($srv['harga_per_satuan'], 0, ',', '.') ?>/<?= htmlspecialchars($srv['satuan']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                        <div class="form-group">
                            <label>Berat / Jumlah</label>
                            <input type="number" step="0.1" name="berat_jumlah" id="inputBerat" required placeholder="cth. 3.5" min="0.1" value="<?= htmlspecialchars($_POST['berat_jumlah'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Estimasi Total</label>
                            <input type="text" id="estimasiTotal" value="Rp 0" readonly style="background: #f8fafc; font-weight: 700; color: var(--primary);">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                        <div class="form-group">
                            <label>Metode Bayar</label>
                            <select name="metode">
                                <option value="Tunai">Tunai</option>
                                <option value="QRIS">QRIS</option>
                                <option value="Transfer">Transfer Bank</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Status Bayar</label>
                            <select name="status_pembayaran">
                                <option value="Lunas">Lunas</option>
                                <option value="Belum Lunas">Belum Lunas</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Catatan Khusus (Opsional)</label>
                        <input type="text" name="catatan" placeholder="cth. Pakaian putih dipisah, wangi mawar">
                    </div>

                    <button type="submit" class="btn-primary">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Simpan Transaksi Cucian
                    </button>
                </form>
            </div>
        </details>

        <!-- Pencarian Instan Transaksi -->
        <form method="GET" action="kasir.php" class="search-wrapper">
            <?php if (!empty($statusFilter)): ?>
                <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
            <?php endif; ?>
            <span class="search-icon">🔍</span>
            <input type="text" name="q" placeholder="Cari nama pelanggan, no HP, atau no nota..." value="<?= htmlspecialchars($searchQuery) ?>" onchange="this.form.submit()">
        </form>

        <!-- Status Filter Tabs -->
        <div class="tabs">
            <a href="kasir.php<?= !empty($searchQuery) ? '?q='.urlencode($searchQuery) : '' ?>" class="tab-btn <?= empty($statusFilter) ? 'active' : '' ?>">Semua</a>
            <a href="kasir.php?status=Antrian<?= !empty($searchQuery) ? '&q='.urlencode($searchQuery) : '' ?>" class="tab-btn <?= $statusFilter === 'Antrian' ? 'active' : '' ?>">Antrian</a>
            <a href="kasir.php?status=Dalam Proses<?= !empty($searchQuery) ? '&q='.urlencode($searchQuery) : '' ?>" class="tab-btn <?= $statusFilter === 'Dalam Proses' ? 'active' : '' ?>">Proses</a>
            <a href="kasir.php?status=Selesai<?= !empty($searchQuery) ? '&q='.urlencode($searchQuery) : '' ?>" class="tab-btn <?= $statusFilter === 'Selesai' ? 'active' : '' ?>">Selesai</a>
            <a href="kasir.php?status=Sudah Diambil<?= !empty($searchQuery) ? '&q='.urlencode($searchQuery) : '' ?>" class="tab-btn <?= $statusFilter === 'Sudah Diambil' ? 'active' : '' ?>">Sudah Diambil</a>
        </div>

        <!-- Daftar Transaksi Antrian Cucian -->
        <div class="order-list">
            <?php if (empty($daftarTransaksi)): ?>
                <div style="text-align: center; color: var(--text-muted); font-size: 0.85rem; padding: 2.5rem 1rem; background: var(--surface); border: 1px solid var(--border); border-radius: 12px;">
                    🧺 Tidak ada antrian cucian ditemukan.
                </div>
            <?php else: ?>
                <?php foreach ($daftarTransaksi as $trx): ?>
                    <?php
                        $badgeClass = match($trx['status_cucian']) {
                            'Antrian'       => 'badge-antrian',
                            'Dalam Proses'  => 'badge-proses',
                            'Selesai'       => 'badge-selesai',
                            'Sudah Diambil' => 'badge-diambil',
                            'Batal'         => 'badge-batal',
                            default         => 'badge-proses'
                        };
                        
                        $hpRaw = preg_replace('/[^0-9]/', '', $trx['no_hp']);
                        $hpWa = str_starts_with($hpRaw, '0') ? ('62' . substr($hpRaw, 1)) : $hpRaw;

                        $waText = "Halo kak *{$trx['nama_pelanggan']}*,\n"
                                . "Update cucian Anda di *Umbah Laundry*:\n"
                                . "📌 No Nota: *{$trx['kode_transaksi']}*\n"
                                . "🧺 Layanan: {$trx['nama_layanan']} ({$trx['berat_jumlah']} {$trx['satuan']})\n"
                                . "💵 Total: Rp " . number_format($trx['total_harga'], 0, ',', '.') . " (" . $trx['status_pembayaran'] . ")\n"
                                . "🏷️ Status Cucian: *{$trx['status_cucian']}*\n\n"
                                . "Terima kasih! 🙏";
                        $waUrl = "https://api.whatsapp.com/send?phone={$hpWa}&text=" . urlencode($waText);
                    ?>
                    <div class="order-card">
                        <div class="order-card-header">
                            <div>
                                <span class="order-code"><?= htmlspecialchars($trx['kode_transaksi']) ?></span>
                                <h5><?= htmlspecialchars($trx['nama_pelanggan']) ?></h5>
                                <div class="order-card-meta">
                                    📱 <a href="<?= $waUrl ?>" target="_blank" style="color: var(--primary); text-decoration: none; font-weight: 600;"><?= htmlspecialchars($trx['no_hp']) ?></a>
                                    <br>
                                    🧺 <?= htmlspecialchars($trx['nama_layanan']) ?> • <?= $trx['berat_jumlah'] ?> <?= htmlspecialchars($trx['satuan']) ?>
                                    <?php if (!empty($trx['catatan'])): ?>
                                        <br><span style="color: #d97706; font-size: 0.75rem;">📝 <?= htmlspecialchars($trx['catatan']) ?></span>
                                    <?php endif; ?>
                                    <br>
                                    🕒 <?= date('d/m/Y H:i', strtotime($trx['tanggal_masuk'])) ?>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div class="order-price">Rp <?= number_format($trx['total_harga'], 0, ',', '.') ?></div>
                                <div style="margin-top: 0.35rem;" class="order-card-badges">
                                    <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($trx['status_cucian']) ?></span>
                                    <?php if ($trx['status_pembayaran'] === 'Lunas'): ?>
                                        <span class="badge badge-lunas">Lunas</span>
                                    <?php else: ?>
                                        <span class="badge badge-belum-lunas">Belum Lunas</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Baris Aksi Kasir -->
                        <div class="order-card-actions">
                            <form method="POST" action="kasir.php" class="status-select-form">
                                <input type="hidden" name="update_status_cucian" value="1">
                                <input type="hidden" name="transaksi_id" value="<?= $trx['id'] ?>">
                                <select name="new_status" onchange="this.form.submit()" title="Ubah status cucian">
                                    <option value="Antrian" <?= $trx['status_cucian'] === 'Antrian' ? 'selected' : '' ?>>Antrian</option>
                                    <option value="Dalam Proses" <?= $trx['status_cucian'] === 'Dalam Proses' ? 'selected' : '' ?>>Proses</option>
                                    <option value="Selesai" <?= $trx['status_cucian'] === 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                                    <option value="Sudah Diambil" <?= $trx['status_cucian'] === 'Sudah Diambil' ? 'selected' : '' ?>>Diambil</option>
                                </select>
                            </form>

                            <div style="display: flex; gap: 0.35rem;">
                                <?php if ($trx['status_pembayaran'] === 'Belum Lunas'): ?>
                                    <form method="POST" action="kasir.php" style="display: inline;">
                                        <input type="hidden" name="update_status_bayar" value="1">
                                        <input type="hidden" name="transaksi_id" value="<?= $trx['id'] ?>">
                                        <input type="hidden" name="new_status_bayar" value="Lunas">
                                        <button type="submit" class="btn-sm btn-sm-nota" title="Tandai Pembayaran Lunas" onclick="return confirm('Tandai transaksi ini Lunas?')">
                                            💰 Lunaskan
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <a href="<?= $waUrl ?>" target="_blank" class="btn-sm btn-sm-whatsapp" title="Kirim WA">
                                    💬 WA
                                </a>

                                <button type="button" class="btn-sm btn-sm-nota" onclick="showReceipt(<?= htmlspecialchars(json_encode($trx)) ?>)">
                                    🧾 Nota
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bottom Navigation Bar Khusus Kasir -->
    <nav class="bottom-nav">
        <a href="kasir.php" class="nav-item active">
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
        <a href="laporan.php" class="nav-item">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            Laporan
        </a>
        <a href="logout.php" class="nav-item" onclick="return confirm('Keluar dari portal kasir?')">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
            Keluar
        </a>
    </nav>
</div>

<!-- Modal Struk Nota Digital -->
<div class="modal-overlay" id="receiptModal">
    <div class="modal-card">
        <div class="modal-header">
            <h5 class="modal-title">Struk Nota Digital</h5>
            <button type="button" class="modal-close" onclick="closeReceipt()">✕</button>
        </div>
        <div class="modal-body">
            <div id="printReceiptArea">
                <div class="receipt-paper">
                    <div class="receipt-header">
                        <div class="receipt-title">UMBAH LAUNDRY</div>
                        <div>Jl. Raya Telang, Kamal, Madura</div>
                        <div>WhatsApp: 0877-1589-0651</div>
                    </div>
                    
                    <div class="receipt-row">
                        <span>No. Nota:</span>
                        <strong id="rcptCode">-</strong>
                    </div>
                    <div class="receipt-row">
                        <span>Tanggal:</span>
                        <span id="rcptDate">-</span>
                    </div>
                    <div class="receipt-row">
                        <span>Pelanggan:</span>
                        <span id="rcptCustomer">-</span>
                    </div>
                    <div class="receipt-row">
                        <span>Kasir:</span>
                        <span id="rcptCashier">-</span>
                    </div>

                    <div class="receipt-divider"></div>

                    <div class="receipt-row">
                        <span id="rcptService">-</span>
                        <span id="rcptQty">-</span>
                    </div>

                    <div class="receipt-divider"></div>

                    <div class="receipt-row receipt-total">
                        <span>TOTAL:</span>
                        <span id="rcptTotal">-</span>
                    </div>
                    <div class="receipt-row">
                        <span>Pembayaran:</span>
                        <span id="rcptPayment">-</span>
                    </div>
                    <div class="receipt-row">
                        <span>Status Cucian:</span>
                        <strong id="rcptStatus">-</strong>
                    </div>

                    <div class="receipt-footer">
                        <div>Terima kasih atas kunjungan Anda!</div>
                        <div>Simpan struk ini untuk pengambilan cucian.</div>
                    </div>
                </div>
            </div>

            <div style="display: flex; gap: 0.5rem; margin-top: 1rem;" class="no-print">
                <button type="button" class="btn-primary" onclick="window.print()" style="min-height: 42px; font-size: 0.85rem;">
                    🖨️ Cetak Struk
                </button>
                <a id="rcptWaBtn" href="#" target="_blank" class="btn-outline btn-sm-whatsapp" style="text-decoration: none; min-height: 42px; font-size: 0.85rem; border: none;">
                    💬 Kirim WA
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    const selectLayanan = document.getElementById('selectLayanan');
    const inputBerat = document.getElementById('inputBerat');
    const estimasiTotal = document.getElementById('estimasiTotal');

    function hitungEstimasi() {
        const opt = selectLayanan.options[selectLayanan.selectedIndex];
        const harga = parseFloat(opt ? opt.getAttribute('data-harga') : 0) || 0;
        const berat = parseFloat(inputBerat.value) || 0;
        const total = Math.round(harga * berat);
        estimasiTotal.value = 'Rp ' + total.toLocaleString('id-ID');
    }

    if (selectLayanan && inputBerat) {
        selectLayanan.addEventListener('change', hitungEstimasi);
        inputBerat.addEventListener('input', hitungEstimasi);
    }

    const receiptModal = document.getElementById('receiptModal');

    function showReceipt(trx) {
        document.getElementById('rcptCode').textContent = trx.kode_transaksi;
        document.getElementById('rcptDate').textContent = trx.tanggal_masuk;
        document.getElementById('rcptCustomer').textContent = trx.nama_pelanggan + ' (' + trx.no_hp + ')';
        document.getElementById('rcptCashier').textContent = trx.nama_karyawan || 'Kasir';
        document.getElementById('rcptService').textContent = trx.nama_layanan;
        document.getElementById('rcptQty').textContent = trx.berat_jumlah + ' ' + trx.satuan;
        document.getElementById('rcptTotal').textContent = 'Rp ' + Number(trx.total_harga).toLocaleString('id-ID');
        document.getElementById('rcptPayment').textContent = trx.metode_pembayaran + ' (' + trx.status_pembayaran + ')';
        document.getElementById('rcptStatus').textContent = trx.status_cucian;

        let hpClean = trx.no_hp.replace(/\D/g, '');
        if (hpClean.startsWith('0')) hpClean = '62' + hpClean.substring(1);
        const text = `Halo kak *${trx.nama_pelanggan}*,\nBerikut struk dari *Umbah Laundry*:\nNo Nota: *${trx.kode_transaksi}*\nLayanan: ${trx.nama_layanan} (${trx.berat_jumlah} ${trx.satuan})\nTotal: Rp ${Number(trx.total_harga).toLocaleString('id-ID')} (${trx.status_pembayaran})\nStatus: *${trx.status_cucian}*\nTerima kasih!`;
        document.getElementById('rcptWaBtn').href = `https://api.whatsapp.com/send?phone=${hpClean}&text=${encodeURIComponent(text)}`;

        receiptModal.classList.add('active');
    }

    function closeReceipt() {
        receiptModal.classList.remove('active');
    }

    receiptModal.addEventListener('click', function(e) {
        if (e.target === receiptModal) closeReceipt();
    });
</script>

</body>
</html>
