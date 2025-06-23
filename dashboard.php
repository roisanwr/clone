<?php
// File: dashboard.php (di folder root proyek_jaya/)

// 1. Panggil Konfigurasi dan mulai session (jika belum)
require_once 'config.php'; // Ini akan memanggil session_start() dari config.php kita

// 2. AUTHENTICATION CHECK (SANGAT PENTING!)
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'auth/login.php?error=2'); // error=2 artinya "Anda harus login dulu"
    exit;
}

// 3. Ambil informasi pengguna dari session untuk personalisasi dan RBAC
$user_id = $_SESSION['user_id'];
$username = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Pengguna';
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : ''; // Misal: 'super_admin', 'admin', 'mandor'

// 4. Memanggil komponen template: HEADER
require_once 'includes/header.php'; 

// 5. Memanggil komponen template: SIDEBAR (sesuai peran pengguna)
if ($user_role == 'super_admin') {
    require_once 'includes/sidebar_super_admin.php';
} elseif ($user_role == 'admin') {
    require_once 'includes/sidebar_admin.php';
} elseif ($user_role == 'mandor') {
    require_once 'includes/sidebar_mandor.php';
} else {
    // Jika role tidak dikenali, bisa tampilkan sidebar default atau pesan error,
    // atau tidak tampilkan sidebar sama sekali. Untuk sekarang, kita diamkan.
    // echo "<p>Error: Role pengguna tidak dikenali, sidebar tidak dapat dimuat.</p>";
}
?>

<div id="main-content-wrapper" class="flex flex-col flex-1">

    <main class="flex-1 mt-16">
        <div class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8">

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
                    Selamat Datang di Dashboard Proyek Jaya, <?php echo $username; ?>!
                </h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Peran Anda: <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $user_role))); ?>
                </p>
                <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">Ini adalah halaman dashboard utama. Kontennya masih kosong, tapi kerangkanya sudah ada!</p>
                <p class="mt-2 text-xs text-gray-500">Waktu server saat ini: <?php echo date('d M Y, H:i:s'); ?> WIB</p>
            </div>

        </div>
    </main>

    <?php
    // [FOOTER] Footer dipanggil di dalam wrapper agar posisinya di paling bawah
    require_once 'includes/footer.php'; 
    ?>

</div>