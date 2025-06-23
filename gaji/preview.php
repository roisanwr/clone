<?php
// File: proyek_jaya/gaji/preview.php
// Deskripsi: Halaman utama untuk menampilkan draf gaji, melakukan koreksi, dan finalisasi.
// VERSI UPDATE: Mencegah duplikasi dengan mengunci baris gaji yang sudah lunas.

require_once '../config.php';

// 1. Autentikasi & Autorisasi: HANYA Super Admin dan Admin
if (!isset($_SESSION['user_id'])) { header('Location: ' . BASE_URL . 'auth/login.php?error=2'); exit; }
$user_role = $_SESSION['role'];
if (!in_array($user_role, ['super_admin', 'admin'])) {
    $_SESSION['pesan_error'] = "MAAF, ANDA TIDAK MEMILIKI HAK AKSES KE HALAMAN INI.";
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

// 2. Ambil & Validasi Parameter dari URL
$filter_id_projek = $_GET['id_projek'] ?? 'semua';
$filter_tgl_mulai = $_GET['tgl_mulai'] ?? '';
$filter_tgl_selesai = $_GET['tgl_selesai'] ?? '';

// Validasi dasar, jika tidak ada tanggal, tendang kembali.
if (empty($filter_tgl_mulai) || empty($filter_tgl_selesai)) {
    $_SESSION['pesan_error_gaji'] = "Periode tanggal harus diisi.";
    header('Location: ' . BASE_URL . 'gaji/generate.php');
    exit;
}

// =========================================================================
// FITUR BARU: Cek pekerja yang sudah digaji lunas pada periode ini
// =========================================================================
$pekerja_sudah_digaji = [];
$sql_cek_lunas = "SELECT id_pekerja FROM gaji WHERE periode_start = ? AND periode_end = ?";
$stmt_cek_lunas = mysqli_prepare($koneksi, $sql_cek_lunas);
if ($stmt_cek_lunas) {
    mysqli_stmt_bind_param($stmt_cek_lunas, "ss", $filter_tgl_mulai, $filter_tgl_selesai);
    mysqli_stmt_execute($stmt_cek_lunas);
    $result_lunas = mysqli_stmt_get_result($stmt_cek_lunas);
    // Ambil hanya kolom 'id_pekerja' ke dalam array sederhana
    $pekerja_sudah_digaji = array_column(mysqli_fetch_all($result_lunas, MYSQLI_ASSOC), 'id_pekerja');
    mysqli_stmt_close($stmt_cek_lunas);
}

// 3. Query Inti: Menghitung Total Kehadiran & Lembur per Pekerja
$sql_gaji = "SELECT 
                p.id_pekerja,
                p.namapekerja,
                j.namajabatan,
                j.gajipokok,
                j.tunjangan_lembur,
                COUNT(a.id_absensi) AS total_hari_kerja,
                SUM(CASE WHEN a.status_hadir = 1 THEN 1 ELSE 0 END) AS total_hari_hadir,
                SUM(CASE WHEN a.lembur = 1 AND a.status_hadir = 1 THEN 1 ELSE 0 END) AS total_hari_lembur
             FROM absensi a
             JOIN pekerja p ON a.id_pekerja = p.id_pekerja
             JOIN jabatan j ON p.id_jabatan = j.id_jabatan
             WHERE (a.tanggal BETWEEN ? AND ?)";

$bind_types = "ss";
$bind_values = [$filter_tgl_mulai, $filter_tgl_selesai];

// Tambahkan filter proyek jika dipilih
$nama_proyek_terpilih = "Semua Proyek Terkait";
if ($filter_id_projek !== 'semua') {
    $sql_gaji .= " AND a.id_projek = ?";
    $bind_types .= "i";
    $bind_values[] = intval($filter_id_projek);
    
    // Ambil nama proyek untuk judul
    $stmt_nama_proyek = mysqli_prepare($koneksi, "SELECT namaprojek FROM projek WHERE id_projek = ?");
    if($stmt_nama_proyek) {
        $index_id_projek = count($bind_values) - 1;
        mysqli_stmt_bind_param($stmt_nama_proyek, "i", $bind_values[$index_id_projek]);
        mysqli_stmt_execute($stmt_nama_proyek);
        $res = mysqli_stmt_get_result($stmt_nama_proyek);
        if($data = mysqli_fetch_assoc($res)) {
            $nama_proyek_terpilih = $data['namaprojek'];
        }
        mysqli_stmt_close($stmt_nama_proyek);
    }
}

$sql_gaji .= " GROUP BY p.id_pekerja ORDER BY p.namapekerja ASC";

$stmt_gaji = mysqli_prepare($koneksi, $sql_gaji);
$draft_gaji = [];
if ($stmt_gaji) {
    mysqli_stmt_bind_param($stmt_gaji, $bind_types, ...$bind_values);
    mysqli_stmt_execute($stmt_gaji);
    $result = mysqli_stmt_get_result($stmt_gaji);
    $draft_gaji = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt_gaji);
}

// Panggil Header & Sidebar
require_once '../includes/header.php'; 
if ($user_role == 'super_admin') { require_once '../includes/sidebar_super_admin.php'; } 
elseif ($user_role == 'admin') { require_once '../includes/sidebar_admin.php'; }
?>

<main class="content-wrapper mt-16 md:ml-72">
    <div class="max-w-full mx-auto p-4 sm:p-6 lg:p-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 sm:p-8">
            <div class="mb-5">
                <a href="<?php echo BASE_URL; ?>gaji/generate.php" class="text-sm text-blue-600 dark:text-blue-400 hover:underline mb-4 inline-block">
                    <i class="fas fa-arrow-left fa-fw mr-1"></i> Kembali ke Halaman Generate
                </a>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 dark:text-white">
                    <i class="fas fa-edit fa-fw mr-3 text-green-500"></i>Pratinjau & Koreksi Gaji
                </h1>
                <p class="text-md text-gray-600 dark:text-gray-400 mt-1">
                    Periode: <span class="font-semibold"><?php echo date('d M Y', strtotime($filter_tgl_mulai)); ?></span> - 
                    <span class="font-semibold"><?php echo date('d M Y', strtotime($filter_tgl_selesai)); ?></span>
                </p>
                <p class="text-md text-gray-600 dark:text-gray-400">
                    Proyek: <span class="font-semibold"><?php echo htmlspecialchars($nama_proyek_terpilih); ?></span>
                </p>
            </div>
            
            <div class="mb-4">
                <label for="searchPekerja" class="sr-only">Cari Pekerja atau Jabatan</label>
                <input type="text" id="searchPekerja" placeholder="Cari nama pekerja atau jabatan..." class="w-full md:w-1/3 px-4 py-2 border rounded-md dark:bg-gray-900 dark:text-white dark:border-gray-600">
            </div>

            <form action="<?php echo BASE_URL; ?>gaji/proses_finalisasi.php" method="POST">
                <input type="hidden" name="periode_start" value="<?php echo $filter_tgl_mulai; ?>">
                <input type="hidden" name="periode_end" value="<?php echo $filter_tgl_selesai; ?>">

                <div class="overflow-x-auto shadow-md rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-100 dark:bg-gray-700">
                            <tr>
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Pekerja</th>
                                <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Hadir</th>
                                <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Lembur</th>
                                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Gaji Pokok</th>
                                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Upah Lembur</th>
                                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider" style="min-width: 150px;">Tj. Transport</th>
                                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider" style="min-width: 150px;">Tj. Kesehatan</th>
                                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider" style="min-width: 150px;">Tj. Lainnya</th>
                                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total Pendapatan</th>
                                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider" style="min-width: 150px;">Potongan</th>
                                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Gaji Bersih</th>
                            </tr>
                        </thead>
                        <tbody id="tabelGajiBody" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <?php if (!empty($draft_gaji)): ?>
                                <?php foreach ($draft_gaji as $gaji): ?>
                                    <?php 
                                        $gaji_pokok_dihitung = $gaji['total_hari_hadir'] * $gaji['gajipokok'];
                                        $upah_lembur_dihitung = $gaji['total_hari_lembur'] * $gaji['tunjangan_lembur'];
                                        
                                        // LOGIKA BARU: Cek apakah pekerja ini sudah lunas & siapkan atribut disabled
                                        $is_lunas = in_array($gaji['id_pekerja'], $pekerja_sudah_digaji);
                                        $disabled_attr = $is_lunas ? 'disabled' : '';
                                    ?>
                                    <tr class="baris-pekerja <?php if($is_lunas) echo 'bg-gray-100 dark:bg-gray-900/50 opacity-60'; ?>">
                                        <td class="px-3 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-2">
                                                <div class="font-semibold text-gray-900 dark:text-white nama-pekerja"><?php echo htmlspecialchars($gaji['namapekerja']); ?></div>
                                                <?php if($is_lunas): ?>
                                                    <span class="text-xs font-bold py-0.5 px-2 bg-green-200 text-green-800 rounded-full">LUNAS</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400 jabatan-pekerja"><?php echo htmlspecialchars($gaji['namajabatan']); ?></div>
                                            
                                            <?php if(!$is_lunas): ?>
                                                <input type="hidden" name="id_pekerja[]" value="<?php echo $gaji['id_pekerja']; ?>">
                                            <?php endif; ?>

                                            <input type="hidden" name="total_hari_hadir[<?php echo $gaji['id_pekerja']; ?>]" value="<?php echo $gaji['total_hari_hadir']; ?>">
                                            <input type="hidden" name="total_lembur[<?php echo $gaji['id_pekerja']; ?>]" value="<?php echo $gaji['total_hari_lembur']; ?>">
                                            <input type="hidden" name="gaji_pokok_bayar[<?php echo $gaji['id_pekerja']; ?>]" value="<?php echo $gaji_pokok_dihitung; ?>">
                                            <input type="hidden" name="lembur_pay[<?php echo $gaji['id_pekerja']; ?>]" value="<?php echo $upah_lembur_dihitung; ?>">
                                        </td>
                                        <td class="px-3 py-4 text-center text-sm text-gray-800 dark:text-gray-300"><?php echo $gaji['total_hari_hadir']; ?></td>
                                        <td class="px-3 py-4 text-center text-sm text-gray-800 dark:text-gray-300"><?php echo $gaji['total_hari_lembur']; ?></td>
                                        <td class="px-3 py-4 text-right text-sm text-gray-800 dark:text-gray-300">Rp <?php echo number_format($gaji_pokok_dihitung, 0, ',', '.'); ?></td>
                                        <td class="px-3 py-4 text-right text-sm text-gray-800 dark:text-gray-300">Rp <?php echo number_format($upah_lembur_dihitung, 0, ',', '.'); ?></td>
                                        
                                        <td class="px-3 py-4"><input type="number" name="tunjangan_transport[<?php echo $gaji['id_pekerja']; ?>]" class="input-gaji w-full text-right" value="0" data-pekerja-id="<?php echo $gaji['id_pekerja']; ?>" <?php echo $disabled_attr; ?>></td>
                                        <td class="px-3 py-4"><input type="number" name="tunjangan_kesehatan[<?php echo $gaji['id_pekerja']; ?>]" class="input-gaji w-full text-right" value="0" data-pekerja-id="<?php echo $gaji['id_pekerja']; ?>" <?php echo $disabled_attr; ?>></td>
                                        <td class="px-3 py-4"><input type="number" name="tunjangan_lainnya[<?php echo $gaji['id_pekerja']; ?>]" class="input-gaji w-full text-right" value="0" data-pekerja-id="<?php echo $gaji['id_pekerja']; ?>" <?php echo $disabled_attr; ?>></td>
                                        <td class="px-3 py-4 text-right font-bold text-gray-900 dark:text-white"><span id="total_pendapatan_<?php echo $gaji['id_pekerja']; ?>">Rp 0</span></td>
                                        <td class="px-3 py-4"><input type="number" name="potongan[<?php echo $gaji['id_pekerja']; ?>]" class="input-gaji w-full text-right" value="0" data-pekerja-id="<?php echo $gaji['id_pekerja']; ?>" <?php echo $disabled_attr; ?>></td>
                                        <td class="px-3 py-4 text-right font-bold text-lg text-green-600 dark:text-green-400"><span id="gaji_bersih_<?php echo $gaji['id_pekerja']; ?>">Rp 0</span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="11" class="text-center p-5 text-gray-500 dark:text-gray-400">Tidak ada data absensi untuk dihitung pada periode dan proyek yang dipilih.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (!empty($draft_gaji)): ?>
                <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700 flex justify-end">
                    <button type="submit" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        <i class="fas fa-check-circle fa-fw mr-2"></i>
                        Finalisasi Gaji
                    </button>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</main>
<style>
.input-gaji {
    padding: 0.5rem;
    border-radius: 0.375rem;
    border: 1px solid #D1D5DB; /* gray-300 */
}
.dark .input-gaji {
    background-color: #1F2937; /* gray-800 */
    border-color: #4B5563; /* gray-600 */
    color: white;
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Fungsi untuk memformat angka menjadi Rupiah
    const formatRupiah = (angka) => {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
    }

    // Fungsi untuk menghitung ulang gaji untuk satu baris pekerja
    const recalculateRow = (pekerjaId) => {
        const row = document.querySelector(`#total_pendapatan_${pekerjaId}`).closest('tr');
        if (!row) return;

        // Ambil nilai dari hidden input
        const gajiPokok = parseFloat(row.querySelector(`input[name="gaji_pokok_bayar[${pekerjaId}]"]`).value) || 0;
        const upahLembur = parseFloat(row.querySelector(`input[name="lembur_pay[${pekerjaId}]"]`).value) || 0;

        // Ambil nilai dari input manual
        const tjTransport = parseFloat(row.querySelector(`input[name="tunjangan_transport[${pekerjaId}]"]`).value) || 0;
        const tjKesehatan = parseFloat(row.querySelector(`input[name="tunjangan_kesehatan[${pekerjaId}]"]`).value) || 0;
        const tjLainnya = parseFloat(row.querySelector(`input[name="tunjangan_lainnya[${pekerjaId}]"]`).value) || 0;
        const potongan = parseFloat(row.querySelector(`input[name="potongan[${pekerjaId}]"]`).value) || 0;

        // Lakukan kalkulasi
        const totalPendapatan = gajiPokok + upahLembur + tjTransport + tjKesehatan + tjLainnya;
        const gajiBersih = totalPendapatan - potongan;

        // Tampilkan hasil
        document.getElementById(`total_pendapatan_${pekerjaId}`).textContent = formatRupiah(totalPendapatan);
        document.getElementById(`gaji_bersih_${pekerjaId}`).textContent = formatRupiah(gajiBersih);
    }

    // Beri event listener ke semua input manual
    const allInputs = document.querySelectorAll('.input-gaji');
    allInputs.forEach(input => {
        // Hitung ulang saat halaman dimuat
        recalculateRow(input.dataset.pekerjaId);

        // Hitung ulang setiap kali ada input
        input.addEventListener('input', function() {
            recalculateRow(this.dataset.pekerjaId);
        });
    });

    // Logika untuk filter pencarian nama DAN JABATAN
    const searchInput = document.getElementById('searchPekerja');
    const tableBody = document.getElementById('tabelGajiBody');
    const rows = tableBody.querySelectorAll('.baris-pekerja');

    searchInput.addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();
        rows.forEach(row => {
            const namaPekerja = row.querySelector('.nama-pekerja').textContent.toLowerCase();
            const jabatanPekerja = row.querySelector('.jabatan-pecker').textContent.toLowerCase(); // Ambil teks jabatan
            
            // Tampilkan baris jika cocok dengan NAMA atau JABATAN
            if (namaPekerja.includes(searchTerm) || jabatanPekerja.includes(searchTerm)) {
                row.style.display = ''; // Tampilkan
            } else {
                row.style.display = 'none'; // Sembunyikan
            }
        });
    });
});
</script>

<?php
require_once '../includes/footer.php';
?>