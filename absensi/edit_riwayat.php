<?php
// File: proyek_jaya/absensi/edit_riwayat.php
// Deskripsi: Halaman "bunglon" untuk melihat/mengedit riwayat absensi.
// VERSI FINAL: Menambahkan "papan pengumuman" untuk notifikasi.

require_once '../config.php';

// =========================================================================
// 1. AUTENTIKASI & OTORISASI (Blueprint Standard)
// =========================================================================
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'mandor') {
    $_SESSION['pesan_error'] = "AKSES DITOLAK: Halaman ini hanya untuk Mandor.";
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}
$id_mandor_login = $_SESSION['id_pekerja_ref'];

// =========================================================================
// 2. VALIDASI PARAMETER & ATURAN WAKTU
// =========================================================================
if (!isset($_GET['tanggal']) || !DateTime::createFromFormat('Y-m-d', $_GET['tanggal'])) {
    $_SESSION['pesan_error'] = "Tanggal riwayat tidak valid atau tidak ditemukan.";
    header('Location: ' . BASE_URL . 'absensi/riwayat.php');
    exit;
}
$tanggal_terpilih_str = $_GET['tanggal'];
$timezone = new DateTimeZone('Asia/Jakarta');
$tanggal_terpilih = new DateTime($tanggal_terpilih_str, $timezone);
$today = new DateTime('now', $timezone);
$today->setTime(0, 0, 0);

$selisih = $today->diff($tanggal_terpilih);
$selisih_hari = (int)$selisih->format('%r%a');

if ($selisih_hari > 0 || $selisih_hari < -6) {
    $_SESSION['pesan_error'] = "Hanya bisa melihat riwayat absensi 7 hari terakhir.";
    header('Location: ' . BASE_URL . 'absensi/riwayat.php');
    exit;
}

$is_locked = ($selisih_hari < -2);
$form_disabled_attr = $is_locked ? 'disabled' : '';

// =========================================================================
// 3. SIAPKAN NOTIFIKASI (PAPAN PENGUMUMAN)
// =========================================================================
$pesan_notifikasi = '';
if (isset($_SESSION['pesan_sukses'])) {
    $pesan_notifikasi = "<div class='mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg shadow-lg'>" . htmlspecialchars($_SESSION['pesan_sukses']) . "</div>";
    unset($_SESSION['pesan_sukses']); 
} elseif (isset($_SESSION['pesan_error_crud'])) {
    $pesan_notifikasi = "<div class='mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg shadow-lg'>" . $_SESSION['pesan_error_crud'] . "</div>";
    unset($_SESSION['pesan_error_crud']);
}

// =========================================================================
// 4. LOGIKA PEMILIHAN PROYEK
// =========================================================================
$id_projek_terpilih = null;
$nama_proyek_terpilih = '';
$query_proyek_mandor = "SELECT id_projek, namaprojek FROM projek WHERE id_mandor_pekerja = ? AND status = 'active' ORDER BY namaprojek ASC";
$stmt_proyek_mandor = mysqli_prepare($koneksi, $query_proyek_mandor);
$daftar_proyek_mandor = [];
if ($stmt_proyek_mandor) {
    mysqli_stmt_bind_param($stmt_proyek_mandor, "i", $id_mandor_login);
    mysqli_stmt_execute($stmt_proyek_mandor);
    $daftar_proyek_mandor = mysqli_fetch_all(mysqli_stmt_get_result($stmt_proyek_mandor), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt_proyek_mandor);
}

if (isset($_GET['id_projek']) && is_numeric($_GET['id_projek'])) {
    $id_projek_terpilih = intval($_GET['id_projek']);
    $proyek_valid = in_array($id_projek_terpilih, array_column($daftar_proyek_mandor, 'id_projek'));
    if (!$proyek_valid) {
        $_SESSION['pesan_error'] = "Akses ditolak. Anda tidak memimpin proyek tersebut.";
        header('Location: ' . BASE_URL . 'absensi/riwayat.php?tanggal=' . $tanggal_terpilih_str);
        exit;
    }
    $nama_proyek_terpilih = array_values(array_filter($daftar_proyek_mandor, fn($p) => $p['id_projek'] == $id_projek_terpilih))[0]['namaprojek'];
} elseif (count($daftar_proyek_mandor) === 1) {
    $id_projek_terpilih = $daftar_proyek_mandor[0]['id_projek'];
    $nama_proyek_terpilih = $daftar_proyek_mandor[0]['namaprojek'];
}

// =========================================================================
// 5. AMBIL DATA TIM & ABSENSI (PENDEKATAN HIBRIDA)
// =========================================================================
$daftar_pekerja_absen = [];
if ($id_projek_terpilih) {
    $sql_tim_dan_mandor = "
        (SELECT p.id_pekerja, p.namapekerja, j.namajabatan, a.status_hadir, a.lembur, a.keterangan
         FROM proyek_pekerja pp
         JOIN pekerja p ON pp.id_pekerja = p.id_pekerja
         JOIN jabatan j ON p.id_jabatan = j.id_jabatan
         LEFT JOIN absensi a ON p.id_pekerja = a.id_pekerja AND a.id_projek = pp.id_projek AND a.tanggal = ?
         WHERE pp.id_projek = ? 
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
        mysqli_stmt_bind_param($stmt_tim, "sissi", $tanggal_terpilih_str, $id_projek_terpilih, $tanggal_terpilih_str, $tanggal_terpilih_str, $id_projek_terpilih);
        mysqli_stmt_execute($stmt_tim);
        $daftar_pekerja_absen = mysqli_fetch_all(mysqli_stmt_get_result($stmt_tim), MYSQLI_ASSOC);
        mysqli_stmt_close($stmt_tim);
    }
}

// =========================================================================
// 6. PANGGIL TEMPLATE HEADER & SIDEBAR
// =========================================================================
require_once '../includes/header.php';
require_once '../includes/sidebar_mandor.php';
?>

<!-- KONTEN UTAMA -->
<main class="content-wrapper mt-16 md:ml-72">
    <div class="max-w-full mx-auto p-4 sm:p-6 lg:p-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 sm:p-8">
            <div class="flex flex-col sm:flex-row justify-between items-start gap-4 mb-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 dark:text-white">
                        <i class="fas fa-history fa-fw mr-3 text-blue-500"></i>Detail Absensi
                    </h1>
                    <p class="text-md text-gray-600 dark:text-gray-400 mt-1">Tanggal: <span class="font-semibold"><?php echo $tanggal_terpilih->format('l, d F Y'); ?></span></p>
                </div>
                <?php if ($is_locked): ?>
                    <div class="p-2.5 bg-yellow-100 dark:bg-yellow-900/50 text-yellow-800 dark:text-yellow-200 rounded-lg flex items-center gap-2 text-sm">
                        <i class="fas fa-lock fa-fw"></i>
                        <span>Mode Lihat Saja (Data Terkunci)</span>
                    </div>
                <?php else: ?>
                    <div class="p-2.5 bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-200 rounded-lg flex items-center gap-2 text-sm">
                        <i class="fas fa-edit fa-fw"></i>
                        <span>Mode Edit (Batas Edit: 2 Hari)</span>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php echo $pesan_notifikasi; // Ini adalah "Papan Pengumuman"-nya ?>

            <a href="<?php echo BASE_URL; ?>absensi/riwayat.php" class="text-sm text-blue-600 dark:text-blue-400 hover:underline mb-6 inline-block">
                <i class="fas fa-arrow-left fa-fw mr-1"></i> Kembali ke Daftar Tanggal
            </a>

            <!-- Form Pemilihan Proyek (jika perlu) -->
            <?php if (count($daftar_proyek_mandor) > 1 && !$id_projek_terpilih): ?>
                <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg border dark:border-gray-600">
                    <p class="text-lg font-semibold text-center text-gray-800 dark:text-white mb-4">Pilih Proyek untuk Dilihat</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                        <?php foreach($daftar_proyek_mandor as $proyek): ?>
                            <a href="?tanggal=<?php echo $tanggal_terpilih_str; ?>&id_projek=<?php echo $proyek['id_projek']; ?>" 
                               class="block p-3 text-center bg-white dark:bg-gray-800 rounded-md shadow hover:bg-blue-50 dark:hover:bg-blue-900/40 transition-colors">
                                <?php echo htmlspecialchars($proyek['namaprojek']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Form Absensi (jika proyek sudah dipilih) -->
            <?php if ($id_projek_terpilih): ?>
                 <form action="<?php echo BASE_URL; ?>absensi/proses_riwayat.php?aksi=simpan" method="POST">
                    <input type="hidden" name="id_projek" value="<?php echo $id_projek_terpilih; ?>">
                    <input type="hidden" name="tanggal_absensi" value="<?php echo $tanggal_terpilih_str; ?>">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-white mb-4">Lembar Absensi: <span class="text-blue-600 dark:text-blue-400"><?php echo htmlspecialchars($nama_proyek_terpilih); ?></span></h2>
                    <div class="overflow-x-auto shadow-md sm:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                           <thead class="bg-gray-100 dark:bg-gray-700">
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
                                    <?php foreach ($daftar_pekerja_absen as $i => $pekerja): ?>
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-600/50">
                                        <td class="px-4 py-4 text-center text-sm text-gray-500 dark:text-gray-400"><?php echo $i + 1; ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div><?php echo htmlspecialchars($pekerja['namapekerja']); ?></div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($pekerja['namajabatan']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <div class="flex justify-center items-center gap-x-6">
                                                <label class="inline-flex items-center <?php echo $is_locked ? 'cursor-not-allowed' : 'cursor-pointer'; ?>">
                                                    <input type="radio" class="form-radio h-4 w-4 text-green-600" name="status_hadir[<?php echo $pekerja['id_pekerja']; ?>]" value="1" <?php echo (isset($pekerja['status_hadir']) && $pekerja['status_hadir'] == 1) ? 'checked' : ''; ?> required <?php echo $form_disabled_attr; ?>>
                                                    <span class="ml-2 <?php echo $is_locked ? 'opacity-70' : ''; ?>">Hadir</span>
                                                </label>
                                                <label class="inline-flex items-center <?php echo $is_locked ? 'cursor-not-allowed' : 'cursor-pointer'; ?>">
                                                    <input type="radio" class="form-radio h-4 w-4 text-red-600" name="status_hadir[<?php echo $pekerja['id_pekerja']; ?>]" value="0" <?php echo (isset($pekerja['status_hadir']) && $pekerja['status_hadir'] == 0) ? 'checked' : ''; ?> required <?php echo $form_disabled_attr; ?>>
                                                    <span class="ml-2 <?php echo $is_locked ? 'opacity-70' : ''; ?>">Tidak</span>
                                                </label>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <input type="hidden" name="lembur[<?php echo $pekerja['id_pekerja']; ?>]" value="0">
                                            <input type="checkbox" name="lembur[<?php echo $pekerja['id_pekerja']; ?>]" value="1" class="h-5 w-5 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500" <?php echo (isset($pekerja['lembur']) && $pekerja['lembur'] == 1) ? 'checked' : ''; ?> <?php echo $form_disabled_attr; ?>>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <input type="text" name="keterangan[<?php echo $pekerja['id_pekerja']; ?>]" value="<?php echo htmlspecialchars($pekerja['keterangan'] ?? ''); ?>" class="w-full text-sm rounded-md border-gray-300 shadow-sm dark:bg-gray-900 dark:border-gray-600 dark:text-white focus:ring-blue-500 focus:border-blue-500" placeholder="-" <?php echo $form_disabled_attr; ?>>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="p-4 text-center text-sm text-gray-500 dark:text-gray-400">Tidak ada tim yang dijadwalkan bertugas di proyek ini pada tanggal tersebut.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-8 flex items-center <?php echo (!$is_locked && !empty($daftar_pekerja_absen)) ? 'justify-between' : 'justify-start'; ?> border-t border-gray-200 dark:border-gray-700 pt-6">
                        <a href="<?php echo BASE_URL; ?>absensi/riwayat.php" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                            <i class="fas fa-arrow-left fa-fw mr-1"></i> Kembali ke Daftar Tanggal
                        </a>

                        <?php if (!$is_locked && !empty($daftar_pekerja_absen)): ?>
                            <button type="submit" name="simpan_absensi" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800">
                                <i class="fas fa-save fa-fw mr-2"></i> Simpan Perubahan
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php
require_once '../includes/footer.php';
?>
