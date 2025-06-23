<?php
// File: proyek_jaya/klien/tambah.php

// 1. Panggil Konfigurasi dan mulai session
require_once '../config.php';

// 2. Autentikasi: Pastikan pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'auth/login.php?error=2');
    exit;
}

// 3. Autorisasi: Hanya Super Admin dan Admin yang boleh menambah klien
$allowed_roles = ['super_admin', 'admin'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    $_SESSION['pesan_error'] = "ANDA TIDAK MEMILIKI HAK AKSES UNTUK MENAMBAH DATA KLIEN.";
    header('Location: ' . BASE_URL . 'klien/'); // Redirect kembali ke daftar klien
    exit;
}

// Ambil role pengguna dari session untuk sidebar
$user_role = $_SESSION['role'];

// 4. Siapkan pesan notifikasi (jika ada dari proses.php setelah validasi gagal)
$pesan_notifikasi_tambah = '';
if (isset($_SESSION['pesan_error_crud'])) {
    $pesan_notifikasi_tambah = "<div class='mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg shadow'>" . htmlspecialchars($_SESSION['pesan_error_crud']) . "</div>";
    unset($_SESSION['pesan_error_crud']); 
}

// Ambil data form yang gagal divalidasi (untuk fitur "sticky form")
$form_data = isset($_SESSION['form_data']) ? $_SESSION['form_data'] : [];
unset($_SESSION['form_data']); // Hapus setelah diambil


// 5. Memanggil komponen template: HEADER
require_once '../includes/header.php'; 

// 6. Memanggil komponen template: SIDEBAR (sesuai peran pengguna)
// Pastikan di sidebar_super_admin.php dan sidebar_admin.php sudah ada logika untuk $menu_aktif = 'klien';
if ($user_role == 'super_admin') {
    require_once '../includes/sidebar_super_admin.php';
} elseif ($user_role == 'admin') {
    require_once '../includes/sidebar_admin.php';
}
?>

<main class="content-wrapper mt-16 md:ml-72">
    <div class="max-w-3xl mx-auto p-4 sm:p-6 lg:p-8"> 

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 sm:p-8">
            <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 dark:text-white mb-6">
                <i class="fas fa-user-tie fa-fw mr-2 text-blue-500"></i>Tambah Klien Baru
            </h1>

            <?php echo $pesan_notifikasi_tambah; // Tampilkan pesan error validasi jika ada ?>

            <form action="<?php echo BASE_URL; ?>klien/proses.php?aksi=tambah" method="POST">
                <div class="mb-5">
                    <label for="nama_klien" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nama Klien <span class="text-red-500">*</span></label>
                    <input type="text" name="nama_klien" id="nama_klien" required maxlength="100"
                           value="<?php echo htmlspecialchars($form_data['nama_klien'] ?? ''); // Sticky form ?>"
                           class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white placeholder-gray-400 dark:placeholder-gray-500"
                           placeholder="Masukkan nama perusahaan atau perorangan klien">
                </div>

                <div class="mb-5">
                    <label for="alamat_klien" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Alamat Klien</label>
                    <textarea name="alamat_klien" id="alamat_klien" rows="3"
                              class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white placeholder-gray-400 dark:placeholder-gray-500"
                              placeholder="Masukkan alamat lengkap klien (opsional)"><?php echo htmlspecialchars($form_data['alamat_klien'] ?? ''); // Sticky form ?></textarea>
                </div>

                <div class="mb-5">
                    <label for="no_telp_klien" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">No. Telepon</label>
                    <input type="text" name="no_telp_klien" id="no_telp_klien" maxlength="20"
                           value="<?php echo htmlspecialchars($form_data['no_telp_klien'] ?? ''); // Sticky form ?>"
                           class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white placeholder-gray-400 dark:placeholder-gray-500"
                           placeholder="Contoh: 021-1234567 atau 0812... (opsional)">
                </div>

                <div class="mb-6">
                    <label for="email_klien" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                    <input type="email" name="email_klien" id="email_klien" maxlength="100"
                           value="<?php echo htmlspecialchars($form_data['email_klien'] ?? ''); // Sticky form ?>"
                           class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white placeholder-gray-400 dark:placeholder-gray-500"
                           placeholder="Contoh: email@klien.com (opsional)">
                           </div>

                <div class="mt-8 flex items-center justify-end gap-x-4 border-t border-gray-200 dark:border-gray-700 pt-6">
                    <a href="<?php echo BASE_URL; ?>klien/" class="text-sm font-semibold leading-6 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition duration-150 ease-in-out">
                        Batal
                    </a>
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md shadow-sm transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800">
                        <i class="fas fa-save fa-fw mr-2"></i>
                        Simpan Data Klien
                    </button>
                </div>
            </form>
        </div> </div> </main>

<?php
// 8. Memanggil komponen template: FOOTER
require_once '../includes/footer.php'; 
?>