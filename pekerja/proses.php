<?php
// File: proyek_jaya/pekerja/proses.php

// 1. Panggil Konfigurasi dan mulai session
require_once '../config.php';

// 2. Autentikasi: Pastikan pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    // Jika belum login, redirect ke halaman login
    header('Location: ' . BASE_URL . 'auth/login.php?error=2');
    exit;
}

// Mendefinisikan peran pengguna saat ini untuk kemudahan dalam pengecekan otorisasi
// Menggunakan null coalescing operator (??) untuk fallback jika $_SESSION['role'] tidak ada
$current_user_role = $_SESSION['role'] ?? 'guest';

// 3. Autorisasi Awal: Hanya Super Admin, Admin, dan Mandor yang boleh mengakses file ini (secara umum)
$allowed_roles_general = ['super_admin', 'admin', 'mandor'];
if (!in_array($current_user_role, $allowed_roles_general)) {
    // Jika peran tidak diizinkan, simpan pesan error di session dan redirect
    $_SESSION['pesan_error_crud'] = "ANDA TIDAK MEMILIKI HAK AKSES UNTUK MELAKUKAN TINDAKAN INI.";
    header('Location: ' . BASE_URL . 'pekerja/index.php'); // Redirect ke daftar pekerja
    exit;
}

// 4. Logika untuk AKSI TAMBAH PEKERJA
// Aksi 'tambah' ini diizinkan untuk 'super_admin', 'admin', dan 'mandor' sesuai otorisasi awal
if (isset($_GET['aksi']) && $_GET['aksi'] == 'tambah') {
    
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        
        // Ambil dan sanitasi data dari form
        // trim() untuk menghapus spasi di awal/akhir string
        // mysqli_real_escape_string untuk mencegah SQL injection dasar pada string
        // intval untuk memastikan nilai adalah integer
        $namapekerja = isset($_POST['namapekerja']) ? mysqli_real_escape_string($koneksi, trim($_POST['namapekerja'])) : '';
        $id_jabatan = isset($_POST['id_jabatan']) ? intval($_POST['id_jabatan']) : 0; 
        $no_hp = isset($_POST['no_hp']) ? mysqli_real_escape_string($koneksi, trim($_POST['no_hp'])) : NULL; // Boleh NULL
        $no_rek = isset($_POST['no_rek']) ? mysqli_real_escape_string($koneksi, trim($_POST['no_rek'])) : NULL; // Boleh NULL
        // Kolom is_active akan di-set default ke 1 (Aktif) langsung di query SQL

        // Validasi dasar: Nama Pekerja dan Jabatan wajib diisi
        if (empty($namapekerja) || empty($id_jabatan)) {
            $_SESSION['pesan_error_crud'] = "Nama pekerja dan jabatan wajib diisi.";
            $_SESSION['form_data'] = $_POST; // Simpan data inputan ke session untuk sticky form
            header('Location: ' . BASE_URL . 'pekerja/tambah.php');
            exit;
        }

        // Siapkan query INSERT menggunakan prepared statement untuk keamanan
        // Kolom 'is_active' langsung diisi nilai 1 (Aktif)
        $sql_insert = "INSERT INTO pekerja (namapekerja, id_jabatan, no_hp, no_rek, is_active) 
                       VALUES (?, ?, ?, ?, 1)"; 
        
        $stmt_insert = mysqli_prepare($koneksi, $sql_insert);

        if ($stmt_insert) {
            // Bind parameter ke statement: s(tring), i(nteger), s(tring), s(tring)
            mysqli_stmt_bind_param($stmt_insert, "siss", 
                $namapekerja, 
                $id_jabatan, 
                $no_hp, 
                $no_rek
            );

            // Eksekusi statement
            if (mysqli_stmt_execute($stmt_insert)) {
                $_SESSION['pesan_sukses'] = "Pekerja '" . htmlspecialchars($namapekerja) . "' berhasil ditambahkan dengan status Aktif!";
                unset($_SESSION['form_data']); // Hapus data form dari session jika sukses
            } else {
                $_SESSION['pesan_error_crud'] = "GAGAL menambahkan pekerja. Terjadi kesalahan saat menyimpan ke database.";
                // error_log("Error INSERT Pekerja: " . mysqli_stmt_error($stmt_insert)); // Catat error ke log server
                $_SESSION['form_data'] = $_POST; // Simpan data form untuk sticky form jika gagal juga
            }
            mysqli_stmt_close($stmt_insert); // Tutup statement insert
        } else {
            // Jika gagal mempersiapkan statement (error di SQL atau koneksi)
            $_SESSION['pesan_error_crud'] = "Terjadi kesalahan pada sistem (gagal mempersiapkan statement SQL untuk tambah pekerja).";
            // error_log("Error PREPARE INSERT Pekerja: " . mysqli_error($koneksi)); // Catat error ke log server
            $_SESSION['form_data'] = $_POST;
        }
        
        // Redirect kembali ke halaman daftar pekerja (baik sukses maupun gagal)
        header('Location: ' . BASE_URL . 'pekerja/');
        exit;

    } else {
        // Jika metode request bukan POST, tapi aksinya 'tambah', mungkin ada yang salah.
        $_SESSION['pesan_error_crud'] = "Metode request tidak valid untuk menambah pekerja.";
        header('Location: ' . BASE_URL . 'pekerja/tambah.php'); // Redirect ke form tambah
        exit;
    }
} 
// --- BLOK UNTUK AKSI EDIT PEKERJA ---
elseif (isset($_GET['aksi']) && $_GET['aksi'] == 'edit' && isset($_GET['id'])) {
    
    // Autorisasi khusus untuk EDIT: Hanya Super Admin dan Admin yang boleh mengedit
    if (!in_array($current_user_role, ['super_admin', 'admin'])) {
        $_SESSION['pesan_error_crud'] = "ANDA TIDAK MEMILIKI HAK AKSES UNTUK MENGEDIT PEKERJA.";
        header('Location: ' . BASE_URL . 'pekerja/');
        exit;
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        
        $id_pekerja_edit = intval($_GET['id']); // Ambil ID pekerja dari URL dan pastikan integer

        // Ambil dan sanitasi data dari form edit
        $namapekerja_baru = isset($_POST['namapekerja']) ? mysqli_real_escape_string($koneksi, trim($_POST['namapekerja'])) : '';
        $id_jabatan_baru = isset($_POST['id_jabatan']) ? intval($_POST['id_jabatan']) : 0;
        $no_hp_baru = isset($_POST['no_hp']) ? mysqli_real_escape_string($koneksi, trim($_POST['no_hp'])) : NULL;
        $no_rek_baru = isset($_POST['no_rek']) ? mysqli_real_escape_string($koneksi, trim($_POST['no_rek'])) : NULL;
        // is_active harus divalidasi, karena dari form bisa jadi 0 atau 1
        $is_active_baru = isset($_POST['is_active']) ? intval($_POST['is_active']) : 0; 

        // Validasi dasar: Nama Pekerja, Jabatan, dan Status Keaktifan wajib diisi dengan benar
        if (empty($namapekerja_baru) || empty($id_jabatan_baru) || !in_array($is_active_baru, [0,1])) {
            $_SESSION['pesan_error_crud'] = "Nama pekerja, jabatan, dan status keaktifan wajib diisi dengan benar.";
            $_SESSION['form_data'] = $_POST; // Simpan data inputan ke session untuk sticky form di edit.php
            header('Location: ' . BASE_URL . 'pekerja/edit.php?id=' . $id_pekerja_edit);
            exit;
        }

        // Siapkan query UPDATE menggunakan prepared statement
        $sql_update = "UPDATE pekerja 
                       SET namapekerja = ?, id_jabatan = ?, no_hp = ?, no_rek = ?, is_active = ? 
                       WHERE id_pekerja = ?";
        
        $stmt_update = mysqli_prepare($koneksi, $sql_update);

        if ($stmt_update) {
            // Bind parameter: s(tring), i(nteger), s(tring), s(tring), i(nteger untuk is_active), i(nteger untuk id_pekerja)
            mysqli_stmt_bind_param($stmt_update, "sissii", 
                $namapekerja_baru, 
                $id_jabatan_baru, 
                $no_hp_baru, 
                $no_rek_baru, 
                $is_active_baru,
                $id_pekerja_edit
            );

            if (mysqli_stmt_execute($stmt_update)) {
                $_SESSION['pesan_sukses'] = "Data pekerja '" . htmlspecialchars($namapekerja_baru) . "' berhasil diperbarui!";
                unset($_SESSION['form_data']); // Hapus sticky form data jika sukses
            } else {
                $_SESSION['pesan_error_crud'] = "GAGAL memperbarui data pekerja. Terjadi kesalahan database.";
                // error_log("Error UPDATE Pekerja: " . mysqli_stmt_error($stmt_update)); // Catat error ke log server
                $_SESSION['form_data'] = $_POST; // Simpan lagi untuk sticky form jika gagal
            }
            mysqli_stmt_close($stmt_update); // Tutup statement update
        } else {
            // Jika gagal mempersiapkan statement (error di SQL atau koneksi)
            $_SESSION['pesan_error_crud'] = "Terjadi kesalahan pada sistem (gagal mempersiapkan statement SQL untuk update pekerja).";
            // error_log("Error PREPARE UPDATE Pekerja: " . mysqli_error($koneksi)); // Catat error ke log server
            $_SESSION['form_data'] = $_POST;
        }
        
        // Redirect kembali ke halaman daftar pekerja jika sukses, atau ke form edit jika ada error database (untuk sticky form)
        if (isset($_SESSION['pesan_sukses'])) {
            header('Location: ' . BASE_URL . 'pekerja/');
        } else {
            header('Location: ' . BASE_URL . 'pekerja/edit.php?id=' . $id_pekerja_edit);
        }
        exit;

    } else {
        // Jika metode request bukan POST untuk aksi edit
        $_SESSION['pesan_error_crud'] = "Metode request tidak valid untuk mengedit pekerja.";
        header('Location: ' . BASE_URL . 'pekerja/'); // Redirect ke daftar pekerja
        exit;
    }
}
// --- BLOK UNTUK AKSI HAPUS PEKERJA (Ini bisa ditambahkan di sini jika diperlukan) ---
elseif (isset($_GET['aksi']) && $_GET['aksi'] == 'hapus' && isset($_GET['id'])) {
    
    // 1. Autorisasi Khusus untuk Hapus Pekerja: HANYA Super Admin
    if ($current_user_role !== 'super_admin') {
        $_SESSION['pesan_error_crud'] = "AKSES DITOLAK: Hanya Super Admin yang dapat menghapus/menonaktifkan data pekerja.";
        header('Location: ' . BASE_URL . 'pekerja/');
        exit;
    }

    $id_pekerja_target = intval($_GET['id']); // Ambil ID pekerja dari URL

    // 2. Lakukan Pengecekan Keterkaitan Data di Tabel Lain
    $ada_keterkaitan = false;
    $pesan_keterkaitan = "";

    // Daftar tabel dan kolom yang perlu dicek yang berelasi dengan id_pekerja
    $tabel_terkait = [
        // Tabel => Kolom yang mereferensi id_pekerja
        'proyek_pekerja' => 'id_pekerja',
        'absensi' => 'id_pekerja',
        'gaji' => 'id_pekerja',
        'users' => 'id_pekerja_ref',      // Jika pekerja adalah seorang mandor di tabel users
        'projek' => 'id_mandor_pekerja'   // Jika pekerja adalah mandor utama di tabel projek
    ];

    foreach ($tabel_terkait as $nama_tabel => $kolom_pekerja) {
        $sql_cek = "SELECT COUNT(*) as jumlah FROM `$nama_tabel` WHERE `$kolom_pekerja` = ?";
        $stmt_cek = mysqli_prepare($koneksi, $sql_cek);
        if ($stmt_cek) {
            mysqli_stmt_bind_param($stmt_cek, "i", $id_pekerja_target);
            mysqli_stmt_execute($stmt_cek);
            $result_cek = mysqli_stmt_get_result($stmt_cek);
            $data_cek = mysqli_fetch_assoc($result_cek);
            mysqli_stmt_close($stmt_cek);

            if ($data_cek['jumlah'] > 0) {
                $ada_keterkaitan = true;
                $pesan_keterkaitan = "Pekerja tidak bisa dihapus permanen karena masih memiliki data terkait di tabel '" . $nama_tabel . "'. Status pekerja akan diubah menjadi Tidak Aktif.";
                break; // Jika sudah ketemu satu keterkaitan, cukup, langsung soft delete
            }
        } else {
            // Gagal prepare statement untuk cek, anggap ada keterkaitan demi keamanan
            $ada_keterkaitan = true;
            $pesan_keterkaitan = "Terjadi kesalahan saat memeriksa keterkaitan data pekerja. Untuk keamanan, pekerja hanya akan dinonaktifkan.";
            // error_log("Error PREPARE CEK KETERKAITAN di tabel $nama_tabel: " . mysqli_error($koneksi));
            break;
        }
    }

    // 3. Ambil nama pekerja untuk pesan notifikasi (sebelum dihapus/dinonaktifkan)
    $nama_pekerja_target = "Pekerja (ID: " . $id_pekerja_target . ")"; // Default
    $q_get_nama = mysqli_prepare($koneksi, "SELECT namapekerja FROM pekerja WHERE id_pekerja = ?");
    if($q_get_nama){
        mysqli_stmt_bind_param($q_get_nama, "i", $id_pekerja_target);
        mysqli_stmt_execute($q_get_nama);
        $res_nama = mysqli_stmt_get_result($q_get_nama);
        if($data_nama = mysqli_fetch_assoc($res_nama)){
            $nama_pekerja_target = htmlspecialchars($data_nama['namapekerja']);
        }
        mysqli_stmt_close($q_get_nama);
    }


    // 4. Lakukan Aksi Berdasarkan Hasil Pengecekan Keterkaitan
    if ($ada_keterkaitan) {
        // Jika ADA KETERKAITAN, lakukan SOFT DELETE (nonaktifkan)
        $sql_aksi = "UPDATE pekerja SET is_active = 0 WHERE id_pekerja = ?";
        $pesan_sukses_aksi = "Pekerja '" . $nama_pekerja_target . "' berhasil dinonaktifkan karena memiliki data terkait.";
        if (!empty($pesan_keterkaitan) && strpos($pesan_keterkaitan, "Terjadi kesalahan saat memeriksa") !== false) {
             // Jika error saat cek, pesannya beda
            $pesan_sukses_aksi = $pesan_keterkaitan; // Gunakan pesan error dari pengecekan
        }
    } else {
        // Jika TIDAK ADA KETERKAITAN, lakukan HARD DELETE (hapus permanen)
        $sql_aksi = "DELETE FROM pekerja WHERE id_pekerja = ?";
        $pesan_sukses_aksi = "Pekerja '" . $nama_pekerja_target . "' berhasil dihapus permanen karena tidak memiliki data terkait.";
    }

    $stmt_aksi = mysqli_prepare($koneksi, $sql_aksi);
    if ($stmt_aksi) {
        mysqli_stmt_bind_param($stmt_aksi, "i", $id_pekerja_target);
        if (mysqli_stmt_execute($stmt_aksi)) {
            if (mysqli_stmt_affected_rows($stmt_aksi) > 0) {
                $_SESSION['pesan_sukses'] = $pesan_sukses_aksi;
            } else {
                $_SESSION['pesan_error_crud'] = "Data pekerja tidak ditemukan atau aksi tidak mengubah data apapun.";
            }
        } else {
            $_SESSION['pesan_error_crud'] = "GAGAL melakukan aksi pada pekerja. Error database: " . mysqli_stmt_error($stmt_aksi);
            // error_log("Error AKSI HAPUS/NONAKTIFKAN Pekerja: " . mysqli_stmt_error($stmt_aksi));
        }
        mysqli_stmt_close($stmt_aksi);
    } else {
        $_SESSION['pesan_error_crud'] = "Terjadi kesalahan pada sistem (gagal mempersiapkan statement SQL untuk aksi).";
        // error_log("Error PREPARE AKSI HAPUS/NONAKTIFKAN Pekerja: " . mysqli_error($koneksi));
    }
    
    // 5. Redirect kembali ke halaman daftar pekerja
    header('Location: ' . BASE_URL . 'pekerja/');
    exit;
}
// --- BLOK ELSE UNTUK AKSI LAINNYA (JIKA ADA) ---
else {
    // Jika parameter 'aksi' tidak ada atau tidak dikenali
    $_SESSION['pesan_error_crud'] = "Aksi tidak valid atau tidak dikenali untuk modul pekerja.";
    header('Location: ' . BASE_URL . 'pekerja/'); // Redirect ke daftar pekerja
    exit;
}

// Tutup koneksi database jika sudah tidak digunakan (biasanya PHP otomatis menutup di akhir skrip)
// mysqli_close($koneksi); // Opsional di sini karena skrip biasanya langsung exit setelah redirect
?>