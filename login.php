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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['username'] ?? '');
    $password   = $_POST['password'] ?? '';

    if (!empty($identifier) && !empty($password)) {
        try {
            $database = new Database();
            $db = $database->getConnection();
            $userModel = new User($db);

            $userData = $userModel->login($identifier, $password);

            if ($userData) {
                $_SESSION['user'] = [
                    'id'       => $userData['id'],
                    'username' => $userData['username'],
                    'nama'     => $userData['nama'],
                    'no_hp'    => $userData['no_hp'],
                    'alamat'   => $userData['alamat'] ?? '',
                    'role'     => $userData['role']
                ];
                
                if ($userData['role'] === 'pelanggan') {
                    header("Location: dashboard_pelanggan.php");
                } else {
                    header("Location: kasir.php");
                }
                exit;
            } else {
                $errorMessage = "Username/No HP atau password salah.";
            }
        } catch (Exception $e) {
            $errorMessage = "Terjadi gangguan sistem: " . $e->getMessage();
        }
    } else {
        $errorMessage = "Harap masukkan username/no HP dan password.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Login - Umbah Laundry</title>
    <link rel="stylesheet" href="assets/css/chingu-style.css">
    <style>
        .login-wrapper {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 85vh;
            padding: 2rem 1.25rem;
            text-align: center;
        }
        .login-brand-icon {
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
        .login-card {
            width: 100%;
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 1.75rem 1.35rem;
            box-shadow: var(--shadow-md);
            text-align: left;
        }
        .login-desc {
            font-size: 0.88rem;
            color: var(--text-muted);
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 1.25rem 0;
            color: var(--text-muted);
            font-size: 0.8rem;
        }
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid var(--border);
        }
        .divider span {
            padding: 0 0.75rem;
        }
    </style>
</head>
<body>

<div class="app-container" style="padding-bottom: 0;">
    <div class="login-wrapper">
        <div class="login-brand-icon">
            <svg width="34" height="34" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
        </div>
        <h2 style="font-weight: 800; color: var(--text-main); margin-bottom: 0.25rem;">Selamat Datang</h2>
        <p class="login-desc">Sistem Kasir & Operasional <strong>Umbah Laundry</strong></p>

        <div class="login-card">
            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
            <?php endif; ?>

            <form method="POST" action="login.php" id="loginForm">
                <div class="form-group">
                    <label for="username">Username atau No. WhatsApp</label>
                    <input type="text" id="username" name="username" placeholder="cth: admin atau 081234567890" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
                </div>

                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label for="password">Password</label>
                    <div class="input-password-wrapper">
                        <input type="password" id="password" name="password" placeholder="Masukkan password" required>
                        <button type="button" class="btn-toggle-pwd" id="btnTogglePwd" title="Tampilkan/Sembunyikan Kata Sandi">
                            <span id="eyeText" style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted);">LIHAT</span>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-primary">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                    </svg>
                    Masuk ke Sistem
                </button>
            </form>

            <div class="divider">
                <span>atau</span>
            </div>

            <a href="register.php" class="btn-outline">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
                Daftar Akun Pelanggan Baru
            </a>

            <div style="text-align: center; margin-top: 1.25rem;">
                <a href="index.php" style="color: var(--text-muted); font-size: 0.82rem; text-decoration: none; font-weight: 600;">
                    &larr; Kembali ke Beranda Utama
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    // Toggle Password Visibility
    const btnToggle = document.getElementById('btnTogglePwd');
    const pwdInput = document.getElementById('password');
    const eyeText = document.getElementById('eyeText');

    btnToggle.addEventListener('click', function() {
        if (pwdInput.type === 'password') {
            pwdInput.type = 'text';
            eyeText.textContent = 'TUTUP';
        } else {
            pwdInput.type = 'password';
            eyeText.textContent = 'LIHAT';
        }
    });
</script>

</body>
</html>