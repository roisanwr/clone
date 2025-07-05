<?php
// File: proyek_jaya/pekerja/edit.php

// 1. Panggil Konfigurasi dan mulai session
require_once '../config.php';

// 2. Autentikasi: Pastikan pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'auth/login.php?error=2');
    exit;
}

// 3. Autorisasi: Hanya Super Admin dan Admin yang boleh mengedit pekerja
$allowed_roles = ['super_admin', 'admin'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    $_SESSION['pesan_error'] = "ANDA TIDAK MEMILIKI HAK AKSES UNTUK MENGEDIT DATA PEKERJA.";
    header('Location: ' . BASE_URL . 'pekerja/'); 
    exit;
}

// Ambil role pengguna dari session untuk sidebar
$user_role = $_SESSION['role'];

// 4. Ambil ID Pekerja dari URL dan Validasi
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['pesan_error_crud'] = "ID Pekerja tidak valid atau tidak ditemukan.";
    header('Location: ' . BASE_URL . 'pekerja/');
    exit;
}
$id_pekerja_edit = intval($_GET['id']);

// 5. Ambil data pekerja yang akan diedit dari database
$sql_get_pekerja = "SELECT id_pekerja, namapekerja, id_jabatan, no_hp, no_rek, is_active FROM pekerja WHERE id_pekerja = ?";
$stmt_get = mysqli_prepare($koneksi, $sql_get_pekerja);

$pekerja_lama = null; // Inisialisasi
if ($stmt_get) {
    mysqli_stmt_bind_param($stmt_get, "i", $id_pekerja_edit);
    mysqli_stmt_execute($stmt_get);
    $result_get = mysqli_stmt_get_result($stmt_get);
    $pekerja_lama = mysqli_fetch_assoc($result_get);
    mysqli_stmt_close($stmt_get);

    if (!$pekerja_lama) {
        $_SESSION['pesan_error_crud'] = "Data pekerja dengan ID " . $id_pekerja_edit . " tidak ditemukan.";
        header('Location: ' . BASE_URL . 'pekerja/');
        exit;
    }
} else {
    $_SESSION['pesan_error_crud'] = "Gagal mengambil data pekerja dari database.";
    header('Location: ' . BASE_URL . 'pekerja/');
    exit;
}

// 6. Ambil data JABATAN untuk dropdown
$query_jabatan_dropdown = "SELECT id_jabatan, namajabatan FROM jabatan ORDER BY namajabatan ASC";
$result_jabatan_dropdown = mysqli_query($koneksi, $query_jabatan_dropdown);
if (!$result_jabatan_dropdown) {
    $_SESSION['pesan_error_crud'] = "Gagal memuat data jabatan untuk form.";
    // Mungkin redirect atau tampilkan error, untuk sekarang kita biarkan dropdown mungkin kosong
    $daftar_jabatan_options = [];
} else {
    $daftar_jabatan_options = mysqli_fetch_all($result_jabatan_dropdown, MYSQLI_ASSOC);
}

// 7. Siapkan pesan notifikasi (jika ada dari proses.php setelah validasi gagal)
$pesan_notifikasi_edit = '';
if (isset($_SESSION['pesan_error_crud'])) {
    $pesan_notifikasi_edit = "<div class='mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg shadow'>" . htmlspecialchars($_SESSION['pesan_error_crud']) . "</div>";
    unset($_SESSION['pesan_error_crud']);
}
// Ambil data form yang gagal divalidasi (untuk sticky form) - jika proses edit mengembalikan data
$form_data = isset($_SESSION['form_data']) ? $_SESSION['form_data'] : $pekerja_lama; // Default ke data lama jika tidak ada form_data
unset($_SESSION['form_data']);


// 8. Memanggil komponen template: HEADER
require_once '../includes/header.php'; 

// 9. Memanggil komponen template: SIDEBAR (sesuai peran pengguna)
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
                <i class="fas fa-user-edit fa-fw mr-2 text-indigo-500"></i>Edit Data Pekerja: <?php echo htmlspecialchars($pekerja_lama['namapekerja']); ?>
            </h1>

            <?php echo $pesan_notifikasi_edit; // Tampilkan pesan error validasi jika ada ?>

            <form action="<?php echo BASE_URL; ?>pekerja/proses.php?aksi=edit&id=<?php echo $id_pekerja_edit; ?>" method="POST">
                <div class="mb-5">
                    <label for="namapekerja" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nama Pekerja <span class="text-red-500">*</span></label>
                    <input type="text" name="namapekerja" id="namapekerja" required maxlength="100"
                           value="<?php echo htmlspecialchars($form_data['namapekerja'] ?? ''); ?>"
                           class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white placeholder-gray-400 dark:placeholder-gray-500">
                </div>

                <div class="mb-5">
                    <label for="id_jabatan" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Jabatan <span class="text-red-500">*</span></label>
                    <select name="id_jabatan" id="id_jabatan" required
                            class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                        <option value="">-- Pilih Jabatan --</option>
                        <?php if (!empty($daftar_jabatan_options)) : ?>
                            <?php foreach ($daftar_jabatan_options as $jabatan_opt) : ?>
                                <option value="<?php echo $jabatan_opt['id_jabatan']; ?>" 
                                    <?php echo (isset($form_data['id_jabatan']) && $form_data['id_jabatan'] == $jabatan_opt['id_jabatan']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($jabatan_opt['namajabatan']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                             <option value="" disabled>Data jabatan tidak tersedia</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="mb-5">
                    <label for="no_hp" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">No. Handphone</label>
                    <input type="text" name="no_hp" id="no_hp" maxlength="20"
                           value="<?php echo htmlspecialchars($form_data['no_hp'] ?? ''); ?>"
                           class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white placeholder-gray-400 dark:placeholder-gray-500">
                </div>

                <div class="mb-5">
                    <label for="no_rek" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">No. Rekening</label>
                    <input type="text" name="no_rek" id="no_rek" maxlength="30"
                           value="<?php echo htmlspecialchars($form_data['no_rek'] ?? ''); ?>"
                           class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white placeholder-gray-400 dark:placeholder-gray-500">
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status Keaktifan <span class="text-red-500">*</span></label>
                    <div class="flex items-center space-x-4 pt-1">
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="radio" name="is_active" value="1" class="form-radio h-4 w-4 text-blue-600 dark:bg-gray-700 border-gray-300 dark:border-gray-600 focus:ring-blue-500"
                                <?php echo (isset($form_data['is_active']) && $form_data['is_active'] == '1') ? 'checked' : ''; ?>>
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Aktif</span>
                        </label>
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="radio" name="is_active" value="0" class="form-radio h-4 w-4 text-blue-600 dark:bg-gray-700 border-gray-300 dark:border-gray-600 focus:ring-blue-500"
                                <?php echo (isset($form_data['is_active']) && $form_data['is_active'] == '0') ? 'checked' : ''; ?>>
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Tidak Aktif</span>
                        </label>
                    </div>
                </div>

                <div class="mt-8 flex items-center justify-end gap-x-4 border-t border-gray-200 dark:border-gray-700 pt-6">
                    <a href="<?php echo BASE_URL; ?>pekerja/" class="text-sm font-semibold leading-6 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition duration-150 ease-in-out">
                        Batal
                    </a>
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md shadow-sm transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                        <i class="fas fa-sync-alt fa-fw mr-2"></i>
                        Update Data Pekerja
                    </button>
                </div>
            </form>
        </div> </div> </main>

<?php
// 11. Memanggil komponen template: FOOTER
require_once '../includes/footer.php'; 
?>