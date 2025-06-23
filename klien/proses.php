<?php
// File: proyek_jaya/klien/proses.php

// 1. Panggil Konfigurasi dan mulai session
require_once '../config.php';

// 2. Autentikasi: Pastikan pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'auth/login.php?error=2');
    exit;
}

// 3. Autorisasi: Hanya Super Admin dan Admin yang boleh melakukan aksi CRUD klien
$current_user_role = $_SESSION['role']; // Ambil peran pengguna saat ini
$allowed_roles_for_proses_klien = ['super_admin', 'admin']; 
if (!in_array($current_user_role, $allowed_roles_for_proses_klien)) {
    $_SESSION['pesan_error_crud'] = "ANDA TIDAK MEMILIKI HAK AKSES UNTUK MELAKUKAN TINDAKAN INI.";
    header('Location: ' . BASE_URL . 'dashboard.php'); 
    exit;
}

// 4. Logika untuk AKSI TAMBAH KLIEN
if (isset($_GET['aksi']) && $_GET['aksi'] == 'tambah') {

    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        // Ambil dan sanitasi data dari form
        $nama_klien = isset($_POST['nama_klien']) ? mysqli_real_escape_string($koneksi, trim($_POST['nama_klien'])) : '';
        // Untuk field opsional, jika kosong kita simpan NULL ke database
        $alamat_klien = isset($_POST['alamat_klien']) && !empty(trim($_POST['alamat_klien'])) ? mysqli_real_escape_string($koneksi, trim($_POST['alamat_klien'])) : NULL;
        $no_telp_klien = isset($_POST['no_telp_klien']) && !empty(trim($_POST['no_telp_klien'])) ? mysqli_real_escape_string($koneksi, trim($_POST['no_telp_klien'])) : NULL;
        $email_klien = isset($_POST['email_klien']) && !empty(trim($_POST['email_klien'])) ? mysqli_real_escape_string($koneksi, trim($_POST['email_klien'])) : NULL;

        // Validasi dasar: Nama Klien wajib diisi
        if (empty($nama_klien)) {
            $_SESSION['pesan_error_crud'] = "Nama klien wajib diisi.";
            $_SESSION['form_data'] = $_POST; // Simpan data inputan untuk sticky form
            header('Location: ' . BASE_URL . 'klien/tambah.php');
            exit;
        }

        // Validasi format email jika email diisi
        if ($email_klien !== NULL && !filter_var($email_klien, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['pesan_error_crud'] = "Format email klien tidak valid.";
            $_SESSION['form_data'] = $_POST; 
            header('Location: ' . BASE_URL . 'klien/tambah.php');
            exit;
        }

        // (Opsional) Cek duplikasi nama klien jika diperlukan
        // $sql_cek_duplikat = "SELECT id_klien FROM klien WHERE nama_klien = ?";
        // ... (logika cek duplikat mirip seperti di modul jabatan) ...

        // Siapkan query INSERT. Kolom created_at dan updated_at akan diisi otomatis oleh MySQL jika diset DEFAULT CURRENT_TIMESTAMP
        // atau kita bisa set manual dengan NOW()
        $sql_insert = "INSERT INTO klien (nama_klien, alamat_klien, no_telp_klien, email_klien, created_at, updated_at) 
                       VALUES (?, ?, ?, ?, NOW(), NOW())"; 

        $stmt_insert = mysqli_prepare($koneksi, $sql_insert);

        if ($stmt_insert) {
            // Bind parameter: s(tring), s(tring), s(tring), s(tring)
            mysqli_stmt_bind_param($stmt_insert, "ssss", 
                $nama_klien, 
                $alamat_klien, 
                $no_telp_klien, 
                $email_klien
            );

            if (mysqli_stmt_execute($stmt_insert)) {
                $_SESSION['pesan_sukses'] = "Klien '" . htmlspecialchars($nama_klien) . "' berhasil ditambahkan!";
                unset($_SESSION['form_data']); 
            } else {
                $_SESSION['pesan_error_crud'] = "GAGAL menambahkan klien. Error database: " . mysqli_stmt_error($stmt_insert);
                $_SESSION['form_data'] = $_POST; 
                // error_log("Error INSERT Klien: " . mysqli_stmt_error($stmt_insert));
            }
            mysqli_stmt_close($stmt_insert);
        } else {
            $_SESSION['pesan_error_crud'] = "Terjadi kesalahan pada sistem (gagal mempersiapkan statement SQL): " . mysqli_error($koneksi);
            $_SESSION['form_data'] = $_POST;
            // error_log("Error PREPARE INSERT Klien: " . mysqli_error($koneksi));
        }

        // Redirect kembali
        if (isset($_SESSION['pesan_sukses'])) {
             header('Location: ' . BASE_URL . 'klien/');
        } else {
             header('Location: ' . BASE_URL . 'klien/tambah.php');
        }
        exit;

    } else { 
        $_SESSION['pesan_error_crud'] = "Metode request tidak valid untuk menambah klien.";
        header('Location: ' . BASE_URL . 'klien/tambah.php');
        exit;
    }
} 
// --- NANTI KITA TAMBAHKAN BLOK 'ELSE IF' UNTUK AKSI EDIT DAN HAPUS KLIEN DI SINI ---
// elseif (isset($_GET['aksi']) && $_GET['aksi'] == 'edit' && isset($_GET['id'])) { /* ... logika edit ... */ }

elseif (isset($_GET['aksi']) && $_GET['aksi'] == 'edit' && isset($_GET['id'])) {
    
    // Pastikan request yang datang adalah metode POST (dari form edit.php)
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        
        $id_klien_edit = intval($_GET['id']); // Ambil ID klien dari URL

        // Ambil dan sanitasi data dari form edit
        $nama_klien_baru = isset($_POST['nama_klien']) ? mysqli_real_escape_string($koneksi, trim($_POST['nama_klien'])) : '';
        $alamat_klien_baru = isset($_POST['alamat_klien']) && !empty(trim($_POST['alamat_klien'])) ? mysqli_real_escape_string($koneksi, trim($_POST['alamat_klien'])) : NULL;
        $no_telp_klien_baru = isset($_POST['no_telp_klien']) && !empty(trim($_POST['no_telp_klien'])) ? mysqli_real_escape_string($koneksi, trim($_POST['no_telp_klien'])) : NULL;
        $email_klien_baru = isset($_POST['email_klien']) && !empty(trim($_POST['email_klien'])) ? mysqli_real_escape_string($koneksi, trim($_POST['email_klien'])) : NULL;

        // Validasi dasar: Nama Klien wajib diisi
        if (empty($nama_klien_baru)) {
            $_SESSION['pesan_error_crud'] = "Nama klien wajib diisi.";
            $_SESSION['form_data'] = $_POST; // Simpan data inputan untuk sticky form
            header('Location: ' . BASE_URL . 'klien/edit.php?id=' . $id_klien_edit);
            exit;
        }

        // Validasi format email jika email diisi
        if ($email_klien_baru !== NULL && !filter_var($email_klien_baru, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['pesan_error_crud'] = "Format email klien tidak valid.";
            $_SESSION['form_data'] = $_POST; 
            header('Location: ' . BASE_URL . 'klien/edit.php?id=' . $id_klien_edit);
            exit;
        }
        
        // (Opsional) Cek duplikasi nama klien, KECUALIKAN klien yang sedang diedit
        // $sql_cek_duplikat_edit = "SELECT id_klien FROM klien WHERE nama_klien = ? AND id_klien != ?";
        // ... (logika cek duplikat mirip seperti di modul jabatan/proses.php aksi=edit) ...
        // Untuk sekarang kita lewati dulu agar lebih fokus.

        // Siapkan query UPDATE menggunakan prepared statement
        // updated_at akan diisi otomatis oleh MySQL jika diset ON UPDATE CURRENT_TIMESTAMP,
        // atau kita bisa set manual dengan NOW(). Eksplisit lebih baik.
        $sql_update = "UPDATE klien 
                       SET nama_klien = ?, alamat_klien = ?, no_telp_klien = ?, email_klien = ?, updated_at = NOW() 
                       WHERE id_klien = ?";
        
        $stmt_update = mysqli_prepare($koneksi, $sql_update);

        if ($stmt_update) {
            // Bind parameter: s(tring), s(tring), s(tring), s(tring), i(nteger untuk id_klien)
            mysqli_stmt_bind_param($stmt_update, "ssssi", 
                $nama_klien_baru, 
                $alamat_klien_baru, 
                $no_telp_klien_baru, 
                $email_klien_baru,
                $id_klien_edit
            );

            if (mysqli_stmt_execute($stmt_update)) {
                $_SESSION['pesan_sukses'] = "Data klien '" . htmlspecialchars($nama_klien_baru) . "' berhasil diperbarui!";
                unset($_SESSION['form_data']); 
            } else {
                $_SESSION['pesan_error_crud'] = "GAGAL memperbarui data klien. Error database: " . mysqli_stmt_error($stmt_update);
                $_SESSION['form_data'] = $_POST; 
                // error_log("Error UPDATE Klien: " . mysqli_stmt_error($stmt_update));
            }
            mysqli_stmt_close($stmt_update);
        } else {
            $_SESSION['pesan_error_crud'] = "Terjadi kesalahan pada sistem (gagal mempersiapkan statement SQL untuk update klien).";
            $_SESSION['form_data'] = $_POST;
            // error_log("Error PREPARE UPDATE Klien: " . mysqli_error($koneksi));
        }
        
        // Redirect kembali
        if (isset($_SESSION['pesan_sukses'])) {
             header('Location: ' . BASE_URL . 'klien/');
        } else {
             header('Location: ' . BASE_URL . 'klien/edit.php?id=' . $id_klien_edit);
        }
        exit;

    } else {
        // Jika metode request bukan POST untuk aksi edit
        $_SESSION['pesan_error_crud'] = "Metode request tidak valid untuk mengedit klien.";
        header('Location: ' . BASE_URL . 'klien/');
        exit;
    }
}

// elseif (isset($_GET['aksi']) && $_GET['aksi'] == 'hapus' && isset($_GET['id'])) { /* ... logika hapus ... */ }
elseif (isset($_GET['aksi']) && $_GET['aksi'] == 'hapus' && isset($_GET['id'])) {
    
    // 1. Autorisasi Khusus untuk Hapus Klien: HANYA Super Admin
    if ($current_user_role !== 'super_admin') {
        $_SESSION['pesan_error_crud'] = "AKSES DITOLAK: Hanya Super Admin yang dapat menghapus data klien.";
        header('Location: ' . BASE_URL . 'klien/');
        exit;
    }

    // 2. Ambil ID klien dari URL dan pastikan integer
    $id_klien_hapus = intval($_GET['id']);

    // 3. Cek apakah klien ini masih memiliki proyek terkait di tabel 'projek'
    $sql_cek_proyek = "SELECT COUNT(*) as jumlah_proyek FROM projek WHERE id_klien = ?";
    $stmt_cek_proyek = mysqli_prepare($koneksi, $sql_cek_proyek);
    
    $bisa_dihapus = false; // Defaultnya tidak bisa dihapus sampai terbukti aman

    if ($stmt_cek_proyek) {
        mysqli_stmt_bind_param($stmt_cek_proyek, "i", $id_klien_hapus);
        mysqli_stmt_execute($stmt_cek_proyek);
        $result_cek_proyek = mysqli_stmt_get_result($stmt_cek_proyek);
        $data_cek = mysqli_fetch_assoc($result_cek_proyek);
        mysqli_stmt_close($stmt_cek_proyek);

        if ($data_cek['jumlah_proyek'] > 0) {
            // Jika klien masih memiliki proyek, JANGAN HAPUS dan beri pesan error
            $_SESSION['pesan_error_crud'] = "Klien tidak dapat dihapus karena masih memiliki " . $data_cek['jumlah_proyek'] . " proyek terkait. Harap selesaikan atau pindahkan dulu proyek yang terkait dengan klien ini.";
        } else {
            // Jika klien TIDAK memiliki proyek terkait, maka aman untuk dihapus
            $bisa_dihapus = true;
        }
    } else {
        // Gagal mempersiapkan statement pengecekan, anggap tidak aman untuk hapus
        $_SESSION['pesan_error_crud'] = "Terjadi kesalahan saat memeriksa keterkaitan proyek klien.";
        // error_log("Error PREPARE CEK PROYEK KLIEN: " . mysqli_error($koneksi));
    }

    // 4. Jika aman untuk dihapus, lanjutkan proses penghapusan
    if ($bisa_dihapus) {
        // Ambil nama klien untuk pesan sukses (sebelum dihapus)
        $nama_klien_target = "Klien (ID: " . $id_klien_hapus . ")"; // Default
        $q_get_nama_klien = mysqli_prepare($koneksi, "SELECT nama_klien FROM klien WHERE id_klien = ?");
        if($q_get_nama_klien){
            mysqli_stmt_bind_param($q_get_nama_klien, "i", $id_klien_hapus);
            mysqli_stmt_execute($q_get_nama_klien);
            $res_nama_klien = mysqli_stmt_get_result($q_get_nama_klien);
            if($data_nama_klien = mysqli_fetch_assoc($res_nama_klien)){
                $nama_klien_target = htmlspecialchars($data_nama_klien['nama_klien']);
            }
            mysqli_stmt_close($q_get_nama_klien);
        }

        // Siapkan query DELETE menggunakan prepared statement
        $sql_hapus = "DELETE FROM klien WHERE id_klien = ?";
        $stmt_hapus = mysqli_prepare($koneksi, $sql_hapus);

        if ($stmt_hapus) {
            mysqli_stmt_bind_param($stmt_hapus, "i", $id_klien_hapus);

            if (mysqli_stmt_execute($stmt_hapus)) {
                if (mysqli_stmt_affected_rows($stmt_hapus) > 0) {
                    $_SESSION['pesan_sukses'] = "Data klien '" . $nama_klien_target . "' berhasil dihapus permanen!";
                } else {
                    $_SESSION['pesan_error_crud'] = "Data klien tidak ditemukan atau mungkin sudah dihapus sebelumnya.";
                }
            } else {
                $_SESSION['pesan_error_crud'] = "GAGAL menghapus klien. Terjadi kesalahan database: " . mysqli_stmt_error($stmt_hapus);
                // error_log("Error DELETE Klien: " . mysqli_stmt_error($stmt_hapus));
            }
            mysqli_stmt_close($stmt_hapus);
        } else {
            $_SESSION['pesan_error_crud'] = "Terjadi kesalahan pada sistem (gagal mempersiapkan statement SQL untuk hapus klien).";
            // error_log("Error PREPARE DELETE Klien: " . mysqli_error($koneksi));
        }
    }
    
    // 5. Redirect kembali ke halaman daftar klien
    header('Location: ' . BASE_URL . 'klien/');
    exit;
}

else {
    $_SESSION['pesan_error_crud'] = "Aksi tidak valid atau tidak dikenali untuk modul klien.";
    header('Location: ' . BASE_URL . 'klien/'); 
    exit;
}
?>