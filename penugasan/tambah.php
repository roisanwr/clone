<?php
// File: proyek_jaya/penugasan/tambah.php (Lengkap dengan Pencegahan Duplikat Pilihan)

require_once '../config.php';

// 1. Autentikasi & Autorisasi (Sama seperti sebelumnya)
if (!isset($_SESSION['user_id'])) { header('Location: ' . BASE_URL . 'auth/login.php?error=2'); exit; }
$user_role = $_SESSION['role'];
if (!isset($_GET['id_projek']) || !is_numeric($_GET['id_projek'])) { header('Location: ' . ($user_role === 'mandor' ? BASE_URL . 'proyek/proyek_saya.php' : BASE_URL . 'proyek/')); exit; }
$id_projek_penugasan = intval($_GET['id_projek']);

// 2. Ambil Detail Proyek & Cek Otorisasi Mandor (Sama seperti sebelumnya)
$sql_proyek = "SELECT namaprojek, id_mandor_pekerja FROM projek WHERE id_projek = ?";
// ... (Lanjutan kode PHP untuk mengambil detail proyek dan validasi otorisasi Mandor) ...
// (Sparky ringkas bagian ini karena sama dengan kode terakhirmu yang sudah benar)
$stmt_proyek = mysqli_prepare($koneksi, $sql_proyek);
mysqli_stmt_bind_param($stmt_proyek, "i", $id_projek_penugasan);
mysqli_stmt_execute($stmt_proyek);
$detail_proyek = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_proyek));
mysqli_stmt_close($stmt_proyek);
if (!$detail_proyek || ($user_role === 'mandor' && $detail_proyek['id_mandor_pekerja'] != ($_SESSION['id_pekerja_ref'] ?? null))) {
    $_SESSION['pesan_error'] = "Akses ditolak atau proyek tidak ditemukan.";
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}
$nama_proyek_penugasan = htmlspecialchars($detail_proyek['namaprojek']);


// 3. Ambil daftar PEKERJA yang tersedia dan siapkan untuk PHP & JavaScript
$nama_jabatan_dikecualikan = 'Mandor'; 
$query_pekerja_options = "SELECT p.id_pekerja, p.namapekerja, j.namajabatan 
                          FROM pekerja p
                          INNER JOIN jabatan j ON p.id_jabatan = j.id_jabatan
                          WHERE p.is_active = 1 AND j.namajabatan != ?
                        --   AND p.id_pekerja NOT IN (
                        --       SELECT pp.id_pekerja FROM proyek_pekerja pp 
                        --       WHERE pp.id_projek = ? AND (pp.tanggal_akhir_penugasan IS NULL OR pp.tanggal_akhir_penugasan >= CURDATE()) AND pp.is_active = 1
                        --   )
                          ORDER BY p.namapekerja ASC";
$stmt_pekerja_options = mysqli_prepare($koneksi, $query_pekerja_options);
$daftar_pekerja_pilihan_php = []; 
$daftar_pekerja_pilihan_json = "[]"; 

if ($stmt_pekerja_options) {
    // Karena placeholder '?' sekarang hanya ada 1 (untuk j.namajabatan), kita sesuaikan bind_param
    mysqli_stmt_bind_param($stmt_pekerja_options, "s", $nama_jabatan_dikecualikan);
    mysqli_stmt_execute($stmt_pekerja_options);
    $result_pekerja_options = mysqli_stmt_get_result($stmt_pekerja_options);
    if($result_pekerja_options){
        $daftar_pekerja_pilihan_php = mysqli_fetch_all($result_pekerja_options, MYSQLI_ASSOC);
        $js_options = [];
        foreach($daftar_pekerja_pilihan_php as $pek_opt){
            $js_options[] = [
                'value' => (string) $pek_opt['id_pekerja'], 
                'label' => htmlspecialchars($pek_opt['namapekerja'], ENT_QUOTES) . " (" . htmlspecialchars($pek_opt['namajabatan'], ENT_QUOTES) . ")"
            ];
        }
        $daftar_pekerja_pilihan_json = json_encode($js_options);
    }
    mysqli_stmt_close($stmt_pekerja_options);
}

// 4. Siapkan pesan notifikasi & sticky form
$pesan_notifikasi_tambah = '';
if (isset($_SESSION['pesan_error_crud'])) { $pesan_notifikasi_tambah = "<div class='mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg shadow'>" . htmlspecialchars($_SESSION['pesan_error_crud']) . "</div>"; unset($_SESSION['pesan_error_crud']); }
$form_data = isset($_SESSION['form_data']) ? $_SESSION['form_data'] : [];
unset($_SESSION['form_data']);

// 5. Panggil Header & Sidebar
require_once '../includes/header.php'; 
if ($user_role == 'super_admin') { require_once '../includes/sidebar_super_admin.php'; } 
elseif ($user_role == 'admin') { require_once '../includes/sidebar_admin.php'; } 
elseif ($user_role == 'mandor') { require_once '../includes/sidebar_mandor.php'; }
?>

<main class="content-wrapper mt-16 md:ml-72">
    <div class="max-w-3xl mx-auto p-4 sm:p-6 lg:p-8"> 
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 sm:p-8">
            <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 dark:text-white mb-2">
                <i class="fas fa-users-cog fa-fw mr-2 text-blue-500"></i>Tugaskan Pekerja ke Proyek
            </h1>
            <p class="text-md text-gray-700 dark:text-gray-300 mb-6">Proyek: <span class="font-semibold"><?php echo $nama_proyek_penugasan; ?></span></p>

            <?php echo $pesan_notifikasi_tambah; ?>

            <form action="<?php echo BASE_URL; ?>penugasan/proses.php?aksi=tambah_multiple&id_projek=<?php echo $id_projek_penugasan; ?>" method="POST">
                
                <div id="daftar-pekerja-container" class="space-y-4">
                    </div>

                <button type="button" id="tambahBarisPekerja" class="mt-3 mb-5 inline-flex items-center px-3 py-1.5 border border-dashed border-gray-400 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                    <i class="fas fa-plus fa-fw mr-2"></i> Tugaskan Pekerja Lain
                </button>
                
                <?php if (empty($daftar_pekerja_pilihan_php)): ?>
                    <p class="mb-4 text-sm text-yellow-600 dark:text-yellow-400">Saat ini tidak ada pekerja yang tersedia/memenuhi kriteria untuk ditugaskan ke proyek ini.</p>
                <?php endif; ?>

                <hr class="my-6 border-gray-300 dark:border-gray-600">
                <div class="mb-5">
                    <label for="tanggal_mulai_penugasan" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tanggal Mulai Tugas (untuk semua pekerja di atas) <span class="text-red-500">*</span></label>
                    <input type="date" name="tanggal_mulai_penugasan" id="tanggal_mulai_penugasan" required
                           value="<?php echo htmlspecialchars($form_data['tanggal_mulai_penugasan'] ?? date('Y-m-d')); ?>"
                           class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm ...">
                </div>
                <div class="mb-6">
                    <label for="tanggal_akhir_penugasan" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tanggal Akhir Tugas (Opsional, untuk semua)</label>
                    <input type="date" name="tanggal_akhir_penugasan" id="tanggal_akhir_penugasan"
                           value="<?php echo htmlspecialchars($form_data['tanggal_akhir_penugasan'] ?? ''); ?>"
                           class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm ...">
                     <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Kosongkan jika penugasan tidak ada batas waktu.</p>
                </div>
                <div class="mt-8 flex items-center justify-end gap-x-4 border-t border-gray-200 dark:border-gray-700 pt-6">
                    <a href="<?php echo BASE_URL; ?>penugasan/detail_tim.php?id_projek=<?php echo $id_projek_penugasan; ?>" class="text-sm font-semibold ...">Batal</a>
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md shadow-sm transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800">
                        <i class="fas fa-users-cog fa-fw mr-2"></i> Tugaskan Pekerja Terpilih
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const daftarPekerjaContainer = document.getElementById('daftar-pekerja-container');
    const tombolTambahBaris = document.getElementById('tambahBarisPekerja');
    window.choicesInstances = {}; // Menyimpan semua instance Choices.js { id: instance }

    // 'Master List' opsi pekerja dari PHP. Ini tidak akan kita ubah.
    const masterOpsiPekerja = <?php echo $daftar_pekerja_pilihan_json; ?>;

    function buatChoicesConfig() {
        return {
            searchEnabled: true, removeItemButton: true, placeholder: true,
            placeholderValue: '-- Pilih atau ketik nama --', itemSelectText: '',
            fuseOptions: { keys: ['label'], threshold: 0.3 }, shouldSort: false,
        };
    }
    
    // Fungsi untuk mengupdate opsi di SEMUA dropdown yang ada
    function updateAllDropdownOptions() {
        const selectedValues = [];
        // 1. Kumpulkan semua value yang SUDAH TERPILIH di semua dropdown
        for (const id in window.choicesInstances) {
            const instance = window.choicesInstances[id];
            const value = instance.getValue(true); // getValue(true) -> dapatkan nilainya
            if (value) {
                selectedValues.push(value.toString());
            }
        }

        // 2. Update setiap dropdown dengan opsi yang sudah disaring
        for (const id in window.choicesInstances) {
            const instance = window.choicesInstances[id];
            const currentValue = instance.getValue(true) ? instance.getValue(true).toString() : null;

            // Buat daftar opsi yang tersedia untuk dropdown INI:
            // yaitu semua dari master list KECUALI yang sudah dipilih di dropdown LAIN,
            // TAPI tetap sertakan nilai yang sedang terpilih di dropdown INI.
            let opsiTersedia = masterOpsiPekerja.filter(opt => 
                !selectedValues.includes(opt.value.toString()) || opt.value.toString() === currentValue
            );

            instance.setChoices(opsiTersedia, 'value', 'label', true); // true = replace semua pilihan lama
        }
    }

    function tambahBarisPekerja() {
        const newIndex = Object.keys(window.choicesInstances).length; // ID unik berdasarkan jumlah instance
        const barisId = `baris_pekerja_${newIndex}`;
        const selectId = `id_pekerja_${newIndex}`;
        
        // Buat elemen HTML untuk satu baris baru
        const barisBaruHTML = `
            <div class="baris-pekerja p-3 border border-gray-200 dark:border-gray-700 rounded-md flex items-center gap-2 mt-4" id="${barisId}">
                <div class="flex-grow">
                    <select name="id_pekerja[]" id="${selectId}" class="w-full select-pekerja">
                        <option value="">-- Pilih --</option>
                    </select>
                </div>
                <button type="button" class="tombol-hapus-baris p-1 text-red-500 hover:text-red-700" title="Hapus Pekerja Ini">
                    <i class="fas fa-times"></i>
                </button>
            </div>`;
        daftarPekerjaContainer.insertAdjacentHTML('beforeend', barisBaruHTML);

        // Inisialisasi Choices.js untuk select baru
        const newSelectElement = document.getElementById(selectId);
        const newChoicesInstance = new Choices(newSelectElement, buatChoicesConfig());
        window.choicesInstances[selectId] = newChoicesInstance;
        
        // Tambahkan event listener ke instance baru
        newSelectElement.addEventListener('change', updateAllDropdownOptions, false);
        
        // Update semua dropdown setelah baris baru ditambahkan
        updateAllDropdownOptions();

        // Tambahkan event listener untuk tombol hapus di baris baru
        document.querySelector(`#${barisId} .tombol-hapus-baris`).addEventListener('click', function() {
            if (Object.keys(window.choicesInstances).length <= 1) {
                alert("Minimal harus ada satu pekerja yang ditugaskan.");
                return;
            }
            window.choicesInstances[selectId].destroy();
            delete window.choicesInstances[selectId];
            document.getElementById(barisId).remove();
            updateAllDropdownOptions(); // Update lagi setelah baris dihapus
        });
    }

    if (tombolTambahBaris) {
        tombolTambahBaris.addEventListener('click', tambahBarisPekerja);
    }
    
    // Tambahkan baris pertama secara otomatis saat halaman dimuat jika ada pekerja yang bisa dipilih
    if (masterOpsiPekerja.length > 0) {
        tambahBarisPekerja();
    }
});
</script>

<?php
require_once '../includes/footer.php'; 
?>