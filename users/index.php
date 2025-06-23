<?php
// File: proyek_jaya/users/index.php

// 1. Panggil Konfigurasi dan mulai session
require_once '../config.php'; 

// 2. Autentikasi: Pastikan pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'auth/login.php?error=2'); 
    exit;
}

// 3. Autorisasi: HANYA Super Admin yang boleh mengakses halaman ini.
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    $_SESSION['pesan_error'] = "MAAF, HANYA SUPER ADMIN YANG DAPAT MENGAKSES HALAMAN MANAJEMEN PENGGUNA.";
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$user_role = $_SESSION['role']; // Meskipun hanya super_admin, kita tetap ambil untuk konsistensi panggil sidebar

// 4. Ambil semua data pengguna dari database
$query_users = "SELECT 
                    users.id_user, users.username, users.role, users.is_active, 
                    pekerja.namapekerja AS nama_pekerja_terkait 
                FROM users 
                LEFT JOIN pekerja ON users.id_pekerja_ref = pekerja.id_pekerja 
                ORDER BY users.username ASC";

$result_users = mysqli_query($koneksi, $query_users);

if (!$result_users) {
    $error_query = "Error mengambil data pengguna: " . mysqli_error($koneksi);
}

// 5. Siapkan pesan notifikasi
$pesan_notifikasi = '';
if (isset($_SESSION['pesan_sukses'])) {
    $pesan_notifikasi = "<div class='mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg shadow'>" . htmlspecialchars($_SESSION['pesan_sukses']) . "</div>";
    unset($_SESSION['pesan_sukses']); 
} elseif (isset($_SESSION['pesan_error_crud'])) {
    $pesan_notifikasi = "<div class='mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg shadow'>" . htmlspecialchars($_SESSION['pesan_error_crud']) . "</div>";
    unset($_SESSION['pesan_error_crud']);
}

// 6. Memanggil komponen template: HEADER
require_once '../includes/header.php'; 

// 7. Memanggil komponen template: SIDEBAR (hanya sidebar_super_admin.php)
// Pastikan di sidebar_super_admin.php sudah ada link ke users/ dan logika $menu_aktif = 'users';
require_once '../includes/sidebar_super_admin.php';
?>

<main class="content-wrapper mt-16 md:ml-72">
    <div class="max-w-full mx-auto p-4 sm:p-6 lg:p-8"> 
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 dark:text-white">
                    Manajemen Pengguna Sistem
                </h1>
                <a href="<?php echo BASE_URL; ?>users/tambah.php" 
                   class="mt-3 sm:mt-0 inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md shadow-sm transition duration-150 ease-in-out">
                    <i class="fas fa-user-plus fa-fw mr-2"></i>
                    Tambah Pengguna Baru
                </a>
            </div>

            <?php echo $pesan_notifikasi; ?>

            <?php if (isset($error_query)): ?>
                <div class='mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg shadow'><?php echo htmlspecialchars($error_query); ?></div>
            <?php endif; ?>

            <div class="overflow-x-auto shadow-md rounded-lg">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-100 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">No.</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Username</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Peran</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Pekerja Terkait (Mandor)</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php if ($result_users && mysqli_num_rows($result_users) > 0) : ?>
                            <?php $nomor = 1; ?>
                            <?php while ($user = mysqli_fetch_assoc($result_users)) : ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-600/50 transition-colors duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200"><?php echo $nomor++; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $user['role']))); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200"><?php echo $user['nama_pekerja_terkait'] ? htmlspecialchars($user['nama_pekerja_terkait']) : '-'; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                        <?php if ($user['is_active'] == 1) : ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-100">Aktif</span>
                                        <?php else : ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-700 dark:text-red-100">Tidak Aktif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-center">
                                        <a href="<?php echo BASE_URL; ?>users/edit.php?id=<?php echo $user['id_user']; ?>" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-200 mr-3 transition-colors duration-150" title="Edit Pengguna">
                                            <i class="fas fa-pencil-alt fa-fw"></i> <span class="sr-only">Edit</span>
                                        </a>
                                        
                                        <?php // Tombol Hapus Permanen hanya muncul jika BUKAN akun Super Admin sendiri
                                        if (isset($_SESSION['user_id']) && isset($user['id_user']) && $_SESSION['user_id'] != $user['id_user']) : ?>
                                            <a href="<?php echo BASE_URL; ?>users/proses.php?aksi=hapus_permanen&id=<?php echo $user['id_user']; ?>" 
                                               class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-200 transition-colors duration-150" 
                                               onclick="return confirm('PERINGATAN KERAS!\nAnda akan MENGHAPUS PERMANEN pengguna <?php echo htmlspecialchars(addslashes($user['username'])); ?>.\n\nSEMUA DATA YANG TERKAIT LANGSUNG DENGAN PENGGUNA INI BISA IKUT TERPENGARUH ATAU TERHAPUS (misalnya jika pengguna ini adalah Mandor dengan proyek atau data absensi).\n\nTINDAKAN INI TIDAK DAPAT DIBATALKAN!\n\nApakah Anda benar-benar yakin ingin melanjutkan?');" 
                                               title="Hapus Pengguna Secara Permanen">
                                               <i class="fas fa-trash-alt fa-fw"></i> <span class="sr-only">Hapus Permanen</span>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Belum ada data pengguna sistem. Silakan klik "Tambah Pengguna Baru".
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <?php
    // 9. Memanggil komponen template: FOOTER
    require_once '../includes/footer.php'; 
    ?>
    ```
<!-- 
**Perubahan Utama di Kolom "Aksi":**

1.  **Ikon Edit:** Sudah diubah menjadi `<i class="fas fa-pencil-alt fa-fw"></i>` (ikon pensil).
2.  **Ikon Hapus:** Sudah diubah menjadi `<i class="fas fa-trash-alt fa-fw"></i>` (ikon tong sampah).
3.  **`href` untuk Hapus:** Diubah menjadi `proses.php?aksi=hapus_permanen&id=...`. Ini agar di `proses.php` kita bisa bedakan dengan jelas antara aksi "nonaktifkan" (yang mungkin kita lakukan via edit) dengan "hapus permanen".
4.  **Pesan Konfirmasi `onclick` untuk Hapus Permanen:** Pesannya dibuat jauh lebih tegas dan detail mengenai risiko kehilangan data dan dampak ke data terkait. Ini sangat penting untuk aksi destruktif seperti hapus permanen.
5.  **Logika `if ($_SESSION['user_id'] != $user['id_user'])`:** Tetap kita pertahankan sesuai permintaanmu, jadi Super Admin tidak bisa melihat tombol untuk menghapus permanen akunnya sendiri dari daftar ini.

**Langkah Selanjutnya yang Perlu Kita Ingat:**

* **Di `proyek_jaya/users/edit.php` (Form Edit Pengguna):**
    * Di sinilah Super Admin akan bisa mengubah status `is_active` pengguna (mengaktifkan atau menonaktifkan). Jadi, form edit akan punya radio button untuk `is_active`.
* **Di `proyek_jaya/users/proses.php`:**
    * Kita perlu buat logika untuk `aksi=tambah` (menambah pengguna baru).
    * Kita perlu buat logika untuk `aksi=edit` (memperbarui data pengguna, termasuk `is_active`).
    * Kita perlu buat logika untuk `aksi=hapus_permanen` (melakukan `DELETE FROM users ...` dengan semua pengecekan dependensi data yang sangat ketat, terutama jika user adalah Mandor).

Silakan kamu ganti dulu isi `proyek_jaya/users/index.php` dengan kode yang sudah disesuaikan ini, Kapten. Pastikan ikonnya sudah berubah sesuai keinginanmu dan logika pencegahan hapus diri sendiri tetap berjalan.

Setelah halaman `index.php` ini siap, kita mau lanjut ke mana?
Apakah kita mau buat **form `users/tambah.php`** dulu? Atau langsung merakit 'mesin pengolahnya' di **`users/proses.php`** untuk ketiga aksi tersebut (Tambah, Edit (termasuk nonaktifkan/aktifkan), dan Hapus Permanen)?

Biasanya, membuat form (`tambah.php`) dulu akan lebih enak alurnya. Bagaimana menurutmu? 😊 -->