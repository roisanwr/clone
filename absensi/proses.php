<?php
// File: proyek_jaya/absensi/proses.php
// Deskripsi: 'Mesin' cerdas untuk memproses penyimpanan data absensi.
// VERSI UPDATE: Hanya memperbarui data yang benar-benar berubah untuk menjaga integritas waktu_update.

require_once '../config.php';

// =========================================================================
// 1. AUTENTIKASI & OTORISASI DASAR (Blueprint Standard)
// =========================================================================
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'mandor') {
    $_SESSION['pesan_error'] = "Akses ditolak.";
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}
if (!isset($_SESSION['id_pekerja_ref']) || empty($_SESSION['id_pekerja_ref'])) {
    $_SESSION['pesan_error'] = "Data pekerja untuk akun Mandor Anda tidak valid.";
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}
$id_mandor_login = $_SESSION['id_pekerja_ref'];

// =========================================================================
// 2. ROUTING AKSI & VALIDASI PERMINTAAN
// =========================================================================
if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST['simpan_absensi'], $_GET['aksi']) || $_GET['aksi'] !== 'simpan') {
    $_SESSION['pesan_error'] = "Permintaan tidak valid.";
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

// =========================================================================
// 3. PENGAMBILAN & VALIDASI DATA DARI FORM
// =========================================================================
$id_projek        = isset($_POST['id_projek']) ? intval($_POST['id_projek']) : 0;
$tanggal_absensi  = isset($_POST['tanggal_absensi']) ? $_POST['tanggal_absensi'] : '';
$array_status_hadir = $_POST['status_hadir'] ?? [];
$array_lembur       = $_POST['lembur'] ?? [];
$array_keterangan   = $_POST['keterangan'] ?? [];

if (empty($id_projek) || empty($tanggal_absensi) || empty($array_status_hadir)) {
    $_SESSION['pesan_error_crud'] = "Data yang dikirim tidak lengkap. Gagal memproses absensi.";
    header('Location: ' . BASE_URL . 'absensi/catat.php');
    exit;
}
if ($tanggal_absensi !== date('Y-m-d')) {
    $_SESSION['pesan_error_crud'] = "GAGAL: Penambahan atau pengeditan dari halaman ini hanya berlaku untuk absensi tanggal hari ini.";
    header('Location: ' . BASE_URL . 'absensi/catat.php');
    exit;
}

// =========================================================================
// 4. OTORISASI SPESIFIK AKSI (PENTING!)
// =========================================================================
$sql_auth_proyek = "SELECT id_mandor_pekerja FROM projek WHERE id_projek = ?";
$stmt_auth = mysqli_prepare($koneksi, $sql_auth_proyek);
mysqli_stmt_bind_param($stmt_auth, "i", $id_projek);
mysqli_stmt_execute($stmt_auth);
$result_auth = mysqli_stmt_get_result($stmt_auth);
$proyek_auth = mysqli_fetch_assoc($result_auth);
mysqli_stmt_close($stmt_auth);

if (!$proyek_auth || $proyek_auth['id_mandor_pekerja'] != $id_mandor_login) {
    $_SESSION['pesan_error_crud'] = "AKSES DITOLAK: Anda tidak berhak menyimpan absensi untuk proyek ini.";
    header('Location: ' . BASE_URL . 'absensi/catat.php');
    exit;
}

// =========================================================================
// 5. LOGIKA INTI: PROSES UPDATE CERDAS
// =========================================================================

// LANGKAH 1: Ambil "foto" data absensi yang sudah ada di DB untuk hari ini
$data_lama_db = [];
$sql_get_old_data = "SELECT id_pekerja, status_hadir, lembur, keterangan FROM absensi WHERE id_projek = ? AND tanggal = ?";
$stmt_get_old = mysqli_prepare($koneksi, $sql_get_old_data);
if ($stmt_get_old) {
    mysqli_stmt_bind_param($stmt_get_old, "is", $id_projek, $tanggal_absensi);
    mysqli_stmt_execute($stmt_get_old);
    $result_old_data = mysqli_stmt_get_result($stmt_get_old);
    while ($row = mysqli_fetch_assoc($result_old_data)) {
        // Simpan data lama dengan key id_pekerja untuk pencarian cepat
        $data_lama_db[$row['id_pekerja']] = $row;
    }
    mysqli_stmt_close($stmt_get_old);
}

// LANGKAH 2: Siapkan query UPSERT (tetap berguna untuk data baru)
$sql_upsert = "INSERT INTO absensi (id_projek, id_pekerja, id_mandor, tanggal, status_hadir, lembur, keterangan)
               VALUES (?, ?, ?, ?, ?, ?, ?)
               ON DUPLICATE KEY UPDATE
               status_hadir = VALUES(status_hadir),
               lembur = VALUES(lembur),
               keterangan = VALUES(keterangan),
               id_mandor = VALUES(id_mandor),
               waktu_update = NOW()";

$stmt_upsert = mysqli_prepare($koneksi, $sql_upsert);

if ($stmt_upsert) {
    $berhasil_diproses = 0;
    $gagal_diproses = 0;
    $tidak_berubah = 0;
    
    mysqli_begin_transaction($koneksi);

    // LANGKAH 3: Loop melalui data dari form dan bandingkan
    foreach ($array_status_hadir as $id_pekerja_loop => $status_hadir_baru) {
        $id_pekerja = intval($id_pekerja_loop);
        $lembur_baru = isset($array_lembur[$id_pekerja]) ? intval($array_lembur[$id_pekerja]) : 0;
        $keterangan_baru = trim($array_keterangan[$id_pekerja] ?? '');
        
        $data_berubah = false; // Anggap tidak ada perubahan awalnya

        if (!isset($data_lama_db[$id_pekerja])) {
            // Jika data pekerja tidak ada di "foto" DB, berarti ini data BARU.
            $data_berubah = true;
        } else {
            // Jika data ada, bandingkan satu per satu
            $data_lama_pekerja = $data_lama_db[$id_pekerja];
            if ($status_hadir_baru != $data_lama_pekerja['status_hadir'] ||
                $lembur_baru != $data_lama_pekerja['lembur'] ||
                $keterangan_baru !== $data_lama_pekerja['keterangan']) {
                $data_berubah = true;
            }
        }
        
        // LANGKAH 4: Eksekusi query HANYA jika data berubah
        if ($data_berubah) {
            mysqli_stmt_bind_param(
                $stmt_upsert, "iiisiss",
                $id_projek, $id_pekerja, $id_mandor_login, $tanggal_absensi,
                $status_hadir_baru, $lembur_baru, $keterangan_baru
            );

            if (mysqli_stmt_execute($stmt_upsert)) {
                $berhasil_diproses++;
            } else {
                $gagal_diproses++;
            }
        } else {
            // Jika tidak ada perubahan, kita catat saja
            $tidak_berubah++;
        }
    } // Akhir dari loop

    // LANGKAH 5: Finalisasi transaksi
    if ($gagal_diproses > 0) {
        mysqli_rollback($koneksi);
        $_SESSION['pesan_error_crud'] = "Terjadi kesalahan! $gagal_diproses data gagal diproses. Tidak ada data yang disimpan.";
    } else {
        mysqli_commit($koneksi);
        $pesan_sukses = "$berhasil_diproses data absensi berhasil disimpan/diperbarui.";
        if ($tidak_berubah > 0) {
            $pesan_sukses .= " ($tidak_berubah data tidak berubah).";
        }
        $_SESSION['pesan_sukses'] = $pesan_sukses;
    }

    mysqli_stmt_close($stmt_upsert);
} else {
    $_SESSION['pesan_error_crud'] = "Terjadi kesalahan pada sistem (gagal mempersiapkan statement SQL).";
}
    
// =========================================================================
// 6. REDIRECT KEMBALI KE HALAMAN CATAT ABSENSI
// =========================================================================
header('Location: ' . BASE_URL . 'absensi/catat.php?id_projek=' . $id_projek);
exit;

?>
