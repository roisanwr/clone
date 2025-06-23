<?php
// File: proyek_jaya/pekerja/tambah.php

// 1. Panggil Konfigurasi dan mulai session
require_once '../config.php';

// 2. Autentikasi: Pastikan pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'auth/login.php?error=2');
    exit;
}

// 3. Autorisasi: Hanya Super Admin dan Admin yang boleh menambah pekerja
$allowed_roles = ['super_admin', 'admin', 'mandor'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    $_SESSION['pesan_error'] = "ANDA TIDAK MEMILIKI HAK AKSES UNTUK MENAMBAH DATA PEKERJA.";
    header('Location: ' . BASE_URL . 'pekerja/'); // Redirect kembali ke daftar pekerja
    exit;
}

// Ambil role pengguna dari session untuk sidebar
$user_role = $_SESSION['role'];

// 4. Ambil data JABATAN dari database untuk ditampilkan di dropdown
$query_jabatan_dropdown = "SELECT id_jabatan, namajabatan FROM jabatan ORDER BY namajabatan ASC";
$result_jabatan_dropdown = mysqli_query($koneksi, $query_jabatan_dropdown);
// Jika query gagal, ini bisa jadi masalah. Untuk sekarang, kita asumsikan berhasil.
// Idealnya ada error handling di sini.
if (!$result_jabatan_dropdown) {
    // Sebaiknya ada penanganan error jika gagal mengambil data jabatan
    // Misalnya, redirect dengan pesan error atau tampilkan pesan di halaman.
    $_SESSION['pesan_error_crud'] = "Gagal memuat data jabatan untuk form. Pastikan ada data di tabel jabatan.";
    // Untuk sementara, kita biarkan dropdown kosong jika error, tapi ini perlu perhatian lebih.
    // header('Location: ' . BASE_URL . 'pekerja/'); // Atau redirect
    // exit;
    $daftar_jabatan_options = []; // Jadikan array kosong agar tidak error di loop
} else {
    $daftar_jabatan_options = mysqli_fetch_all($result_jabatan_dropdown, MYSQLI_ASSOC);
}


// 5. Siapkan pesan notifikasi (jika ada dari proses.php setelah validasi gagal)
$pesan_notifikasi_tambah = '';
if (isset($_SESSION['pesan_error_crud'])) {
    $pesan_notifikasi_tambah = "<div class='mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg shadow'>" . htmlspecialchars($_SESSION['pesan_error_crud']) . "</div>";
    unset($_SESSION['pesan_error_crud']); 
}

// Ambil data form yang gagal divalidasi (untuk fitur "sticky form")
$form_data = isset($_SESSION['form_data']) ? $_SESSION['form_data'] : [];
unset($_SESSION['form_data']); // Hapus setelah diambil agar tidak muncul lagi di refresh berikutnya


// 6. Memanggil komponen template: HEADER
require_once '../includes/header.php'; 

// 7. Memanggil komponen template: SIDEBAR (sesuai peran pengguna)
if ($user_role == 'super_admin') {
    require_once '../includes/sidebar_super_admin.php';
} elseif ($user_role == 'admin') {
    require_once '../includes/sidebar_admin.php';
} elseif ($user_role == 'mandor') {
    require_once '../includes/sidebar_mandor.php';
}
?>

<main class="content-wrapper mt-16 md:ml-72">
    <div class="max-w-3xl mx-auto p-4 sm:p-6 lg:p-8"> 

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 sm:p-8">
            <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 dark:text-white mb-6">
                <i class="fas fa-user-plus fa-fw mr-2 text-blue-500"></i>Tambah Pekerja Baru
            </h1>

            <?php echo $pesan_notifikasi_tambah; // Tampilkan pesan error validasi jika ada ?>

            <form action="<?php echo BASE_URL; ?>pekerja/proses.php?aksi=tambah" method="POST">
                <div class="mb-5">
                    <label for="namapekerja" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nama Pekerja <span class="text-red-500">*</span></label>
                    <input type="text" name="namapekerja" id="namapekerja" required maxlength="100"
                           value="<?php echo htmlspecialchars($form_data['namapekerja'] ?? ''); // Sticky form ?>"
                           class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white placeholder-gray-400 dark:placeholder-gray-500"
                           placeholder="Masukkan nama lengkap pekerja">
                </div>

                <div class="mb-5">
                    <label for="id_jabatan" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Jabatan <span class="text-red-500">*</span></label>
                    <select name="id_jabatan" id="id_jabatan" required
                            class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                        <option value="">-- Pilih Jabatan --</option>
                        <?php if (!empty($daftar_jabatan_options)) : ?>
                            <?php foreach ($daftar_jabatan_options as $jabatan_opt) : ?>
                                <option value="<?php echo $jabatan_opt['id_jabatan']; ?>" 
                                    <?php echo (isset($form_data['id_jabatan']) && $form_data['id_jabatan'] == $jabatan_opt['id_jabatan']) ? 'selected' : ''; // Sticky form ?>>
                                    <?php echo htmlspecialchars($jabatan_opt['namajabatan']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="" disabled>Data jabatan tidak tersedia atau kosong</option>
                        <?php endif; ?>
                    </select>
                    <?php if (empty($daftar_jabatan_options)): ?>
                        <p class="mt-1 text-xs text-yellow-600 dark:text-yellow-400">Tip: Tambahkan data jabatan terlebih dahulu di menu Jabatan agar muncul di sini.</p>
                    <?php endif; ?>
                </div>

                <div class="mb-5">
                    <label for="no_hp" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">No. Handphone</label>
                    <input type="text" name="no_hp" id="no_hp" maxlength="20"
                           value="<?php echo htmlspecialchars($form_data['no_hp'] ?? ''); // Sticky form ?>"
                           class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white placeholder-gray-400 dark:placeholder-gray-500"
                           placeholder="Contoh: 081234567890 (opsional)">
                </div>

                <div class="mb-5">
                    <label for="no_rek" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">No. Rekening</label>
                    <input type="text" name="no_rek" id="no_rek" maxlength="30"
                           value="<?php echo htmlspecialchars($form_data['no_rek'] ?? ''); // Sticky form ?>"
                           class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white placeholder-gray-400 dark:placeholder-gray-500"
                           placeholder="Masukkan nomor rekening bank (opsional)">
                </div>


                <div class="mt-8 flex items-center justify-end gap-x-4 border-t border-gray-200 dark:border-gray-700 pt-6">
                    <a href="<?php echo BASE_URL; ?>pekerja/" class="text-sm font-semibold leading-6 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition duration-150 ease-in-out">
                        Batal
                    </a>
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md shadow-sm transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800">
                        <i class="fas fa-save fa-fw mr-2"></i>
                        Simpan Data Pekerja
                    </button>
                </div>
            </form>
        </div> </div> </main>

<?php
// 9. Memanggil komponen template: FOOTER
require_once '../includes/footer.php'; 
?>