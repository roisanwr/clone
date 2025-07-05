<?php
// File: users/proses.php (Revisi Total - Mengelola Akun di Tabel Pekerja)

require_once '../config.php'; 

// 1. Autentikasi & Autorisasi (Hanya Super Admin)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    $_SESSION['pesan_error_crud'] = "AKSES DITOLAK: Anda tidak memiliki izin untuk melakukan tindakan ini.";
    header('Location: ' . BASE_URL . 'dashboard.php'); 
    exit;
}

// 2. Routing berdasarkan Aksi dari URL
if (isset($_GET['aksi'])) {
    $aksi = $_GET['aksi'];

    // =============================================================
    // --- AKSI: TAMBAH AKUN PENGGUNA BARU ---
    // =============================================================
    if ($aksi == 'tambah' && $_SERVER["REQUEST_METHOD"] == "POST") {
        
        // Ambil data dari form tambah.php
        $id_pekerja = isset($_POST['id_pekerja']) ? intval($_POST['id_pekerja']) : 0;
        $username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $password = $_POST['password'] ?? '';
        $konfirmasi_password = $_POST['konfirmasi_password'] ?? '';

        // Validasi
        if (empty($id_pekerja) || empty($username) || empty($password)) {
            $_SESSION['pesan_error_crud'] = "Semua field wajib diisi.";
            header('Location: ' . BASE_URL . 'users/tambah.php');
            exit;
        }
        if ($password !== $konfirmasi_password) {
            $_SESSION['pesan_error_crud'] = "Password dan Konfirmasi Password tidak cocok.";
            header('Location: ' . BASE_URL . 'users/tambah.php');
            exit;
        }

        // Hash password sebelum disimpan
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Query UPDATE untuk 'mempromosikan' pekerja
        $sql = "UPDATE pekerja SET username = ?, password = ? WHERE id_pekerja = ?";
        $stmt = mysqli_prepare($koneksi, $sql);
        mysqli_stmt_bind_param($stmt, "ssi", $username, $hashed_password, $id_pekerja);

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['pesan_sukses'] = "Akun untuk pengguna berhasil dibuat!";
        } else {
            // Cek jika error karena username duplikat
            if (mysqli_errno($koneksi) == 1062) { // 1062 adalah kode error untuk duplikat entry
                $_SESSION['pesan_error_crud'] = "Username '$username' sudah digunakan. Pilih username lain.";
            } else {
                $_SESSION['pesan_error_crud'] = "Gagal membuat akun. Terjadi kesalahan database.";
            }
        }
        mysqli_stmt_close($stmt);
        header('Location: ' . BASE_URL . 'users/');
        exit;
    }

    // =============================================================
    // --- AKSI: EDIT AKUN PENGGUNA ---
    // =============================================================
    elseif ($aksi == 'edit' && isset($_GET['id']) && $_SERVER["REQUEST_METHOD"] == "POST") {
        
        $id_pekerja_edit = intval($_GET['id']);
        $username_baru = trim($_POST['username'] ?? '');
        $password_baru = $_POST['password'] ?? '';
        $konfirmasi_password_baru = $_POST['konfirmasi_password'] ?? '';
        $is_active_baru = isset($_POST['is_active']) ? intval($_POST['is_active']) : 0;

        // Validasi
        if (empty($username_baru)) {
            $_SESSION['pesan_error_crud'] = "Username tidak boleh kosong.";
            header('Location: ' . BASE_URL . 'users/edit.php?id=' . $id_pekerja_edit);
            exit;
        }
        if (!empty($password_baru) && $password_baru !== $konfirmasi_password_baru) {
             $_SESSION['pesan_error_crud'] = "Password baru dan konfirmasinya tidak cocok.";
            header('Location: ' . BASE_URL . 'users/edit.php?id=' . $id_pekerja_edit);
            exit;
        }

        // Bangun query UPDATE secara dinamis
        $sql_parts = ["username = ?", "is_active = ?"];
        $bind_types = "si";
        $bind_values = [$username_baru, $is_active_baru];

        if (!empty($password_baru)) {
            $hashed_password_baru = password_hash($password_baru, PASSWORD_DEFAULT);
            $sql_parts[] = "password = ?";
            $bind_types .= "s";
            $bind_values[] = $hashed_password_baru;
        }

        $bind_values[] = $id_pekerja_edit;
        $bind_types .= "i";

        $sql = "UPDATE pekerja SET " . implode(", ", $sql_parts) . " WHERE id_pekerja = ?";
        
        $stmt = mysqli_prepare($koneksi, $sql);
        mysqli_stmt_bind_param($stmt, $bind_types, ...$bind_values);

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['pesan_sukses'] = "Akun pengguna berhasil diperbarui!";
        } else {
             if (mysqli_errno($koneksi) == 1062) {
                $_SESSION['pesan_error_crud'] = "Username '$username_baru' sudah digunakan. Pilih username lain.";
            } else {
                $_SESSION['pesan_error_crud'] = "Gagal memperbarui akun. Terjadi kesalahan database.";
            }
        }
        mysqli_stmt_close($stmt);
        header('Location: ' . BASE_URL . 'users/');
        exit;
    }

    // =============================================================
    // --- AKSI: HAPUS AKUN (Mencabut Hak Akses) ---
    // =============================================================
    elseif ($aksi == 'hapus_akun' && isset($_GET['id'])) {
        
        $id_pekerja_hapus = intval($_GET['id']);

        // Super Admin tidak boleh menghapus akunnya sendiri
        if ($id_pekerja_hapus == $_SESSION['user_id']) {
            $_SESSION['pesan_error_crud'] = "Anda tidak dapat menghapus akun Anda sendiri.";
            header('Location: ' . BASE_URL . 'users/');
            exit;
        }

        // Query UPDATE untuk menghapus akses (set username dan password menjadi NULL)
        $sql = "UPDATE pekerja SET username = NULL, password = NULL WHERE id_pekerja = ?";
        $stmt = mysqli_prepare($koneksi, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id_pekerja_hapus);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['pesan_sukses'] = "Hak akses login untuk pengguna telah berhasil dihapus.";
        } else {
            $_SESSION['pesan_error_crud'] = "Gagal menghapus hak akses. Terjadi kesalahan database.";
        }
        mysqli_stmt_close($stmt);
        header('Location: ' . BASE_URL . 'users/');
        exit;
    }
}

// Jika tidak ada aksi yang cocok
$_SESSION['pesan_error_crud'] = "Aksi tidak valid atau tidak dikenali.";
header('Location: ' . BASE_URL . 'users/');
exit;
?>
