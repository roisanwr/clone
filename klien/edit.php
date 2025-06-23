<?php
// File: proyek_jaya/klien/edit.php

// 1. Panggil Konfigurasi dan mulai session
require_once '../config.php';

// 2. Autentikasi: Pastikan pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'auth/login.php?error=2');
    exit;
}

// 3. Autorisasi: Hanya Super Admin dan Admin yang boleh mengedit klien
$allowed_roles = ['super_admin', 'admin'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    $_SESSION['pesan_error'] = "ANDA TIDAK MEMILIKI HAK AKSES UNTUK MENGEDIT DATA KLIEN.";
    header('Location: ' . BASE_URL . 'klien/'); 
    exit;
}

// Ambil role pengguna dari session untuk sidebar
$user_role = $_SESSION['role'];

// 4. Ambil ID Klien dari URL dan Validasi
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['pesan_error_crud'] = "ID Klien tidak valid atau tidak ditemukan.";
    header('Location: ' . BASE_URL . 'klien/');
    exit;
}
$id_klien_edit = intval($_GET['id']);

// 5. Ambil data klien yang akan diedit dari database
$sql_get_klien = "SELECT id_klien, nama_klien, alamat_klien, no_telp_klien, email_klien FROM klien WHERE id_klien = ?";
$stmt_get = mysqli_prepare($koneksi, $sql_get_klien);

$klien_lama = null; // Inisialisasi
if ($stmt_get) {
    mysqli_stmt_bind_param($stmt_get, "i", $id_klien_edit);
    mysqli_stmt_execute($stmt_get);
    $result_get = mysqli_stmt_get_result($stmt_get);
    $klien_lama = mysqli_fetch_assoc($result_get);
    mysqli_stmt_close($stmt_get);

    if (!$klien_lama) {
        $_SESSION['pesan_error_crud'] = "Data klien dengan ID " . $id_klien_edit . " tidak ditemukan.";
        header('Location: ' . BASE_URL . 'klien/');
        exit;
    }
} else {
    $_SESSION['pesan_error_crud'] = "Gagal mengambil data klien dari database: " . mysqli_error($koneksi);
    header('Location: ' . BASE_URL . 'klien/');
    exit;
}

// 6. Siapkan pesan notifikasi (jika ada dari proses.php setelah validasi gagal)
$pesan_notifikasi_edit = '';
if (isset($_SESSION['pesan_error_crud'])) {
    $pesan_notifikasi_edit = "<div class='mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg shadow'>" . htmlspecialchars($_SESSION['pesan_error_crud']) . "</div>";
    unset($_SESSION['pesan_error_crud']);
}
// Ambil data form yang gagal divalidasi (untuk fitur "sticky form")
// Default ke data lama jika tidak ada data dari session (pertama kali buka form edit)
$form_data = isset($_SESSION['form_data']) ? $_SESSION['form_data'] : $klien_lama; 
unset($_SESSION['form_data']); // Hapus setelah diambil


// 7. Memanggil komponen template: HEADER
require_once '../includes/header.php'; 

// 8. Memanggil komponen template: SIDEBAR (sesuai peran pengguna)
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
                <i class="fas fa-user-edit fa-fw mr-2 text-indigo-500"></i>Edit Data Klien: <?php echo htmlspecialchars($klien_lama['nama_klien']); ?>
            </h1>

            <?php echo $pesan_notifikasi_edit; // Tampilkan pesan error validasi jika ada ?>

            <form action="<?php echo BASE_URL; ?>klien/proses.php?aksi=edit&id=<?php echo $id_klien_edit; ?>" method="POST">
                <div class="mb-5">
                    <label for="nama_klien" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nama Klien <span class="text-red-500">*</span></label>
                    <input type="text" name="nama_klien" id="nama_klien" required maxlength="100"
                           value="<?php echo htmlspecialchars($form_data['nama_klien'] ?? ''); ?>"
                           class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white placeholder-gray-400 dark:placeholder-gray-500">
                </div>

                <div class="mb-5">
                    <label for="alamat_klien" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Alamat Klien</label>
                    <textarea name="alamat_klien" id="alamat_klien" rows="3"
                              class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white placeholder-gray-400 dark:placeholder-gray-500"><?php echo htmlspecialchars($form_data['alamat_klien'] ?? ''); ?></textarea>
                </div>

                <div class="mb-5">
                    <label for="no_telp_klien" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">No. Telepon</label>
                    <input type="text" name="no_telp_klien" id="no_telp_klien" maxlength="20"
                           value="<?php echo htmlspecialchars($form_data['no_telp_klien'] ?? ''); ?>"
                           class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white placeholder-gray-400 dark:placeholder-gray-500">
                </div>

                <div class="mb-6">
                    <label for="email_klien" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                    <input type="email" name="email_klien" id="email_klien" maxlength="100"
                           value="<?php echo htmlspecialchars($form_data['email_klien'] ?? ''); ?>"
                           class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white placeholder-gray-400 dark:placeholder-gray-500">
                </div>

                <div class="mt-8 flex items-center justify-end gap-x-4 border-t border-gray-200 dark:border-gray-700 pt-6">
                    <a href="<?php echo BASE_URL; ?>klien/" class="text-sm font-semibold leading-6 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition duration-150 ease-in-out">
                        Batal
                    </a>
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md shadow-sm transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                        <i class="fas fa-sync-alt fa-fw mr-2"></i>
                        Update Data Klien
                    </button>
                </div>
            </form>
        </div> </div> </main>

<?php
// 10. Memanggil komponen template: FOOTER
require_once '../includes/footer.php'; 
?>