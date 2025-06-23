<?php
// File: proyek_jaya/absensi/catat.php
// Deskripsi: Halaman khusus Mandor untuk mencatat atau mengedit absensi timnya PADA HARI INI.
// VERSI UPDATE: Mandor penanggung jawab proyek sekarang otomatis masuk ke dalam daftar absensi.

require_once '../config.php';

// =========================================================================
// 1. AUTENTIKASI & OTORISASI (Blueprint Standard)
// =========================================================================
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'mandor') {
    $_SESSION['pesan_error'] = "AKSES DITOLAK: Halaman ini hanya untuk Mandor.";
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}
if (!isset($_SESSION['id_pekerja_ref']) || empty($_SESSION['id_pekerja_ref'])) {
    $_SESSION['pesan_error'] = "Data pekerja untuk akun Mandor Anda tidak ditemukan. Harap hubungi Super Admin.";
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

// =========================================================================
// 2. INISIALISASI VARIABEL PENTING
// =========================================================================
$id_mandor_login = $_SESSION['id_pekerja_ref'];
$user_role = $_SESSION['role'];
$tanggal_absensi = date('Y-m-d'); 
$id_projek_terpilih = null;
$daftar_pekerja_absen = [];
$nama_proyek_terpilih = '';
$error_query = '';

// =========================================================================
// 3. AMBIL DAFTAR PROYEK AKTIF YANG DIPIMPIN MANDOR INI
// =========================================================================
$query_proyek_mandor = "SELECT id_projek, namaprojek FROM projek WHERE id_mandor_pekerja = ? AND status = 'active' ORDER BY namaprojek ASC";
$stmt_proyek_mandor = mysqli_prepare($koneksi, $query_proyek_mandor);
$daftar_proyek_mandor = [];
if ($stmt_proyek_mandor) {
    mysqli_stmt_bind_param($stmt_proyek_mandor, "i", $id_mandor_login);
    mysqli_stmt_execute($stmt_proyek_mandor);
    $result_proyek = mysqli_stmt_get_result($stmt_proyek_mandor);
    $daftar_proyek_mandor = mysqli_fetch_all($result_proyek, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt_proyek_mandor);
}

// =========================================================================
// 4. PROSES PEMILIHAN PROYEK (USER FLOW)
// =========================================================================
if (isset($_GET['id_projek']) && is_numeric($_GET['id_projek'])) {
    $id_projek_terpilih = intval($_GET['id_projek']);
    $proyek_valid = false;
    foreach ($daftar_proyek_mandor as $proyek) {
        if ($proyek['id_projek'] == $id_projek_terpilih) {
            $proyek_valid = true;
            $nama_proyek_terpilih = $proyek['namaprojek'];
            break;
        }
    }
    if (!$proyek_valid) { 
        $_SESSION['pesan_error'] = "Akses ditolak. Anda tidak memimpin proyek tersebut.";
        header('Location: ' . BASE_URL . 'absensi/catat.php');
        exit;
    }
} 
elseif (count($daftar_proyek_mandor) === 1) {
    $id_projek_terpilih = $daftar_proyek_mandor[0]['id_projek'];
    $nama_proyek_terpilih = $daftar_proyek_mandor[0]['namaprojek'];
}

// =========================================================================
// 5. JIKA PROYEK SUDAH TERPILIH, AMBIL DATA TIM & ABSENSI (VERSI UPDATE DENGAN MANDOR)
// =========================================================================
if ($id_projek_terpilih) {
    // Query UNION:
    // Bagian pertama mengambil TIM PEKERJA dari proyek_pekerja.
    // Bagian kedua mengambil MANDOR PJ dari tabel projek.
    // UNION akan menggabungkan hasilnya dan otomatis menghilangkan duplikat.
    $sql_tim_dan_mandor = "
        (SELECT p.id_pekerja, p.namapekerja, j.namajabatan, a.status_hadir, a.lembur, a.keterangan
         FROM proyek_pekerja pp
         JOIN pekerja p ON pp.id_pekerja = p.id_pekerja
         JOIN jabatan j ON p.id_jabatan = j.id_jabatan
         LEFT JOIN absensi a ON p.id_pekerja = a.id_pekerja AND a.id_projek = pp.id_projek AND a.tanggal = ?
         WHERE pp.id_projek = ? 
           AND pp.is_active = 1
           AND ? BETWEEN pp.tanggal_mulai_penugasan AND IFNULL(pp.tanggal_akhir_penugasan, '9999-12-31')
        )
        UNION
        (SELECT p.id_pekerja, p.namapekerja, j.namajabatan, a.status_hadir, a.lembur, a.keterangan
         FROM projek pr
         JOIN pekerja p ON pr.id_mandor_pekerja = p.id_pekerja
         JOIN jabatan j ON p.id_jabatan = j.id_jabatan
         LEFT JOIN absensi a ON p.id_pekerja = a.id_pekerja AND a.id_projek = pr.id_projek AND a.tanggal = ?
         WHERE pr.id_projek = ?
        )
        ORDER BY namapekerja ASC";

    $stmt_tim = mysqli_prepare($koneksi, $sql_tim_dan_mandor);
    if ($stmt_tim) {
        // Karena ada 5 placeholder '?' di query, kita harus bind 5 parameter.
        mysqli_stmt_bind_param($stmt_tim, "sissi", 
            $tanggal_absensi, $id_projek_terpilih, $tanggal_absensi, // Untuk query pertama
            $tanggal_absensi, $id_projek_terpilih                    // Untuk query kedua
        );
        mysqli_stmt_execute($stmt_tim);
        $result_tim = mysqli_stmt_get_result($stmt_tim);
        $daftar_pekerja_absen = mysqli_fetch_all($result_tim, MYSQLI_ASSOC);
        mysqli_stmt_close($stmt_tim);
    } else {
        $error_query = "Error mengambil data tim proyek: " . mysqli_error($koneksi);
    }
}

// =========================================================================
// 6. SIAPKAN NOTIFIKASI (Blueprint Standard)
// =========================================================================
$pesan_notifikasi = '';
if (isset($_SESSION['pesan_sukses'])) {
    $pesan_notifikasi = "<div class='mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg shadow'>" . htmlspecialchars($_SESSION['pesan_sukses']) . "</div>";
    unset($_SESSION['pesan_sukses']); 
} elseif (isset($_SESSION['pesan_error_crud'])) {
    $pesan_notifikasi = "<div class='mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg shadow'>" . $_SESSION['pesan_error_crud'] . "</div>";
    unset($_SESSION['pesan_error_crud']);
}

// =========================================================================
// 7. PANGGIL TEMPLATE HEADER & SIDEBAR
// =========================================================================
require_once '../includes/header.php'; 
require_once '../includes/sidebar_mandor.php';
?>

<!-- ======================================================================= -->
<!-- MULAI KONTEN UTAMA HALAMAN                                              -->
<!-- ======================================================================= -->
<main class="content-wrapper mt-16 md:ml-72">
    <div class="max-w-full mx-auto p-4 sm:p-6 lg:p-8"> 
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 sm:p-8">
            <!-- (Bagian heading tidak berubah, tetap sama) -->
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 dark:text-white">
                        <i class="fas fa-calendar-check fa-fw mr-3 text-blue-500"></i>Catat Absensi Tim
                    </h1>
                    <p class="text-md text-gray-600 dark:text-gray-400 mt-1">Tanggal: <span class="font-semibold"><?php echo date('l, d F Y'); ?></span></p>
                </div>
            </div>
            
            <?php echo $pesan_notifikasi; ?>
            <?php if ($error_query) echo "<div class='mb-4 p-4 bg-red-100 text-red-700 rounded-lg'>$error_query</div>"; ?>

            <!-- (Form pemilihan proyek tidak berubah, tetap sama) -->
            <?php if (count($daftar_proyek_mandor) > 1): ?>
                <form method="GET" action="" class="mb-6 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg border dark:border-gray-600">
                    <label for="id_projek" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Anda memimpin lebih dari satu proyek. Silakan pilih proyek untuk diabsen:</label>
                    <select name="id_projek" id="id_projek" onchange="this.form.submit()" class="mt-2 block w-full md:w-1/2 rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-600 dark:text-white focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        <option value="">-- Pilih Proyek Anda --</option>
                        <?php foreach($daftar_proyek_mandor as $proyek): ?>
                            <option value="<?php echo $proyek['id_projek']; ?>" <?php echo ($id_projek_terpilih == $proyek['id_projek'] ? 'selected' : ''); ?>>
                                <?php echo htmlspecialchars($proyek['namaprojek']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            <?php elseif (count($daftar_proyek_mandor) === 0): ?>
                 <p class="mb-4 p-4 bg-yellow-100 dark:bg-yellow-900/50 text-yellow-800 dark:text-yellow-200 rounded-lg">Anda tidak sedang memimpin proyek aktif. Tidak ada tim yang bisa diabsen.</p>
            <?php endif; ?>


            <!-- Tampilkan lembar absensi HANYA jika proyek sudah terpilih -->
            <?php if ($id_projek_terpilih): ?>
                <hr class="my-6 border-gray-200 dark:border-gray-700">
                <form action="<?php echo BASE_URL; ?>absensi/proses.php?aksi=simpan" method="POST">
                    <input type="hidden" name="id_projek" value="<?php echo $id_projek_terpilih; ?>">
                    <input type="hidden" name="tanggal_absensi" value="<?php echo $tanggal_absensi; ?>">

                    <h2 class="text-xl font-semibold text-gray-800 dark:text-white mb-4">
                        Lembar Absensi: <span class="text-blue-600 dark:text-blue-400"><?php echo htmlspecialchars($nama_proyek_terpilih); ?></span>
                    </h2>

                    <div class="overflow-x-auto shadow-md sm:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-100 dark:bg-gray-700">
                                <!-- (Header tabel tidak berubah) -->
                                 <tr>
                                    <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-12">No.</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nama Pekerja (Jabatan)</th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kehadiran</th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-24">Lembur</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <?php if (!empty($daftar_pekerja_absen)): ?>
                                    <?php $nomor = 1; ?>
                                    <?php foreach ($daftar_pekerja_absen as $pekerja): ?>
                                        <?php
                                            // Menandai baris mandor agar bisa diberi style khusus
                                            $is_mandor_row = ($pekerja['id_pekerja'] == $id_mandor_login);
                                        ?>
                                        <tr class="<?php echo $is_mandor_row ? 'bg-blue-50 dark:bg-blue-900/20' : 'hover:bg-gray-50 dark:hover:bg-gray-600/50'; ?>">
                                            <td class="px-4 py-4 text-sm text-center text-gray-500 dark:text-gray-400"><?php echo $nomor++; ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <span class="text-sm font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($pekerja['namapekerja']); ?></span>
                                                    <?php if ($is_mandor_row): ?>
                                                        <span class="ml-2 text-xs font-bold py-0.5 px-2 bg-blue-200 text-blue-800 rounded-full">Anda</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($pekerja['namajabatan']); ?></div>
                                            </td>
                                            <!-- (Kolom form lainnya tidak berubah) -->
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <div class="flex justify-center items-center gap-x-6">
                                                    <label class="inline-flex items-center cursor-pointer">
                                                        <input type="radio" class="form-radio h-4 w-4 text-green-600" name="status_hadir[<?php echo $pekerja['id_pekerja']; ?>]" value="1" <?php echo (isset($pekerja['status_hadir']) && $pekerja['status_hadir'] == 1) || !isset($pekerja['status_hadir']) ? 'checked' : ''; ?> required>
                                                        <span class="ml-2 dark:text-gray-300">Hadir</span>
                                                    </label>
                                                    <label class="inline-flex items-center cursor-pointer">
                                                        <input type="radio" class="form-radio h-4 w-4 text-red-600" name="status_hadir[<?php echo $pekerja['id_pekerja']; ?>]" value="0" <?php echo (isset($pekerja['status_hadir']) && $pekerja['status_hadir'] == 0) ? 'checked' : ''; ?> required>
                                                        <span class="ml-2 dark:text-gray-300">Tidak</span>
                                                    </label>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <input type="hidden" name="lembur[<?php echo $pekerja['id_pekerja']; ?>]" value="0">
                                                <input type="checkbox" name="lembur[<?php echo $pekerja['id_pekerja']; ?>]" value="1" class="h-5 w-5 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" <?php echo (isset($pekerja['lembur']) && $pekerja['lembur'] == 1) ? 'checked' : ''; ?>>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <input type="text" name="keterangan[<?php echo $pekerja['id_pekerja']; ?>]" value="<?php echo htmlspecialchars($pekerja['keterangan'] ?? ''); ?>" class="w-full text-sm rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-600 dark:text-white focus:ring-indigo-500 focus:border-indigo-500" placeholder="Cth: Sakit, Izin, dll.">
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">Tidak ada pekerja yang aktif ditugaskan di proyek ini untuk tanggal hari ini.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <!-- (Tombol simpan tidak berubah) -->
                    <?php if (!empty($daftar_pekerja_absen)): ?>
                        <div class="mt-8 flex justify-end border-t border-gray-200 dark:border-gray-700 pt-6">
                            <button type="submit" name="simpan_absensi" value="1" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800">
                                <i class="fas fa-save fa-fw mr-2"></i>
                                Simpan Perubahan Absensi
                            </button>
                        </div>
                    <?php endif; ?>
                </form>
            <?php endif; ?>
        </div>
    </div>
</main>
<!-- ======================================================================= -->
<!-- AKHIR KONTEN UTAMA HALAMAN                                               -->
<!-- ======================================================================= -->

<?php
require_once '../includes/footer.php'; 
?>
