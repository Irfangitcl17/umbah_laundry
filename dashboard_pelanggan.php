<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// Jika staf/admin masuk ke sini, kita beri akses namun beri opsi ke portal kasir
$isStaff = in_array($_SESSION['user']['role'] ?? '', ['admin', 'karyawan']);

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/classes/Pelanggan.php';
require_once __DIR__ . '/classes/Layanan.php';
require_once __DIR__ . '/classes/Transaksi.php';

$db = (new Database())->getConnection();
$layananModel   = new Layanan($db);
$pelangganModel = new Pelanggan($db);
$transaksiModel = new Transaksi($db);

// Sinkronkan data pelanggan
$userNama   = $_SESSION['user']['nama'] ?? 'Pelanggan';
$userNoHp   = $_SESSION['user']['no_hp'] ?? '';
$userAlamat = $_SESSION['user']['alamat'] ?? '';

$pelangganId = $pelangganModel->findOrCreate($userNama, $userNoHp, $userAlamat);

$successMsg = '';
$errorMsg   = '';

// Proses Order Cucian Baru oleh Pelanggan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_laundry'])) {
    $id_layanan  = (int)($_POST['id_layanan'] ?? 0);
    $berat       = (float)($_POST['berat_jumlah'] ?? 0);
    $metode      = $_POST['metode'] ?? 'Tunai';
    $catatan     = trim($_POST['catatan'] ?? '');
    $metodeKirim = $_POST['metode_kirim'] ?? 'Antar Sendiri';
    $alamatJemput= trim($_POST['alamat_jemput'] ?? '');

    $catatanGabung = "[{$metodeKirim}]";
    if ($metodeKirim === 'Minta Dijemput' && !empty($alamatJemput)) {
        $catatanGabung .= " Alamat Jemput: {$alamatJemput}.";
    }
    if (!empty($catatan)) {
        $catatanGabung .= " Catatan: {$catatan}";
    }

    if ($id_layanan > 0 && $berat > 0) {
        $kode = $transaksiModel->buatTransaksi(
            $pelangganId,
            $id_layanan,
            null, // Belum ada kasir spesifik yang menangani (order online mandiri)
            $berat,
            $metode,
            'Belum Lunas', // Default belum lunas sampai dibayar saat serah terima
            $catatanGabung
        );

        $successMsg = "Pesanan Laundry Berhasil Dibuat! No. Nota Anda: {$kode}.";
    } else {
        $errorMsg = "Harap pilih jenis layanan dan masukkan perkiraan berat/jumlah cucian.";
    }
}

$services = $layananModel->getAll();
$riwayatPesanan = $transaksiModel->getTransaksiByPelangganId($pelangganId);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Dashboard Pelanggan - Umbah Laundry</title>
    <link rel="stylesheet" href="assets/css/chingu-style.css">
    <style>
        .customer-banner {
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            color: #ffffff;
            border-radius: var(--radius-lg);
            padding: 1.25rem 1.15rem;
            margin-bottom: 1.25rem;
            box-shadow: 0 8px 20px -4px rgba(2, 132, 199, 0.25);
        }
        .customer-greeting {
            font-size: 1.2rem;
            font-weight: 800;
            margin-bottom: 0.2rem;
        }
        .customer-subtext {
            font-size: 0.82rem;
            opacity: 0.9;
        }
        .order-step-timeline {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 0.75rem;
            padding-top: 0.75rem;
            border-top: 1px dashed var(--border);
            font-size: 0.72rem;
        }
        .step-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 0.2rem;
            flex: 1;
            color: var(--text-muted);
        }
        .step-dot {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: #e2e8f0;
            border: 2px solid #cbd5e1;
        }
        .step-item.active-step {
            color: var(--primary);
            font-weight: 700;
        }
        .step-item.active-step .step-dot {
            background: var(--primary);
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.2);
        }
        .step-item.passed-step {
            color: #059669;
        }
        .step-item.passed-step .step-dot {
            background: #059669;
            border-color: #a7f3d0;
        }
    </style>
</head>
<body>

<div class="app-container">
    <!-- Header Pelanggan -->
    <header class="top-header">
        <div class="brand-wrapper">
            <div class="brand-logo-icon">🧺</div>
            <div>
                <a href="dashboard_pelanggan.php" class="brand-title">UMBAH LAUNDRY</a>
                <div class="brand-loc">Portal Pelanggan</div>
            </div>
        </div>
        <div style="display: flex; align-items: center; gap: 0.5rem;">
            <div class="user-badge">
                <div class="user-badge-avatar"><?= strtoupper(substr($userNama, 0, 1)) ?></div>
                <div class="user-badge-name"><?= htmlspecialchars($userNama) ?></div>
            </div>
            <a href="logout.php" title="Keluar" onclick="return confirm('Keluar dari akun Anda?')" style="color: #ef4444; text-decoration: none; font-size: 1.1rem; padding: 0.2rem;">
                🚪
            </a>
        </div>
    </header>

    <div class="content">
        <?php if ($isStaff): ?>
            <div style="background: #fef3c7; color: #92400e; padding: 0.6rem 0.85rem; border-radius: 8px; font-size: 0.8rem; margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center;">
                <span>Akun Anda memiliki hak akses <strong>Staf Kasir</strong>.</span>
                <a href="kasir.php" style="color: #0284c7; font-weight: 700; text-decoration: underline;">Ke Portal Kasir &rarr;</a>
            </div>
        <?php endif; ?>

        <!-- Banner Selamat Datang Pelanggan -->
        <div class="customer-banner">
            <div class="customer-greeting">Halo, Kak <?= htmlspecialchars($userNama) ?>! 👋</div>
            <div class="customer-subtext">Mau cuci apa hari ini? Pesan sekarang dan nikmati cucian bersih, wangi & rapi.</div>
            <div style="margin-top: 0.75rem; display: flex; gap: 0.5rem;">
                <a href="#formOrder" class="btn-primary" style="background: #ffffff; color: var(--primary); box-shadow: none; min-height: 38px; font-size: 0.82rem; padding: 0.4rem 0.85rem;">
                    + Pesan Laundry Baru
                </a>
                <a href="https://api.whatsapp.com/send?phone=6287715890651&text=Halo%20Umbah%20Laundry,%20saya%20mau%20tanya-tanya%20layanan" target="_blank" class="btn-primary" style="background: #25d366; color: #fff; box-shadow: none; min-height: 38px; font-size: 0.82rem; padding: 0.4rem 0.85rem; border: none;">
                    💬 Chat WA
                </a>
            </div>
        </div>

        <?php if ($successMsg): ?>
            <div class="alert alert-success">✅ <?= htmlspecialchars($successMsg) ?></div>
        <?php endif; ?>

        <?php if ($errorMsg): ?>
            <div class="alert alert-danger">⚠️ <?= htmlspecialchars($errorMsg) ?></div>
        <?php endif; ?>

        <!-- Form Order Laundry Baru (Customer POV) -->
        <details class="collapse-card" id="formOrder" open>
            <summary class="collapse-summary">
                <span><span class="badge-icon">🧺</span> Buat Pesanan Laundry Baru</span>
                <span>▼</span>
            </summary>
            <div class="collapse-body">
                <form method="POST" action="dashboard_pelanggan.php">
                    <input type="hidden" name="order_laundry" value="1">
                    
                    <div class="form-group">
                        <label>Pilih Layanan Cucian</label>
                        <select name="id_layanan" id="custSelectLayanan" required>
                            <option value="">-- Pilih Jenis Layanan --</option>
                            <?php foreach ($services as $srv): ?>
                                <option value="<?= $srv['id'] ?>" data-harga="<?= $srv['harga_per_satuan'] ?>" data-satuan="<?= htmlspecialchars($srv['satuan']) ?>">
                                    <?= htmlspecialchars($srv['nama_layanan']) ?> (Rp <?= number_format($srv['harga_per_satuan'], 0, ',', '.') ?>/<?= htmlspecialchars($srv['satuan']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                        <div class="form-group">
                            <label>Perkiraan Berat/Jumlah</label>
                            <input type="number" step="0.1" name="berat_jumlah" id="custInputBerat" required placeholder="cth. 3 (kg/pcs)" min="0.1" value="1">
                        </div>
                        <div class="form-group">
                            <label>Estimasi Biaya</label>
                            <input type="text" id="custEstimasiTotal" value="Rp 0" readonly style="background: #f8fafc; font-weight: 700; color: var(--primary);">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Metode Penyerahan Cucian</label>
                        <select name="metode_kirim" id="metodeKirim" onchange="toggleAlamatJemput()">
                            <option value="Antar Sendiri">Antar Sendiri ke Outlet (Drop-off)</option>
                            <option value="Minta Dijemput">Minta Dijemput Petugas (Pickup)</option>
                        </select>
                    </div>

                    <div class="form-group" id="alamatJemputGroup" style="display: none;">
                        <label>Alamat Penjemputan / Rumah Anda</label>
                        <textarea name="alamat_jemput" rows="2" placeholder="Tuliskan alamat lengkap penjemputan cucian..."><?= htmlspecialchars($userAlamat) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Rencana Pembayaran</label>
                        <select name="metode">
                            <option value="Tunai">Tunai saat Serah Terima</option>
                            <option value="QRIS">QRIS</option>
                            <option value="Transfer">Transfer Bank</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Catatan Khusus Cucian (Opsional)</label>
                        <input type="text" name="catatan" placeholder="cth: Pakaian putih dipisah, jangan disikat kasar">
                    </div>

                    <button type="submit" class="btn-primary">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                        Kirim Pesanan Laundry
                    </button>
                </form>
            </div>
        </details>

        <!-- Daftar Riwayat & Status Cucian Saya -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin: 1.25rem 0 0.75rem;">
            <h4 style="font-size: 0.95rem; font-weight: 700;">Pesanan & Status Cucian Saya (<?= count($riwayatPesanan) ?>)</h4>
            <a href="dashboard_pelanggan.php" style="font-size: 0.75rem; color: var(--primary); text-decoration: none; font-weight: 600;">🔄 Segarkan</a>
        </div>

        <div class="order-list">
            <?php if (empty($riwayatPesanan)): ?>
                <div style="text-align: center; color: var(--text-muted); font-size: 0.85rem; padding: 2.5rem 1rem; background: var(--surface); border: 1px solid var(--border); border-radius: 12px;">
                    🧺 Belum ada pesanan cucian. Buat pesanan pertama Anda pada formulir di atas!
                </div>
            <?php else: ?>
                <?php foreach ($riwayatPesanan as $trx): ?>
                    <?php
                        $badgeClass = match($trx['status_cucian']) {
                            'Antrian'       => 'badge-antrian',
                            'Dalam Proses'  => 'badge-proses',
                            'Selesai'       => 'badge-selesai',
                            'Sudah Diambil' => 'badge-diambil',
                            'Batal'         => 'badge-batal',
                            default         => 'badge-proses'
                        };

                        $waText = "Halo Umbah Laundry,\n"
                                . "Saya mau konfirmasi pesanan cucian saya:\n"
                                . "No Nota: *{$trx['kode_transaksi']}*\n"
                                . "Nama: {$trx['nama_pelanggan']}\n"
                                . "Layanan: {$trx['nama_layanan']} ({$trx['berat_jumlah']} {$trx['satuan']})\n"
                                . "Mohon info perkiraan selesainya ya kak. Terima kasih!";
                        $waUrl = "https://api.whatsapp.com/send?phone=6287715890651&text=" . urlencode($waText);

                        // Visual timeline status
                        $s = $trx['status_cucian'];
                        $isAntrian = in_array($s, ['Antrian', 'Dalam Proses', 'Selesai', 'Sudah Diambil']);
                        $isProses  = in_array($s, ['Dalam Proses', 'Selesai', 'Sudah Diambil']);
                        $isSelesai = in_array($s, ['Selesai', 'Sudah Diambil']);
                        $isDiambil = ($s === 'Sudah Diambil');
                    ?>
                    <div class="order-card">
                        <div class="order-card-header">
                            <div>
                                <span class="order-code"><?= htmlspecialchars($trx['kode_transaksi']) ?></span>
                                <h5><?= htmlspecialchars($trx['nama_layanan']) ?></h5>
                                <div class="order-card-meta">
                                    ⚖️ Berat: <?= $trx['berat_jumlah'] ?> <?= htmlspecialchars($trx['satuan']) ?>
                                    <br>
                                    🕒 Tanggal Masuk: <?= date('d M Y H:i', strtotime($trx['tanggal_masuk'])) ?>
                                    <?php if (!empty($trx['catatan'])): ?>
                                        <br><span style="color: #d97706; font-size: 0.75rem;">📝 <?= htmlspecialchars($trx['catatan']) ?></span>
                                    <?php endif; ?>
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

                        <!-- Timeline Visual Progres Cucian -->
                        <div class="order-step-timeline">
                            <div class="step-item <?= $s === 'Antrian' ? 'active-step' : ($isProses ? 'passed-step' : '') ?>">
                                <div class="step-dot"></div>
                                <span>1. Antrian</span>
                            </div>
                            <div class="step-item <?= $s === 'Dalam Proses' ? 'active-step' : ($isSelesai ? 'passed-step' : '') ?>">
                                <div class="step-dot"></div>
                                <span>2. Dicuci</span>
                            </div>
                            <div class="step-item <?= $s === 'Selesai' ? 'active-step' : ($isDiambil ? 'passed-step' : '') ?>">
                                <div class="step-dot"></div>
                                <span>3. Selesai</span>
                            </div>
                            <div class="step-item <?= $isDiambil ? 'active-step passed-step' : '' ?>">
                                <div class="step-dot"></div>
                                <span>4. Diambil</span>
                            </div>
                        </div>

                        <!-- Aksi Pelanggan -->
                        <div class="order-card-actions">
                            <span style="font-size: 0.75rem; color: var(--text-muted);">
                                Status: <strong><?= htmlspecialchars($trx['status_cucian']) ?></strong>
                            </span>
                            <div style="display: flex; gap: 0.4rem;">
                                <a href="<?= $waUrl ?>" target="_blank" class="btn-sm btn-sm-whatsapp">
                                    💬 Tanya Outlet
                                </a>
                                <button type="button" class="btn-sm btn-sm-nota" onclick="showReceipt(<?= htmlspecialchars(json_encode($trx)) ?>)">
                                    🧾 Struk Nota
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Katalog Tarif Layanan -->
        <details class="collapse-card" style="margin-top: 1.5rem;">
            <summary class="collapse-summary">
                <span>📋 Katalog & Tarif Semua Layanan</span>
                <span>▼</span>
            </summary>
            <div class="collapse-body">
                <div class="order-list">
                    <?php foreach ($services as $srv): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0; border-bottom: 1px dashed var(--border);">
                            <div>
                                <div style="font-weight: 700; font-size: 0.88rem;"><?= htmlspecialchars($srv['nama_layanan']) ?></div>
                                <div style="font-size: 0.72rem; color: var(--text-muted);">Kategori: <?= htmlspecialchars($srv['kategori']) ?></div>
                            </div>
                            <div style="font-weight: 800; color: var(--primary); font-size: 0.92rem;">
                                Rp <?= number_format($srv['harga_per_satuan'], 0, ',', '.') ?> / <?= htmlspecialchars($srv['satuan']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </details>
    </div>

    <!-- Bottom Navigation Bar Khusus Pelanggan -->
    <nav class="bottom-nav">
        <a href="dashboard_pelanggan.php" class="nav-item active">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            Pesanan
        </a>
        <a href="#formOrder" class="nav-item">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Order
        </a>
        <a href="https://api.whatsapp.com/send?phone=6287715890651&text=Halo%20Umbah%20Laundry,%20saya%20mau%20tanya%20pesanan%20cucian%20saya" target="_blank" class="nav-item">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
            Chat Outlet
        </a>
        <a href="logout.php" class="nav-item" onclick="return confirm('Keluar dari akun Anda?')">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
            Keluar
        </a>
    </nav>
</div>

<!-- Modal Struk Nota Pelanggan -->
<div class="modal-overlay" id="receiptModal">
    <div class="modal-card">
        <div class="modal-header">
            <h5 class="modal-title">Nota Cucian Anda</h5>
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
                        <span>Nama:</span>
                        <span id="rcptCustomer">-</span>
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
                        <div>Tunjukkan nota ini saat pengambilan cucian.</div>
                    </div>
                </div>
            </div>

            <div style="display: flex; gap: 0.5rem; margin-top: 1rem;" class="no-print">
                <button type="button" class="btn-primary" onclick="window.print()" style="min-height: 40px; font-size: 0.85rem;">
                    🖨️ Cetak Nota
                </button>
                <button type="button" class="btn-outline" onclick="closeReceipt()" style="min-height: 40px; font-size: 0.85rem;">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    const custSelectLayanan = document.getElementById('custSelectLayanan');
    const custInputBerat = document.getElementById('custInputBerat');
    const custEstimasiTotal = document.getElementById('custEstimasiTotal');

    function hitungCustEstimasi() {
        const opt = custSelectLayanan.options[custSelectLayanan.selectedIndex];
        const harga = parseFloat(opt ? opt.getAttribute('data-harga') : 0) || 0;
        const berat = parseFloat(custInputBerat.value) || 0;
        const total = Math.round(harga * berat);
        custEstimasiTotal.value = 'Rp ' + total.toLocaleString('id-ID');
    }

    if (custSelectLayanan && custInputBerat) {
        custSelectLayanan.addEventListener('change', hitungCustEstimasi);
        custInputBerat.addEventListener('input', hitungCustEstimasi);
    }

    function toggleAlamatJemput() {
        const metode = document.getElementById('metodeKirim').value;
        const group = document.getElementById('alamatJemputGroup');
        group.style.display = (metode === 'Minta Dijemput') ? 'block' : 'none';
    }

    const receiptModal = document.getElementById('receiptModal');

    function showReceipt(trx) {
        document.getElementById('rcptCode').textContent = trx.kode_transaksi;
        document.getElementById('rcptDate').textContent = trx.tanggal_masuk;
        document.getElementById('rcptCustomer').textContent = trx.nama_pelanggan;
        document.getElementById('rcptService').textContent = trx.nama_layanan;
        document.getElementById('rcptQty').textContent = trx.berat_jumlah + ' ' + trx.satuan;
        document.getElementById('rcptTotal').textContent = 'Rp ' + Number(trx.total_harga).toLocaleString('id-ID');
        document.getElementById('rcptPayment').textContent = trx.metode_pembayaran + ' (' + trx.status_pembayaran + ')';
        document.getElementById('rcptStatus').textContent = trx.status_cucian;
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
