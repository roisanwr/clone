<?php
// File: proyek_jaya/proyek/proyek_saya.php

// 1. Panggil Konfigurasi dan mulai session
require_once '../config.php'; 

// 2. Autentikasi: Pastikan pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'auth/login.php?error=2'); 
    exit;
}

// 3. Autorisasi: HANYA Mandor yang boleh mengakses halaman ini.
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'mandor') {
    $_SESSION['pesan_error'] = "MAAF, ANDA TIDAK MEMILIKI HAK AKSES KE HALAMAN INI.";
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

// Pastikan Mandor ini terhubung dengan data pekerja
if (!isset($_SESSION['id_pekerja_ref']) || empty($_SESSION['id_pekerja_ref'])) {
    $_SESSION['pesan_error'] = "Data pekerja untuk akun Mandor Anda tidak ditemukan atau belum diatur. Harap hubungi Super Admin.";
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$user_role = $_SESSION['role']; // Untuk panggil sidebar_mandor.php
$id_mandor_login = $_SESSION['id_pekerja_ref']; // ID Pekerja dari Mandor yang login

// 4. Ambil data proyek yang dipegang oleh Mandor ini
$query_proyek_mandor = "SELECT 
                            p.id_projek, p.namaprojek, p.status, 
                            p.tanggal_mulai_projek, p.tanggal_selesai_projek,
                            k.nama_klien 
                        FROM projek p
                        INNER JOIN klien k ON p.id_klien = k.id_klien 
                        WHERE p.id_mandor_pekerja = ? 
                        ORDER BY p.namaprojek ASC";
    
$stmt_proyek_mandor = mysqli_prepare($koneksi, $query_proyek_mandor);
$daftar_proyek_mandor = []; // Inisialisasi

if ($stmt_proyek_mandor) {
    mysqli_stmt_bind_param($stmt_proyek_mandor, "i", $id_mandor_login);
    mysqli_stmt_execute($stmt_proyek_mandor);
    $result_proyek_mandor = mysqli_stmt_get_result($stmt_proyek_mandor);
    if ($result_proyek_mandor) {
        $daftar_proyek_mandor = mysqli_fetch_all($result_proyek_mandor, MYSQLI_ASSOC);
    } else {
        $error_query = "Error mengambil data proyek: " . mysqli_error($koneksi);
    }
    mysqli_stmt_close($stmt_proyek_mandor);
} else {
    $error_query = "Gagal mempersiapkan query data proyek: " . mysqli_error($koneksi);
}

// 5. Siapkan pesan notifikasi
$pesan_notifikasi = '';
if (isset($_SESSION['pesan_sukses'])) {
    $pesan_notifikasi = "<div class='mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg shadow'>" . htmlspecialchars($_SESSION['pesan_sukses']) . "</div>";
    unset($_SESSION['pesan_sukses']); 
} elseif (isset($_SESSION['pesan_error_crud'])) {
    $pesan_notifikasi = "<div class='mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg shadow'>" . htmlspecialchars($_SESSION['pesan_error_crud']) . "</div>";
    unset($_SESSION['pesan_error_crud']);
} elseif (isset($_SESSION['pesan_error'])) { 
    $pesan_notifikasi = "<div class='mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg shadow'>" . htmlspecialchars($_SESSION['pesan_error']) . "</div>";
    unset($_SESSION['pesan_error']);
}

// 6. Memanggil komponen template: HEADER
require_once '../includes/header.php'; 

// 7. Memanggil komponen template: SIDEBAR (Khusus Mandor)
require_once '../includes/sidebar_mandor.php'; // Pastikan $menu_aktif_mandor = 'proyek_saya_mandor'; diatur di sini
?>

<main class="content-wrapper mt-16 md:ml-72">
    <div class="max-w-full mx-auto p-4 sm:p-6 lg:p-8"> 
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 dark:text-white">
                    <i class="fas fa-briefcase fa-fw mr-2 text-blue-500"></i>Proyek Saya
                </h1>
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
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Klien</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tgl Mulai</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tgl Selesai</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php if (!empty($daftar_proyek_mandor)) : ?>
                            <?php $nomor = 1; ?>
                            <?php foreach ($daftar_proyek_mandor as $proyek) : ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-600/50 transition-colors duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200"><?php echo $nomor++; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($proyek['namaprojek']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200"><?php echo htmlspecialchars($proyek['nama_klien']); ?></td>
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
                                           class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800" 
                                           title="Kelola Tim & Penugasan Pekerja">
                                            <i class="fas fa-users-cog fa-fw mr-1"></i> Kelola Tim
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Anda belum menjadi penanggung jawab untuk proyek manapun yang aktif atau terdaftar.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

<?php
// 10. Memanggil komponen template: FOOTER
require_once '../includes/footer.php'; 
?>