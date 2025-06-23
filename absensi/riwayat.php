<?php
// File: proyek_jaya/absensi/riwayat.php
// Deskripsi: Halaman "hub" untuk Mandor memilih tanggal dari 7 hari terakhir yang ingin dilihat riwayat absensinya.

require_once '../config.php';

// =========================================================================
// 1. AUTENTIKASI & OTORISASI (Blueprint Standard)
// =========================================================================
// Pastikan hanya mandor yang sudah login yang bisa akses
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'mandor') {
    $_SESSION['pesan_error'] = "AKSES DITOLAK: Halaman ini hanya untuk Mandor.";
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

// =========================================================================
// 2. LOGIKA UNTUK MENGHASILKAN DAFTAR TANGGAL
// =========================================================================
$daftar_tanggal_riwayat = [];
// Zona waktu diatur ke Jakarta untuk konsistensi
$timezone = new DateTimeZone('Asia/Jakarta');
$today = new DateTime('now', $timezone);

// Loop untuk 7 hari, dari hari ini (0) hingga 6 hari yang lalu.
for ($i = 0; $i < 7; $i++) {
    // Buat salinan objek tanggal agar tidak mengubah tanggal asli di setiap loop
    $tanggal = clone $today;
    // Mundurkan tanggal sebanyak $i hari
    $tanggal->modify("-$i days");
    
    $daftar_tanggal_riwayat[] = [
        'url_param' => $tanggal->format('Y-m-d'), // Format untuk link: 2025-06-09
        'display_text' => $tanggal->format('l, d F Y') // Format untuk tampilan: Monday, 09 June 2025
    ];
}

// =========================================================================
// 3. PANGGIL TEMPLATE HEADER & SIDEBAR
// =========================================================================
require_once '../includes/header.php'; 
// Pastikan di sidebar_mandor.php, menu 'riwayat_absensi_mandor' akan aktif saat membuka halaman ini.
require_once '../includes/sidebar_mandor.php';
?>

<!-- ======================================================================= -->
<!-- MULAI KONTEN UTAMA HALAMAN                                              -->
<!-- ======================================================================= -->
<main class="content-wrapper mt-16 md:ml-72">
    <div class="max-w-4xl mx-auto p-4 sm:p-6 lg:p-8"> 
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 sm:p-8">
            <div class="mb-6">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 dark:text-white">
                    <i class="fas fa-history fa-fw mr-3 text-indigo-500"></i>Riwayat Absensi Tim
                </h1>
                <p class="text-md text-gray-600 dark:text-gray-400 mt-1">Pilih tanggal untuk melihat atau mengedit catatan absensi.</p>
            </div>

            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <div class="space-y-3">
                    <?php foreach ($daftar_tanggal_riwayat as $item_tanggal): ?>
                        <a href="<?php echo BASE_URL; ?>absensi/edit_riwayat.php?tanggal=<?php echo $item_tanggal['url_param']; ?>"
                           class="block p-4 sm:p-5 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 hover:border-indigo-400 dark:hover:border-indigo-500 transition-all duration-200 ease-in-out transform hover:scale-[1.03]">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-semibold text-lg text-gray-800 dark:text-gray-100">
                                        <?php echo $item_tanggal['display_text']; ?>
                                    </p>
                                    <?php
                                    // Beri label tambahan untuk kejelasan
                                    $label_hari = '';
                                    if ($item_tanggal['url_param'] == date('Y-m-d')) {
                                        $label_hari = '<span class="text-xs font-bold py-0.5 px-2 bg-blue-200 text-blue-800 rounded-full">Hari Ini</span>';
                                    } elseif ($item_tanggal['url_param'] == date('Y-m-d', strtotime('-1 day'))) {
                                        $label_hari = '<span class="text-xs font-bold py-0.5 px-2 bg-gray-200 text-gray-800 rounded-full">Kemarin</span>';
                                    }
                                    echo "<p class='text-sm text-gray-500 dark:text-gray-400 mt-1'>$label_hari</p>";
                                    ?>
                                </div>
                                <i class="fas fa-chevron-right text-gray-400 dark:text-gray-500 group-hover:text-indigo-500 transition-colors"></i>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</main>
<!-- ======================================================================= -->
<!-- AKHIR KONTEN UTAMA HALAMAN                                               -->
<!-- ======================================================================= -->

<?php
require_once '../includes/footer.php'; 
?>
