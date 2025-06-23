<?php
// File: auth/login.php

// Pastikan BASE_URL sudah didefinisikan di config.php
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config.php';
}

// (Logika PHP Anda tidak berubah)
if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$display_message = '';
$message_class = '';

if (isset($_GET['error'])) {
    $error_code = $_GET['error'];
    $session_error_message = $_SESSION['pesan_error_login'] ?? ''; 
    $message_class = 'error-message';

    if ($error_code == 1) { $display_message = !empty($session_error_message) ? $session_error_message : 'Username atau password salah!'; }
    elseif ($error_code == 2) { $display_message = 'Anda harus login terlebih dahulu.'; }
    elseif ($error_code == 3) { $display_message = !empty($session_error_message) ? $session_error_message : 'Username dan password wajib diisi.'; }
    elseif ($error_code == 4) { $display_message = !empty($session_error_message) ? $session_error_message : 'Akun Anda tidak aktif. Hubungi Administrator.'; }
    elseif ($error_code == 99) { $display_message = !empty($session_error_message) ? $session_error_message : 'Terjadi kesalahan pada sistem.'; }
    else { $display_message = 'Terjadi kesalahan yang tidak diketahui.'; }
    unset($_SESSION['pesan_error_login']);

} elseif (isset($_GET['logout']) && $_GET['logout'] == 1) {
    $display_message = "Anda telah berhasil logout.";
    $message_class = 'success-message';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Azrina Construction</title>
    <link rel="icon" type="image/png" href="../assets/img/logo.png">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Arahkan ke file CSS yang baru dengan cache buster versi 3 -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/login_style.css?v=4">
</head>
<body>
    <div class="login-container">
        <!-- Panel Kiri untuk Branding -->
        <div class="branding-panel">
            <div class="content">
                <h1>Membangun Masa Depan.</h1>
                <p>Presisi dalam setiap detail, kualitas dalam setiap proyek.</p>
            </div>
        </div>

        <!-- Panel Kanan untuk Form Login -->
        <div class="form-panel">
            <div class="form-box">
                <a href="<?php echo BASE_URL; ?>index.php" class="back-to-home" title="Kembali ke Beranda">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <img src="<?php echo BASE_URL; ?>assets/img/azrina_logo.png" alt="Logo Azrina" class="login-logo" onerror="this.onerror=null;this.style.display='none';">
                
                <h2>Selamat Datang</h2>
                <p class="subtitle">Silakan masuk untuk melanjutkan</p>
                
                <?php if (!empty($display_message)): ?>
                    <div class="<?php echo $message_class; ?>"><?php echo htmlspecialchars($display_message); ?></div>
                <?php endif; ?>

                <form action="<?php echo BASE_URL; ?>auth/proses_login.php" method="POST" class="login-form">
                    <div class="input-group">
                        <i class="fas fa-user icon"></i>
                        <input type="text" name="username" id="username" placeholder="Username" required>
                    </div>
                    <div class="input-group">
                        <i class="fas fa-lock icon"></i>
                        <input type="password" name="password" id="password" placeholder="Password" required>
                    </div>
                    <button type="submit">Login</button>
                </form>

                <div class="register-link">
                    Belum punya akun? <a href="#">Hubungi Admin</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
