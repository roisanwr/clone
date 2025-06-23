<?php
// File: proyek_jaya/penugasan/detail_tim.php

// 1. Panggil Konfigurasi dan mulai session
require_once '../config.php'; 

// 2. Autentikasi: Pastikan pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'auth/login.php?error=2'); 
    exit;
}

// 3. Ambil ID Proyek dari URL dan Validasi
if (!isset($_GET['id_projek']) || !is_numeric($_GET['id_projek'])) {
    $_SESSION['pesan_error'] = "ID Proyek tidak valid atau tidak ditemukan.";
    header('Location: ' . BASE_URL . 'proyek/proyek_saya.php'); // Kembali ke daftar proyek mandor
    exit;
}
$id_projek_terpilih = intval($_GET['id_projek']);

// 4. Autorisasi: Pastikan yang login adalah Mandor dan dia adalah PJ Proyek ini, atau Super Admin/Admin
$user_role = $_SESSION['role'];
$id_pekerja_mandor_login = $_SESSION['id_pekerja_ref'] ?? null; // id_pekerja_ref dari Mandor yang login

$sql_cek_proyek = "SELECT namaprojek, id_mandor_pekerja FROM projek WHERE id_projek = ?";
$stmt_cek_proyek = mysqli_prepare($koneksi, $sql_cek_proyek);
$nama_proyek_terpilih = "Proyek Tidak Ditemukan"; // Default

if ($stmt_cek_proyek) {
    mysqli_stmt_bind_param($stmt_cek_proyek, "i", $id_projek_terpilih);
    mysqli_stmt_execute($stmt_cek_proyek);
    $result_proyek_detail = mysqli_stmt_get_result($stmt_cek_proyek);
    $proyek_detail = mysqli_fetch_assoc($result_proyek_detail);
    mysqli_stmt_close($stmt_cek_proyek);

    if (!$proyek_detail) {
        $_SESSION['pesan_error'] = "Proyek dengan ID " . $id_projek_terpilih . " tidak ditemukan.";
        header('Location: ' . ($user_role === 'mandor' ? BASE_URL . 'proyek/proyek_saya.php' : BASE_URL . 'proyek/'));
        exit;
    }
    $nama_proyek_terpilih = htmlspecialchars($proyek_detail['namaprojek']);

    // Jika yang login adalah Mandor, pastikan dia PJ proyek ini
    if ($user_role === 'mandor' && $proyek_detail['id_mandor_pekerja'] != $id_pekerja_mandor_login) {
        $_SESSION['pesan_error'] = "ANDA TIDAK MEMILIKI HAK AKSES UNTUK MENGELOLA TIM PROYEK INI.";
        header('Location: ' . BASE_URL . 'proyek/proyek_saya.php');
        exit;
    } elseif (!in_array($user_role, ['super_admin', 'admin', 'mandor'])) { // Jika bukan ketiganya
         $_SESSION['pesan_error'] = "ANDA TIDAK MEMILIKI HAK AKSES KE HALAMAN INI.";
         header('Location: ' . BASE_URL . 'dashboard.php');
         exit;
    }
} else {
    $_SESSION['pesan_error'] = "Gagal mengambil detail proyek.";
    header('Location: ' . ($user_role === 'mandor' ? BASE_URL . 'proyek/proyek_saya.php' : BASE_URL . 'proyek/'));
    exit;
}

// 5. Ambil data pekerja yang sudah ditugaskan ke proyek ini
$query_pekerja_ditugaskan = "SELECT 
                                pp.id_penugasan, 
                                pek.namapekerja, 
                                pp.tanggal_mulai_penugasan, 
                                pp.tanggal_akhir_penugasan, 
                                pp.is_active AS status_penugasan_proyek_ini
                            FROM proyek_pekerja pp
                            INNER JOIN pekerja pek ON pp.id_pekerja = pek.id_pekerja
                            WHERE pp.id_projek = ?
                            ORDER BY pek.namapekerja ASC";
$stmt_pekerja_ditugaskan = mysqli_prepare($koneksi, $query_pekerja_ditugaskan);
$daftar_pekerja_ditugaskan = [];
if ($stmt_pekerja_ditugaskan) {
    mysqli_stmt_bind_param($stmt_pekerja_ditugaskan, "i", $id_projek_terpilih);
    mysqli_stmt_execute($stmt_pekerja_ditugaskan);
    $result_pekerja_ditugaskan = mysqli_stmt_get_result($stmt_pekerja_ditugaskan);
    $daftar_pekerja_ditugaskan = $result_pekerja_ditugaskan ? mysqli_fetch_all($result_pekerja_ditugaskan, MYSQLI_ASSOC) : [];
    mysqli_stmt_close($stmt_pekerja_ditugaskan);
} else {
    $error_query_tim = "Error mengambil data tim proyek: " . mysqli_error($koneksi);
}


// 6. Siapkan pesan notifikasi
$pesan_notifikasi = '';
// ... (logika pesan notifikasi sama seperti modul lain) ...
if (isset($_SESSION['pesan_sukses'])) {
    $pesan_notifikasi = "<div class='mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg shadow'>" . htmlspecialchars($_SESSION['pesan_sukses']) . "</div>";
    unset($_SESSION['pesan_sukses']); 
} elseif (isset($_SESSION['pesan_error_crud'])) {
    $pesan_notifikasi = "<div class='mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg shadow'>" . $_SESSION['pesan_error_crud'] . "</div>";
    unset($_SESSION['pesan_error_crud']);
} elseif (isset($_SESSION['pesan_error'])) { 
    $pesan_notifikasi = "<div class='mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg shadow'>" . htmlspecialchars($_SESSION['pesan_error']) . "</div>";
    unset($_SESSION['pesan_error']);
}

// 7. Memanggil komponen template: HEADER
require_once '../includes/header.php'; 

// 8. Memanggil komponen template: SIDEBAR
if ($user_role == 'super_admin') {
    require_once '../includes/sidebar_super_admin.php';
} elseif ($user_role == 'admin') {
    require_once '../includes/sidebar_admin.php';
} elseif ($user_role == 'mandor') {
    require_once '../includes/sidebar_mandor.php'; // Pastikan menu "Proyek Saya" aktif
}
?>

<main class="content-wrapper mt-16 md:ml-72">
    <div class="max-w-full mx-auto p-4 sm:p-6 lg:p-8"> 

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-2">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 dark:text-white">
                    Kelola Tim Proyek: <?php echo $nama_proyek_terpilih; ?>
                </h1>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Lihat dan tugaskan pekerja ke proyek ini.</p>


            <?php echo $pesan_notifikasi; ?>
            <?php if (isset($error_query_tim)): ?>
                <div class='mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg shadow'><?php echo htmlspecialchars($error_query_tim); ?></div>
            <?php endif; ?>

            <div class="mb-6">
                 <a href="<?php echo BASE_URL; ?>penugasan/tambah.php?id_projek=<?php echo $id_projek_terpilih; ?>" 
                   class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md shadow-sm transition duration-150 ease-in-out">
                    <i class="fas fa-user-plus fa-fw mr-2"></i>
                    Tugaskan Pekerja Baru ke Proyek Ini
                </a>
            </div>

            <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-200 mb-4">Daftar Pekerja Ditugaskan</h2>
            <div class="overflow-x-auto shadow-md rounded-lg">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-100 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">No.</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nama Pekerja</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tgl Mulai Tugas</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tgl Akhir Tugas</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status Penugasan</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php if (!empty($daftar_pekerja_ditugaskan)) : ?>
                            <?php $nomor_tim = 1; ?>
                            <?php foreach ($daftar_pekerja_ditugaskan as $tugas_pek) : ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-600/50 transition-colors duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200"><?php echo $nomor_tim++; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($tugas_pek['namapekerja']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200"><?php echo $tugas_pek['tanggal_mulai_penugasan'] ? date('d M Y', strtotime($tugas_pek['tanggal_mulai_penugasan'])) : '-'; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200"><?php echo $tugas_pek['tanggal_akhir_penugasan'] ? date('d M Y', strtotime($tugas_pek['tanggal_akhir_penugasan'])) : 'Masih Berjalan'; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                        <?php if ($tugas_pek['status_penugasan_proyek_ini'] == 1) : ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-100">Aktif</span>
                                        <?php else : ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-700 dark:text-red-100">Tidak Aktif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-center">
                                        <a href="<?php echo BASE_URL; ?>penugasan/edit.php?id_penugasan=<?php echo $tugas_pek['id_penugasan']; ?>&id_projek=<?php echo $id_projek_terpilih; ?>" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-200 mr-3 transition-colors duration-150" title="Edit Penugasan">
                                            <i class="fas fa-user-edit fa-fw"></i>
                                        </a>
                                        <?php // Aksi hapus/batalkan penugasan bisa ditambahkan di sini (misal hanya Mandor PJ atau Admin/SA) ?>
                                        <?php if (in_array($_SESSION['role'], ['super_admin', 'admin']) || (isset($_SESSION['id_pekerja_ref']) && $_SESSION['id_pekerja_ref'] == $proyek_detail['id_mandor_pekerja']) ) : ?>
                                            <a href="<?php echo BASE_URL; ?>penugasan/proses.php?aksi=hapus&id_penugasan=<?php echo $tugas_pek['id_penugasan']; ?>&id_projek=<?php echo $id_projek_terpilih; ?>" 
                                               class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-200 transition-colors duration-150" 
                                               onclick="return confirm('Apakah Anda yakin ingin menghapus/membatalkan penugasan pekerja <?php echo htmlspecialchars(addslashes($tugas_pek['namapekerja'])); ?> dari proyek ini?');" title="Hapus/Batalkan Penugasan">
                                               <i class="fas fa-user-times fa-fw"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Belum ada pekerja yang ditugaskan ke proyek ini.
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