<?php
// File: proyek_jaya/pekerja/index.php

// 1. Panggil Konfigurasi dan mulai session
require_once '../config.php'; 

// 2. Autentikasi: Pastikan pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'auth/login.php?error=2'); 
    exit;
}

// 3. Autorisasi: Hanya Super Admin dan Admin yang boleh akses halaman ini untuk melihat SEMUA pekerja.
$allowed_roles = ['super_admin', 'admin'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    $_SESSION['pesan_error'] = "MAAF, ANDA TIDAK MEMILIKI HAK AKSES KE HALAMAN MANAJEMEN PEKERJA.";
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

// Ambil role pengguna dari session untuk menentukan sidebar mana yang di-load
$user_role = $_SESSION['role'];

// 4. Ambil data untuk filter (Jabatan, Status, dan Nama Pekerja)
// Data Jabatan untuk dropdown filter
$query_jabatan_filter = "SELECT id_jabatan, namajabatan FROM jabatan ORDER BY namajabatan ASC";
$result_jabatan_filter = mysqli_query($koneksi, $query_jabatan_filter);
$daftar_jabatan_filter = $result_jabatan_filter ? mysqli_fetch_all($result_jabatan_filter, MYSQLI_ASSOC) : [];

// 5. Logika untuk filter
$filter_id_jabatan = $_GET['id_jabatan'] ?? '';
$filter_is_active = $_GET['is_active'] ?? ''; // '1', '0', atau '' (semua)
$filter_nama_pekerja = $_GET['nama_pekerja'] ?? ''; // Menangkap input nama pekerja

$where_clauses = [];
$bind_types = "";
$bind_values = [];

// Filter berdasarkan Jabatan
if (!empty($filter_id_jabatan)) {
    $where_clauses[] = "p.id_jabatan = ?";
    $bind_types .= "i";
    $bind_values[] = intval($filter_id_jabatan);
}

// Filter berdasarkan Status Keaktifan
if ($filter_is_active !== '') { // Jika tidak kosong, berarti ada pilihan 'Aktif' atau 'Tidak Aktif'
    $where_clauses[] = "p.is_active = ?";
    $bind_types .= "i";
    $bind_values[] = intval($filter_is_active);
}

// Filter berdasarkan Nama Pekerja
if (!empty($filter_nama_pekerja)) {
    $where_clauses[] = "p.namapekerja LIKE ?";
    $bind_types .= "s";
    $bind_values[] = "%" . $filter_nama_pekerja . "%"; // Gunakan wildcard untuk pencarian parsial
}

$where_sql = "";
if (!empty($where_clauses)) {
    $where_sql = " WHERE " . implode(" AND ", $where_clauses);
}

// 6. Ambil semua data pekerja dari database, GABUNGKAN (JOIN) dengan tabel jabatan untuk mendapatkan nama jabatan
// Urutkan berdasarkan nama pekerja
$query_pekerja = "SELECT p.id_pekerja, p.namapekerja, j.namajabatan, p.no_hp, p.no_rek, p.is_active 
                  FROM pekerja p
                  INNER JOIN jabatan j ON p.id_jabatan = j.id_jabatan"
                  . $where_sql .
                  " ORDER BY p.namapekerja ASC";

$stmt_pekerja = mysqli_prepare($koneksi, $query_pekerja);
$daftar_pekerja = [];

if ($stmt_pekerja) {
    if (!empty($bind_types)) {
        mysqli_stmt_bind_param($stmt_pekerja, $bind_types, ...$bind_values);
    }
    mysqli_stmt_execute($stmt_pekerja);
    $result_pekerja = mysqli_stmt_get_result($stmt_pekerja);
    $daftar_pekerja = $result_pekerja ? mysqli_fetch_all($result_pekerja, MYSQLI_ASSOC) : [];
    mysqli_stmt_close($stmt_pekerja);
} else {
    error_log("Error mempersiapkan query data pekerja: " . mysqli_error($koneksi));
    $daftar_pekerja = []; 
}

// 7. Siapkan pesan notifikasi (jika ada dari proses tambah/edit/hapus sebelumnya)
$pesan_notifikasi = '';
if (isset($_SESSION['pesan_sukses'])) {
    $pesan_notifikasi = "<div class='mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg shadow'>" . htmlspecialchars($_SESSION['pesan_sukses']) . "</div>";
    unset($_SESSION['pesan_sukses']); 
} elseif (isset($_SESSION['pesan_error_crud'])) {
    $pesan_notifikasi = "<div class='mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg shadow'>" . htmlspecialchars($_SESSION['pesan_error_crud']) . "</div>";
    unset($_SESSION['pesan_error_crud']);
}

// 8. Memanggil komponen template: HEADER
require_once '../includes/header.php'; 

// 9. Memanggil komponen template: SIDEBAR (sesuai peran pengguna)
if ($user_role == 'super_admin') {
    require_once '../includes/sidebar_super_admin.php';
} elseif ($user_role == 'admin') {
    require_once '../includes/sidebar_admin.php';
}
?>

<main class="content-wrapper mt-16 md:ml-72">
    <div class="max-w-full mx-auto p-4 sm:p-6 lg:p-8"> 

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 dark:text-white">
                    Manajemen Pekerja
                </h1>
                <a href="<?php echo BASE_URL; ?>pekerja/tambah.php" 
                   class="mt-3 sm:mt-0 inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md shadow-sm transition duration-150 ease-in-out">
                    <i class="fas fa-user-plus fa-fw mr-2"></i>
                    Tambah Pekerja Baru
                </a>
            </div>

            <?php echo $pesan_notifikasi; ?>

            <!-- Form Filter Pekerja -->
            <form method="GET" action="" class="mb-6 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg border dark:border-gray-600">
                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4 items-end">
                    <div>
                        <label for="nama_pekerja" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Cari Nama Pekerja</label>
                        <input type="text" name="nama_pekerja" id="nama_pekerja" 
                               value="<?php echo htmlspecialchars($filter_nama_pekerja); ?>"
                               placeholder="Cari nama..."
                               class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                    </div>
                    <div>
                        <label for="id_jabatan" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Filter Jabatan</label>
                        <select name="id_jabatan" id="id_jabatan" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                            <option value="">Semua Jabatan</option>
                            <?php foreach($daftar_jabatan_filter as $jabatan_filter): ?>
                                <option value="<?php echo $jabatan_filter['id_jabatan']; ?>" <?php echo (intval($filter_id_jabatan) === intval($jabatan_filter['id_jabatan']) ? 'selected' : ''); ?>>
                                    <?php echo htmlspecialchars($jabatan_filter['namajabatan']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="is_active" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status Keaktifan</label>
                        <select name="is_active" id="is_active" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                            <option value="">Semua Status</option>
                            <option value="1" <?php echo (string)$filter_is_active === '1' ? 'selected' : ''; ?>>Aktif</option>
                            <option value="0" <?php echo (string)$filter_is_active === '0' ? 'selected' : ''; ?>>Tidak Aktif</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2 pt-4 md:pt-0">
                        <button type="submit" class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-filter fa-fw mr-1"></i> Filter
                        </button>
                        <a href="<?php echo BASE_URL; ?>pekerja/" class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-gray-500 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                            <i class="fas fa-sync-alt fa-fw mr-1"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
            <!-- End Form Filter Pekerja -->

            <div class="overflow-x-auto shadow-md rounded-lg">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-100 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">No.</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nama Pekerja</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Jabatan</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">No. HP</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">No. Rekening</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php if (!empty($daftar_pekerja)) : ?>
                            <?php $nomor = 1; ?>
                            <?php foreach ($daftar_pekerja as $pekerja) : ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-600/50 transition-colors duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200"><?php echo $nomor++; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($pekerja['namapekerja']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200"><?php echo htmlspecialchars($pekerja['namajabatan']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200"><?php echo htmlspecialchars($pekerja['no_hp'] ?? '-'); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200"><?php echo htmlspecialchars($pekerja['no_rek'] ?? '-'); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                        <?php if ($pekerja['is_active'] == 1) : ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-100">Aktif</span>
                                        <?php else : ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-700 dark:text-red-100">Tidak Aktif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-center">
                                        <a href="<?php echo BASE_URL; ?>pekerja/edit.php?id=<?php echo $pekerja['id_pekerja']; ?>" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-200 mr-3 transition-colors duration-150" title="Edit">
                                            <i class="fas fa-edit fa-fw"></i> <span class="sr-only">Edit</span>
                                        </a>
                                        <?php if ($_SESSION['role'] === 'super_admin') : // Tombol hapus mungkin hanya untuk Super Admin ?>
                                            <a href="<?php echo BASE_URL; ?>pekerja/proses.php?aksi=hapus&id=<?php echo $pekerja['id_pekerja']; ?>" 
                                               class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-200 transition-colors duration-150" 
                                               onclick="return confirm('Apakah Anda yakin ingin menghapus pekerja <?php echo htmlspecialchars(addslashes($pekerja['namapekerja'])); ?>? Tindakan ini tidak dapat dibatalkan!');" title="Hapus">
                                               <i class="fas fa-trash-alt fa-fw"></i> <span class="sr-only">Hapus</span>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Belum ada data pekerja yang ditambahkan atau tidak ditemukan dengan filter yang dipilih.
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
// 10. Memanggil komponen template: FOOTER
require_once '../includes/footer.php'; 
?>
