<?php
// File: proyek_jaya/jabatan/index.php

// 1. Panggil Konfigurasi dan mulai session
require_once '../config.php'; // Path relatif dari jabatan/ ke root

// 2. Autentikasi: Pastikan pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    // Jika belum login, tendang ke halaman login dengan pesan error
    header('Location: ' . BASE_URL . 'auth/login.php?error=2'); // error=2 artinya "Anda harus login dulu"
    exit;
}

// 3. Autorisasi: Hanya Super Admin dan Admin yang boleh mengakses halaman ini
$allowed_roles = ['super_admin', 'admin'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    // Jika peran tidak diizinkan, simpan pesan error di session dan redirect ke dashboard
    $_SESSION['pesan_error'] = "MAAF, ANDA TIDAK MEMILIKI HAK AKSES KE HALAMAN MANAJEMEN JABATAN.";
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

// Ambil role pengguna dari session untuk menentukan sidebar mana yang di-load
$user_role = $_SESSION['role'];

// 4. Logika untuk filter pencarian nama jabatan
$filter_namajabatan = $_GET['namajabatan'] ?? ''; // Menangkap input nama jabatan dari URL

$where_clauses = [];
$bind_types = "";
$bind_values = [];

// Filter berdasarkan Nama Jabatan
if (!empty($filter_namajabatan)) {
    $where_clauses[] = "namajabatan LIKE ?";
    $bind_types .= "s";
    $bind_values[] = "%" . $filter_namajabatan . "%"; // Gunakan wildcard untuk pencarian parsial
}

$where_sql = "";
if (!empty($where_clauses)) {
    $where_sql = " WHERE " . implode(" AND ", $where_clauses);
}

// 5. Ambil semua data jabatan dari database, urutkan berdasarkan nama jabatan
$query_jabatan = "SELECT id_jabatan, namajabatan, gajipokok, tunjangan_lembur 
                  FROM jabatan"
                  . $where_sql .
                  " ORDER BY namajabatan ASC";

$stmt_jabatan = mysqli_prepare($koneksi, $query_jabatan);
$daftar_jabatan = [];

if ($stmt_jabatan) {
    if (!empty($bind_types)) {
        mysqli_stmt_bind_param($stmt_jabatan, $bind_types, ...$bind_values);
    }
    mysqli_stmt_execute($stmt_jabatan);
    $result_jabatan = mysqli_stmt_get_result($stmt_jabatan);
    $daftar_jabatan = $result_jabatan ? mysqli_fetch_all($result_jabatan, MYSQLI_ASSOC) : [];
    mysqli_stmt_close($stmt_jabatan);
} else {
    // Jika query gagal, bisa tampilkan error atau catat ke log
    error_log("Error mempersiapkan query data jabatan: " . mysqli_error($koneksi));
    $daftar_jabatan = [];
    $_SESSION['pesan_error_crud'] = "Terjadi kesalahan saat mengambil data jabatan.";
}

// 6. Siapkan pesan notifikasi (jika ada dari proses tambah/edit/hapus sebelumnya)
$pesan_notifikasi = '';
if (isset($_SESSION['pesan_sukses'])) {
    // Pesan sukses (background hijau muda, teks hijau tua)
    $pesan_notifikasi = "<div class='mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg shadow'>" . htmlspecialchars($_SESSION['pesan_sukses']) . "</div>";
    unset($_SESSION['pesan_sukses']); // Hapus pesan setelah ditampilkan agar tidak muncul lagi
} elseif (isset($_SESSION['pesan_error_crud'])) {
    // Pesan error dari operasi CRUD (background merah muda, teks merah tua)
    $pesan_notifikasi = "<div class='mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg shadow'>" . htmlspecialchars($_SESSION['pesan_error_crud']) . "</div>";
    unset($_SESSION['pesan_error_crud']);
}

// 7. Memanggil komponen template: HEADER
require_once '../includes/header.php'; 

// 8. Memanggil komponen template: SIDEBAR (sesuai peran pengguna)
if ($user_role == 'super_admin') {
    require_once '../includes/sidebar_super_admin.php';
} elseif ($user_role == 'admin') {
    require_once '../includes/sidebar_admin.php';
}
?>

<main class="content-wrapper mt-16 md:ml-72"> 
    <div class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8">

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 dark:text-white">
                    Manajemen Jabatan
                </h1>
                <a href="<?php echo BASE_URL; ?>jabatan/tambah.php" 
                   class="mt-3 sm:mt-0 inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md shadow-sm transition duration-150 ease-in-out">
                    <i class="fas fa-plus fa-fw mr-2"></i>
                    Tambah Jabatan Baru
                </a>
            </div>

            <?php echo $pesan_notifikasi; ?>

            <!-- Form Filter Jabatan -->
            <form method="GET" action="" class="mb-6 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg border dark:border-gray-600">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-end">
                    <div>
                        <label for="namajabatan" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Cari Nama Jabatan</label>
                        <input type="text" name="namajabatan" id="namajabatan" 
                               value="<?php echo htmlspecialchars($filter_namajabatan); ?>"
                               placeholder="Cari nama jabatan..."
                               class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                    </div>
                    <div class="flex items-center gap-2 pt-4 md:pt-0">
                        <button type="submit" class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-filter fa-fw mr-1"></i> Filter
                        </button>
                        <a href="<?php echo BASE_URL; ?>jabatan/" class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-gray-500 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                            <i class="fas fa-sync-alt fa-fw mr-1"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
            <!-- End Form Filter Jabatan -->

            <div class="overflow-x-auto shadow-md rounded-lg">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-100 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">No.</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nama Jabatan</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Gaji Pokok (Per Hari)</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tunj. Lembur (Per Hari)</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php if (!empty($daftar_jabatan)) : ?>
                            <?php $nomor = 1; ?>
                            <?php foreach ($daftar_jabatan as $jabatan) : ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-600/50 transition-colors duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200"><?php echo $nomor++; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($jabatan['namajabatan']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200 text-right">Rp <?php echo number_format($jabatan['gajipokok'], 0, ',', '.'); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200 text-right">Rp <?php echo number_format($jabatan['tunjangan_lembur'], 0, ',', '.'); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-center">
                                        <a href="<?php echo BASE_URL; ?>jabatan/edit.php?id=<?php echo $jabatan['id_jabatan']; ?>" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-200 mr-3 transition-colors duration-150" title="Edit">
                                            <i class="fas fa-edit fa-fw"></i> <span class="sr-only">Edit</span>
                                        </a>
                                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin') : // HANYA TAMPILKAN UNTUK SUPER ADMIN ?>
                                            <a href="<?php echo BASE_URL; ?>jabatan/proses.php?aksi=hapus&id=<?php echo $jabatan['id_jabatan']; ?>" 
                                            class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-200 transition-colors duration-150" 
                                            onclick="return confirm('Apakah Anda yakin ingin menghapus jabatan <?php echo htmlspecialchars(addslashes($jabatan['namajabatan'])); ?>? Tindakan ini tidak dapat dibatalkan!');" title="Hapus">
                                            <i class="fas fa-trash-alt fa-fw"></i> <span class="sr-only">Hapus</span>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Belum ada data jabatan yang ditambahkan atau tidak ditemukan dengan filter yang dipilih.
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
// 9. Memanggil komponen template: FOOTER
require_once '../includes/footer.php'; 
?>