<?php
// File: proyek_jaya/absensi/index.php
// VERSI UPDATE: Link 'Edit' diperbarui untuk mengarah ke halaman edit khusus Admin.

require_once '../config.php'; 

// ... (Blok PHP #1, #2, #3, #4 di atas tidak perlu diubah, sudah sangat baik) ...
// 1. Autentikasi & Autorisasi: HANYA Super Admin dan Admin
if (!isset($_SESSION['user_id'])) { header('Location: ' . BASE_URL . 'auth/login.php?error=2'); exit; }
$user_role = $_SESSION['role'];
if (!in_array($user_role, ['super_admin', 'admin'])) {
    $_SESSION['pesan_error'] = "MAAF, ANDA TIDAK MEMILIKI HAK AKSES KE HALAMAN INI.";
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

// 2. Ambil data untuk filter
$query_proyek_filter = "SELECT id_projek, namaprojek FROM projek ORDER BY namaprojek ASC";
$result_proyek_filter = mysqli_query($koneksi, $query_proyek_filter);
$daftar_proyek_filter = $result_proyek_filter ? mysqli_fetch_all($result_proyek_filter, MYSQLI_ASSOC) : [];

// 3. Logika untuk filter
$filter_id_projek = $_GET['id_projek'] ?? '';
$filter_tgl_mulai = $_GET['tgl_mulai'] ?? '';
$filter_tgl_selesai = $_GET['tgl_selesai'] ?? '';

$where_clauses = [];
$bind_types = "";
$bind_values = [];

if (!empty($filter_id_projek)) {
    $where_clauses[] = "a.id_projek = ?";
    $bind_types .= "i";
    $bind_values[] = $filter_id_projek;
}
if (!empty($filter_tgl_mulai)) {
    $where_clauses[] = "a.tanggal >= ?";
    $bind_types .= "s";
    $bind_values[] = $filter_tgl_mulai;
}
if (!empty($filter_tgl_selesai)) {
    $where_clauses[] = "a.tanggal <= ?";
    $bind_types .= "s";
    $bind_values[] = $filter_tgl_selesai;
}

$where_sql = "";
if (!empty($where_clauses)) {
    $where_sql = " WHERE " . implode(" AND ", $where_clauses);
}

// 4. Ambil semua data absensi dari database dengan filter
$query_absensi = "SELECT 
                        a.id_absensi, a.tanggal, a.status_hadir, a.lembur, a.keterangan,
                        pr.namaprojek,
                        pek.namapekerja AS nama_pekerja_diabsen,
                        mandor.namapekerja AS nama_mandor_pencatat
                    FROM absensi a
                    INNER JOIN projek pr ON a.id_projek = pr.id_projek
                    INNER JOIN pekerja pek ON a.id_pekerja = pek.id_pekerja
                    LEFT JOIN pekerja mandor ON a.id_mandor = mandor.id_pekerja"
                    . $where_sql .
                    " ORDER BY a.tanggal DESC, pr.namaprojek ASC, pek.namapekerja ASC";

$stmt_absensi = mysqli_prepare($koneksi, $query_absensi);
if ($stmt_absensi && !empty($bind_types)) {
    mysqli_stmt_bind_param($stmt_absensi, $bind_types, ...$bind_values);
}

$daftar_absensi = [];
if ($stmt_absensi) {
    mysqli_stmt_execute($stmt_absensi);
    $result_absensi = mysqli_stmt_get_result($stmt_absensi);
    $daftar_absensi = $result_absensi ? mysqli_fetch_all($result_absensi, MYSQLI_ASSOC) : [];
    mysqli_stmt_close($stmt_absensi);
} else {
    error_log("Error mengambil data absensi: " . mysqli_error($koneksi));
    $daftar_absensi = []; 
}


// Panggil Header & Sidebar
require_once '../includes/header.php'; 
if ($user_role == 'super_admin') { require_once '../includes/sidebar_super_admin.php'; } 
elseif ($user_role == 'admin') { require_once '../includes/sidebar_admin.php'; }
?>

<main class="content-wrapper mt-16 md:ml-72">
    <div class="max-w-full mx-auto p-4 sm:p-6 lg:p-8"> 
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-700 dark:text-gray-300 mb-6">
                Laporan Absensi Global
            </h1>

            <form method="GET" action="" class="mb-6 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg border dark:border-gray-600">
                 <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                    <div>
                        <label for="id_projek" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Filter Proyek</label>
                        <select name="id_projek" id="id_projek" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                            <option value="">Semua Proyek</option>
                            <?php foreach($daftar_proyek_filter as $proyek_filter): ?>
                                <option value="<?php echo $proyek_filter['id_projek']; ?>" <?php echo ($filter_id_projek == $proyek_filter['id_projek'] ? 'selected' : ''); ?>>
                                    <?php echo htmlspecialchars($proyek_filter['namaprojek']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="tgl_mulai" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Dari Tanggal</label>
                        <input type="date" name="tgl_mulai" id="tgl_mulai" value="<?php echo htmlspecialchars($filter_tgl_mulai); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-600 dark:text-gray-300 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    <div>
                        <label for="tgl_selesai" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Sampai Tanggal</label>
                        <input type="date" name="tgl_selesai" id="tgl_selesai" value="<?php echo htmlspecialchars($filter_tgl_selesai); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-600 dark:text-gray-300 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    <div class="flex items-center gap-2 pt-4 md:pt-0">
                        <button type="submit" class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">Filter</button>
                        <a href="<?php echo BASE_URL; ?>absensi/" class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-gray-500 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">Reset</a>
                    </div>
                </div>
            </form>

            <div class="overflow-x-auto shadow-md rounded-lg">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-100 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left ...">No.</th>
                            <th class="px-6 py-3 text-left ...">Tanggal</th>
                            <th class="px-6 py-3 text-left ...">Nama Proyek</th>
                            <th class="px-6 py-3 text-left ...">Nama Pekerja</th>
                            <th class="px-6 py-3 text-center ...">Status Hadir</th>
                            <th class="px-6 py-3 text-center ...">Lembur</th>
                            <th class="px-6 py-3 text-left ...">Keterangan</th>
                            <th class="px-6 py-3 text-left ...">Dicatat Oleh</th>
                            <th class="px-6 py-3 text-center ...">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php if (!empty($daftar_absensi)) : ?>
                            <?php $nomor = 1; ?>
                            <?php foreach ($daftar_absensi as $absen) : ?>
                                <tr>
                                    <td class="px-6 py-4 ..."><?php echo $nomor++; ?></td>
                                    <td class="px-6 py-4 ..."><?php echo date('d M Y', strtotime($absen['tanggal'])); ?></td>
                                    <td class="px-6 py-4 ..."><?php echo htmlspecialchars($absen['namaprojek']); ?></td>
                                    <td class="px-6 py-4 ... font-medium"><?php echo htmlspecialchars($absen['nama_pekerja_diabsen']); ?></td>
                                    <td class="px-6 py-4 text-center ...">
                                        <?php if ($absen['status_hadir']) : ?>
                                            <span class='px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100'>Hadir</span>
                                        <?php else : ?>
                                            <span class='px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100'>Tidak Hadir</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-center ...">
                                        <?php echo $absen['lembur'] ? "<span class='text-blue-500'><i class='fas fa-check-circle'></i></span>" : '-'; ?>
                                    </td>
                                    <td class="px-6 py-4 ..."><?php echo htmlspecialchars($absen['keterangan'] ?? ''); ?></td>
                                    <td class="px-6 py-4 ..."><?php echo htmlspecialchars($absen['nama_mandor_pencatat'] ?? 'N/A'); ?></td>
                                    <td class="px-6 py-4 text-center ...">
                                        <a href="<?php echo BASE_URL; ?>absensi/admin_edit.php?id_absensi=<?php echo $absen['id_absensi']; ?>" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-600" title="Edit Absensi">
                                            <i class="fas fa-edit fa-fw"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="9" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Tidak ada data absensi yang ditemukan.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>
