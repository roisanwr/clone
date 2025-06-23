<!-- <?php
// File: proyek_jaya/penugasan/index.php

// 1. Panggil Konfigurasi dan mulai session
require_once '../config.php'; 

// 2. Autentikasi: Pastikan pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'auth/login.php?error=2'); 
    exit;
}

// 3. Autorisasi: Untuk daftar SEMUA penugasan, kita batasi untuk Super Admin dan Admin dulu.
// Mandor nanti akan punya cara sendiri untuk melihat penugasan terkait proyeknya atau yang dia buat.
$allowed_roles = ['super_admin', 'admin'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    $_SESSION['pesan_error'] = "MAAF, ANDA TIDAK MEMILIKI HAK AKSES KE HALAMAN INI.";
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$user_role = $_SESSION['role']; // Ambil role untuk sidebar

// 4. Ambil semua data penugasan dari database
// Kita JOIN untuk mendapatkan Nama Proyek, Nama Pekerja yang Ditugaskan, dan Nama Mandor yang Menugaskan
$query_penugasan = "SELECT 
                        pp.id_penugasan, 
                        pr.namaprojek, 
                        pek.namapekerja AS nama_pekerja_ditugaskan, 
                        pp.tanggal_mulai_penugasan, 
                        pp.tanggal_akhir_penugasan, 
                        pp.is_active AS status_penugasan,
                        mandor_penugas.namapekerja AS nama_mandor_penugas 
                    FROM proyek_pekerja pp
                    INNER JOIN projek pr ON pp.id_projek = pr.id_projek
                    INNER JOIN pekerja pek ON pp.id_pekerja = pek.id_pekerja
                    LEFT JOIN users u_mandor ON pp.created_by_mandor_id = u_mandor.id_user 
                    LEFT JOIN pekerja mandor_penugas ON u_mandor.id_pekerja_ref = mandor_penugas.id_pekerja
                    ORDER BY pr.namaprojek ASC, pp.tanggal_mulai_penugasan DESC";

// Catatan: pp.created_by_mandor_id merujuk ke users.id_user, lalu users.id_pekerja_ref ke pekerja.id_pekerja
// Jika created_by_mandor_id adalah user ID dari mandor, maka JOIN nya perlu disesuaikan.
// Asumsi Sparky dari skema: created_by_mandor_id adalah id_pekerja dari si Mandor. Jika bukan, query di atas perlu disesuaikan.
// Jika created_by_mandor_id adalah id_user dari tabel users yang rolenya mandor:
/*
$query_penugasan = "SELECT 
                        pp.id_penugasan, 
                        pr.namaprojek, 
                        pek.namapekerja AS nama_pekerja_ditugaskan, 
                        pp.tanggal_mulai_penugasan, 
                        pp.tanggal_akhir_penugasan, 
                        pp.is_active AS status_penugasan,
                        usr_mandor.username AS username_mandor_penugas -- atau ambil dari pekerja jika ada id_pekerja_ref
                    FROM proyek_pekerja pp
                    INNER JOIN projek pr ON pp.id_projek = pr.id_projek
                    INNER JOIN pekerja pek ON pp.id_pekerja = pek.id_pekerja
                    LEFT JOIN users usr_mandor ON pp.created_by_mandor_id = usr_mandor.id_user 
                    ORDER BY pr.namaprojek ASC, pp.tanggal_mulai_penugasan DESC";
*/
// Untuk sementara kita pakai query yang pertama, asumsi created_by_mandor_id di proyek_pekerja adalah id_pekerja mandor.
// Jika created_by_mandor_id adalah id_user mandor, dan kita mau nama pekerjanya, maka joinnya jadi:
// LEFT JOIN users u_penugas ON pp.created_by_mandor_id = u_penugas.id_user
// LEFT JOIN pekerja mandor_penugas ON u_penugas.id_pekerja_ref = mandor_penugas.id_pekerja
// Kita akan pakai yang ini karena lebih sesuai dengan struktur users dan pekerja.

$result_penugasan = mysqli_query($koneksi, $query_penugasan);

if (!$result_penugasan) {
    $error_query = "Error mengambil data penugasan: " . mysqli_error($koneksi);
}

// 5. Siapkan pesan notifikasi
$pesan_notifikasi = '';
// ... (logika pesan notifikasi sama seperti modul lain) ...
if (isset($_SESSION['pesan_sukses'])) {
    $pesan_notifikasi = "<div class='mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg shadow'>" . htmlspecialchars($_SESSION['pesan_sukses']) . "</div>";
    unset($_SESSION['pesan_sukses']); 
} elseif (isset($_SESSION['pesan_error_crud'])) {
    $pesan_notifikasi = "<div class='mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg shadow'>" . htmlspecialchars($_SESSION['pesan_error_crud']) . "</div>";
    unset($_SESSION['pesan_error_crud']);
}


// 6. Memanggil komponen template: HEADER
require_once '../includes/header.php'; 

// 7. Memanggil komponen template: SIDEBAR
// PASTIKAN DI FILE SIDEBAR (sidebar_super_admin.php & sidebar_admin.php) SUDAH ADA LOGIKA UNTUK $menu_aktif = 'penugasan';
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
                    Manajemen Penugasan Pekerja
                </h1>
                <a href="<?php echo BASE_URL; ?>penugasan/tambah.php" 
                   class="mt-3 sm:mt-0 inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md shadow-sm transition duration-150 ease-in-out">
                    <i class="fas fa-user-plus fa-fw mr-2"></i> Buat Penugasan Baru
                </a>
            </div>

            <?php echo $pesan_notifikasi; ?>

            <?php if (isset($error_query)): ?>
                <div class='mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg shadow'><?php echo htmlspecialchars($error_query); ?></div>
            <?php endif; ?>

            <div class="overflow-x-auto shadow-md rounded-lg">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-100 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">No.</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nama Proyek</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nama Pekerja</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tgl Mulai Tugas</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tgl Akhir Tugas</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status Tugas</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ditugaskan Oleh (Mandor)</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php if ($result_penugasan && mysqli_num_rows($result_penugasan) > 0) : ?>
                            <?php $nomor = 1; ?>
                            <?php while ($tugas = mysqli_fetch_assoc($result_penugasan)) : ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-600/50 transition-colors duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200"><?php echo $nomor++; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($tugas['namaprojek']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200"><?php echo htmlspecialchars($tugas['nama_pekerja_ditugaskan']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200"><?php echo $tugas['tanggal_mulai_penugasan'] ? date('d M Y', strtotime($tugas['tanggal_mulai_penugasan'])) : '-'; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200"><?php echo $tugas['tanggal_akhir_penugasan'] ? date('d M Y', strtotime($tugas['tanggal_akhir_penugasan'])) : 'Masih Berjalan'; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                        <?php if ($tugas['status_penugasan'] == 1) : ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-100">Aktif</span>
                                        <?php else : ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-700 dark:text-red-100">Tidak Aktif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200"><?php echo $tugas['nama_mandor_penugas'] ? htmlspecialchars($tugas['nama_mandor_penugas']) : '-'; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-center">
                                        <a href="<?php echo BASE_URL; ?>penugasan/edit.php?id=<?php echo $tugas['id_penugasan']; ?>" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-200 mr-3 transition-colors duration-150" title="Edit Penugasan">
                                            <i class="fas fa-edit fa-fw"></i> <span class="sr-only">Edit</span>
                                        </a>
                                        <?php // Aksi hapus/batalkan penugasan bisa ditambahkan di sini (misal hanya Super Admin) ?>
                                        <?php if ($_SESSION['role'] === 'super_admin') : ?>
                                            <a href="<?php echo BASE_URL; ?>penugasan/proses.php?aksi=hapus&id=<?php echo $tugas['id_penugasan']; ?>" 
                                               class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-200 transition-colors duration-150" 
                                               onclick="return confirm('Apakah Anda yakin ingin menghapus/membatalkan penugasan ini?');" title="Hapus/Batalkan Penugasan">
                                               <i class="fas fa-calendar-times fa-fw"></i> <span class="sr-only">Hapus</span>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Belum ada data penugasan pekerja ke proyek. Silakan buat penugasan baru.
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
?> -->