<?php
session_start();

if (isset($_SESSION['user'])) {
    if ($_SESSION['user']['role'] === 'pelanggan') {
        header("Location: dashboard_pelanggan.php");
    } else {
        header("Location: kasir.php");
    }
    exit;
}

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/classes/User.php';

$errorMessage = '';
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $nama     = trim($_POST['nama'] ?? '');
    $no_hp    = trim($_POST['no_hp'] ?? '');
    $alamat   = trim($_POST['alamat'] ?? '');

    if (!empty($username) && !empty($password) && !empty($nama) && !empty($no_hp)) {
        try {
            $database = new Database();
            $db = $database->getConnection();
            $userModel = new User($db);

            if ($userModel->isUsernameExists($username)) {
                $errorMessage = "Username '{$username}' sudah digunakan, silakan pilih username lain.";
            } else {
                // Mendaftar sebagai role 'pelanggan' (Member Pelanggan Laundry)
                $isCreated = $userModel->register($username, $password, $nama, $no_hp, 'pelanggan', $alamat);
                if ($isCreated) {
                    $successMessage = "Akun Pelanggan berhasil dibuat! Silakan masuk untuk mulai memesan laundry.";
                } else {
                    $errorMessage = "Terjadi kesalahan saat mendaftarkan akun.";
                }
            }
        } catch (Exception $e) {
            $errorMessage = "Gangguan koneksi database: " . $e->getMessage();
        }
    } else {
        $errorMessage = "Harap lengkapi Nama, No. WhatsApp, Username, dan Password.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Daftar Akun Pelanggan - Umbah Laundry</title>
    <link rel="stylesheet" href="assets/css/chingu-style.css">
    <style>
        .register-wrapper {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 85vh;
            padding: 2rem 1.25rem;
            text-align: center;
        }
        .register-brand-icon {
            width: 72px;
            height: 72px;
            background: var(--primary-light);
            color: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            margin: 0 auto 1rem;
            box-shadow: 0 4px 14px rgba(2, 132, 199, 0.2);
        }
        .register-card {
            width: 100%;
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 1.75rem 1.35rem;
            box-shadow: var(--shadow-md);
            text-align: left;
        }
        .register-desc {
            font-size: 0.88rem;
            color: var(--text-muted);
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .auth-footer {
            margin-top: 1.25rem;
            text-align: center;
            font-size: 0.85rem;
            color: var(--text-muted);
        }
        .auth-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 700;
        }
    </style>
</head>
<body>

<div class="app-container" style="padding-bottom: 0;">
    <div class="register-wrapper">
        <div class="register-brand-icon">🧺</div>
        <h2 style="font-weight: 800; color: var(--text-main); margin-bottom: 0.25rem;">Daftar Akun Pelanggan</h2>
        <p class="register-desc">Pendaftaran Member Pelanggan <strong>Umbah Laundry</strong></p>

        <div class="register-card">
            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
            <?php endif; ?>

            <?php if (!empty($successMessage)): ?>
                <div class="alert alert-success">
                    <div><?= htmlspecialchars($successMessage) ?></div>
                    <div style="margin-top: 0.85rem;">
                        <a href="login.php" class="btn-primary" style="text-decoration:none; padding: 0.6rem 1.25rem; min-height: 40px; font-size: 0.88rem;">Masuk Sekarang</a>
                    </div>
                </div>
            <?php else: ?>
                <form method="POST" action="register.php">
                    <div class="form-group">
                        <label for="nama">Nama Lengkap</label>
                        <input type="text" id="nama" name="nama" placeholder="cth. Dian Wildan" value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" required autofocus>
                    </div>

                    <div class="form-group">
                        <label for="no_hp">Nomor WhatsApp Aktif</label>
                        <input type="tel" id="no_hp" name="no_hp" placeholder="cth. 081234567890 (untuk info status cucian)" value="<?= htmlspecialchars($_POST['no_hp'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="alamat">Alamat Lengkap (Untuk Antar-Jemput Cucian)</label>
                        <textarea id="alamat" name="alamat" rows="2" placeholder="cth. Perum Telang Indah Blok C No 12"><?= htmlspecialchars($_POST['alamat'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="username">Username Akun</label>
                        <input type="text" id="username" name="username" placeholder="Buat username untuk login" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                    </div>

                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label for="password">Password</label>
                        <div class="input-password-wrapper">
                            <input type="password" id="password" name="password" placeholder="Buat kata sandi minimal 6 karakter" required>
                            <button type="button" class="btn-toggle-pwd" id="btnTogglePwd">
                                <span id="eyeIcon">👁️</span>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-primary">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        Daftar Sebagai Pelanggan
                    </button>
                </form>
            <?php endif; ?>

            <div class="auth-footer">
                Sudah punya akun pelanggan? <a href="login.php">Masuk di sini</a>
                <div style="margin-top: 0.75rem;">
                    <a href="index.php" style="color: var(--text-muted); font-size: 0.8rem; font-weight: 500;">
                        &larr; Kembali ke Beranda Utama
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const btnToggle = document.getElementById('btnTogglePwd');
    const pwdInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');

    if (btnToggle && pwdInput) {
        btnToggle.addEventListener('click', function() {
            if (pwdInput.type === 'password') {
                pwdInput.type = 'text';
                eyeIcon.textContent = '🙈';
            } else {
                pwdInput.type = 'password';
                eyeIcon.textContent = '👁️';
            }
        });
    }
</script>

</body>
</html>