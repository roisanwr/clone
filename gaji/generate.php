<?php
// File: proyek_jaya/gaji/generate.php
// Deskripsi: Halaman untuk memulai proses pembuatan laporan gaji.
// VERSI UPDATE: Dropdown proyek lebih cerdas, mengelompokkan proyek aktif dan baru selesai.

require_once '../config.php';

// 1. Autentikasi & Autorisasi: HANYA Super Admin dan Admin
if (!isset($_SESSION['user_id'])) { header('Location: ' . BASE_URL . 'auth/login.php?error=2'); exit; }
$user_role = $_SESSION['role'];
if (!in_array($user_role, ['super_admin', 'admin'])) {
    $_SESSION['pesan_error'] = "MAAF, ANDA TIDAK MEMILIKI HAK AKSES KE HALAMAN INI.";
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

// 2. Ambil data untuk filter proyek (LOGIKA BARU)
// Ambil proyek yang 'active' ATAU yang 'completed' dalam 30 hari terakhir.
// Ini untuk mengatasi kasus pembayaran gaji final setelah proyek selesai.
$query_proyek_filter = "SELECT id_projek, namaprojek, status 
                        FROM projek 
                        WHERE status = 'active' OR (status = 'completed' AND tanggal_selesai_projek >= DATE_SUB(NOW(), INTERVAL 30 DAY))
                        ORDER BY status ASC, namaprojek ASC";
$result_proyek_filter = mysqli_query($koneksi, $query_proyek_filter);
$daftar_proyek_filter = $result_proyek_filter ? mysqli_fetch_all($result_proyek_filter, MYSQLI_ASSOC) : [];

// 3. Logika untuk menentukan tanggal default (minggu lalu)
$default_tgl_mulai = date('Y-m-d', strtotime('monday last week'));
$default_tgl_selesai = date('Y-m-d', strtotime('sunday last week'));

// Panggil Header & Sidebar
require_once '../includes/header.php'; 
if ($user_role == 'super_admin') { require_once '../includes/sidebar_super_admin.php'; } 
elseif ($user_role == 'admin') { require_once '../includes/sidebar_admin.php'; }
?>

<main class="content-wrapper mt-16 md:ml-72">
    <div class="max-w-4xl mx-auto p-4 sm:p-6 lg:p-8"> 
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 sm:p-8">
            <div class="mb-6">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 dark:text-white">
                    <i class="fas fa-file-invoice-dollar fa-fw mr-3 text-green-500"></i>Generate Laporan Gaji
                </h1>
                <p class="text-md text-gray-600 dark:text-gray-400 mt-1">Pilih proyek dan periode untuk menghitung draf gaji pekerja.</p>
            </div>
            
            <?php
            if (isset($_SESSION['pesan_error_gaji'])) {
                echo "<div class='mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg'>" . htmlspecialchars($_SESSION['pesan_error_gaji']) . "</div>";
                unset($_SESSION['pesan_error_gaji']);
            }
            ?>

            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <form action="<?php echo BASE_URL; ?>gaji/preview.php" method="GET">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="id_projek" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Pilih Proyek</label>
                            <select name="id_projek" id="id_projek" class="mt-1 block w-full pl-3 pr-10 py-2.5 text-base border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                <option value="semua">-- Semua Proyek Aktif --</option>
                                
                                <optgroup label="Proyek Aktif">
                                    <?php foreach($daftar_proyek_filter as $proyek): ?>
                                        <?php if ($proyek['status'] == 'active'): ?>
                                            <option value="<?php echo $proyek['id_projek']; ?>">
                                                <?php echo htmlspecialchars($proyek['namaprojek']); ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </optgroup>
                                <optgroup label="Proyek Baru Selesai">
                                     <?php foreach($daftar_proyek_filter as $proyek): ?>
                                        <?php if ($proyek['status'] == 'completed'): ?>
                                            <option value="<?php echo $proyek['id_projek']; ?>">
                                                <?php echo htmlspecialchars($proyek['namaprojek']); ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </optgroup>

                            </select>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Pilih "Semua Proyek" untuk menggabungkan absensi dari semua proyek dalam satu periode.</p>
                        </div>
                        <div>
                            </div>
                        <div>
                            <label for="tgl_mulai" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Periode Mulai</label>
                            <input type="date" name="tgl_mulai" id="tgl_mulai" required
                                   value="<?php echo $default_tgl_mulai; ?>" 
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-600 dark:text-gray-300 focus:ring-blue-500 focus:border-blue-500 sm:text-sm py-2.5">
                        </div>
                        <div>
                            <label for="tgl_selesai" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Periode Selesai</label>
                            <input type="date" name="tgl_selesai" id="tgl_selesai" required
                                   value="<?php echo $default_tgl_selesai; ?>"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-600 dark:text-gray-300 focus:ring-blue-500 focus:border-blue-500 sm:text-sm py-2.5">
                        </div>
                    </div>
                    <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700 flex justify-end">
                        <button type="submit" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-cogs fa-fw mr-2"></i>
                            Generate Draft Gaji
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php
require_once '../includes/footer.php';
?>
