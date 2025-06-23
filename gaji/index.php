<?php
// File: proyek_jaya/gaji/index.php
// Deskripsi: Halaman untuk menampilkan riwayat laporan gaji yang sudah difinalisasi.

require_once '../config.php';

// 1. Autentikasi & Autorisasi: HANYA Super Admin dan Admin
if (!isset($_SESSION['user_id'])) { header('Location: ' . BASE_URL . 'auth/login.php?error=2'); exit; }
$user_role = $_SESSION['role'];
if (!in_array($user_role, ['super_admin', 'admin'])) {
    $_SESSION['pesan_error'] = "MAAF, ANDA TIDAK MEMILIKI HAK AKSES KE HALAMAN INI.";
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

// 2. Ambil data Riwayat Periode Gaji
// Kita ambil periode unik yang sudah ada di tabel gaji.
$sql_riwayat = "SELECT DISTINCT periode_start, periode_end FROM gaji ORDER BY periode_start DESC";
$result_riwayat = mysqli_query($koneksi, $sql_riwayat);
$daftar_riwayat_gaji = $result_riwayat ? mysqli_fetch_all($result_riwayat, MYSQLI_ASSOC) : [];

// 3. Siapkan Notifikasi
$pesan_notifikasi = '';
if (isset($_SESSION['pesan_sukses_gaji'])) {
    $pesan_notifikasi = "<div class='mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg shadow-lg'>" . htmlspecialchars($_SESSION['pesan_sukses_gaji']) . "</div>";
    unset($_SESSION['pesan_sukses_gaji']); 
}

// Panggil Header & Sidebar
require_once '../includes/header.php'; 
// Asumsikan menu 'gaji' sudah ditambahkan di sidebar_admin dan sidebar_super_admin
if ($user_role == 'super_admin') { require_once '../includes/sidebar_super_admin.php'; } 
elseif ($user_role == 'admin') { require_once '../includes/sidebar_admin.php'; }
?>

<main class="content-wrapper mt-16 md:ml-72">
    <div class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 sm:p-8">
            <div class="flex flex-col sm:flex-row justify-between items-start gap-4 mb-6">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 dark:text-white">
                        <i class="fas fa-archive fa-fw mr-3 text-blue-500"></i>Arsip Laporan Gaji
                    </h1>
                    <p class="text-md text-gray-600 dark:text-gray-400 mt-1">Daftar semua laporan gaji yang telah difinalisasi.</p>
                </div>
                <a href="<?php echo BASE_URL; ?>gaji/generate.php" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md shadow-sm">
                    <i class="fas fa-plus fa-fw mr-2"></i> Buat Laporan Gaji Baru
                </a>
            </div>
            
            <?php echo $pesan_notifikasi; ?>

            <div class="overflow-x-auto shadow-md rounded-lg">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-100 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">No.</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Periode Penggajian</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php if (!empty($daftar_riwayat_gaji)): ?>
                            <?php foreach ($daftar_riwayat_gaji as $i => $riwayat): ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-gray-300"><?php echo $i + 1; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-white">
                                        <?php echo date('d F Y', strtotime($riwayat['periode_start'])); ?> - <?php echo date('d F Y', strtotime($riwayat['periode_end'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <a href="<?php echo BASE_URL; ?>gaji/detail_laporan.php?start=<?php echo $riwayat['periode_start']; ?>&end=<?php echo $riwayat['periode_end']; ?>" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700">
                                            <i class="fas fa-eye fa-fw mr-1"></i> Lihat Detail
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center p-5 text-gray-500 dark:text-gray-400">
                                    Belum ada laporan gaji yang difinalisasi.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php
require_once '../includes/footer.php';
?>
