<?php
// File: proyek_jaya/pekerja/edit.php (Revisi)

require_once '../config.php';

// --- Bagian Autentikasi & Autorisasi tidak berubah ---
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'auth/login.php?error=2');
    exit;
}
$user_role = $_SESSION['role'];
if (!in_array($user_role, ['super_admin', 'admin'])) {
    $_SESSION['pesan_error'] = "ANDA TIDAK MEMILIKI HAK AKSES UNTUK MENGEDIT DATA PEKERJA.";
    header('Location: ' . BASE_URL . 'pekerja/'); 
    exit;
}

// --- Bagian mengambil ID dari URL & data jabatan tidak berubah ---
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['pesan_error_crud'] = "ID Pekerja tidak valid atau tidak ditemukan.";
    header('Location: ' . BASE_URL . 'pekerja/');
    exit;
}
$id_pekerja_edit = intval($_GET['id']);

// --- Query mengambil data pekerja ditambah kolom username ---
$sql_get_pekerja = "SELECT id_pekerja, namapekerja, id_jabatan, no_hp, no_rek, is_active, username FROM pekerja WHERE id_pekerja = ?";
$stmt_get = mysqli_prepare($koneksi, $sql_get_pekerja);

$pekerja_lama = null;
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

// Mengambil daftar jabatan untuk dropdown
$query_jabatan_dropdown = "SELECT id_jabatan, namajabatan FROM jabatan ORDER BY namajabatan ASC";
$result_jabatan_dropdown = mysqli_query($koneksi, $query_jabatan_dropdown);
$daftar_jabatan_options = $result_jabatan_dropdown ? mysqli_fetch_all($result_jabatan_dropdown, MYSQLI_ASSOC) : [];

// Menyiapkan notifikasi dan sticky form
$pesan_notifikasi_edit = '';
if (isset($_SESSION['pesan_error_crud'])) {
    $pesan_notifikasi_edit = "<div class='mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg shadow'>" . htmlspecialchars($_SESSION['pesan_error_crud']) . "</div>";
    unset($_SESSION['pesan_error_crud']);
}
$form_data = isset($_SESSION['form_data']) ? $_SESSION['form_data'] : $pekerja_lama;
unset($_SESSION['form_data']);

require_once '../includes/header.php'; 

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

            <?php echo $pesan_notifikasi_edit; ?>

            <form action="<?php echo BASE_URL; ?>pekerja/proses.php?aksi=edit&id=<?php echo $id_pekerja_edit; ?>" method="POST">
                <!-- Field data personalia tetap sama -->
                <div class="mb-5">
                    <label for="namapekerja" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nama Pekerja <span class="text-red-500">*</span></label>
                    <input type="text" name="namapekerja" id="namapekerja" required value="<?php echo htmlspecialchars($form_data['namapekerja'] ?? ''); ?>" class="w-full px-3 py-2.5 border border-gray-300 rounded-md ...">
                </div>
                <div class="mb-5">
                    <label for="id_jabatan" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Jabatan <span class="text-red-500">*</span></label>
                    <select name="id_jabatan" id="id_jabatan" required class="w-full px-3 py-2.5 border border-gray-300 rounded-md ...">
                        <option value="">-- Pilih Jabatan --</option>
                        <?php foreach ($daftar_jabatan_options as $jabatan_opt) : ?>
                            <option value="<?php echo $jabatan_opt['id_jabatan']; ?>" <?php echo (isset($form_data['id_jabatan']) && $form_data['id_jabatan'] == $jabatan_opt['id_jabatan']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($jabatan_opt['namajabatan']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-5">
                    <label for="no_hp" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">No. Handphone</label>
                    <input type="text" name="no_hp" id="no_hp" value="<?php echo htmlspecialchars($form_data['no_hp'] ?? ''); ?>" class="w-full px-3 py-2.5 border border-gray-300 rounded-md ...">
                </div>
                <div class="mb-5">
                    <label for="no_rek" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">No. Rekening</label>
                    <input type="text" name="no_rek" id="no_rek" value="<?php echo htmlspecialchars($form_data['no_rek'] ?? ''); ?>" class="w-full px-3 py-2.5 border border-gray-300 rounded-md ...">
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status Keaktifan <span class="text-red-500">*</span></label>
                    <div class="flex items-center space-x-4 pt-1">
                        <label><input type="radio" name="is_active" value="1" class="form-radio..." <?php echo (isset($form_data['is_active']) && $form_data['is_active'] == '1') ? 'checked' : ''; ?>> Aktif</label>
                        <label><input type="radio" name="is_active" value="0" class="form-radio..." <?php echo (isset($form_data['is_active']) && $form_data['is_active'] == '0') ? 'checked' : ''; ?>> Tidak Aktif</label>
                    </div>
                </div>

                <!-- ====================================================== -->
                <!-- BAGIAN BARU: Pengaturan Akun Login (Hanya Super Admin) -->
                <!-- ====================================================== -->
                <?php if ($user_role === 'super_admin'): ?>
                <div class="mt-8 pt-6 border-t border-dashed border-gray-300 dark:border-gray-600">
                    <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-200">Pengaturan Akun Login</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Atur username dan password jika pekerja ini bisa login ke sistem.</p>
                    
                    <div class="mt-4 mb-5">
                        <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Username</label>
                        <input type="text" name="username" id="username"
                               value="<?php echo htmlspecialchars($form_data['username'] ?? ''); ?>"
                               class="w-full px-3 py-2.5 border border-gray-300 rounded-md ..."
                               placeholder="Kosongkan jika tidak bisa login">
                    </div>
                    
                    <div class="mb-5">
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Password Baru (Opsional)</label>
                        <input type="password" name="password" id="password"
                               class="w-full px-3 py-2.5 border border-gray-300 rounded-md ..."
                               placeholder="Isi hanya jika ingin mengubah password">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Kosongkan jika tidak ingin mengubah password yang sudah ada.</p>
                    </div>
                </div>
                <?php endif; ?>
                <!-- ====================================================== -->
                <!-- AKHIR BAGIAN BARU                                      -->
                <!-- ====================================================== -->

                <div class="mt-8 flex items-center justify-end gap-x-4 border-t border-gray-200 dark:border-gray-700 pt-6">
                    <a href="<?php echo BASE_URL; ?>pekerja/" class="text-sm font-semibold ...">Batal</a>
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 ...">
                        <i class="fas fa-sync-alt fa-fw mr-2"></i> Update Data Pekerja
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php
require_once '../includes/footer.php'; 
?>
