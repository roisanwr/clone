<?php
// File: proyek_jaya/gaji/detail_laporan.php
// Deskripsi: Menampilkan rincian laporan gaji yang sudah final untuk satu periode.

require_once '../config.php';

// 1. Autentikasi & Autorisasi: HANYA Super Admin dan Admin
if (!isset($_SESSION['user_id'])) { header('Location: ' . BASE_URL . 'auth/login.php?error=2'); exit; }
$user_role = $_SESSION['role'];
if (!in_array($user_role, ['super_admin', 'admin'])) {
    $_SESSION['pesan_error'] = "MAAF, ANDA TIDAK MEMILIKI HAK AKSES KE HALAMAN INI.";
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

// 2. Ambil & Validasi Parameter Periode dari URL
$periode_start = $_GET['start'] ?? '';
$periode_end = $_GET['end'] ?? '';

if (empty($periode_start) || empty($periode_end)) {
    $_SESSION['pesan_error_gaji'] = "Periode laporan tidak valid.";
    header('Location: ' . BASE_URL . 'gaji/index.php');
    exit;
}

// 3. Ambil data Gaji yang sudah difinalisasi untuk periode ini
$sql_detail = "SELECT 
                    g.*, 
                    p.namapekerja, 
                    j.namajabatan 
               FROM gaji g
               JOIN pekerja p ON g.id_pekerja = p.id_pekerja
               JOIN jabatan j ON p.id_jabatan = j.id_jabatan
               WHERE g.periode_start = ? AND g.periode_end = ?
               ORDER BY p.namapekerja ASC";

$stmt_detail = mysqli_prepare($koneksi, $sql_detail);
$daftar_gaji_detail = [];
if ($stmt_detail) {
    mysqli_stmt_bind_param($stmt_detail, "ss", $periode_start, $periode_end);
    mysqli_stmt_execute($stmt_detail);
    $result = mysqli_stmt_get_result($stmt_detail);
    $daftar_gaji_detail = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt_detail);
}


// Panggil Header & Sidebar
require_once '../includes/header.php'; 
if ($user_role == 'super_admin') { require_once '../includes/sidebar_super_admin.php'; } 
elseif ($user_role == 'admin') { require_once '../includes/sidebar_admin.php'; }
?>

<main class="content-wrapper mt-16 md:ml-72">
    <div class="max-w-full mx-auto p-4 sm:p-6 lg:p-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 sm:p-8">
            <!-- Header Halaman -->
            <div class="mb-5">
                <a href="<?php echo BASE_URL; ?>gaji/index.php" class="text-sm text-blue-600 dark:text-blue-400 hover:underline mb-4 inline-block">
                    <i class="fas fa-arrow-left fa-fw mr-1"></i> Kembali ke Arsip Laporan
                </a>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 dark:text-white">
                    <i class="fas fa-receipt fa-fw mr-3 text-green-500"></i>Detail Laporan Gaji
                </h1>
                <p class="text-md text-gray-600 dark:text-gray-400 mt-1">
                    Periode: <span class="font-semibold"><?php echo date('d M Y', strtotime($periode_start)); ?></span> - 
                    <span class="font-semibold"><?php echo date('d M Y', strtotime($periode_end)); ?></span>
                </p>

                <!-- Tombol untuk mencetak semua slip gaji -->
                <?php if (!empty($daftar_gaji_detail)): // Tampilkan hanya jika ada data gaji ?>
                <div class="mt-4">
                    <a href="<?php echo BASE_URL; ?>gaji/cetak_semua_slip.php?start=<?php echo urlencode($periode_start); ?>&end=<?php echo urlencode($periode_end); ?>" target="_blank"
                       class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
                        <i class="fas fa-print fa-fw mr-2"></i> Print Semua Slip
                    </a>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="overflow-x-auto shadow-md rounded-lg">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-100 dark:bg-gray-700">
                        <tr>
                            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Pekerja</th>
                            <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Gaji Pokok</th>
                            <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Upah Lembur</th>
                            <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tunjangan</th>
                            <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Total Pendapatan</th>
                            <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Potongan</th>
                            <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Gaji Bersih</th>
                            <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php if (!empty($daftar_gaji_detail)): ?>
                            <?php foreach ($daftar_gaji_detail as $gaji): ?>
                                <?php $total_tunjangan = $gaji['tunjangan_transport_manual'] + $gaji['tunjangan_kesehatan_manual'] + $gaji['tunjangan_rumah_manual']; ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-3 py-4 whitespace-nowrap">
                                        <div class="font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($gaji['namapekerja']); ?></div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($gaji['namajabatan']); ?></div>
                                    </td>
                                    <td class="px-3 py-4 text-right text-sm text-gray-800 dark:text-gray-300">Rp <?php echo number_format($gaji['gaji_pokok_bayar'], 0, ',', '.'); ?></td>
                                    <td class="px-3 py-4 text-right text-sm text-gray-800 dark:text-gray-300">Rp <?php echo number_format($gaji['lembur_pay'], 0, ',', '.'); ?></td>
                                    <td class="px-3 py-4 text-right text-sm text-gray-800 dark:text-gray-300">Rp <?php echo number_format($total_tunjangan, 0, ',', '.'); ?></td>
                                    <td class="px-3 py-4 text-right text-sm font-semibold text-gray-900 dark:text-white">Rp <?php echo number_format($gaji['total_pendapatan_bruto'], 0, ',', '.'); ?></td>
                                    <td class="px-3 py-4 text-right text-sm text-red-600 dark:text-red-400">(Rp <?php echo number_format($gaji['total_potongan_manual'], 0, ',', '.'); ?>)</td>
                                    <td class="px-3 py-4 text-right text-sm font-bold text-green-600 dark:text-green-400">Rp <?php echo number_format($gaji['total_gaji_netto'], 0, ',', '.'); ?></td>
                                    <td class="px-3 py-4 text-center">
                                        <a href="<?php echo BASE_URL; ?>gaji/cetak_slip.php?id_gaji=<?php echo $gaji['id_gaji']; ?>" target="_blank" class="text-blue-600 hover:text-blue-800 dark:text-blue-400" title="Cetak Slip Gaji">
                                            <i class="fas fa-print fa-lg"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center p-5 text-gray-500 dark:text-gray-400">
                                    Tidak ada data gaji yang ditemukan untuk periode ini.
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
