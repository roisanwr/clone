<?php
// File: proyek_jaya/proyek/tambah.php

// 1. Panggil Konfigurasi dan mulai session
require_once '../config.php';

// 2. Autentikasi: Pastikan pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'auth/login.php?error=2');
    exit;
}

// 3. Autorisasi: Hanya Super Admin dan Admin yang boleh menambah proyek
$allowed_roles = ['super_admin', 'admin'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    $_SESSION['pesan_error'] = "ANDA TIDAK MEMILIKI HAK AKSES UNTUK MENAMBAH DATA PROYEK.";
    header('Location: ' . BASE_URL . 'proyek/'); 
    exit;
}

$user_role = $_SESSION['role']; // Ambil role untuk sidebar

// 4. Ambil data KLIEN untuk dropdown
$query_klien_dropdown = "SELECT id_klien, nama_klien FROM klien ORDER BY nama_klien ASC";
$result_klien_dropdown = mysqli_query($koneksi, $query_klien_dropdown);
$daftar_klien_options = $result_klien_dropdown ? mysqli_fetch_all($result_klien_dropdown, MYSQLI_ASSOC) : [];

// 5. Ambil data PEKERJA (yang aktif) untuk dropdown Mandor Penanggung Jawab
// Anda bisa tambahkan filter berdasarkan id_jabatan jika punya ID khusus untuk Mandor
$nama_jabatan_untuk_mandor_pj = 'Mandor'; // PASTIKAN NAMA INI SAMA PERSIS DENGAN YANG ADA DI TABEL JABATANMU
                                       // Jika nama jabatan untuk mandor di tabelmu beda (misal "Kepala Lapangan"), ganti di sini.

$query_pekerja_dropdown = "SELECT pekerja.id_pekerja, pekerja.namapekerja 
                           FROM pekerja 
                           INNER JOIN jabatan ON pekerja.id_jabatan = jabatan.id_jabatan 
                           WHERE pekerja.is_active = 1 AND jabatan.namajabatan = ?
                           ORDER BY pekerja.namapekerja ASC";

$stmt_pekerja_dropdown = mysqli_prepare($koneksi, $query_pekerja_dropdown);
$daftar_pekerja_options = []; // Inisialisasi sebagai array kosong

if ($stmt_pekerja_dropdown) {
    mysqli_stmt_bind_param($stmt_pekerja_dropdown, "s", $nama_jabatan_untuk_mandor_pj);
    mysqli_stmt_execute($stmt_pekerja_dropdown);
    $result_pekerja_dropdown = mysqli_stmt_get_result($stmt_pekerja_dropdown);
    
    if ($result_pekerja_dropdown) {
        $daftar_pekerja_options = mysqli_fetch_all($result_pekerja_dropdown, MYSQLI_ASSOC);
    } else {
        // Bisa tambahkan error log jika query gagal mendapatkan hasil
        // error_log("Gagal fetch hasil query pekerja (mandor) dropdown: " . mysqli_error($koneksi));
    }
    mysqli_stmt_close($stmt_pekerja_dropdown);
} else {
    // Bisa tambahkan error log jika prepare statement gagal
    // error_log("Gagal prepare statement pekerja (mandor) dropdown: " . mysqli_error($koneksi));
}

// Siapkan pesan jika tidak ada mandor yang ditemukan
$pesan_jika_mandor_kosong = '';
if (empty($daftar_pekerja_options)) {
    $pesan_jika_mandor_kosong = "<p class='mt-1 text-xs text-yellow-500 dark:text-yellow-400'>Tip: Tidak ditemukan pekerja aktif dengan jabatan '" . htmlspecialchars($nama_jabatan_untuk_mandor_pj) . "'. Pastikan data sudah benar di Manajemen Pekerja dan Jabatan.</p>";
}
// 6. Siapkan pesan notifikasi & data sticky form
$pesan_notifikasi_tambah = '';
if (isset($_SESSION['pesan_error_crud'])) {
    $pesan_notifikasi_tambah = "<div class='mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg shadow'>" . htmlspecialchars($_SESSION['pesan_error_crud']) . "</div>";
    unset($_SESSION['pesan_error_crud']); 
}
$form_data = isset($_SESSION['form_data']) ? $_SESSION['form_data'] : [];
unset($_SESSION['form_data']);

// 7. Memanggil komponen template: HEADER
require_once '../includes/header.php'; 

// 8. Memanggil komponen template: SIDEBAR
if ($user_role == 'super_admin') {
    require_once '../includes/sidebar_super_admin.php';
} elseif ($user_role == 'admin') {
    require_once '../includes/sidebar_admin.php';
}
?>

<main class="content-wrapper mt-16 md:ml-72">
    <div class="max-w-4xl mx-auto p-4 sm:p-6 lg:p-8"> 
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 sm:p-8">
            <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 dark:text-white mb-6">
                <i class="fas fa-folder-plus fa-fw mr-2 text-blue-500"></i>Tambah Proyek Baru
            </h1>

            <?php echo $pesan_notifikasi_tambah; ?>

            <form action="<?php echo BASE_URL; ?>proyek/proses.php?aksi=tambah" method="POST">
                <div class="mb-5">
                    <label for="namaprojek" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nama Proyek <span class="text-red-500">*</span></label>
                    <input type="text" name="namaprojek" id="namaprojek" required maxlength="150"
                           value="<?php echo htmlspecialchars($form_data['namaprojek'] ?? ''); ?>"
                           class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white placeholder-gray-400 dark:placeholder-gray-500"
                           placeholder="Masukkan nama proyek">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-5">
                    <div>
                        <label for="id_klien" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Klien Pemilik Proyek <span class="text-red-500">*</span></label>
                        <select name="id_klien" id="id_klien" required
                                class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            <option value="">-- Pilih Klien --</option>
                            <?php foreach ($daftar_klien_options as $klien_opt) : ?>
                                <option value="<?php echo $klien_opt['id_klien']; ?>" <?php echo (isset($form_data['id_klien']) && $form_data['id_klien'] == $klien_opt['id_klien']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($klien_opt['nama_klien']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="id_mandor_pekerja" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Mandor Penanggung Jawab <span class="text-red-500">*</span></label>
                        <select name="id_mandor_pekerja" id="id_mandor_pekerja" required
                                class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            <option value="">-- Pilih Mandor Penanggung jawab --</option>
                            <?php foreach ($daftar_pekerja_options as $pekerja_opt) : ?>
                                <option value="<?php echo $pekerja_opt['id_pekerja']; ?>" <?php echo (isset($form_data['id_mandor_pekerja']) && $form_data['id_mandor_pekerja'] == $pekerja_opt['id_pekerja']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($pekerja_opt['namapekerja']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-5">
                    <label for="jenisprojek" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Jenis Proyek</label>
                    <input type="text" name="jenisprojek" id="jenisprojek" maxlength="100"
                           value="<?php echo htmlspecialchars($form_data['jenisprojek'] ?? ''); ?>"
                           class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white placeholder-gray-400 dark:placeholder-gray-500"
                           placeholder="Contoh: Pembangunan Rumah Tipe 70, Renovasi Gedung (opsional)">
                </div>

                <div class="mb-5">
                    <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status Proyek <span class="text-red-500">*</span></label>
                    <select name="status" id="status" required
                            class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                        <option value="planning" <?php echo (isset($form_data['status']) && $form_data['status'] == 'planning') ? 'selected' : ((!isset($form_data['status'])) ? 'selected' : ''); // Default ke planning ?>>Planning</option>
                        <option value="active" <?php echo (isset($form_data['status']) && $form_data['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                        <option value="completed" <?php echo (isset($form_data['status']) && $form_data['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>

                <div class="mb-5">
                    <label for="lokasi" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Lokasi Proyek</label>
                    <textarea name="lokasi" id="lokasi" rows="3"
                              class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white placeholder-gray-400 dark:placeholder-gray-500"
                              placeholder="Masukkan alamat atau deskripsi lokasi proyek (opsional)"><?php echo htmlspecialchars($form_data['lokasi'] ?? ''); ?></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="tanggal_mulai_projek" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tanggal Mulai Proyek</label>
                        <input type="date" name="tanggal_mulai_projek" id="tanggal_mulai_projek"
                               value="<?php echo htmlspecialchars($form_data['tanggal_mulai_projek'] ?? ''); ?>"
                               class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                    </div>

                    <div>
                        <label for="tanggal_selesai_projek" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tanggal Selesai Proyek (Estimasi/Aktual)</label>
                        <input type="date" name="tanggal_selesai_projek" id="tanggal_selesai_projek"
                               value="<?php echo htmlspecialchars($form_data['tanggal_selesai_projek'] ?? ''); ?>"
                               class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                    </div>
                </div>

                <div class="mt-8 flex items-center justify-end gap-x-4 border-t border-gray-200 dark:border-gray-700 pt-6">
                    <a href="<?php echo BASE_URL; ?>proyek/" class="text-sm font-semibold leading-6 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition duration-150 ease-in-out">
                        Batal
                    </a>
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md shadow-sm transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800">
                        <i class="fas fa-save fa-fw mr-2"></i>
                        Simpan Data Proyek
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php
// 10. Memanggil komponen template: FOOTER
require_once '../includes/footer.php'; 
?>