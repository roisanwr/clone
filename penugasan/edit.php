<?php
// File: proyek_jaya/penugasan/edit.php

require_once '../config.php';

// 1. Autentikasi
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'auth/login.php?error=2');
    exit;
}
$user_role = $_SESSION['role'];

// 2. Ambil ID Penugasan dari URL dan Validasi
if (!isset($_GET['id_penugasan']) || !is_numeric($_GET['id_penugasan'])) {
    $_SESSION['pesan_error'] = "ID Penugasan tidak valid.";
    header('Location: ' . BASE_URL . 'dashboard.php'); // Redirect ke tempat aman
    exit;
}
$id_penugasan_edit = intval($_GET['id_penugasan']);

// 3. Ambil data penugasan yang akan diedit, JOIN dengan proyek dan pekerja untuk info detail
$sql_get_penugasan = "SELECT 
                          pp.id_penugasan, pp.id_projek, pp.tanggal_mulai_penugasan, pp.tanggal_akhir_penugasan, pp.is_active,
                          pr.namaprojek, 
                          pek.namapekerja,
                          pr.id_mandor_pekerja
                      FROM proyek_pekerja pp
                      INNER JOIN projek pr ON pp.id_projek = pr.id_projek
                      INNER JOIN pekerja pek ON pp.id_pekerja = pek.id_pekerja
                      WHERE pp.id_penugasan = ?";
$stmt_get = mysqli_prepare($koneksi, $sql_get_penugasan);
$penugasan_lama = null;

if ($stmt_get) {
    mysqli_stmt_bind_param($stmt_get, "i", $id_penugasan_edit);
    mysqli_stmt_execute($stmt_get);
    $result_get = mysqli_stmt_get_result($stmt_get);
    $penugasan_lama = mysqli_fetch_assoc($result_get);
    mysqli_stmt_close($stmt_get);

    if (!$penugasan_lama) {
        $_SESSION['pesan_error_crud'] = "Data penugasan dengan ID " . $id_penugasan_edit . " tidak ditemukan.";
        // Coba redirect ke halaman detail tim jika id_projek ada, jika tidak ke dashboard
        $redirect_url = isset($_GET['id_projek']) ? BASE_URL . 'penugasan/detail_tim.php?id_projek=' . intval($_GET['id_projek']) : BASE_URL . 'dashboard.php';
        header('Location: ' . $redirect_url);
        exit;
    }
} else { /* Handle error prepare statement */ /* ... */ }

// 4. Otorisasi: Pastikan yang login adalah Mandor PJ proyek ini, atau Super Admin/Admin
$id_projek_terkait = $penugasan_lama['id_projek'];
$id_mandor_pj_proyek = $penugasan_lama['id_mandor_pekerja'];
$id_pekerja_mandor_login = $_SESSION['id_pekerja_ref'] ?? null;

$can_edit = false;
if (in_array($user_role, ['super_admin', 'admin'])) {
    $can_edit = true;
} elseif ($user_role === 'mandor' && $id_mandor_pj_proyek == $id_pekerja_mandor_login) {
    $can_edit = true;
}

if (!$can_edit) {
    $_SESSION['pesan_error'] = "Anda tidak berhak mengedit penugasan ini.";
    header('Location: ' . BASE_URL . 'penugasan/detail_tim.php?id_projek=' . $id_projek_terkait);
    exit;
}

// 5. Siapkan pesan notifikasi & sticky form
$pesan_notifikasi_edit = '';
if (isset($_SESSION['pesan_error_crud'])) { $pesan_notifikasi_edit = "<div class='mb-4 p-3 bg-red-100 ...'>" . htmlspecialchars($_SESSION['pesan_error_crud']) . "</div>"; unset($_SESSION['pesan_error_crud']); }
$form_data = isset($_SESSION['form_data']) ? $_SESSION['form_data'] : $penugasan_lama;
unset($_SESSION['form_data']);

// 6. Panggil Header & Sidebar
require_once '../includes/header.php'; 
if ($user_role == 'super_admin') { require_once '../includes/sidebar_super_admin.php'; } 
elseif ($user_role == 'admin') { require_once '../includes/sidebar_admin.php'; } 
elseif ($user_role == 'mandor') { require_once '../includes/sidebar_mandor.php'; }
?>

<main class="content-wrapper mt-16 md:ml-72">
    <div class="max-w-2xl mx-auto p-4 sm:p-6 lg:p-8"> 
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 sm:p-8">
            <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 dark:text-white mb-6">
                <i class="fas fa-edit fa-fw mr-2 text-indigo-500"></i>Edit Penugasan Pekerja
            </h1>
            
            <?php echo $pesan_notifikasi_edit; ?>

            <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-700">
                <p class="text-sm text-gray-600 dark:text-gray-400">Proyek: <strong class="text-gray-800 dark:text-white"><?php echo htmlspecialchars($penugasan_lama['namaprojek']); ?></strong></p>
                <p class="text-sm text-gray-600 dark:text-gray-400">Pekerja: <strong class="text-gray-800 dark:text-white"><?php echo htmlspecialchars($penugasan_lama['namapekerja']); ?></strong></p>
            </div>

            <form action="<?php echo BASE_URL; ?>penugasan/proses.php?aksi=edit&id_penugasan=<?php echo $id_penugasan_edit; ?>&id_projek=<?php echo $id_projek_terkait; ?>" method="POST">
                <div class="mb-5">
                    <label for="tanggal_mulai_penugasan" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tanggal Mulai Tugas <span class="text-red-500">*</span></label>
                    <input type="date" name="tanggal_mulai_penugasan" id="tanggal_mulai_penugasan" required
                           value="<?php echo htmlspecialchars($form_data['tanggal_mulai_penugasan'] ?? ''); ?>"
                           class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm ...">
                </div>

                <div class="mb-6">
                    <label for="tanggal_akhir_penugasan" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tanggal Akhir Tugas (Opsional)</label>
                    <input type="date" name="tanggal_akhir_penugasan" id="tanggal_akhir_penugasan"
                           value="<?php echo htmlspecialchars($form_data['tanggal_akhir_penugasan'] ?? ''); ?>"
                           class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm ...">
                     <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Kosongkan jika penugasan tidak ada batas waktu.</p>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status Penugasan <span class="text-red-500">*</span></label>
                    <div class="flex items-center space-x-4 pt-1">
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="radio" name="is_active" value="1" class="form-radio ..." 
                                <?php echo (isset($form_data['is_active']) && $form_data['is_active'] == '1') ? 'checked' : ''; ?>>
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Aktif</span>
                        </label>
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="radio" name="is_active" value="0" class="form-radio ..." 
                                <?php echo (isset($form_data['is_active']) && $form_data['is_active'] == '0') ? 'checked' : ''; ?>>
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Tidak Aktif (Dibatalkan)</span>
                        </label>
                    </div>
                </div>

                <div class="mt-8 flex items-center justify-end gap-x-4 border-t border-gray-200 dark:border-gray-700 pt-6">
                    <a href="<?php echo BASE_URL; ?>penugasan/detail_tim.php?id_projek=<?php echo $id_projek_terkait; ?>" class="text-sm font-semibold ...">Batal</a>
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md shadow-sm transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800">
                        <i class="fas fa-users-cog fa-fw mr-2"></i> Update Penugasan
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php
require_once '../includes/footer.php'; 
?>