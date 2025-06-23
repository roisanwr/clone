<?php
// File: proyek_jaya/users/edit.php

// 1. Panggil Konfigurasi dan mulai session
require_once '../config.php';

// 2. Autentikasi & Autorisasi Super Ketat: HANYA Super Admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    $_SESSION['pesan_error'] = "AKSES DITOLAK: Halaman ini hanya untuk Super Administrator.";
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}
// $user_role_loggedin = $_SESSION['role']; // Tidak terlalu dipakai di sini karena sudah pasti super_admin

// 3. Ambil ID User dari URL dan Validasi
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['pesan_error_crud'] = "ID Pengguna tidak valid atau tidak ditemukan.";
    header('Location: ' . BASE_URL . 'users/');
    exit;
}
$id_user_edit = intval($_GET['id']);

// 4. Ambil data pengguna yang akan diedit dari database
// Kita tidak mengambil password di sini untuk ditampilkan di form
$sql_get_user = "SELECT id_user, username, role, id_pekerja_ref, is_active FROM users WHERE id_user = ?";
$stmt_get = mysqli_prepare($koneksi, $sql_get_user);
$user_lama = null; 
if ($stmt_get) {
    mysqli_stmt_bind_param($stmt_get, "i", $id_user_edit);
    mysqli_stmt_execute($stmt_get);
    $result_get = mysqli_stmt_get_result($stmt_get);
    $user_lama = mysqli_fetch_assoc($result_get);
    mysqli_stmt_close($stmt_get);

    if (!$user_lama) {
        $_SESSION['pesan_error_crud'] = "Data pengguna dengan ID " . $id_user_edit . " tidak ditemukan.";
        header('Location: ' . BASE_URL . 'users/');
        exit;
    }
} else {
    $_SESSION['pesan_error_crud'] = "Gagal mengambil data pengguna dari database: " . mysqli_error($koneksi);
    header('Location: ' . BASE_URL . 'users/');
    exit;
}

// 5. Ambil data PEKERJA untuk dropdown id_pekerja_ref
// Termasuk pekerja yang saat ini terhubung dengan user ini (jika ada)
// dan pekerja aktif lain yang belum terhubung DENGAN JABATAN MANDOR.
$nama_jabatan_mandor_untuk_user = 'Mandor'; // Sesuaikan jika perlu

$query_pekerja_ref_options = "SELECT p.id_pekerja, p.namapekerja 
                              FROM pekerja p 
                              INNER JOIN jabatan j ON p.id_jabatan = j.id_jabatan
                              LEFT JOIN users u ON p.id_pekerja = u.id_pekerja_ref
                              WHERE p.is_active = 1 
                                AND j.namajabatan = ?
                                AND (u.id_user IS NULL OR u.id_user = ?) -- Belum terkait ATAU terkait dengan user ini
                              ORDER BY p.namapekerja ASC";
$stmt_pekerja_ref = mysqli_prepare($koneksi, $query_pekerja_ref_options);
$daftar_pekerja_ref_options = [];
if ($stmt_pekerja_ref) {
    mysqli_stmt_bind_param($stmt_pekerja_ref, "si", $nama_jabatan_mandor_untuk_user, $id_user_edit);
    mysqli_stmt_execute($stmt_pekerja_ref);
    $result_pekerja_ref = mysqli_stmt_get_result($stmt_pekerja_ref);
    $daftar_pekerja_ref_options = $result_pekerja_ref ? mysqli_fetch_all($result_pekerja_ref, MYSQLI_ASSOC) : [];
    mysqli_stmt_close($stmt_pekerja_ref);
}


// 6. Siapkan pesan notifikasi & data sticky form
$pesan_notifikasi_edit = '';
if (isset($_SESSION['pesan_error_crud'])) {
    $pesan_notifikasi_edit = "<div class='mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg shadow'>" . htmlspecialchars($_SESSION['pesan_error_crud']) . "</div>";
    unset($_SESSION['pesan_error_crud']);
}
// Default ke data lama jika tidak ada data dari session (pertama kali buka form edit)
$form_data = isset($_SESSION['form_data']) ? $_SESSION['form_data'] : $user_lama; 
unset($_SESSION['form_data']);

// 7. Memanggil komponen template: HEADER
require_once '../includes/header.php'; 

// 8. Memanggil komponen template: SIDEBAR (Hanya Super Admin)
require_once '../includes/sidebar_super_admin.php';
?>

<main class="content-wrapper mt-16 md:ml-72">
    <div class="max-w-2xl mx-auto p-4 sm:p-6 lg:p-8"> 
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 sm:p-8">
            <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 dark:text-white mb-6">
                <i class="fas fa-user-edit fa-fw mr-2 text-indigo-500"></i>Edit Pengguna: <?php echo htmlspecialchars($user_lama['username']); ?>
            </h1>

            <?php echo $pesan_notifikasi_edit; ?>

            <form action="<?php echo BASE_URL; ?>users/proses.php?aksi=edit&id=<?php echo $id_user_edit; ?>" method="POST" id="formEditUser">
                <div class="mb-5">
                    <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Username <span class="text-red-500">*</span></label>
                    <input type="text" name="username" id="username" required maxlength="50"
                           value="<?php echo htmlspecialchars($form_data['username'] ?? ''); ?>"
                           class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white placeholder-gray-400 dark:placeholder-gray-500">
                </div>

                <div class="mb-2">
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Password Baru (Opsional)</label>
                    <input type="password" name="password" id="password" 
                           class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                           placeholder="Isi jika ingin ganti password (min. 6 karakter)">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Kosongkan jika tidak ingin mengubah password.</p>
                </div>
                
                <div class="mb-5">
                    <label for="konfirmasi_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Konfirmasi Password Baru</label>
                    <input type="password" name="konfirmasi_password" id="konfirmasi_password"
                           class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                           placeholder="Ketik ulang password baru">
                </div>

                <div class="mb-5">
                    <label for="role" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Peran (Role) <span class="text-red-500">*</span></label>
                    <select name="role" id="role" required <?php echo ($user_lama['id_user'] == $_SESSION['user_id'] && $user_lama['role'] == 'super_admin') ? 'disabled' : ''; ?>
                            class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 <?php echo ($user_lama['id_user'] == $_SESSION['user_id'] && $user_lama['role'] == 'super_admin') ? 'bg-gray-100 dark:bg-gray-700 cursor-not-allowed' : 'dark:bg-gray-700 dark:text-white'; ?>">
                        <option value="admin" <?php echo (isset($form_data['role']) && $form_data['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                        <option value="mandor" <?php echo (isset($form_data['role']) && $form_data['role'] == 'mandor') ? 'selected' : ''; ?>>Mandor</option>
                        <?php if ($user_lama['role'] == 'super_admin'): // Jika yang diedit adalah super_admin, opsi super_admin tetap ada & terpilih ?>
                            <option value="super_admin" <?php echo (isset($form_data['role']) && $form_data['role'] == 'super_admin') ? 'selected' : ''; ?>>Super Admin</option>
                        <?php endif; ?>
                    </select>
                    <?php if ($user_lama['id_user'] == $_SESSION['user_id'] && $user_lama['role'] == 'super_admin'): ?>
                        <p class="mt-1 text-xs text-yellow-600 dark:text-yellow-400">Super Admin tidak dapat mengubah perannya sendiri.</p>
                        <input type="hidden" name="role" value="super_admin"> <?php endif; ?>
                </div>

                <div class="mb-5" id="kolom_pekerja_ref_edit" style="display: <?php echo (isset($form_data['role']) && $form_data['role'] == 'mandor') ? 'block' : 'none'; ?>;">
                    <label for="id_pekerja_ref" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kaitkan dengan Data Pekerja (Jabatan: <?php echo htmlspecialchars($nama_jabatan_mandor_untuk_user); ?>) <span id="pekerja_ref_wajib_mark_edit" class="text-red-500" style="display: none;">*</span></label>
                    <select name="id_pekerja_ref" id="id_pekerja_ref_edit"
                            class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                        <option value="">-- Pilih Pekerja (Jika Peran Mandor) --</option>
                        <?php foreach ($daftar_pekerja_ref_options as $pekerja_opt) : ?>
                            <option value="<?php echo $pekerja_opt['id_pekerja']; ?>" <?php echo (isset($form_data['id_pekerja_ref']) && $form_data['id_pekerja_ref'] == $pekerja_opt['id_pekerja']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($pekerja_opt['namapekerja']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Pilih pekerja jika peran adalah 'Mandor'. Jika tidak, biarkan atau pilih opsi "-- Pilih Pekerja --".</p>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status Akun <span class="text-red-500">*</span></label>
                    <div class="flex items-center space-x-4 pt-1">
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="radio" name="is_active" value="1" class="form-radio ..." 
                                <?php echo (isset($form_data['is_active']) && $form_data['is_active'] == '1') ? 'checked' : ''; ?>
                                <?php echo ($user_lama['id_user'] == $_SESSION['user_id']) ? 'disabled title="Tidak dapat menonaktifkan akun sendiri"' : ''; // Tidak bisa nonaktifkan diri sendiri ?>>
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Aktif</span>
                        </label>
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="radio" name="is_active" value="0" class="form-radio ..." 
                                <?php echo (isset($form_data['is_active']) && $form_data['is_active'] == '0') ? 'checked' : ''; ?>
                                <?php echo ($user_lama['id_user'] == $_SESSION['user_id']) ? 'disabled title="Tidak dapat menonaktifkan akun sendiri"' : ''; // Tidak bisa nonaktifkan diri sendiri ?>>
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Tidak Aktif</span>
                        </label>
                    </div>
                     <?php if ($user_lama['id_user'] == $_SESSION['user_id']): ?>
                        <p class="mt-1 text-xs text-yellow-600 dark:text-yellow-400">Anda tidak dapat menonaktifkan akun Anda sendiri.</p>
                        <input type="hidden" name="is_active" value="1"> <?php endif; ?>
                </div>

                <div class="mt-8 flex items-center justify-end gap-x-4 border-t border-gray-200 dark:border-gray-700 pt-6">
                    <a href="<?php echo BASE_URL; ?>users/" class="text-sm font-semibold ...">Batal</a>
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 ...">
                        <i class="fas fa-user-check fa-fw mr-2"></i>Update Pengguna
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const roleSelectEdit = document.getElementById('role'); // ID dropdown role
    const pekerjaRefContainerEdit = document.getElementById('kolom_pekerja_ref_edit'); // ID div pembungkus
    const pekerjaRefSelectEdit = document.getElementById('id_pekerja_ref_edit'); // ID select pekerja
    const pekerjaRefWajibMarkEdit = document.getElementById('pekerja_ref_wajib_mark_edit'); // ID span tanda bintang

    function togglePekerjaRefEdit() {
        if (roleSelectEdit.value === 'mandor') {
            pekerjaRefContainerEdit.style.display = 'block';
            pekerjaRefSelectEdit.required = true; 
            pekerjaRefWajibMarkEdit.style.display = 'inline'; 
        } else {
            pekerjaRefContainerEdit.style.display = 'none';
            pekerjaRefSelectEdit.required = false; 
            // pekerjaRefSelectEdit.value = ''; // Jangan dikosongkan otomatis jika sudah ada nilai dari DB
            pekerjaRefWajibMarkEdit.style.display = 'none'; 
        }
    }
    
    if (roleSelectEdit) {
        roleSelectEdit.addEventListener('change', togglePekerjaRefEdit);
        togglePekerjaRefEdit(); // Panggil saat load untuk initial state
    }
});
</script>

<?php
// 10. Memanggil komponen template: FOOTER
require_once '../includes/footer.php'; 
?>