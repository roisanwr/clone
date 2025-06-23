<?php
// File: proyek_jaya/jabatan/edit.php

// 1. Panggil Konfigurasi dan mulai session
require_once '../config.php';

// 2. Autentikasi: Pastikan pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'auth/login.php?error=2');
    exit;
}

// 3. Autorisasi: Hanya Super Admin dan Admin yang boleh mengedit jabatan
$allowed_roles = ['super_admin', 'admin'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    $_SESSION['pesan_error'] = "ANDA TIDAK MEMILIKI HAK AKSES UNTUK MENGEDIT JABATAN.";
    header('Location: ' . BASE_URL . 'jabatan/'); // Redirect kembali ke daftar jabatan
    exit;
}

// Ambil role pengguna dari session untuk sidebar
$user_role = $_SESSION['role'];

// 4. Ambil ID Jabatan dari URL dan Validasi
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['pesan_error_crud'] = "ID Jabatan tidak valid atau tidak ditemukan.";
    header('Location: ' . BASE_URL . 'jabatan/');
    exit;
}
$id_jabatan_edit = intval($_GET['id']);

// 5. Ambil data jabatan yang akan diedit dari database
$sql_get_jabatan = "SELECT id_jabatan, namajabatan, gajipokok, tunjangan_lembur FROM jabatan WHERE id_jabatan = ?";
$stmt_get = mysqli_prepare($koneksi, $sql_get_jabatan);

if ($stmt_get) {
    mysqli_stmt_bind_param($stmt_get, "i", $id_jabatan_edit);
    mysqli_stmt_execute($stmt_get);
    $result_get = mysqli_stmt_get_result($stmt_get);
    $jabatan_lama = mysqli_fetch_assoc($result_get);
    mysqli_stmt_close($stmt_get);

    if (!$jabatan_lama) {
        $_SESSION['pesan_error_crud'] = "Data jabatan dengan ID " . $id_jabatan_edit . " tidak ditemukan.";
        header('Location: ' . BASE_URL . 'jabatan/');
        exit;
    }
} else {
    $_SESSION['pesan_error_crud'] = "Gagal mengambil data jabatan dari database.";
    header('Location: ' . BASE_URL . 'jabatan/');
    exit;
}

// 6. Siapkan pesan notifikasi (jika ada dari proses sebelumnya, misal validasi gagal di proses.php)
$pesan_notifikasi_edit = '';
if (isset($_SESSION['pesan_error_crud'])) {
    $pesan_notifikasi_edit = "<div class='mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg shadow'>" . htmlspecialchars($_SESSION['pesan_error_crud']) . "</div>";
    unset($_SESSION['pesan_error_crud']);
}

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
    <div class="max-w-2xl mx-auto p-4 sm:p-6 lg:p-8">
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 sm:p-8">
            <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 dark:text-white mb-6">
                <i class="fas fa-edit mr-2 text-indigo-500"></i>Edit Jabatan: <?php echo htmlspecialchars($jabatan_lama['namajabatan']); ?>
            </h1>

            <?php echo $pesan_notifikasi_edit; // Tampilkan pesan error jika ada ?>

            <form action="<?php echo BASE_URL; ?>jabatan/proses.php?aksi=edit&id=<?php echo $id_jabatan_edit; ?>" method="POST">
                <div class="mb-5">
                    <label for="namajabatan" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nama Jabatan <span class="text-red-500">*</span></label>
                    <input type="text" name="namajabatan" id="namajabatan" required maxlength="50"
                           value="<?php echo htmlspecialchars($jabatan_lama['namajabatan']); ?>"
                           class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white placeholder-gray-400 dark:placeholder-gray-500">
                </div>

                <div class="mb-5">
                    <label for="gajipokok" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Gaji Pokok (per hari) <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 dark:text-gray-400 sm:text-sm">Rp</span>
                        </div>
                        <input type="number" name="gajipokok" id="gajipokok" required step="1" min="0"
                            value="<?php echo htmlspecialchars($jabatan_lama['gajipokok']); ?>"
                            class="w-full pl-10 pr-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 <?php echo (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') ? 'bg-gray-100 dark:bg-gray-700 cursor-not-allowed' : 'dark:bg-gray-700 dark:text-white'; ?> placeholder-gray-400 dark:placeholder-gray-500"
                            <?php echo (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') ? 'readonly' : ''; // Tambahkan readonly jika admin ?>>
                    </div>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Admin tidak dapat mengubah gaji pokok.</p>
                    <?php endif; ?>
                </div>

                // Input Tunjangan Lembur
                <div class="mb-6">
                    <label for="tunjangan_lembur" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tunjangan Lembur (per hari) <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 dark:text-gray-400 sm:text-sm">Rp</span>
                        </div>
                        <input type="number" name="tunjangan_lembur" id="tunjangan_lembur" required step="1" min="0"
                            value="<?php echo htmlspecialchars($jabatan_lama['tunjangan_lembur']); ?>"
                            class="w-full pl-10 pr-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 <?php echo (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') ? 'bg-gray-100 dark:bg-gray-700 cursor-not-allowed' : 'dark:bg-gray-700 dark:text-white'; ?> placeholder-gray-400 dark:placeholder-gray-500"
                            <?php echo (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') ? 'readonly' : ''; // Tambahkan readonly jika admin ?>>
                    </div>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Admin tidak dapat mengubah tunjangan lembur.</p>
                    <?php endif; ?>
                </div>

                <div class="mt-8 flex items-center justify-end gap-x-4 border-t border-gray-200 dark:border-gray-700 pt-6">
                    <a href="<?php echo BASE_URL; ?>jabatan/" class="text-sm font-semibold leading-6 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition duration-150 ease-in-out">
                        Batal
                    </a>
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md shadow-sm transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                        <i class="fas fa-sync-alt fa-fw mr-2"></i>
                        Update Jabatan
                    </button>
                </div>
            </form>
        </div> </div> </main>

<?php
// 10. Memanggil komponen template: FOOTER
require_once '../includes/footer.php'; 
?>