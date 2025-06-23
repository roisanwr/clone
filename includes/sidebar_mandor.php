<?php
// File: includes/sidebar_mandor.php
// (Diasumsikan session sudah dimulai oleh file pemanggil)
// VERSI UPDATE: Menambahkan menu Riwayat Absensi dan memperbaiki logika menu aktif.

// Logika untuk menandai menu aktif
$menu_aktif_mandor = ''; 
$current_uri_path_mandor = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base_url_path_mandor = rtrim(parse_url(BASE_URL, PHP_URL_PATH), '/');
$relative_path_mandor = ltrim(str_replace($base_url_path_mandor, '', $current_uri_path_mandor), '/');


if (strpos($relative_path_mandor, 'proyek/proyek_saya.php') === 0 || 
           strpos($relative_path_mandor, 'penugasan/detail_tim.php') === 0 || 
           strpos($relative_path_mandor, 'penugasan/tambah.php') === 0 ||
           strpos($relative_path_mandor, 'penugasan/edit.php') === 0) {
    $menu_aktif_mandor = 'proyek_saya_mandor';
} elseif (strpos($relative_path_mandor, 'pekerja/tambah.php') === 0 && isset($_SESSION['role']) && $_SESSION['role'] === 'mandor') { 
    $menu_aktif_mandor = 'tambah_pekerja_mandor';
} elseif (strpos($relative_path_mandor, 'absensi/catat.php') === 0) { // Lebih spesifik
    $menu_aktif_mandor = 'catat_absensi_mandor';
} elseif (strpos($relative_path_mandor, 'absensi/riwayat.php') === 0 || strpos($relative_path_mandor, 'absensi/edit_riwayat.php') === 0) { // Baru
    $menu_aktif_mandor = 'riwayat_absensi_mandor';
}
?>
<aside id="sidebar" class="bg-gradient-to-b from-white to-gray-50 dark:from-gray-800 dark:to-gray-900 w-[85%] max-w-[320px] md:w-72 h-[calc(100vh-4rem)] fixed left-0 top-16 transform -translate-x-full md:translate-x-0 transition-all duration-300 ease-in-out shadow-lg z-40 overflow-y-auto"> 
    <div class="h-full flex flex-col overflow-y-auto">
        <div class="flex-1 px-4 py-4">
            <div class="flex flex-col items-center space-y-3 mb-6 -mt-2">
                <div class="relative group">
                    <img class="h-16 w-16 rounded-full object-cover ring-2 ring-blue-500 dark:ring-blue-400 p-1 transition-transform duration-300 group-hover:scale-105" 
                         src="https://ui-avatars.com/api/?name=<?php echo isset($_SESSION['username']) ? urlencode($_SESSION['username']) : 'M'; ?>&background=random&color=fff&font-size=0.5" 
                         alt="Profile">
                    <div class="absolute inset-0 rounded-full bg-blue-500 opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
                </div>
                <div class="text-center">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Welcome,</p>
                    <p class="text-base font-bold bg-gradient-to-r from-blue-600 to-blue-800 dark:from-blue-400 dark:to-blue-600 bg-clip-text text-transparent"><?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'User'; ?></p>
                    <p class="text-xs text-gray-500 dark:text-gray-400"><?php echo isset($_SESSION['role']) ? htmlspecialchars(ucwords(str_replace('_', ' ', $_SESSION['role']))) : 'Role'; ?></p>
                </div>
            </div>

            <div class="border-t border-gray-200 dark:border-gray-700 mb-4"></div>
            
            <p class="px-4 pt-2 pb-1 text-xs font-semibold text-gray-400 uppercase tracking-wider">Menu Utama</p>
            <nav class="space-y-1.5"> 
                <a href="<?php echo BASE_URL; ?>absensi/catat.php" 
                   class="<?php echo $menu_aktif_mandor == 'catat_absensi_mandor' ? 'bg-blue-50/80 dark:bg-blue-900/50 text-blue-600 dark:text-blue-300' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50/80 dark:hover:bg-gray-700/50'; ?> group flex items-center px-4 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 ease-in-out hover:scale-[1.02]">
                    <i class="fas fa-calendar-check fa-fw mr-3"></i> Catat Absensi 
                </a>
                <a href="<?php echo BASE_URL; ?>proyek/proyek_saya.php" 
                   class="<?php echo $menu_aktif_mandor == 'proyek_saya_mandor' ? 'bg-blue-50/80 dark:bg-blue-900/50 text-blue-600 dark:text-blue-300' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50/80 dark:hover:bg-gray-700/50'; ?> group flex items-center px-4 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 ease-in-out hover:scale-[1.02]">
                    <i class="fas fa-briefcase fa-fw mr-3"></i> Proyek Saya
                </a>
                 <a href="<?php echo BASE_URL; ?>pekerja/tambah.php" 
                   class="<?php echo $menu_aktif_mandor == 'tambah_pekerja_mandor' ? 'bg-blue-50/80 dark:bg-blue-900/50 text-blue-600 dark:text-blue-300' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50/80 dark:hover:bg-gray-700/50'; ?> group flex items-center px-4 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 ease-in-out hover:scale-[1.02]">
                    <i class="fas fa-user-plus fa-fw mr-3"></i> Tambah Pekerja
                </a>
                <a href="<?php echo BASE_URL; ?>absensi/riwayat.php" 
                   class="<?php echo $menu_aktif_mandor == 'riwayat_absensi_mandor' ? 'bg-blue-50/80 dark:bg-blue-900/50 text-blue-600 dark:text-blue-300' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50/80 dark:hover:bg-gray-700/50'; ?> group flex items-center px-4 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 ease-in-out hover:scale-[1.02]">
                    <i class="fas fa-history fa-fw mr-3"></i> Riwayat Absensi
                </a>
            </nav>
        </div>

        <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-4 mt-auto"> 
            <a href="<?php echo BASE_URL; ?>auth/logout.php" class="group flex items-center px-4 py-2.5 text-sm font-medium text-red-600 dark:text-red-400 rounded-lg hover:bg-red-50/80 dark:hover:bg-red-900/30 transition-all duration-200 ease-in-out hover:scale-[1.02]">
                <i class="fas fa-sign-out-alt w-5 h-5 mr-3 transition-all duration-200 group-hover:scale-110 group-hover:-rotate-12"></i>
                <span class="transition-all duration-200 group-hover:translate-x-0.5">Logout</span>
            </a>
        </div>
    </div>
</aside>

<div id="sidebar-overlay" class="fixed inset-0 bg-black/50 backdrop-blur-sm opacity-0 invisible transition-opacity duration-300 ease-in-out z-30 md:hidden cursor-pointer"></div>
