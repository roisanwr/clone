<?php
// File: proyek_jaya/jabatan/tambah.php

// 1. Panggil Konfigurasi dan mulai session
require_once '../config.php';

// 2. Autentikasi: Pastikan pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'auth/login.php?error=2');
    exit;
}

// 3. Autorisasi: Hanya Super Admin dan Admin yang boleh menambah jabatan
$allowed_roles = ['super_admin', 'admin'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    $_SESSION['pesan_error'] = "ANDA TIDAK MEMILIKI HAK AKSES UNTUK MENAMBAH JABATAN.";
    header('Location: ' . BASE_URL . 'jabatan/'); // Redirect kembali ke daftar jabatan
    exit;
}
// Siapkan variabel untuk pesan notifikasi
$pesan_notifikasi_tambah = '';
if (isset($_SESSION['pesan_error_crud'])) {
    // Jika ada pesan error dari proses sebelumnya (misalnya, validasi gagal, duplikat)
    $pesan_notifikasi_tambah = "<div class='mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg shadow'>" . htmlspecialchars($_SESSION['pesan_error_crud']) . "</div>";
    unset($_SESSION['pesan_error_crud']); // Penting! Hapus pesan setelah disiapkan untuk ditampilkan agar tidak muncul terus.
}

// Ambil role pengguna dari session untuk sidebar
$user_role = $_SESSION['role'];

// 4. Memanggil komponen template: HEADER
require_once '../includes/header.php'; 

// 5. Memanggil komponen template: SIDEBAR (sesuai peran pengguna)
if ($user_role == 'super_admin') {
    require_once '../includes/sidebar_super_admin.php';
} elseif ($user_role == 'admin') {
    require_once '../includes/sidebar_admin.php';
}
?>

<main class="content-wrapper mt-16 md:ml-72">
    <div class="max-w-2xl mx-auto p-4 sm:p-6 lg:p-8">
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 sm:p-8">
            <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 dark:text-white mb-6">
                <i class="fas fa-plus-circle mr-2 text-blue-500"></i>Tambah Jabatan Baru
            </h1>
            <?php echo $pesan_notifikasi_tambah; // Tampilkan pesan notifikasi di sini ?>
            <form action="<?php echo BASE_URL; ?>jabatan/proses.php?aksi=tambah" method="POST">
                <div class="mb-5">
                    <label for="namajabatan" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nama Jabatan <span class="text-red-500">*</span></label>
                    <input type="text" name="namajabatan" id="namajabatan" required maxlength="50"
                           class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white placeholder-gray-400 dark:placeholder-gray-500"
                           placeholder="Contoh: Mandor Proyek, Tukang Batu">
                </div>

                <div class="mb-5">
                    <label for="gajipokok" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Gaji Pokok (per hari) <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 dark:text-gray-400 sm:text-sm">Rp</span>
                        </div>
                        <input type="number" name="gajipokok" id="gajipokok" required step="1" min="0"
                               class="w-full pl-10 pr-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white placeholder-gray-400 dark:placeholder-gray-500"
                               placeholder="Contoh: 150000">
                    </div>
                </div>

                <div class="mb-6">
                    <label for="tunjangan_lembur" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tunjangan Lembur (per hari) <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 dark:text-gray-400 sm:text-sm">Rp</span>
                        </div>
                        <input type="number" name="tunjangan_lembur" id="tunjangan_lembur" required step="1" min="0"
                               class="w-full pl-10 pr-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white placeholder-gray-400 dark:placeholder-gray-500"
                               placeholder="Contoh: 50000">
                    </div>
                </div>

                <div class="mt-8 flex items-center justify-end gap-x-4 border-t border-gray-200 dark:border-gray-700 pt-6">
                    <a href="<?php echo BASE_URL; ?>jabatan/" class="text-sm font-semibold leading-6 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition duration-150 ease-in-out">
                        Batal
                    </a>
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md shadow-sm transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800">
                        <i class="fas fa-save fa-fw mr-2"></i>
                        Simpan Jabatan
                    </button>
                </div>
            </form>
        </div> </div> </main>

<?php
// 7. Memanggil komponen template: FOOTER
require_once '../includes/footer.php'; 
?>