<?php
// File: proyek_jaya/absensi/proses_admin_edit.php
// Deskripsi: 'Mesin' final untuk memproses koreksi atau penghapusan data absensi oleh Admin/Super Admin.

require_once '../config.php';

// 1. Autentikasi & Autorisasi Dasar
if (!isset($_SESSION['user_id'])) { header('Location: ' . BASE_URL . 'auth/login.php?error=2'); exit; }
$user_role = $_SESSION['role'];
if (!in_array($user_role, ['super_admin', 'admin'])) {
    $_SESSION['pesan_error'] = "MAAF, ANDA TIDAK MEMILIKI HAK AKSES.";
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

// 2. Routing Aksi (Simpan atau Hapus)
if (isset($_GET['aksi'])) {
    $aksi = $_GET['aksi'];

    // =============================================================
    // --- AKSI: SIMPAN KOREKSI (Untuk Admin & Super Admin) ---
    // =============================================================
    if ($aksi == 'simpan' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_koreksi'])) {
        
        // Ambil dan validasi data dari form
        $id_absensi = isset($_POST['id_absensi']) ? intval($_POST['id_absensi']) : 0;
        $status_hadir = isset($_POST['status_hadir']) ? intval($_POST['status_hadir']) : null;
        $lembur = isset($_POST['lembur']) ? intval($_POST['lembur']) : 0;
        $keterangan = trim($_POST['keterangan'] ?? '');

        if ($id_absensi === 0 || is_null($status_hadir)) {
            $_SESSION['pesan_error_crud'] = "Data tidak lengkap. Gagal menyimpan.";
            // Jika ID absensi ada, kembali ke halaman edit, jika tidak, ke index.
            header('Location: ' . ($id_absensi ? BASE_URL . 'absensi/admin_edit.php?id_absensi=' . $id_absensi : BASE_URL . 'absensi/index.php'));
            exit;
        }

        // Siapkan dan eksekusi query UPDATE
        $sql_update = "UPDATE absensi SET status_hadir = ?, lembur = ?, keterangan = ?, waktu_update = NOW() WHERE id_absensi = ?";
        $stmt_update = mysqli_prepare($koneksi, $sql_update);
        if ($stmt_update) {
            mysqli_stmt_bind_param($stmt_update, "iisi", $status_hadir, $lembur, $keterangan, $id_absensi);
            if (mysqli_stmt_execute($stmt_update)) {
                $_SESSION['pesan_sukses'] = "Data absensi berhasil dikoreksi.";
            } else {
                $_SESSION['pesan_error_crud'] = "Gagal memperbarui data absensi: " . mysqli_stmt_error($stmt_update);
            }
            mysqli_stmt_close($stmt_update);
        } else {
            $_SESSION['pesan_error_crud'] = "Terjadi kesalahan sistem (SQL Prepare Error).";
        }
        
        // Redirect kembali ke halaman edit
        header('Location: ' . BASE_URL . 'absensi/admin_edit.php?id_absensi=' . $id_absensi);
        exit;
    }
    // =============================================================
    // --- AKSI: HAPUS DATA (HANYA UNTUK SUPER ADMIN) ---
    // =============================================================
    elseif ($aksi == 'hapus' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_absensi'])) {

        // Validasi paling penting: pastikan hanya Super Admin yang bisa menghapus
        if ($user_role !== 'super_admin') {
            $_SESSION['pesan_error_crud'] = "AKSES DITOLAK: Hanya Super Admin yang dapat menghapus data.";
            header('Location: ' . BASE_URL . 'absensi/index.php');
            exit;
        }

        // Ambil dan validasi data dari form hapus
        $id_absensi_hapus = isset($_POST['id_absensi_hapus']) ? intval($_POST['id_absensi_hapus']) : 0;

        if ($id_absensi_hapus === 0) {
            $_SESSION['pesan_error_crud'] = "ID Absensi untuk dihapus tidak valid.";
            header('Location: ' . BASE_URL . 'absensi/index.php');
            exit;
        }
        
        // Siapkan dan eksekusi query DELETE
        $sql_delete = "DELETE FROM absensi WHERE id_absensi = ?";
        $stmt_delete = mysqli_prepare($koneksi, $sql_delete);
        if ($stmt_delete) {
            mysqli_stmt_bind_param($stmt_delete, "i", $id_absensi_hapus);
            if (mysqli_stmt_execute($stmt_delete)) {
                // Cek apakah ada baris yang benar-benar terhapus
                if (mysqli_stmt_affected_rows($stmt_delete) > 0) {
                    $_SESSION['pesan_sukses'] = "Data absensi (ID #$id_absensi_hapus) telah berhasil dihapus permanen.";
                } else {
                    $_SESSION['pesan_error_crud'] = "Data absensi (ID #$id_absensi_hapus) tidak ditemukan untuk dihapus.";
                }
            } else {
                $_SESSION['pesan_error_crud'] = "Gagal menghapus data absensi: " . mysqli_stmt_error($stmt_delete);
            }
            mysqli_stmt_close($stmt_delete);
        } else {
            $_SESSION['pesan_error_crud'] = "Terjadi kesalahan sistem (SQL Prepare Error).";
        }

        // Redirect ke halaman daftar (karena halaman detailnya sudah tidak ada)
        header('Location: ' . BASE_URL . 'absensi/index.php');
        exit;
    }
}

// Jika aksi tidak valid atau tidak ada, redirect ke halaman utama laporan
$_SESSION['pesan_error_crud'] = "Permintaan tidak valid atau aksi tidak dikenali.";
header('Location: ' . BASE_URL . 'absensi/index.php');
exit;
?>
