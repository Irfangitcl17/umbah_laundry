<?php
session_start();

// Jika sudah login, langsung arahkan ke dashboard yang sesuai
if (isset($_SESSION['user'])) {
    if ($_SESSION['user']['role'] === 'pelanggan') {
        header("Location: dashboard_pelanggan.php");
    } else {
        header("Location: kasir.php");
    }
    exit;
}

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/classes/Layanan.php';
require_once __DIR__ . '/classes/Transaksi.php';

$db = (new Database())->getConnection();
$layananModel   = new Layanan($db);
$transaksiModel = new Transaksi($db);

$services = $layananModel->getAll();

// Fitur Lacak Cucian Publik
$lacakKeyword = trim($_GET['lacak'] ?? '');
$hasilLacak = [];
if (!empty($lacakKeyword)) {
    $hasilLacak = $transaksiModel->lacakTransaksi($lacakKeyword);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Umbah Laundry - Solusi Cucian Bersih, Wangi & Cepat di Telang Madura</title>
    <meta name="description" content="Layanan laundry kiloan dan satuan higienis di Telang, Kamal, Madura. 1 Mesin 1 Pelanggan, wangi tahan lama, proses cepat.">
    <link rel="stylesheet" href="assets/css/chingu-style.css">
    <style>
        /* Landing Page Specific Styling */
        .landing-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.25rem;
            position: sticky;
            top: 0;
            background: var(--surface-glass);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            z-index: 50;
        }
        .landing-hero {
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            color: #ffffff;
            border-radius: var(--radius-xl);
            padding: 2.25rem 1.35rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 10px 25px -5px rgba(2, 132, 199, 0.3);
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0.3rem 0.75rem;
            border-radius: var(--radius-full);
            margin-bottom: 1rem;
            backdrop-filter: blur(4px);
        }
        .hero-title {
            font-size: 1.65rem;
            font-weight: 800;
            line-height: 1.25;
            margin-bottom: 0.75rem;
            letter-spacing: -0.5px;
        }
        .hero-desc {
            font-size: 0.88rem;
            opacity: 0.92;
            line-height: 1.5;
            margin-bottom: 1.5rem;
            max-width: 420px;
            margin-left: auto;
            margin-right: auto;
        }
        .hero-actions {
            display: flex;
            flex-direction: column;
            gap: 0.65rem;
            max-width: 320px;
            margin: 0 auto;
        }
        @media (min-width: 480px) {
            .hero-actions {
                flex-direction: row;
            }
        }
        .btn-hero-primary {
            background: #ffffff;
            color: var(--primary);
            font-weight: 800;
            padding: 0.8rem 1.25rem;
            border-radius: var(--radius-sm);
            text-decoration: none;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            box-shadow: 0 4px 14px rgba(0,0,0,0.15);
            transition: all 0.2s;
        }
        .btn-hero-primary:hover {
            background: #f8fafc;
            transform: translateY(-1px);
        }
        .btn-hero-secondary {
            background: rgba(255, 255, 255, 0.18);
            color: #ffffff;
            font-weight: 700;
            padding: 0.8rem 1.25rem;
            border-radius: var(--radius-sm);
            text-decoration: none;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            border: 1px solid rgba(255, 255, 255, 0.35);
        }
        .section-box {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 1.35rem 1.15rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-sm);
        }
        .section-title {
            font-size: 1.05rem;
            font-weight: 800;
            color: var(--text-main);
            margin-bottom: 0.35rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }
        .section-sub {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
        }
        .feature-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            margin-top: 0.75rem;
        }
        .feature-card {
            background: var(--bg-page);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 0.9rem;
            text-align: center;
        }
        .feature-icon {
            font-size: 1.6rem;
            margin-bottom: 0.35rem;
        }
        .feature-name {
            font-weight: 700;
            font-size: 0.85rem;
            color: var(--text-main);
            margin-bottom: 0.2rem;
        }
        .feature-desc {
            font-size: 0.72rem;
            color: var(--text-muted);
            line-height: 1.35;
        }
        .landing-footer {
            text-align: center;
            padding: 1.75rem 1rem;
            border-top: 1px solid var(--border);
            margin-top: 1.5rem;
            font-size: 0.78rem;
            color: var(--text-muted);
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

<div class="app-container" style="padding-bottom: 0;">
    <!-- Header Publik Umbah Laundry -->
    <header class="landing-header">
        <div class="brand-wrapper">
            <div class="brand-logo-icon">
                <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
            </div>
            <div>
                <a href="index.php" class="brand-title">UMBAH LAUNDRY</a>
                <div class="brand-loc">Telang, Kamal, Madura</div>
            </div>
        </div>
        <div style="display: flex; gap: 0.4rem;">
            <a href="login.php" class="btn-sm btn-outline" style="min-height: 36px; padding: 0.35rem 0.75rem; font-size: 0.82rem;">
                Masuk
            </a>
            <a href="register.php" class="btn-sm btn-primary" style="min-height: 36px; padding: 0.35rem 0.75rem; font-size: 0.82rem;">
                Daftar
            </a>
        </div>
    </header>

    <div class="content">
        <!-- Hero Banner Landing Page -->
        <div class="landing-hero">
            <div class="hero-badge">Laundry Terpercaya di Telang Madura</div>
            <h1 class="hero-title">Cucian Bersih, Wangi & Rapi Tanpa Repot</h1>
            <p class="hero-desc">
                Solusi cerdas kebutuhan laundry harian Anda. 1 mesin 1 pelanggan, deterjen berkualitas, wangi tahan lama, dengan kemudahan lacak status secara real-time.
            </p>
            <div class="hero-actions">
                <a href="register.php" class="btn-hero-primary">
                    Pesan Laundry Sekarang
                </a>
                <a href="#lacakSection" class="btn-hero-secondary">
                    Lacak Status Cucian
                </a>
            </div>
        </div>

        <!-- Section 1: Lacak Status Cucian Cepat (Tanpa Perlu Login) -->
        <div class="section-box" id="lacakSection">
            <div class="section-title">
                Lacak Status Cucian Anda
            </div>
            <div class="section-sub">
                Masukkan <strong>Nomor Nota (cth: UMB-...)</strong> atau <strong>Nomor WhatsApp</strong> untuk melihat progres cucian Anda secara langsung:
            </div>

            <form method="GET" action="index.php#lacakSection" style="margin-bottom: 0.75rem;">
                <div class="search-wrapper" style="margin-bottom: 0.75rem;">
                    <span class="search-icon" style="display:inline-flex;align-items:center;">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </span>
                    <input type="text" name="lacak" placeholder="Ketik No. Nota atau No. WhatsApp Anda..." value="<?= htmlspecialchars($lacakKeyword) ?>" required>
                </div>
                <button type="submit" class="btn-primary" style="min-height: 42px; font-size: 0.88rem; width: 100%;">
                    Cek Status Cucian Sekarang
                </button>
            </form>

            <!-- Hasil Pencarian Tracking -->
            <?php if (!empty($lacakKeyword)): ?>
                <div style="margin-top: 1.25rem;">
                    <?php if (empty($hasilLacak)): ?>
                        <div class="alert alert-danger" style="margin-bottom: 0;">
                            Tidak ditemukan cucian dengan nomor nota atau no WhatsApp "<strong><?= htmlspecialchars($lacakKeyword) ?></strong>". Mohon pastikan nomor yang dimasukkan sudah benar.
                        </div>
                    <?php else: ?>
                        <div style="font-weight: 700; font-size: 0.85rem; color: var(--primary); margin-bottom: 0.65rem;">
                            Ditemukan <?= count($hasilLacak) ?> data cucian untuk "<?= htmlspecialchars($lacakKeyword) ?>":
                        </div>
                        <div class="order-list">
                            <?php foreach ($hasilLacak as $trx): ?>
                                <?php
                                    $badgeClass = match($trx['status_cucian']) {
                                        'Antrian'       => 'badge-antrian',
                                        'Dalam Proses'  => 'badge-proses',
                                        'Selesai'       => 'badge-selesai',
                                        'Sudah Diambil' => 'badge-diambil',
                                        default         => 'badge-proses'
                                    };
                                    $s = $trx['status_cucian'];
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
                                                Pelanggan: <strong><?= htmlspecialchars($trx['nama_pelanggan']) ?></strong>
                                                <br>
                                                Berat/Jumlah: <?= $trx['berat_jumlah'] ?> <?= htmlspecialchars($trx['satuan']) ?>
                                                <br>
                                                Masuk: <?= date('d M Y H:i', strtotime($trx['tanggal_masuk'])) ?>
                                            </div>
                                        </div>
                                        <div style="text-align: right;">
                                            <div class="order-price">Rp <?= number_format($trx['total_harga'], 0, ',', '.') ?></div>
                                            <div style="margin-top: 0.35rem;" class="order-card-badges">
                                                <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($trx['status_cucian']) ?></span>
                                                <span class="badge <?= $trx['status_pembayaran'] === 'Lunas' ? 'badge-lunas' : 'badge-belum-lunas' ?>">
                                                    <?= htmlspecialchars($trx['status_pembayaran']) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Timeline Step -->
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
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Section 2: Simulasi Biaya Cucian (Kalkulator Interaktif) -->
        <div class="section-box">
            <div class="section-title">
                Kalkulator Estimasi Biaya
            </div>
            <div class="section-sub">
                Hitung perkiraan biaya laundry Anda sebelum memesan:
            </div>

            <div class="form-group">
                <label>Pilih Layanan</label>
                <select id="calcLayanan" onchange="hitungSimulasi()">
                    <?php foreach ($services as $srv): ?>
                        <option value="<?= $srv['harga_per_satuan'] ?>" data-satuan="<?= htmlspecialchars($srv['satuan']) ?>">
                            <?= htmlspecialchars($srv['nama_layanan']) ?> (Rp <?= number_format($srv['harga_per_satuan'], 0, ',', '.') ?>/<?= htmlspecialchars($srv['satuan']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                <div class="form-group">
                    <label>Perkiraan Berat (<span id="calcSatuanLabel">kg</span>)</label>
                    <input type="number" step="0.5" id="calcBerat" value="3" min="0.5" oninput="hitungSimulasi()">
                </div>
                <div class="form-group">
                    <label>Perkiraan Total</label>
                    <input type="text" id="calcTotal" value="Rp 0" readonly style="background: var(--bg-page); font-weight: 800; color: var(--primary);">
                </div>
            </div>

            <a href="register.php" class="btn-primary" style="margin-top: 0.5rem; text-decoration: none;">
                Pesan Layanan Ini Sekarang
            </a>
        </div>

        <!-- Section 3: Daftar Layanan & Tarif -->
        <div class="section-box">
            <div class="section-title">
                Daftar Layanan & Tarif
            </div>
            <div class="section-sub">
                Harga terjangkau, transparan, dan hasil cucian terjamin higienis:
            </div>

            <div class="order-list">
                <?php foreach ($services as $srv): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.65rem 0; border-bottom: 1px dashed var(--border);">
                        <div>
                            <div style="font-weight: 700; font-size: 0.92rem; color: var(--text-main);"><?= htmlspecialchars($srv['nama_layanan']) ?></div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">Kategori: <?= htmlspecialchars($srv['kategori']) ?></div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-weight: 800; color: var(--primary); font-size: 0.95rem;">
                                Rp <?= number_format($srv['harga_per_satuan'], 0, ',', '.') ?>
                            </div>
                            <div style="font-size: 0.72rem; color: var(--text-muted);">/ <?= htmlspecialchars($srv['satuan']) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Section 4: Mengapa Memilih Umbah Laundry? -->
        <div class="section-box">
            <div class="section-title">
                Keunggulan Umbah Laundry
            </div>
            <div class="section-sub">
                Komitmen kami memberikan standar kebersihan terbaik untuk pakaian Anda:
            </div>

            <div class="feature-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div class="feature-name">1 Mesin 1 Pelanggan</div>
                    <div class="feature-desc">Cucian Anda tidak pernah dicampur dengan pakaian orang lain. Higienis & aman.</div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path></svg>
                    </div>
                    <div class="feature-name">Wangi Tahan Lama</div>
                    <div class="feature-desc">Menggunakan deterjen dan pewangi premium khusus laundry berkualitas tinggi.</div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div class="feature-name">Tepat Waktu</div>
                    <div class="feature-desc">Jadwal selesai yang disiplin dan konsisten untuk kenyamanan aktivitas Anda.</div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                    </div>
                    <div class="feature-name">Tracking Online</div>
                    <div class="feature-desc">Bisa pantau progres cucian langsung dari smartphone kapan saja.</div>
                </div>
            </div>
        </div>

        <!-- Section 5: Lokasi & Hubungi Kami -->
        <div class="section-box">
            <div class="section-title">
                Lokasi & Jam Buka Outlet
            </div>
            <div class="section-sub">
                Kunjungi outlet kami atau hubungi kami untuk layanan antar-jemput:
            </div>

            <div style="font-size: 0.85rem; color: var(--text-main); line-height: 1.6; margin-bottom: 1rem;">
                <strong>Alamat:</strong> Jl. Raya Telang No. 12, Kamal, Bangkalan, Madura<br>
                <strong>Jam Operasional:</strong> Buka Setiap Hari (07.00 - 21.00 WIB)<br>
                <strong>WhatsApp:</strong> 0877-1589-0651
            </div>

            <a href="https://api.whatsapp.com/send?phone=6287715890651&text=Halo%20Umbah%20Laundry,%20saya%20mau%20tanya%20layanan%20laundry" target="_blank" class="btn-primary" style="background: #25d366; border: none; text-decoration: none;">
                Hubungi Kami via WhatsApp
            </a>
        </div>

        <!-- Footer -->
        <footer class="landing-footer">
            <div style="font-weight: 700; color: var(--text-main); margin-bottom: 0.35rem;">
                UMBAH LAUNDRY
            </div>
            <div>Solusi Cucian Bersih, Wangi & Rapi • Telang, Madura</div>
            <div style="margin-top: 1rem; padding-top: 0.75rem; border-top: 1px solid var(--border);">
                <a href="login.php" style="color: var(--text-muted); text-decoration: underline; font-size: 0.75rem;">
                    Portal Staf / Login Kasir
                </a>
            </div>
        </footer>
    </div>
</div>

<script>
    function hitungSimulasi() {
        const select = document.getElementById('calcLayanan');
        const opt = select.options[select.selectedIndex];
        const harga = parseFloat(opt.value) || 0;
        const satuan = opt.getAttribute('data-satuan') || 'kg';
        document.getElementById('calcSatuanLabel').textContent = satuan;

        const berat = parseFloat(document.getElementById('calcBerat').value) || 0;
        const total = Math.round(harga * berat);
        document.getElementById('calcTotal').value = 'Rp ' + total.toLocaleString('id-ID');
    }

    // Jalankan kalkulator pertama kali
    hitungSimulasi();
</script>

</body>
</html>