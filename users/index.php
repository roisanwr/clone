<?php
// File: users/index.php (Revisi - Mengambil data dari tabel PEKERJA)

require_once '../config.php'; 

// 1. Autentikasi & Autorisasi (Tetap sama, hanya Super Admin)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    $_SESSION['pesan_error'] = "MAAF, HANYA SUPER ADMIN YANG DAPAT MENGAKSES HALAMAN MANAJEMEN PENGGUNA.";
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$user_role = $_SESSION['role'];

// 2. Query BARU: Ambil data dari tabel 'pekerja' yang punya username
// Kita hanya menampilkan pekerja yang kolom username-nya TIDAK KOSONG.
// Kita juga JOIN dengan tabel 'jabatan' untuk menampilkan nama jabatannya.
$query_pengguna = "SELECT 
                        p.id_pekerja, p.username, p.namapekerja, p.is_active,
                        j.namajabatan
                   FROM pekerja p
                   JOIN jabatan j ON p.id_jabatan = j.id_jabatan
                   WHERE p.username IS NOT NULL AND p.username != ''
                   ORDER BY p.username ASC";

$result_pengguna = mysqli_query($koneksi, $query_pengguna);

if (!$result_pengguna) {
    $error_query = "Error mengambil data pengguna: " . mysqli_error($koneksi);
}

// 3. Siapkan pesan notifikasi (Tidak berubah)
$pesan_notifikasi = '';
if (isset($_SESSION['pesan_sukses'])) {
    $pesan_notifikasi = "<div class='mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg shadow'>" . htmlspecialchars($_SESSION['pesan_sukses']) . "</div>";
    unset($_SESSION['pesan_sukses']); 
} elseif (isset($_SESSION['pesan_error_crud'])) {
    $pesan_notifikasi = "<div class='mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg shadow'>" . htmlspecialchars($_SESSION['pesan_error_crud']) . "</div>";
    unset($_SESSION['pesan_error_crud']);
}

// 4. Panggil komponen template (Tidak berubah)
require_once '../includes/header.php'; 
require_once '../includes/sidebar_super_admin.php';
?>

<main class="content-wrapper mt-16 md:ml-72">
    <div class="max-w-full mx-auto p-4 sm:p-6 lg:p-8"> 
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 dark:text-white">
                    Manajemen Pengguna Sistem
                </h1>
                <!-- Tombol ini sekarang akan mengarah ke form untuk 'mempromosikan' pekerja menjadi pengguna -->
                <a href="<?php echo BASE_URL; ?>users/tambah.php" 
                   class="mt-3 sm:mt-0 inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md shadow-sm">
                    <i class="fas fa-user-plus fa-fw mr-2"></i>
                    Buat Akun Pengguna Baru
                </a>
            </div>

            <?php echo $pesan_notifikasi; ?>

            <?php if (isset($error_query)): ?>
                <div class='mb-4 p-4 bg-red-100 ...'><?php echo htmlspecialchars($error_query); ?></div>
            <?php endif; ?>

            <div class="overflow-x-auto shadow-md rounded-lg">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-100 dark:bg-gray-700">
                        <tr>
                            <!-- Header tabel disesuaikan dengan data baru -->
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No.</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Username</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Lengkap</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jabatan (Role)</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status Akun</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php if ($result_pengguna && mysqli_num_rows($result_pengguna) > 0) : ?>
                            <?php $nomor = 1; ?>
                            <?php while ($pengguna = mysqli_fetch_assoc($result_pengguna)) : ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-600/50">
                                    <td class="px-6 py-4"><?php echo $nomor++; ?></td>
                                    <td class="px-6 py-4 font-semibold"><?php echo htmlspecialchars($pengguna['username']); ?></td>
                                    <td class="px-6 py-4"><?php echo htmlspecialchars($pengguna['namapekerja']); ?></td>
                                    <td class="px-6 py-4"><?php echo htmlspecialchars($pengguna['namajabatan']); ?></td>
                                    <td class="px-6 py-4 text-center">
                                        <?php if ($pengguna['is_active'] == 1) : ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Aktif</span>
                                        <?php else : ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Tidak Aktif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <!-- PERUBAHAN PENTING: ID yang dikirim sekarang adalah id_pekerja -->
                                        <a href="<?php echo BASE_URL; ?>users/edit.php?id=<?php echo $pengguna['id_pekerja']; ?>" class="text-indigo-600 hover:text-indigo-800" title="Edit Pengguna">
                                            <i class="fas fa-pencil-alt fa-fw"></i>
                                        </a>
                                        <?php if ($_SESSION['user_id'] != $pengguna['id_pekerja']) : ?>
                                            <a href="<?php echo BASE_URL; ?>users/proses.php?aksi=hapus_akun&id=<?php echo $pengguna['id_pekerja']; ?>" 
                                               class="text-red-600 hover:text-red-800 ml-3" 
                                               onclick="return confirm('PERINGATAN!\nAnda akan MENGHAPUS HAK AKSES LOGIN untuk pengguna <?php echo htmlspecialchars(addslashes($pengguna['username'])); ?>.\n\nData pekerja tidak akan hilang, hanya akun loginnya saja.\n\nApakah Anda yakin?');" 
                                               title="Hapus Akun Login">
                                               <i class="fas fa-user-slash fa-fw"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                    Belum ada pekerja yang memiliki akun login.
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
