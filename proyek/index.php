<?php
// File: proyek_jaya/proyek/index.php

// 1. Panggil Konfigurasi dan mulai session
require_once '../config.php'; 

// 2. Autentikasi: Pastikan pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'auth/login.php?error=2'); 
    exit;
}

// 3. Autorisasi: Untuk daftar SEMUA proyek, hanya Super Admin dan Admin.
$allowed_roles = ['super_admin', 'admin'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    $_SESSION['pesan_error'] = "MAAF, ANDA TIDAK MEMILIKI HAK AKSES KE HALAMAN MANAJEMEN PROYEK.";
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$user_role = $_SESSION['role']; // Ambil role untuk sidebar

// 4. Ambil data untuk filter (Klien, Mandor, Status, dan Nama Proyek)
// Data Klien untuk dropdown filter
$query_klien_filter = "SELECT id_klien, nama_klien FROM klien ORDER BY nama_klien ASC";
$result_klien_filter = mysqli_query($koneksi, $query_klien_filter);
$daftar_klien_filter = $result_klien_filter ? mysqli_fetch_all($result_klien_filter, MYSQLI_ASSOC) : [];

// Data Mandor untuk dropdown filter (hanya pekerja dengan jabatan 'Mandor' dan is_active=1)
$query_mandor_filter = "SELECT p.id_pekerja, p.namapekerja 
                        FROM pekerja p
                        INNER JOIN jabatan j ON p.id_jabatan = j.id_jabatan
                        WHERE j.namajabatan = 'Mandor' AND p.is_active = 1
                        ORDER BY p.namapekerja ASC";
$result_mandor_filter = mysqli_query($koneksi, $query_mandor_filter);
$daftar_mandor_filter = $result_mandor_filter ? mysqli_fetch_all($result_mandor_filter, MYSQLI_ASSOC) : [];

// 5. Logika untuk filter
$filter_id_klien = $_GET['id_klien'] ?? '';
$filter_id_mandor_pekerja = $_GET['id_mandor_pekerja'] ?? '';
$filter_status = $_GET['status'] ?? '';
$filter_namaprojek = $_GET['namaprojek'] ?? ''; // Menangkap input nama proyek

$where_clauses = [];
$bind_types = "";
$bind_values = [];

// Filter berdasarkan Nama Proyek
if (!empty($filter_namaprojek)) {
    $where_clauses[] = "projek.namaprojek LIKE ?";
    $bind_types .= "s";
    $bind_values[] = "%" . $filter_namaprojek . "%";
}

// Filter berdasarkan Klien
if (!empty($filter_id_klien)) {
    $where_clauses[] = "projek.id_klien = ?";
    $bind_types .= "i";
    $bind_values[] = intval($filter_id_klien);
}

// Filter berdasarkan Mandor PJ
if (!empty($filter_id_mandor_pekerja)) {
    $where_clauses[] = "projek.id_mandor_pekerja = ?";
    $bind_types .= "i";
    $bind_values[] = intval($filter_id_mandor_pekerja);
}

// Filter berdasarkan Status Proyek
if (!empty($filter_status)) {
    $where_clauses[] = "projek.status = ?";
    $bind_types .= "s";
    $bind_values[] = $filter_status;
}

$where_sql = "";
if (!empty($where_clauses)) {
    $where_sql = " WHERE " . implode(" AND ", $where_clauses);
}

// 6. Ambil semua data proyek dari database dengan filter
// Kita akan JOIN dengan tabel klien untuk nama klien, dan tabel pekerja untuk nama mandor penanggung jawab
$query_proyek = "SELECT 
                    projek.id_projek, projek.namaprojek, projek.status, 
                    projek.tanggal_mulai_projek, projek.tanggal_selesai_projek,
                    klien.nama_klien, 
                    pekerja.namapekerja AS nama_mandor 
                 FROM projek 
                 INNER JOIN klien ON projek.id_klien = klien.id_klien 
                 INNER JOIN pekerja ON projek.id_mandor_pekerja = pekerja.id_pekerja"
                 . $where_sql .
                 " ORDER BY projek.namaprojek ASC";

$stmt_proyek = mysqli_prepare($koneksi, $query_proyek);
$daftar_proyek = [];

if ($stmt_proyek) {
    if (!empty($bind_types)) {
        mysqli_stmt_bind_param($stmt_proyek, $bind_types, ...$bind_values);
    }
    mysqli_stmt_execute($stmt_proyek);
    $result_proyek = mysqli_stmt_get_result($stmt_proyek);
    $daftar_proyek = $result_proyek ? mysqli_fetch_all($result_proyek, MYSQLI_ASSOC) : [];
    mysqli_stmt_close($stmt_proyek);
} else {
    error_log("Error mempersiapkan query data proyek: " . mysqli_error($koneksi));
    // Jika query gagal, atur daftar_proyek menjadi array kosong dan set pesan error
    $daftar_proyek = [];
    $_SESSION['pesan_error_crud'] = "Terjadi kesalahan saat mengambil data proyek.";
}

// 7. Siapkan pesan notifikasi
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

// 9. Memanggil komponen template: SIDEBAR
// PASTIKAN DI FILE SIDEBAR (sidebar_super_admin.php & sidebar_admin.php) SUDAH ADA LOGIKA UNTUK $menu_aktif = 'proyek';
// Contoh: elseif (strpos($relative_path, 'proyek/') === 0) { $menu_aktif = 'proyek'; }
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
                    Manajemen Proyek
                </h1>
                <a href="<?php echo BASE_URL; ?>proyek/tambah.php" 
                   class="mt-3 sm:mt-0 inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md shadow-sm transition duration-150 ease-in-out">
                    <i class="fas fa-folder-plus fa-fw mr-2"></i> Tambah Proyek Baru
                </a>
            </div>

            <?php echo $pesan_notifikasi; ?>

            <!-- Form Filter Proyek -->
            <form method="GET" action="" class="mb-6 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg border dark:border-gray-600">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                    <div>
                        <label for="namaprojek" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Cari Nama Proyek</label>
                        <input type="text" name="namaprojek" id="namaprojek" 
                               value="<?php echo htmlspecialchars($filter_namaprojek); ?>"
                               placeholder="Cari nama proyek..."
                               class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                    </div>
                    <div>
                        <label for="id_klien" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Filter Klien</label>
                        <select name="id_klien" id="id_klien" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                            <option value="">Semua Klien</option>
                            <?php foreach($daftar_klien_filter as $klien_filter): ?>
                                <option value="<?php echo $klien_filter['id_klien']; ?>" <?php echo (intval($filter_id_klien) === intval($klien_filter['id_klien']) ? 'selected' : ''); ?>>
                                    <?php echo htmlspecialchars($klien_filter['nama_klien']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="id_mandor_pekerja" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Filter Mandor PJ</label>
                        <select name="id_mandor_pekerja" id="id_mandor_pekerja" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                            <option value="">Semua Mandor</option>
                            <?php foreach($daftar_mandor_filter as $mandor_filter): ?>
                                <option value="<?php echo $mandor_filter['id_pekerja']; ?>" <?php echo (intval($filter_id_mandor_pekerja) === intval($mandor_filter['id_pekerja']) ? 'selected' : ''); ?>>
                                    <?php echo htmlspecialchars($mandor_filter['namapekerja']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Filter Status</label>
                        <select name="status" id="status" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                            <option value="">Semua Status</option>
                            <option value="planning" <?php echo $filter_status === 'planning' ? 'selected' : ''; ?>>Planning</option>
                            <option value="active" <?php echo $filter_status === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="completed" <?php echo $filter_status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2 pt-4 md:pt-0 col-span-1 md:col-span-2 lg:col-span-4"> <!-- Tombol di baris baru dan melebar -->
                        <button type="submit" class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-filter fa-fw mr-1"></i> Filter
                        </button>
                        <a href="<?php echo BASE_URL; ?>proyek/" class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-gray-500 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                            <i class="fas fa-sync-alt fa-fw mr-1"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
            <!-- End Form Filter Proyek -->

            <div class="overflow-x-auto shadow-md rounded-lg">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-100 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">No.</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nama Proyek</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Klien</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Mandor PJ</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tgl Mulai</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tgl Selesai</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php if (!empty($daftar_proyek)) : ?>
                            <?php $nomor = 1; ?>
                            <?php foreach ($daftar_proyek as $proyek) : ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-600/50 transition-colors duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200"><?php echo $nomor++; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($proyek['namaprojek']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200"><?php echo htmlspecialchars($proyek['nama_klien']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200"><?php echo htmlspecialchars($proyek['nama_mandor']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                        <?php 
                                            $status_class = '';
                                            if ($proyek['status'] == 'planning') $status_class = 'bg-yellow-100 text-yellow-800 dark:bg-yellow-700 dark:text-yellow-100';
                                            elseif ($proyek['status'] == 'active') $status_class = 'bg-blue-100 text-blue-800 dark:bg-blue-700 dark:text-blue-100';
                                            elseif ($proyek['status'] == 'completed') $status_class = 'bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-100';
                                        ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                            <?php echo htmlspecialchars(ucfirst($proyek['status'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200"><?php echo $proyek['tanggal_mulai_projek'] ? date('d M Y', strtotime($proyek['tanggal_mulai_projek'])) : '-'; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200"><?php echo $proyek['tanggal_selesai_projek'] ? date('d M Y', strtotime($proyek['tanggal_selesai_projek'])) : '-'; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-center">
                                        <a href="<?php echo BASE_URL; ?>penugasan/detail_tim.php?id_projek=<?php echo $proyek['id_projek']; ?>" 
                                        class="text-teal-600 hover:text-teal-800 dark:text-teal-400 dark:hover:text-teal-200 mr-3 transition-colors duration-150" 
                                        title="Kelola Tim & Penugasan Pekerja">
                                            <i class="fas fa-users-cog fa-fw"></i> 
                                            <span class="sr-only">Kelola Tim</span>
                                        </a>
                                        <a href="<?php echo BASE_URL; ?>proyek/edit.php?id=<?php echo $proyek['id_projek']; ?>" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-200 mr-3 transition-colors duration-150" title="Edit">
                                            <i class="fas fa-edit fa-fw"></i> <span class="sr-only">Edit</span>
                                        </a>
                                        <?php if ($_SESSION['role'] === 'super_admin') : ?>
                                            <a href="<?php echo BASE_URL; ?>proyek/proses.php?aksi=hapus&id=<?php echo $proyek['id_projek']; ?>" 
                                               class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-200 transition-colors duration-150" 
                                               onclick="return confirm('Apakah Anda yakin ingin menghapus proyek <?php echo htmlspecialchars(addslashes($proyek['namaprojek'])); ?>? Tindakan ini akan menghapus semua data terkait proyek ini juga (penugasan, absensi, gaji)!');" title="Hapus">
                                               <i class="fas fa-trash-alt fa-fw"></i> <span class="sr-only">Hapus</span>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Belum ada data proyek yang ditambahkan atau tidak ditemukan dengan filter yang dipilih.
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
