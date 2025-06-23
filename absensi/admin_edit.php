<?php
// File: proyek_jaya/absensi/admin_edit.php
// Deskripsi: Halaman khusus Admin untuk mengedit satu data absensi tanpa batasan.
// VERSI UPDATE: Menambahkan tombol Hapus permanen hanya untuk Super Admin.

require_once '../config.php';

// 1. Autentikasi & Autorisasi: HANYA Super Admin dan Admin
if (!isset($_SESSION['user_id'])) { header('Location: ' . BASE_URL . 'auth/login.php?error=2'); exit; }
$user_role = $_SESSION['role'];
if (!in_array($user_role, ['super_admin', 'admin'])) {
    $_SESSION['pesan_error'] = "MAAF, ANDA TIDAK MEMILIKI HAK AKSES KE HALAMAN INI.";
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

// 2. Validasi & Ambil Data Absensi yang Akan Diedit
if (!isset($_GET['id_absensi']) || !is_numeric($_GET['id_absensi'])) {
    $_SESSION['pesan_error_crud'] = "ID Absensi tidak valid.";
    header('Location: ' . BASE_URL . 'absensi/index.php');
    exit;
}
$id_absensi_edit = intval($_GET['id_absensi']);

// Query untuk mengambil detail absensi yang spesifik
$sql_get_absensi = "SELECT a.*, p.namapekerja, pr.namaprojek 
                    FROM absensi a
                    JOIN pekerja p ON a.id_pekerja = p.id_pekerja
                    JOIN projek pr ON a.id_projek = pr.id_projek
                    WHERE a.id_absensi = ?";
$stmt_get = mysqli_prepare($koneksi, $sql_get_absensi);
$absensi_lama = null;
if($stmt_get){
    mysqli_stmt_bind_param($stmt_get, "i", $id_absensi_edit);
    mysqli_stmt_execute($stmt_get);
    $result_get = mysqli_stmt_get_result($stmt_get);
    $absensi_lama = mysqli_fetch_assoc($result_get);
    mysqli_stmt_close($stmt_get);
}

if (!$absensi_lama) {
    $_SESSION['pesan_error_crud'] = "Data absensi dengan ID #$id_absensi_edit tidak ditemukan.";
    header('Location: ' . BASE_URL . 'absensi/index.php');
    exit;
}

// 3. Siapkan Notifikasi
$pesan_notifikasi = '';
if (isset($_SESSION['pesan_sukses'])) {
    $pesan_notifikasi = "<div class='mb-4 p-3 bg-green-100 text-green-800 rounded-lg'>" . htmlspecialchars($_SESSION['pesan_sukses']) . "</div>";
    unset($_SESSION['pesan_sukses']); 
} elseif (isset($_SESSION['pesan_error_crud'])) {
    $pesan_notifikasi = "<div class='mb-4 p-3 bg-red-100 text-red-800 rounded-lg'>" . $_SESSION['pesan_error_crud'] . "</div>";
    unset($_SESSION['pesan_error_crud']);
}

// Panggil Header & Sidebar
require_once '../includes/header.php'; 
if ($user_role == 'super_admin') { require_once '../includes/sidebar_super_admin.php'; } 
elseif ($user_role == 'admin') { require_once '../includes/sidebar_admin.php'; }
?>
<main class="content-wrapper mt-16 md:ml-72">
    <div class="max-w-2xl mx-auto p-4 sm:p-6 lg:p-8"> 
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 sm:p-8">
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white mb-6">
                <i class="fas fa-edit fa-fw mr-3 text-blue-500"></i>Koreksi Data Absensi
            </h1>
            
            <?php echo $pesan_notifikasi; ?>

            <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-700 space-y-2">
                <p class="text-sm text-gray-600 dark:text-gray-400">Proyek: <strong class="text-gray-800 dark:text-white"><?php echo htmlspecialchars($absensi_lama['namaprojek']); ?></strong></p>
                <p class="text-sm text-gray-600 dark:text-gray-400">Pekerja: <strong class="text-gray-800 dark:text-white"><?php echo htmlspecialchars($absensi_lama['namapekerja']); ?></strong></p>
                <p class="text-sm text-gray-600 dark:text-gray-400">Tanggal: <strong class="text-gray-800 dark:text-white"><?php echo date('d F Y', strtotime($absensi_lama['tanggal'])); ?></strong></p>
            </div>

            <form action="<?php echo BASE_URL; ?>absensi/proses_admin_edit.php?aksi=simpan" method="POST">
                <input type="hidden" name="id_absensi" value="<?php echo $id_absensi_edit; ?>">
                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status Kehadiran <span class="text-red-500">*</span></label>
                    <div class="flex items-center space-x-6">
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="radio" name="status_hadir" value="1" class="form-radio h-4 w-4 text-green-600" 
                                <?php echo ($absensi_lama['status_hadir'] == '1') ? 'checked' : ''; ?>>
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Hadir</span>
                        </label>
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="radio" name="status_hadir" value="0" class="form-radio h-4 w-4 text-red-600" 
                                <?php echo ($absensi_lama['status_hadir'] == '0') ? 'checked' : ''; ?>>
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Tidak Hadir</span>
                        </label>
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Lembur</label>
                    <label class="inline-flex items-center cursor-pointer">
                         <input type="hidden" name="lembur" value="0">
                         <input type="checkbox" name="lembur" value="1" class="form-checkbox h-5 w-5 rounded text-blue-600"
                            <?php echo ($absensi_lama['lembur'] == '1') ? 'checked' : ''; ?>>
                         <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Ya, pekerja ini lembur</span>
                    </label>
                </div>
                
                <div class="mb-6">
                    <label for="keterangan" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Keterangan (Opsional)</label>
                    <textarea name="keterangan" id="keterangan" rows="3" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm dark:bg-gray-900 dark:text-white focus:ring-blue-500 focus:border-blue-500"><?php echo htmlspecialchars($absensi_lama['keterangan'] ?? ''); ?></textarea>
                </div>

                <div class="mt-8 flex items-center justify-end gap-x-4 border-t border-gray-200 dark:border-gray-700 pt-6">
                    <a href="<?php echo BASE_URL; ?>absensi/index.php" class="text-sm font-semibold text-gray-600 dark:text-gray-300 hover:text-gray-800">Batal</a>
                    <button type="submit" name="simpan_koreksi" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-save fa-fw mr-2"></i> Simpan Koreksi
                    </button>
                </div>
            </form>

            <!-- ====================================================== -->
            <!-- BAGIAN BARU: Area Berbahaya untuk Super Admin -->
            <!-- ====================================================== -->
            <?php if ($user_role === 'super_admin'): ?>
                <div class="mt-10 pt-6 border-t border-dashed border-red-300 dark:border-red-700">
                    <h3 class="text-lg font-semibold text-red-600 dark:text-red-400">Area Berbahaya</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Tindakan ini tidak dapat diurungkan. Menghapus data absensi ini akan menghilangkannya secara permanen dari sistem.</p>
                    <div class="mt-4">
                        <form action="<?php echo BASE_URL; ?>absensi/proses_admin_edit.php?aksi=hapus" method="POST" onsubmit="return confirm('PERINGATAN:\n\nAnda akan menghapus data absensi ini secara permanen.\n\nApakah Anda benar-benar yakin?');">
                            <input type="hidden" name="id_absensi_hapus" value="<?php echo $id_absensi_edit; ?>">
                            <button type="submit" name="hapus_absensi" class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                <i class="fas fa-trash-alt fa-fw mr-2"></i>
                                Ya, Hapus Data Absensi Ini
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
            <!-- ====================================================== -->
            <!-- AKHIR BAGIAN BARU                                      -->
            <!-- ====================================================== -->

        </div>
    </div>
</main>
<?php require_once '../includes/footer.php'; ?>
