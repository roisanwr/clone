<?php
// File: proyek_jaya/users/tambah.php

// 1. Panggil Konfigurasi dan mulai session
require_once '../config.php';

// 2. Autentikasi & Autorisasi Super Ketat: HANYA Super Admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    $_SESSION['pesan_error'] = "AKSES DITOLAK: Halaman ini hanya untuk Super Administrator.";
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}
// $user_role = $_SESSION['role']; // Tidak perlu karena sudah pasti super_admin untuk panggil sidebar

// 3. Ambil data PEKERJA (yang aktif dan BELUM TERKAIT dengan user lain) untuk dropdown id_pekerja_ref
// 3. Ambil data PEKERJA (yang aktif, jabatannya 'Mandor', DAN BELUM TERKAIT dengan user lain) 
//    untuk dropdown id_pekerja_ref
$nama_jabatan_mandor_untuk_user = 'Mandor'; // PASTIKAN NAMA INI SAMA PERSIS DENGAN 'namajabatan'
                                           // di tabel `jabatan` untuk peran Mandor. Sesuaikan jika beda.

$query_pekerja_ref_options = "SELECT p.id_pekerja, p.namapekerja 
                              FROM pekerja p 
                              INNER JOIN jabatan j ON p.id_jabatan = j.id_jabatan
                              LEFT JOIN users u ON p.id_pekerja = u.id_pekerja_ref
                              WHERE p.is_active = 1 
                                AND j.namajabatan = ?  -- Filter berdasarkan nama jabatan
                                AND u.id_user IS NULL  -- Hanya yang belum terkait dengan user lain
                              ORDER BY p.namapekerja ASC";

$stmt_pekerja_ref = mysqli_prepare($koneksi, $query_pekerja_ref_options);
$daftar_pekerja_ref_options = []; // Inisialisasi sebagai array kosong

if ($stmt_pekerja_ref) {
    mysqli_stmt_bind_param($stmt_pekerja_ref, "s", $nama_jabatan_mandor_untuk_user);
    mysqli_stmt_execute($stmt_pekerja_ref);
    $result_pekerja_ref = mysqli_stmt_get_result($stmt_pekerja_ref);
    
    if ($result_pekerja_ref) {
        $daftar_pekerja_ref_options = mysqli_fetch_all($result_pekerja_ref, MYSQLI_ASSOC);
    } else {
        // error_log("Gagal fetch hasil query pekerja (mandor user) dropdown: " . mysqli_error($koneksi));
        // Biarkan $daftar_pekerja_ref_options kosong, pesan akan ditampilkan di bawah dropdown
    }
    mysqli_stmt_close($stmt_pekerja_ref);
} else {
    // error_log("Gagal prepare statement pekerja (mandor user) dropdown: " . mysqli_error($koneksi));
    // Biarkan $daftar_pekerja_ref_options kosong
}

// Siapkan pesan jika tidak ada Mandor yang sesuai/tersedia
$pesan_jika_mandor_ref_kosong = '';
// Pesan ini akan ditampilkan oleh JavaScript jika role 'mandor' dipilih dan $daftar_pekerja_ref_options kosong
if (empty($daftar_pekerja_ref_options)) {
    $pesan_jika_mandor_ref_kosong_text = "Tip: Tidak ditemukan pekerja aktif dengan jabatan '" . htmlspecialchars($nama_jabatan_mandor_untuk_user) . "' yang belum terkait dengan akun user. Pastikan data sudah benar di Manajemen Pekerja dan Jabatan, atau pekerja tersebut belum dikaitkan dengan user lain.";
}
// 4. Siapkan pesan notifikasi & data sticky form
$pesan_notifikasi_tambah = '';
if (isset($_SESSION['pesan_error_crud'])) {
    $pesan_notifikasi_tambah = "<div class='mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg shadow'>" . htmlspecialchars($_SESSION['pesan_error_crud']) . "</div>";
    unset($_SESSION['pesan_error_crud']); 
}
$form_data = isset($_SESSION['form_data']) ? $_SESSION['form_data'] : [];
unset($_SESSION['form_data']);

// 5. Memanggil komponen template: HEADER
require_once '../includes/header.php'; 

// 6. Memanggil komponen template: SIDEBAR (Hanya Super Admin)
require_once '../includes/sidebar_super_admin.php';
?>

<main class="content-wrapper mt-16 md:ml-72">
    <div class="max-w-2xl mx-auto p-4 sm:p-6 lg:p-8"> 
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 sm:p-8">
            <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 dark:text-white mb-6">
                <i class="fas fa-user-plus fa-fw mr-2 text-blue-500"></i>Tambah Pengguna Sistem Baru
            </h1>

            <?php echo $pesan_notifikasi_tambah; ?>

            <form action="<?php echo BASE_URL; ?>users/proses.php?aksi=tambah" method="POST" id="formTambahUser">
                <div class="mb-5">
                    <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Username <span class="text-red-500">*</span></label>
                    <input type="text" name="username" id="username" required maxlength="50"
                           value="<?php echo htmlspecialchars($form_data['username'] ?? ''); ?>"
                           class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white placeholder-gray-400 dark:placeholder-gray-500"
                           placeholder="Masukkan username unik">
                </div>

                <div class="mb-5">
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Password <span class="text-red-500">*</span></label>
                    <input type="password" name="password" id="password" required 
                           class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                           placeholder="Minimal 6 karakter">
                </div>
                
                <div class="mb-5">
                    <label for="konfirmasi_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Konfirmasi Password <span class="text-red-500">*</span></label>
                    <input type="password" name="konfirmasi_password" id="konfirmasi_password" required
                           class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                           placeholder="Ketik ulang password">
                </div>

                <div class="mb-5">
                    <label for="role" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Peran (Role) <span class="text-red-500">*</span></label>
                    <select name="role" id="role" required
                            class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                        <option value="">-- Pilih Peran --</option>
                        <option value="admin" <?php echo (isset($form_data['role']) && $form_data['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                        <option value="mandor" <?php echo (isset($form_data['role']) && $form_data['role'] == 'mandor') ? 'selected' : ''; ?>>Mandor</option>
                    </select>
                </div>

                <div class="mb-5" id="kolom_pekerja_ref" style="display: <?php echo (isset($form_data['role']) && $form_data['role'] == 'mandor') ? 'block' : 'none'; ?>;">
                    <label for="id_pekerja_ref" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kaitkan dengan Data Pekerja (untuk Mandor) <span id="pekerja_ref_wajib_mark" class="text-red-500" style="display: none;">*</span></label>
                    <select name="id_pekerja_ref" id="id_pekerja_ref"
                            class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                        <option value="">-- Pilih Pekerja (Jika Peran Mandor) --</option>
                        <?php foreach ($daftar_pekerja_ref_options as $pekerja_opt) : ?>
                            <option value="<?php echo $pekerja_opt['id_pekerja']; ?>" <?php echo (isset($form_data['id_pekerja_ref']) && $form_data['id_pekerja_ref'] == $pekerja_opt['id_pekerja']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($pekerja_opt['namapekerja']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($daftar_pekerja_ref_options) && (isset($form_data['role']) && $form_data['role'] == 'mandor')): ?>
                        <p class="mt-1 text-xs text-yellow-500 dark:text-yellow-400">Tip: Tidak ada data pekerja aktif yang belum terkait dengan user lain untuk dipilih sebagai Mandor. Tambahkan data pekerja terlebih dahulu jika diperlukan.</p>
                    <?php endif; ?>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Pilih pekerja jika peran adalah 'Mandor'. Jika tidak, biarkan atau pilih opsi "-- Pilih Pekerja --".</p>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status Akun <span class="text-red-500">*</span></label>
                    <div class="flex items-center space-x-4 pt-1">
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="radio" name="is_active" value="1" class="form-radio h-4 w-4 text-blue-600 dark:bg-gray-700 border-gray-300 dark:border-gray-600 focus:ring-blue-500" 
                                <?php echo (!isset($form_data['is_active']) || $form_data['is_active'] == '1') ? 'checked' : ''; ?>>
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
                    <a href="<?php echo BASE_URL; ?>users/" class="text-sm font-semibold leading-6 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition duration-150 ease-in-out">
                        Batal
                    </a>
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md shadow-sm transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800">
                        <i class="fas fa-user-check fa-fw mr-2"></i>Simpan Pengguna
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const roleSelect = document.getElementById('role');
    const pekerjaRefContainer = document.getElementById('kolom_pekerja_ref');
    const pekerjaRefSelect = document.getElementById('id_pekerja_ref');
    const pekerjaRefWajibMark = document.getElementById('pekerja_ref_wajib_mark');

    function togglePekerjaRef() {
        if (roleSelect.value === 'mandor') {
            pekerjaRefContainer.style.display = 'block';
            pekerjaRefSelect.required = true; 
            pekerjaRefWajibMark.style.display = 'inline'; 
        } else {
            pekerjaRefContainer.style.display = 'none';
            pekerjaRefSelect.required = false; 
            pekerjaRefSelect.value = ''; 
            pekerjaRefWajibMark.style.display = 'none'; 
        }
    }
    
    if (roleSelect) {
        roleSelect.addEventListener('change', togglePekerjaRef);
        togglePekerjaRef(); // Panggil saat load untuk initial state
    }
});
</script>

<?php
// 8. Memanggil komponen template: FOOTER
require_once '../includes/footer.php'; 
?>