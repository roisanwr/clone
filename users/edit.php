<?php
// File: users/edit.php (Revisi - Mengedit Akun Login Pekerja)

require_once '../config.php';

// 1. Autentikasi & Autorisasi (Hanya Super Admin)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    $_SESSION['pesan_error'] = "AKSES DITOLAK: Halaman ini hanya untuk Super Administrator.";
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

// 2. Ambil ID Pekerja dari URL dan Validasi
// Ingat, ID yang kita pakai sekarang adalah id_pekerja
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['pesan_error_crud'] = "ID Pengguna (Pekerja) tidak valid.";
    header('Location: ' . BASE_URL . 'users/');
    exit;
}
$id_pekerja_edit = intval($_GET['id']);

// 3. Query BARU: Ambil data pekerja yang akan diedit dari tabel 'pekerja'
$sql_get_pengguna = "SELECT id_pekerja, namapekerja, username, is_active FROM pekerja WHERE id_pekerja = ?";
$stmt_get = mysqli_prepare($koneksi, $sql_get_pengguna);
$pengguna_lama = null; 
if ($stmt_get) {
    mysqli_stmt_bind_param($stmt_get, "i", $id_pekerja_edit);
    mysqli_stmt_execute($stmt_get);
    $result_get = mysqli_stmt_get_result($stmt_get);
    $pengguna_lama = mysqli_fetch_assoc($result_get);
    mysqli_stmt_close($stmt_get);

    if (!$pengguna_lama || empty($pengguna_lama['username'])) {
        $_SESSION['pesan_error_crud'] = "Pengguna dengan ID Pekerja #" . $id_pekerja_edit . " tidak ditemukan atau belum memiliki akun.";
        header('Location: ' . BASE_URL . 'users/');
        exit;
    }
} else {
    // Handle error prepare statement
    $_SESSION['pesan_error_crud'] = "Gagal mengambil data pengguna dari database.";
    header('Location: ' . BASE_URL . 'users/');
    exit;
}

// 4. Siapkan notifikasi & sticky form
$pesan_notifikasi_edit = '';
if (isset($_SESSION['pesan_error_crud'])) {
    $pesan_notifikasi_edit = "<div class='mb-4 p-3 bg-red-100 ...'>" . htmlspecialchars($_SESSION['pesan_error_crud']) . "</div>";
    unset($_SESSION['pesan_error_crud']);
}
$form_data = isset($_SESSION['form_data']) ? $_SESSION['form_data'] : $pengguna_lama; 
unset($_SESSION['form_data']);

// 5. Panggil template
require_once '../includes/header.php'; 
require_once '../includes/sidebar_super_admin.php';
?>

<main class="content-wrapper mt-16 md:ml-72">
    <div class="max-w-2xl mx-auto p-4 sm:p-6 lg:p-8"> 
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 sm:p-8">
            <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 dark:text-white mb-6">
                <i class="fas fa-user-edit fa-fw mr-2 text-indigo-500"></i>Edit Akun Pengguna
            </h1>

            <?php echo $pesan_notifikasi_edit; ?>

            <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg border">
                <p class="text-sm text-gray-600 dark:text-gray-400">Nama Lengkap: <strong class="text-gray-800 dark:text-white"><?php echo htmlspecialchars($pengguna_lama['namapekerja']); ?></strong></p>
            </div>

            <form action="<?php echo BASE_URL; ?>users/proses.php?aksi=edit&id=<?php echo $id_pekerja_edit; ?>" method="POST">
                
                <div class="mb-5">
                    <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Username <span class="text-red-500">*</span></label>
                    <input type="text" name="username" id="username" required maxlength="50"
                           value="<?php echo htmlspecialchars($form_data['username'] ?? ''); ?>"
                           class="w-full px-3 py-2.5 border border-gray-300 rounded-md ...">
                </div>

                <div class="mb-2">
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Password Baru (Opsional)</label>
                    <input type="password" name="password" id="password" 
                           class="w-full px-3 py-2.5 border border-gray-300 rounded-md ..."
                           placeholder="Isi hanya jika ingin ganti password">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Kosongkan jika tidak ingin mengubah password.</p>
                </div>
                
                <div class="mb-6">
                    <label for="konfirmasi_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Konfirmasi Password Baru</label>
                    <input type="password" name="konfirmasi_password" id="konfirmasi_password"
                           class="w-full px-3 py-2.5 border border-gray-300 rounded-md ..."
                           placeholder="Ketik ulang password baru jika diisi">
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status Akun <span class="text-red-500">*</span></label>
                    <div class="flex items-center space-x-4 pt-1">
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="radio" name="is_active" value="1" class="form-radio..." 
                                <?php echo (isset($form_data['is_active']) && $form_data['is_active'] == '1') ? 'checked' : ''; ?>
                                <?php echo ($_SESSION['user_id'] == $id_pekerja_edit) ? 'disabled title="Tidak dapat menonaktifkan akun sendiri"' : ''; ?>>
                            <span class="ml-2 text-sm">Aktif</span>
                        </label>
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="radio" name="is_active" value="0" class="form-radio..." 
                                <?php echo (isset($form_data['is_active']) && $form_data['is_active'] == '0') ? 'checked' : ''; ?>
                                <?php echo ($_SESSION['user_id'] == $id_pekerja_edit) ? 'disabled' : ''; ?>>
                            <span class="ml-2 text-sm">Tidak Aktif</span>
                        </label>
                    </div>
                    <?php if ($_SESSION['user_id'] == $id_pekerja_edit): ?>
                        <p class="mt-1 text-xs text-yellow-600">Anda tidak dapat menonaktifkan akun Anda sendiri.</p>
                        <input type="hidden" name="is_active" value="1"> 
                    <?php endif; ?>
                </div>


                <div class="mt-8 flex items-center justify-end gap-x-4 border-t border-gray-200 dark:border-gray-700 pt-6">
                    <a href="<?php echo BASE_URL; ?>users/" class="text-sm font-semibold ...">Batal</a>
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 ...">
                        <i class="fas fa-user-check fa-fw mr-2"></i>Update Akun
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php
require_once '../includes/footer.php'; 
?>
