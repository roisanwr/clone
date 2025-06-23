<?php
// File: includes/sidebar_super_admin.php
// VERSI LENGKAP: Menu lengkap untuk Super Admin dengan logika menu aktif yang benar.

$menu_aktif = ''; // Default

// Logika untuk menentukan menu aktif berdasarkan URL
$current_uri_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base_url_path = rtrim(parse_url(BASE_URL, PHP_URL_PATH), '/');
$relative_path = ltrim(str_replace($base_url_path, '', $current_uri_path), '/');

if ($relative_path == 'dashboard.php' || $relative_path == '') {
    $menu_aktif = 'dashboard';
} elseif (strpos($relative_path, 'users/') === 0) {
    $menu_aktif = 'users';
} elseif (strpos($relative_path, 'jabatan/') === 0) {
    $menu_aktif = 'jabatan';
} elseif (strpos($relative_path, 'pekerja/') === 0) {
    $menu_aktif = 'pekerja';
} elseif (strpos($relative_path, 'klien/') === 0) {
    $menu_aktif = 'klien';
} elseif (strpos($relative_path, 'proyek/') === 0 || strpos($relative_path, 'penugasan/') === 0) {
    $menu_aktif = 'proyek';
} elseif (strpos($relative_path, 'absensi/') === 0) { // LOGIKA DIPERBAIKI: Menambahkan deteksi untuk absensi
    $menu_aktif = 'absensi';
} elseif (strpos($relative_path, 'gaji/') === 0) { 
    $menu_aktif = 'gaji';
}
?>
<aside id="sidebar" class="bg-gradient-to-b from-white to-gray-50 dark:from-gray-800 dark:to-gray-900 w-[85%] max-w-[320px] md:w-72 h-[calc(100vh-4rem)] fixed left-0 top-16 transform -translate-x-full md:translate-x-0 transition-all duration-300 ease-in-out shadow-lg z-40 overflow-y-auto">
    <div class="h-full flex flex-col overflow-y-auto">
        <!-- Sidebar Content -->
        <div class="flex-1 px-4 py-4">
            <!-- Profile Section -->
            <div class="flex flex-col items-center space-y-3 mb-6 -mt-2">
                <div class="relative group">
                    <img class="h-16 w-16 rounded-full object-cover ring-2 ring-blue-500 dark:ring-blue-400 p-1 transition-transform duration-300 group-hover:scale-105" 
                         src="https://ui-avatars.com/api/?name=<?php echo isset($_SESSION['username']) ? urlencode($_SESSION['username']) : 'SA'; ?>&background=random&color=fff&font-size=0.5" 
                         alt="Profile">
                    <div class="absolute inset-0 rounded-full bg-blue-500 opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
                </div>
                <div class="text-center">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Welcome,</p>
                    <p class="text-base font-bold bg-gradient-to-r from-blue-600 to-blue-800 dark:from-blue-400 dark:to-blue-600 bg-clip-text text-transparent"><?php echo htmlspecialchars($_SESSION['username']); ?></p>
                    <p class="text-xs text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $_SESSION['role']))); ?></p>
                </div>
            </div>

            <div class="border-t border-gray-200 dark:border-gray-700 mb-4"></div>

            <!-- Navigation Menu -->
            <nav class="space-y-1.5">
                <a href="<?php echo BASE_URL; ?>dashboard.php" class="<?php echo $menu_aktif == 'dashboard' ? 'bg-blue-50/80 dark:bg-blue-900/50 text-blue-600 dark:text-blue-300' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50/80 dark:hover:bg-gray-700/50'; ?> group flex items-center px-4 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 ease-in-out hover:scale-[1.02]">
                    <i class="fas fa-home fa-fw w-5 mr-3"></i> Dashboard
                </a>
                <a href="<?php echo BASE_URL; ?>users/" class="<?php echo $menu_aktif == 'users' ? 'bg-blue-50/80 dark:bg-blue-900/50 text-blue-600 dark:text-blue-300' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50/80 dark:hover:bg-gray-700/50'; ?> group flex items-center px-4 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 ease-in-out hover:scale-[1.02]">
                    <i class="fas fa-users-cog fa-fw w-5 mr-3"></i> Pengguna
                </a>
                
                <p class="px-4 pt-4 pb-1 text-xs font-semibold text-gray-400 uppercase tracking-wider">Master Data</p>

                <a href="<?php echo BASE_URL; ?>jabatan/" class="<?php echo $menu_aktif == 'jabatan' ? 'bg-blue-50/80 dark:bg-blue-900/50 text-blue-600 dark:text-blue-300' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50/80 dark:hover:bg-gray-700/50'; ?> group flex items-center px-4 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 ease-in-out hover:scale-[1.02]">
                    <i class="fas fa-id-badge fa-fw w-5 mr-3"></i> Jabatan 
                </a>
                <a href="<?php echo BASE_URL; ?>pekerja/" class="<?php echo $menu_aktif == 'pekerja' ? 'bg-blue-50/80 dark:bg-blue-900/50 text-blue-600 dark:text-blue-300' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50/80 dark:hover:bg-gray-700/50'; ?> group flex items-center px-4 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 ease-in-out hover:scale-[1.02]">
                    <i class="fas fa-hard-hat fa-fw w-5 mr-3"></i> Pekerja 
                </a>
                <a href="<?php echo BASE_URL; ?>klien/" class="<?php echo $menu_aktif == 'klien' ? 'bg-blue-50/80 dark:bg-blue-900/50 text-blue-600 dark:text-blue-300' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50/80 dark:hover:bg-gray-700/50'; ?> group flex items-center px-4 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 ease-in-out hover:scale-[1.02]">
                    <i class="fas fa-handshake fa-fw w-5 mr-3"></i> Klien 
                </a>

                <p class="px-4 pt-4 pb-1 text-xs font-semibold text-gray-400 uppercase tracking-wider">Manajemen Proyek</p>
                
                <a href="<?php echo BASE_URL; ?>proyek/" class="<?php echo $menu_aktif == 'proyek' ? 'bg-blue-50/80 dark:bg-blue-900/50 text-blue-600 dark:text-blue-300' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50/80 dark:hover:bg-gray-700/50'; ?> group flex items-center px-4 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 ease-in-out hover:scale-[1.02]">
                    <i class="fas fa-briefcase fa-fw w-5 mr-3"></i> Proyek & Tim
                </a>
                <!-- MENU DIPERBAIKI DAN DITAMBAHKAN -->
                <a href="<?php echo BASE_URL; ?>absensi/index.php" class="<?php echo $menu_aktif == 'absensi' ? 'bg-blue-50/80 dark:bg-blue-900/50 text-blue-600 dark:text-blue-300' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50/80 dark:hover:bg-gray-700/50'; ?> group flex items-center px-4 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 ease-in-out hover:scale-[1.02]">
                    <i class="fas fa-clipboard-list fa-fw w-5 mr-3"></i> Laporan Absensi
                </a>
                <a href="<?php echo BASE_URL; ?>gaji/index.php" class="<?php echo $menu_aktif == 'gaji' ? 'bg-blue-50/80 dark:bg-blue-900/50 text-blue-600 dark:text-blue-300' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50/80 dark:hover:bg-gray-700/50'; ?> group flex items-center px-4 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 ease-in-out hover:scale-[1.02]">
                    <i class="fas fa-clipboard-list fa-fw w-5 mr-3"></i> Laporan Gaji
                </a>
            </nav>
        </div>

        <!-- Bottom Section -->
        <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-4 mt-auto">
            <a href="<?php echo BASE_URL; ?>auth/logout.php"
               class="group flex items-center px-4 py-2.5 text-sm font-medium text-red-600 dark:text-red-400 rounded-lg hover:bg-red-50/80 dark:hover:bg-red-900/30 transition-all duration-200 ease-in-out hover:scale-[1.02]">
                <i class="fas fa-sign-out-alt fa-fw w-5 h-5 mr-3 transition-all duration-200 group-hover:scale-110 group-hover:-rotate-12"></i>
                <span class="transition-all duration-200 group-hover:translate-x-0.5">Logout</span>
            </a>
        </div>
    </div>
</aside>
<div id="sidebar-overlay" class="fixed inset-0 bg-black/50 backdrop-blur-sm opacity-0 invisible transition-opacity duration-300 ease-in-out z-30 md:hidden cursor-pointer"></div>
