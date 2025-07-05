<?php
// File: users/tambah.php (Revisi - Mempromosikan Pekerja menjadi Pengguna)

require_once '../config.php';

// 1. Autentikasi & Autorisasi (Tetap sama, hanya Super Admin)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    $_SESSION['pesan_error'] = "AKSES DITOLAK: Halaman ini hanya untuk Super Administrator.";
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

// 2. Query BARU: Ambil daftar pekerja yang BELUM punya akun
// Syaratnya: is_active = 1 DAN username-nya masih KOSONG.
$query_pekerja_options = "SELECT 
                                p.id_pekerja, p.namapekerja, j.namajabatan 
                          FROM pekerja p
                          JOIN jabatan j ON p.id_jabatan = j.id_jabatan
                          WHERE p.is_active = 1 AND (p.username IS NULL OR p.username = '')
                          ORDER BY p.namapekerja ASC";

$result_pekerja_options = mysqli_query($koneksi, $query_pekerja_options);
$daftar_pekerja_tersedia = $result_pekerja_options ? mysqli_fetch_all($result_pekerja_options, MYSQLI_ASSOC) : [];

// 3. Siapkan pesan notifikasi & data sticky form (Tidak berubah)
$pesan_notifikasi_tambah = '';
if (isset($_SESSION['pesan_error_crud'])) {
    $pesan_notifikasi_tambah = "<div class='mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg shadow'>" . htmlspecialchars($_SESSION['pesan_error_crud']) . "</div>";
    unset($_SESSION['pesan_error_crud']); 
}
$form_data = isset($_SESSION['form_data']) ? $_SESSION['form_data'] : [];
unset($_SESSION['form_data']);

// 4. Panggil komponen template (Tidak berubah)
require_once '../includes/header.php'; 
require_once '../includes/sidebar_super_admin.php';
?>

<main class="content-wrapper mt-16 md:ml-72">
    <div class="max-w-2xl mx-auto p-4 sm:p-6 lg:p-8"> 
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 sm:p-8">
            <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 dark:text-white mb-6">
                <i class="fas fa-user-plus fa-fw mr-2 text-blue-500"></i>Buat Akun Pengguna Baru
            </h1>

            <?php echo $pesan_notifikasi_tambah; ?>

            <form action="<?php echo BASE_URL; ?>users/proses.php?aksi=tambah" method="POST">
                
                <!-- Elemen Form BARU: Dropdown untuk memilih pekerja -->
                <div class="mb-5">
                    <label for="id_pekerja" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Pilih Pekerja <span class="text-red-500">*</span></label>
                    <select name="id_pekerja" id="id_pekerja" required
                            class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                        <option value="">-- Pilih Pekerja untuk diberi akun --</option>
                        <?php if (!empty($daftar_pekerja_tersedia)): ?>
                            <?php foreach ($daftar_pekerja_tersedia as $pekerja_opt) : ?>
                                <option value="<?php echo $pekerja_opt['id_pekerja']; ?>" <?php echo (isset($form_data['id_pekerja']) && $form_data['id_pekerja'] == $pekerja_opt['id_pekerja']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($pekerja_opt['namapekerja']); ?> (<?php echo htmlspecialchars($pekerja_opt['namajabatan']); ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="" disabled>Tidak ada pekerja aktif yang belum memiliki akun.</option>
                        <?php endif; ?>
                    </select>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Hanya pekerja aktif yang belum punya akun yang muncul di sini.</p>
                </div>

                <!-- Field untuk username dan password tetap ada -->
                <div class="mb-5">
                    <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Username Baru <span class="text-red-500">*</span></label>
                    <input type="text" name="username" id="username" required maxlength="50"
                           value="<?php echo htmlspecialchars($form_data['username'] ?? ''); ?>"
                           class="w-full px-3 py-2.5 border border-gray-300 rounded-md ..."
                           placeholder="Masukkan username unik untuk login">
                </div>

                <div class="mb-5">
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Password <span class="text-red-500">*</span></label>
                    <input type="password" name="password" id="password" required
                           class="w-full px-3 py-2.5 border border-gray-300 rounded-md ..."
                           placeholder="Minimal 6 karakter">
                </div>
                
                <div class="mb-6">
                    <label for="konfirmasi_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Konfirmasi Password <span class="text-red-500">*</span></label>
                    <input type="password" name="konfirmasi_password" id="konfirmasi_password" required
                           class="w-full px-3 py-2.5 border border-gray-300 rounded-md ..."
                           placeholder="Ketik ulang password">
                </div>

                <div class="mt-8 flex items-center justify-end gap-x-4 border-t border-gray-200 dark:border-gray-700 pt-6">
                    <a href="<?php echo BASE_URL; ?>users/" class="text-sm font-semibold ...">Batal</a>
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 ...">
                        <i class="fas fa-user-check fa-fw mr-2"></i>Buat Akun
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php
require_once '../includes/footer.php'; 
?>
