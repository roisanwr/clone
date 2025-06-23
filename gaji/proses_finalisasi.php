<?php
// File: proyek_jaya/gaji/proses_finalisasi.php
// Deskripsi: 'Mesin' untuk finalisasi dan penyimpanan data gaji ke database.

require_once '../config.php';

// 1. Autentikasi & Autorisasi: HANYA Super Admin dan Admin
if (!isset($_SESSION['user_id'])) { header('Location: ' . BASE_URL . 'auth/login.php?error=2'); exit; }
$user_role = $_SESSION['role'];
if (!in_array($user_role, ['super_admin', 'admin'])) {
    $_SESSION['pesan_error'] = "MAAF, ANDA TIDAK MEMILIKI HAK AKSES KE HALAMAN INI.";
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

// 2. Validasi Permintaan
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'gaji/generate.php');
    exit;
}

// 3. Ambil data dari Form
$periode_start = $_POST['periode_start'] ?? '';
$periode_end = $_POST['periode_end'] ?? '';
$array_id_pekerja = $_POST['id_pekerja'] ?? [];

// Validasi data utama
if (empty($periode_start) || empty($periode_end)) {
    $_SESSION['pesan_error_gaji'] = "Periode tanggal tidak valid. Proses finalisasi dibatalkan.";
    header('Location: ' . BASE_URL . 'gaji/generate.php');
    exit;
}

// Jika tidak ada pekerja yang belum lunas untuk difinalisasi
if (empty($array_id_pekerja)) {
    $_SESSION['pesan_sukses_gaji'] = "Tidak ada data gaji baru untuk difinalisasi. Semua gaji pada periode ini mungkin sudah lunas.";
    header('Location: ' . BASE_URL . 'gaji/index.php');
    exit;
}


// 4. Siapkan Query INSERT yang Aman
$sql_insert = "INSERT INTO gaji (
                    id_pekerja, periode_start, periode_end, 
                    total_hari_hadir, total_lembur, 
                    gaji_pokok_bayar, lembur_pay, 
                    tunjangan_transport_manual, tunjangan_kesehatan_manual, tunjangan_rumah_manual, 
                    total_pendapatan_bruto, total_potongan_manual, total_gaji_netto, 
                    tanggal_bayar
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

$stmt_insert = mysqli_prepare($koneksi, $sql_insert);

if (!$stmt_insert) {
    // Jika statement gagal disiapkan, ini masalah serius
    error_log("Gagal mempersiapkan statement SQL untuk finalisasi gaji: " . mysqli_error($koneksi));
    $_SESSION['pesan_error_gaji'] = "Terjadi kesalahan kritis pada sistem. Harap hubungi administrator.";
    header('Location: ' . BASE_URL . 'gaji/generate.php');
    exit;
}

// 5. Proses Data di dalam Transaksi
$berhasil_disimpan = 0;
$gagal_disimpan = 0;
$pesan_gagal_detail = [];

mysqli_begin_transaction($koneksi);

foreach ($array_id_pekerja as $id_pekerja) {
    // Ambil semua data untuk pekerja ini
    $id_pekerja_int = intval($id_pekerja);
    $total_hari_hadir = intval($_POST['total_hari_hadir'][$id_pekerja_int] ?? 0);
    $total_lembur = intval($_POST['total_lembur'][$id_pekerja_int] ?? 0);
    $gaji_pokok_bayar = floatval($_POST['gaji_pokok_bayar'][$id_pekerja_int] ?? 0);
    $lembur_pay = floatval($_POST['lembur_pay'][$id_pekerja_int] ?? 0);
    $t_transport = floatval($_POST['tunjangan_transport'][$id_pekerja_int] ?? 0);
    $t_kesehatan = floatval($_POST['tunjangan_kesehatan'][$id_pekerja_int] ?? 0);
    $t_lainnya = floatval($_POST['tunjangan_lainnya'][$id_pekerja_int] ?? 0); // Di DB nama kolomnya tunjangan_rumah_manual
    $potongan = floatval($_POST['potongan'][$id_pekerja_int] ?? 0);

    // Lakukan kalkulasi final di backend (sebagai validasi ulang)
    $total_pendapatan = $gaji_pokok_bayar + $lembur_pay + $t_transport + $t_kesehatan + $t_lainnya;
    $total_gaji_netto = $total_pendapatan - $potongan;

    // Bind parameter ke statement
    mysqli_stmt_bind_param($stmt_insert, "issiiidddddds",
        $id_pekerja_int, $periode_start, $periode_end,
        $total_hari_hadir, $total_lembur,
        $gaji_pokok_bayar, $lembur_pay,
        $t_transport, $t_kesehatan, $t_lainnya,
        $total_pendapatan, $potongan, $total_gaji_netto
    );

    // Eksekusi
    if (!mysqli_stmt_execute($stmt_insert)) {
        $gagal_disimpan++;
        // Error duplikasi tidak akan terjadi lagi karena sudah dicegah di frontend,
        // tapi ini sebagai pengaman tambahan.
        if (mysqli_errno($koneksi) == 1062) { 
            $pesan_gagal_detail[] = "Gaji untuk pekerja ID #$id_pekerja_int pada periode ini sudah ada.";
        } else {
            $pesan_gagal_detail[] = "Error database untuk Pekerja ID #$id_pekerja_int: " . mysqli_stmt_error($stmt_insert);
        }
    } else {
        $berhasil_disimpan++;
    }
}

// 6. Finalisasi Transaksi
if ($gagal_disimpan > 0) {
    // Jika ada satu saja yang gagal, batalkan semua
    mysqli_rollback($koneksi);
    $_SESSION['pesan_error_gaji'] = "Proses finalisasi GAGAL. " . implode(" ", $pesan_gagal_detail);
    header('Location: ' . BASE_URL . 'gaji/preview.php?tgl_mulai='.$periode_start.'&tgl_selesai='.$periode_end.'&id_projek=semua');
    exit;
} else {
    // Jika semua berhasil, simpan permanen
    mysqli_commit($koneksi);
    $_SESSION['pesan_sukses_gaji'] = "$berhasil_disimpan data gaji pekerja berhasil difinalisasi dan disimpan.";
    // Redirect ke halaman riwayat gaji
    header('Location: ' . BASE_URL . 'gaji/index.php');
    exit;
}

mysqli_stmt_close($stmt_insert);
?>
