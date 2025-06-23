<?php
// File: proyek_jaya/jabatan/proses.php

// 1. Panggil Konfigurasi dan mulai session
require_once '../config.php';

// 2. Autentikasi: Pastikan pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'auth/login.php?error=2');
    exit;
}

// 3. Autorisasi Awal: Hanya Super Admin dan Admin yang boleh mengakses file ini
$allowed_roles_to_access_this_file = ['super_admin', 'admin'];
if (!in_array($_SESSION['role'], $allowed_roles_to_access_this_file)) {
    $_SESSION['pesan_error_crud'] = "MAAF, ANDA TIDAK MEMILIKI HAK AKSES UNTUK MELAKUKAN TINDAKAN INI.";
    header('Location: ' . BASE_URL . 'jabatan/');
    exit;
}

// Dapatkan peran pengguna saat ini untuk kemudahan
$current_user_role = $_SESSION['role'];

// 4. Logika untuk AKSI TAMBAH JABATAN
if (isset($_GET['aksi']) && $_GET['aksi'] == 'tambah') {
    // Tidak perlu cek peran lagi di sini, karena 'admin' dan 'super_admin' sudah boleh masuk
    // dan keduanya boleh menambah jabatan.

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $namajabatan = isset($_POST['namajabatan']) ? mysqli_real_escape_string($koneksi, trim($_POST['namajabatan'])) : '';
        $gajipokok = isset($_POST['gajipokok']) ? floatval($_POST['gajipokok']) : 0;
        $tunjangan_lembur = isset($_POST['tunjangan_lembur']) ? floatval($_POST['tunjangan_lembur']) : 0;

        if (empty($namajabatan) || $gajipokok <= 0 || $tunjangan_lembur < 0) {
            $_SESSION['pesan_error_crud'] = "Nama jabatan wajib diisi. Gaji pokok harus lebih besar dari 0, dan tunjangan lembur tidak boleh negatif.";
            header('Location: ' . BASE_URL . 'jabatan/tambah.php');
            exit;
        }

        $sql_cek_duplikat = "SELECT id_jabatan FROM jabatan WHERE namajabatan = ?";
        $stmt_cek = mysqli_prepare($koneksi, $sql_cek_duplikat);
        mysqli_stmt_bind_param($stmt_cek, "s", $namajabatan);
        mysqli_stmt_execute($stmt_cek);
        $result_cek = mysqli_stmt_get_result($stmt_cek);

        if (mysqli_num_rows($result_cek) > 0) {
            mysqli_stmt_close($stmt_cek);
            $_SESSION['pesan_error_crud'] = "Nama jabatan '" . htmlspecialchars($namajabatan) . "' sudah terdaftar. Silakan gunakan nama lain.";
            header('Location: ' . BASE_URL . 'jabatan/tambah.php');
            exit;
        }
        mysqli_stmt_close($stmt_cek);

        $sql_insert = "INSERT INTO jabatan (namajabatan, gajipokok, tunjangan_lembur) VALUES (?, ?, ?)";
        $stmt_insert = mysqli_prepare($koneksi, $sql_insert);

        if ($stmt_insert) {
            mysqli_stmt_bind_param($stmt_insert, "sdd", $namajabatan, $gajipokok, $tunjangan_lembur);
            if (mysqli_stmt_execute($stmt_insert)) {
                $_SESSION['pesan_sukses'] = "Jabatan '" . htmlspecialchars($namajabatan) . "' berhasil ditambahkan!";
            } else {
                $_SESSION['pesan_error_crud'] = "GAGAL menambahkan jabatan. Terjadi kesalahan saat menyimpan ke database.";
            }
            mysqli_stmt_close($stmt_insert);
        } else {
            $_SESSION['pesan_error_crud'] = "Terjadi kesalahan pada sistem (gagal mempersiapkan statement SQL).";
        }

        header('Location: ' . BASE_URL . 'jabatan/');
        exit;
    } else {
        $_SESSION['pesan_error_crud'] = "Metode request tidak valid untuk menambah jabatan.";
        header('Location: ' . BASE_URL . 'jabatan/tambah.php');
        exit;
    }
}
// --- BLOK UNTUK AKSI EDIT JABATAN ---
elseif (isset($_GET['aksi']) && $_GET['aksi'] == 'edit' && isset($_GET['id'])) {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $id_jabatan_edit = intval($_GET['id']);
        $namajabatan_baru = isset($_POST['namajabatan']) ? mysqli_real_escape_string($koneksi, trim($_POST['namajabatan'])) : '';
        
        // Inisialisasi variabel untuk gaji dan tunjangan lembur
        $gajipokok_baru = 0;
        $tunjangan_lembur_baru = 0;

        // Ambil data jabatan lama dari database, ini penting untuk admin
        // agar nilai gaji pokok dan tunjangan lembur tidak hilang jika admin hanya update nama
        $sql_get_old_data = "SELECT gajipokok, tunjangan_lembur FROM jabatan WHERE id_jabatan = ?";
        $stmt_get_old = mysqli_prepare($koneksi, $sql_get_old_data);
        if ($stmt_get_old) {
            mysqli_stmt_bind_param($stmt_get_old, "i", $id_jabatan_edit);
            mysqli_stmt_execute($stmt_get_old);
            $result_old = mysqli_stmt_get_result($stmt_get_old);
            if ($old_data = mysqli_fetch_assoc($result_old)) {
                $gajipokok_lama = $old_data['gajipokok'];
                $tunjangan_lembur_lama = $old_data['tunjangan_lembur'];
            } else {
                $_SESSION['pesan_error_crud'] = "Jabatan tidak ditemukan untuk diperbarui.";
                header('Location: ' . BASE_URL . 'jabatan/');
                exit;
            }
            mysqli_stmt_close($stmt_get_old);
        } else {
            $_SESSION['pesan_error_crud'] = "Terjadi kesalahan sistem saat mengambil data lama.";
            header('Location: ' . BASE_URL . 'jabatan/');
            exit;
        }


        // Logika update berdasarkan peran
        if ($current_user_role === 'admin') {
            // Admin hanya bisa update nama jabatan, gaji dan tunjangan tetap pakai nilai lama
            $sql_update = "UPDATE jabatan SET namajabatan = ? WHERE id_jabatan = ?";
            $stmt_update = mysqli_prepare($koneksi, $sql_update);
            if ($stmt_update) {
                mysqli_stmt_bind_param($stmt_update, "si", $namajabatan_baru, $id_jabatan_edit);
            }
            
            // Validasi untuk admin (hanya nama jabatan yang wajib)
            if (empty($namajabatan_baru)) {
                $_SESSION['pesan_error_crud'] = "Nama jabatan wajib diisi.";
                header('Location: ' . BASE_URL . 'jabatan/edit.php?id=' . $id_jabatan_edit);
                exit;
            }

        } elseif ($current_user_role === 'super_admin') {
            // Super Admin bisa update semua field
            $gajipokok_baru = isset($_POST['gajipokok']) ? floatval($_POST['gajipokok']) : 0;
            $tunjangan_lembur_baru = isset($_POST['tunjangan_lembur']) ? floatval($_POST['tunjangan_lembur']) : 0;

            $sql_update = "UPDATE jabatan SET namajabatan = ?, gajipokok = ?, tunjangan_lembur = ? WHERE id_jabatan = ?";
            $stmt_update = mysqli_prepare($koneksi, $sql_update);
            if ($stmt_update) {
                mysqli_stmt_bind_param($stmt_update, "sddi", $namajabatan_baru, $gajipokok_baru, $tunjangan_lembur_baru, $id_jabatan_edit);
            }

            // Validasi untuk super admin (semua wajib diisi)
            if (empty($namajabatan_baru) || $gajipokok_baru <= 0 || $tunjangan_lembur_baru < 0) {
                $_SESSION['pesan_error_crud'] = "Nama jabatan wajib diisi. Gaji pokok harus lebih besar dari 0, dan tunjangan lembur tidak boleh negatif.";
                header('Location: ' . BASE_URL . 'jabatan/edit.php?id=' . $id_jabatan_edit);
                exit;
            }

        } else {
            // Seharusnya tidak tercapai karena sudah di filter di awal file
            // Tapi sebagai fallback, bisa dipertahankan.
            $_SESSION['pesan_error_crud'] = "Peran tidak valid untuk operasi ini.";
            header('Location: ' . BASE_URL . 'jabatan/');
            exit;
        }

        // Cek duplikasi nama jabatan, KECUALIKAN jabatan yang sedang diedit
        $sql_cek_duplikat_edit = "SELECT id_jabatan FROM jabatan WHERE namajabatan = ? AND id_jabatan != ?";
        $stmt_cek_edit = mysqli_prepare($koneksi, $sql_cek_duplikat_edit);
        mysqli_stmt_bind_param($stmt_cek_edit, "si", $namajabatan_baru, $id_jabatan_edit);
        mysqli_stmt_execute($stmt_cek_edit);
        $result_cek_edit = mysqli_stmt_get_result($stmt_cek_edit);

        if (mysqli_num_rows($result_cek_edit) > 0) {
            mysqli_stmt_close($stmt_cek_edit);
            $_SESSION['pesan_error_crud'] = "Nama jabatan '" . htmlspecialchars($namajabatan_baru) . "' sudah digunakan oleh jabatan lain. Silakan gunakan nama lain.";
            header('Location: ' . BASE_URL . 'jabatan/edit.php?id=' . $id_jabatan_edit);
            exit;
        }
        mysqli_stmt_close($stmt_cek_edit);

        if (isset($stmt_update) && $stmt_update) {
            if (mysqli_stmt_execute($stmt_update)) {
                $_SESSION['pesan_sukses'] = "Data jabatan '" . htmlspecialchars($namajabatan_baru) . "' berhasil diperbarui!";
            } else {
                $_SESSION['pesan_error_crud'] = "GAGAL memperbarui jabatan. Terjadi kesalahan saat menyimpan ke database.";
            }
            mysqli_stmt_close($stmt_update);
        } else {
            $_SESSION['pesan_error_crud'] = "Terjadi kesalahan pada sistem (gagal mempersiapkan statement SQL untuk update).";
        }

        header('Location: ' . BASE_URL . 'jabatan/');
        exit;

    } else {
        $_SESSION['pesan_error_crud'] = "Metode request tidak valid untuk mengedit jabatan.";
        header('Location: ' . BASE_URL . 'jabatan/');
        exit;
    }
}

// --- BLOK UNTUK AKSI HAPUS JABATAN ---
elseif (isset($_GET['aksi']) && $_GET['aksi'] == 'hapus' && isset($_GET['id'])) {
    // Pengecekan peran untuk aksi hapus (hanya Super Admin yang boleh)
    if ($current_user_role !== 'super_admin') {
        $_SESSION['pesan_error_crud'] = "AKSES DITOLAK: Hanya Super Admin yang dapat menghapus data jabatan.";
        header('Location: ' . BASE_URL . 'jabatan/');
        exit;
    }

    $id_jabatan_hapus = intval($_GET['id']);

    $sql_cek_penggunaan = "SELECT COUNT(*) as jumlah_terpakai FROM pekerja WHERE id_jabatan = ?";
    $stmt_cek = mysqli_prepare($koneksi, $sql_cek_penggunaan);

    if ($stmt_cek) {
        mysqli_stmt_bind_param($stmt_cek, "i", $id_jabatan_hapus);
        mysqli_stmt_execute($stmt_cek);
        $result_cek = mysqli_stmt_get_result($stmt_cek);
        $data_cek = mysqli_fetch_assoc($result_cek);
        mysqli_stmt_close($stmt_cek);

        if ($data_cek['jumlah_terpakai'] > 0) {
            $_SESSION['pesan_error_crud'] = "Jabatan tidak dapat dihapus karena masih digunakan oleh " . $data_cek['jumlah_terpakai'] . " pekerja. Harap ubah atau hapus dulu data pekerja yang terkait dengan jabatan ini.";
        } else {
            $sql_hapus = "DELETE FROM jabatan WHERE id_jabatan = ?";
            $stmt_hapus = mysqli_prepare($koneksi, $sql_hapus);

            if ($stmt_hapus) {
                mysqli_stmt_bind_param($stmt_hapus, "i", $id_jabatan_hapus);
                if (mysqli_stmt_execute($stmt_hapus)) {
                    if (mysqli_stmt_affected_rows($stmt_hapus) > 0) {
                        $_SESSION['pesan_sukses'] = "Data jabatan berhasil dihapus!";
                    } else {
                        $_SESSION['pesan_error_crud'] = "Data jabatan tidak ditemukan atau mungkin sudah dihapus sebelumnya.";
                    }
                } else {
                    $_SESSION['pesan_error_crud'] = "GAGAL menghapus jabatan. Terjadi kesalahan database.";
                }
                mysqli_stmt_close($stmt_hapus);
            } else {
                $_SESSION['pesan_error_crud'] = "Terjadi kesalahan pada sistem (gagal mempersiapkan statement SQL untuk hapus).";
            }
        }
    } else {
        $_SESSION['pesan_error_crud'] = "Terjadi kesalahan saat memeriksa penggunaan jabatan.";
    }

    header('Location: ' . BASE_URL . 'jabatan/');
    exit;
}

// --- BLOK ELSE UNTUK AKSI LAINNYA (JIKA ADA) ---
else {
    $_SESSION['pesan_error_crud'] = "Aksi tidak valid atau tidak dikenali.";
    header('Location: ' . BASE_URL . 'jabatan/');
    exit;
}
?>