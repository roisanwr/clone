<?php
// File: proyek_jaya/proyek/proses.php (Versi BARU Akurat - Fokus Tambah Dulu)

// 1. Panggil Konfigurasi dan pastikan session sudah dimulai
require_once '../config.php'; 

// 2. Autentikasi: Pastikan pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'auth/login.php?error=2');
    exit;
}

// Ambil peran pengguna saat ini untuk pengecekan otorisasi
$current_user_role = $_SESSION['role'];

// 3. Autorisasi Awal: Hanya Super Admin dan Admin yang boleh mengakses file proses proyek
$allowed_roles_for_proses_proyek = ['super_admin', 'admin']; 
if (!in_array($current_user_role, $allowed_roles_for_proses_proyek)) {
    $_SESSION['pesan_error_crud'] = "ANDA TIDAK MEMILIKI HAK AKSES UNTUK MELAKUKAN TINDAKAN INI.";
    header('Location: ' . BASE_URL . 'dashboard.php'); 
    exit;
}

// --- LOGIKA UNTUK AKSI TAMBAH PROYEK ---
if (isset($_GET['aksi']) && $_GET['aksi'] == 'tambah') {

    // Autorisasi spesifik untuk aksi tambah (dalam kasus ini sama dengan otorisasi file)
    // Jika nanti ada perbedaan hak antara tambah, edit, hapus oleh Admin vs Super Admin, cek di sini.
    // Untuk sekarang, Super Admin dan Admin boleh tambah.

    // Pastikan request yang datang adalah metode POST
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        
        // Ambil dan sanitasi data dari form
        $namaprojek = isset($_POST['namaprojek']) ? mysqli_real_escape_string($koneksi, trim($_POST['namaprojek'])) : '';
        $id_klien = isset($_POST['id_klien']) ? intval($_POST['id_klien']) : 0;
        $id_mandor_pekerja = isset($_POST['id_mandor_pekerja']) ? intval($_POST['id_mandor_pekerja']) : 0;
        $jenisprojek = isset($_POST['jenisprojek']) && !empty(trim($_POST['jenisprojek'])) ? mysqli_real_escape_string($koneksi, trim($_POST['jenisprojek'])) : NULL;
        $status = isset($_POST['status']) ? mysqli_real_escape_string($koneksi, trim($_POST['status'])) : 'planning'; // Default 'planning'
        $lokasi = isset($_POST['lokasi']) && !empty(trim($_POST['lokasi'])) ? mysqli_real_escape_string($koneksi, trim($_POST['lokasi'])) : NULL;
        
        $tanggal_mulai_projek = isset($_POST['tanggal_mulai_projek']) && !empty($_POST['tanggal_mulai_projek']) ? $_POST['tanggal_mulai_projek'] : NULL;
        $tanggal_selesai_projek = isset($_POST['tanggal_selesai_projek']) && !empty($_POST['tanggal_selesai_projek']) ? $_POST['tanggal_selesai_projek'] : NULL;

        // Validasi dasar: Field yang wajib diisi
        if (empty($namaprojek) || empty($id_klien) || empty($id_mandor_pekerja) || empty($status)) {
            $_SESSION['pesan_error_crud'] = "Nama Proyek, Klien, Mandor Penanggung Jawab, dan Status Proyek wajib diisi.";
            $_SESSION['form_data'] = $_POST; 
            header('Location: ' . BASE_URL . 'proyek/tambah.php');
            exit;
        }

        $allowed_status = ['planning', 'active', 'completed']; // Sesuai ENUM di DB
        if (!in_array($status, $allowed_status)) {
            $_SESSION['pesan_error_crud'] = "Status proyek tidak valid.";
            $_SESSION['form_data'] = $_POST;
            header('Location: ' . BASE_URL . 'proyek/tambah.php');
            exit;
        }

        if ($tanggal_mulai_projek && $tanggal_selesai_projek && strtotime($tanggal_selesai_projek) < strtotime($tanggal_mulai_projek)) {
            $_SESSION['pesan_error_crud'] = "Tanggal selesai proyek tidak boleh sebelum tanggal mulai proyek.";
            $_SESSION['form_data'] = $_POST;
            header('Location: ' . BASE_URL . 'proyek/tambah.php');
            exit;
        }

        // Siapkan query INSERT (TANPA created_at dan updated_at, karena tidak ada di tabel projek)
        $sql_insert = "INSERT INTO projek (namaprojek, id_klien, id_mandor_pekerja, jenisprojek, status, lokasi, tanggal_mulai_projek, tanggal_selesai_projek) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?)"; 
        
        $stmt_insert = mysqli_prepare($koneksi, $sql_insert);

        if ($stmt_insert) {
            // Bind parameter: s(tring), i(nteger), i(nteger), s(tring), s(tring), s(tring), s(tring untuk date), s(tring untuk date)
            // Total 8 parameter
            mysqli_stmt_bind_param($stmt_insert, "siisssss", 
                $namaprojek, 
                $id_klien, 
                $id_mandor_pekerja,
                $jenisprojek,
                $status,
                $lokasi,
                $tanggal_mulai_projek,
                $tanggal_selesai_projek
            );

            if (mysqli_stmt_execute($stmt_insert)) {
                $_SESSION['pesan_sukses'] = "Proyek '" . htmlspecialchars($namaprojek) . "' berhasil ditambahkan!";
                unset($_SESSION['form_data']); 
            } else {
                $_SESSION['pesan_error_crud'] = "GAGAL menambahkan proyek. Error database: " . mysqli_stmt_error($stmt_insert);
                $_SESSION['form_data'] = $_POST; 
            }
            mysqli_stmt_close($stmt_insert);
        } else {
            $_SESSION['pesan_error_crud'] = "Terjadi kesalahan pada sistem (gagal mempersiapkan statement SQL): " . mysqli_error($koneksi);
            $_SESSION['form_data'] = $_POST;
        }
        
        if (isset($_SESSION['pesan_sukses'])) {
             header('Location: ' . BASE_URL . 'proyek/');
        } else {
             header('Location: ' . BASE_URL . 'proyek/tambah.php');
        }
        exit;

    } else { 
        $_SESSION['pesan_error_crud'] = "Metode request tidak valid untuk menambah proyek.";
        header('Location: ' . BASE_URL . 'proyek/tambah.php');
        exit;
    }
} 
// --- TEMPAT UNTUK BLOK 'ELSE IF' AKSI EDIT DAN HAPUS PROYEK NANTI ---
// aksi edit proyek
elseif (isset($_GET['aksi']) && $_GET['aksi'] == 'edit' && isset($_GET['id'])) {
    
    // Autorisasi spesifik untuk EDIT (sama dengan otorisasi file, tapi bisa lebih spesifik jika perlu)
    // Untuk sekarang, Super Admin dan Admin boleh edit.

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        
        $id_projek_edit = intval($_GET['id']); // Ambil ID proyek dari URL

        // Ambil dan sanitasi data dari form edit
        $namaprojek_baru = isset($_POST['namaprojek']) ? mysqli_real_escape_string($koneksi, trim($_POST['namaprojek'])) : '';
        $id_klien_baru = isset($_POST['id_klien']) ? intval($_POST['id_klien']) : 0;
        $id_mandor_pekerja_baru = isset($_POST['id_mandor_pekerja']) ? intval($_POST['id_mandor_pekerja']) : 0;
        $jenisprojek_baru = isset($_POST['jenisprojek']) && !empty(trim($_POST['jenisprojek'])) ? mysqli_real_escape_string($koneksi, trim($_POST['jenisprojek'])) : NULL;
        $status_baru = isset($_POST['status']) ? mysqli_real_escape_string($koneksi, trim($_POST['status'])) : '';
        $lokasi_baru = isset($_POST['lokasi']) && !empty(trim($_POST['lokasi'])) ? mysqli_real_escape_string($koneksi, trim($_POST['lokasi'])) : NULL;
        
        $tanggal_mulai_projek_baru = isset($_POST['tanggal_mulai_projek']) && !empty($_POST['tanggal_mulai_projek']) ? $_POST['tanggal_mulai_projek'] : NULL;
        $tanggal_selesai_projek_baru = isset($_POST['tanggal_selesai_projek']) && !empty($_POST['tanggal_selesai_projek']) ? $_POST['tanggal_selesai_projek'] : NULL;

        // Validasi dasar: Field yang wajib diisi
        if (empty($namaprojek_baru) || empty($id_klien_baru) || empty($id_mandor_pekerja_baru) || empty($status_baru)) {
            $_SESSION['pesan_error_crud'] = "Nama Proyek, Klien, Mandor Penanggung Jawab, dan Status Proyek wajib diisi.";
            $_SESSION['form_data'] = $_POST; 
            header('Location: ' . BASE_URL . 'proyek/edit.php?id=' . $id_projek_edit);
            exit;
        }

        $allowed_status = ['planning', 'active', 'completed']; // Sesuai ENUM di DB
        if (!in_array($status_baru, $allowed_status)) {
            $_SESSION['pesan_error_crud'] = "Status proyek tidak valid.";
            $_SESSION['form_data'] = $_POST;
            header('Location: ' . BASE_URL . 'proyek/edit.php?id=' . $id_projek_edit);
            exit;
        }

        if ($tanggal_mulai_projek_baru && $tanggal_selesai_projek_baru && strtotime($tanggal_selesai_projek_baru) < strtotime($tanggal_mulai_projek_baru)) {
            $_SESSION['pesan_error_crud'] = "Tanggal selesai proyek tidak boleh sebelum tanggal mulai proyek.";
            $_SESSION['form_data'] = $_POST;
            header('Location: ' . BASE_URL . 'proyek/edit.php?id=' . $id_projek_edit);
            exit;
        }

        // (Opsional) Cek duplikasi nama proyek, KECUALIKAN proyek yang sedang diedit
        // ... (Jika diperlukan, logikanya mirip dengan cek duplikat di modul lain) ...

        // Siapkan query UPDATE menggunakan prepared statement
        // Tabel 'projek' kita tidak punya kolom 'updated_at' berdasarkan skema jaya.sql terakhir
        $sql_update = "UPDATE projek SET 
                           namaprojek = ?, 
                           id_klien = ?, 
                           id_mandor_pekerja = ?, 
                           jenisprojek = ?, 
                           status = ?, 
                           lokasi = ?, 
                           tanggal_mulai_projek = ?, 
                           tanggal_selesai_projek = ? 
                       WHERE id_projek = ?";
        
        $stmt_update = mysqli_prepare($koneksi, $sql_update);

        if ($stmt_update) {
            // Bind parameter: s(tring), i(nteger), i(nteger), s(tring), s(tring), s(tring), s(date), s(date), i(ID)
            // Total 9 parameter
            mysqli_stmt_bind_param($stmt_update, "siisssssi", 
                $namaprojek_baru, 
                $id_klien_baru, 
                $id_mandor_pekerja_baru,
                $jenisprojek_baru,
                $status_baru,
                $lokasi_baru,
                $tanggal_mulai_projek_baru,
                $tanggal_selesai_projek_baru,
                $id_projek_edit
            );

            if (mysqli_stmt_execute($stmt_update)) {
                $_SESSION['pesan_sukses'] = "Data proyek '" . htmlspecialchars($namaprojek_baru) . "' berhasil diperbarui!";
                unset($_SESSION['form_data']); 
            } else {
                $_SESSION['pesan_error_crud'] = "GAGAL memperbarui data proyek. Error database: " . mysqli_stmt_error($stmt_update);
                $_SESSION['form_data'] = $_POST; 
                // error_log("Error UPDATE Proyek: " . mysqli_stmt_error($stmt_update));
            }
            mysqli_stmt_close($stmt_update);
        } else {
            $_SESSION['pesan_error_crud'] = "Terjadi kesalahan pada sistem (gagal mempersiapkan statement SQL untuk update proyek).";
            $_SESSION['form_data'] = $_POST;
            // error_log("Error PREPARE UPDATE Proyek: " . mysqli_error($koneksi));
        }
        
        if (isset($_SESSION['pesan_sukses'])) {
             header('Location: ' . BASE_URL . 'proyek/');
        } else {
             header('Location: ' . BASE_URL . 'proyek/edit.php?id=' . $id_projek_edit);
        }
        exit;

    } else {
        // Jika metode request bukan POST untuk aksi edit
        $_SESSION['pesan_error_crud'] = "Metode request tidak valid untuk mengedit proyek.";
        header('Location: ' . BASE_URL . 'proyek/');
        exit;
    }
}

// aksi hapus proyek

elseif (isset($_GET['aksi']) && $_GET['aksi'] == 'hapus' && isset($_GET['id'])) {
    
    // 1. Autorisasi Khusus untuk Hapus Proyek: HANYA Super Admin
    if ($current_user_role !== 'super_admin') {
        $_SESSION['pesan_error_crud'] = "AKSES DITOLAK: Hanya Super Admin yang dapat menghapus data proyek.";
        header('Location: ' . BASE_URL . 'proyek/');
        exit;
    }

    // 2. Ambil ID proyek dari URL dan pastikan integer
    $id_projek_hapus = intval($_GET['id']);

    // 3. Cek keterkaitan data di tabel 'proyek_pekerja'
    $sql_cek_penugasan = "SELECT COUNT(*) as jumlah_penugasan FROM proyek_pekerja WHERE id_projek = ?";
    $stmt_cek_penugasan = mysqli_prepare($koneksi, $sql_cek_penugasan);
    $ada_keterkaitan = false;

    if ($stmt_cek_penugasan) {
        mysqli_stmt_bind_param($stmt_cek_penugasan, "i", $id_projek_hapus);
        mysqli_stmt_execute($stmt_cek_penugasan);
        $result_penugasan = mysqli_stmt_get_result($stmt_cek_penugasan);
        $data_penugasan = mysqli_fetch_assoc($result_penugasan);
        mysqli_stmt_close($stmt_cek_penugasan);
        if ($data_penugasan['jumlah_penugasan'] > 0) {
            $ada_keterkaitan = true;
            $_SESSION['pesan_error_crud'] = "Proyek tidak dapat dihapus karena masih memiliki " . $data_penugasan['jumlah_penugasan'] . " data penugasan pekerja terkait.";
        }
    } else {
        $ada_keterkaitan = true; // Anggap ada keterkaitan jika query cek gagal, demi keamanan
        $_SESSION['pesan_error_crud'] = "Terjadi kesalahan saat memeriksa data penugasan terkait proyek.";
    }

    // 4. Jika tidak ada keterkaitan di 'proyek_pekerja', cek keterkaitan di 'absensi'
    if (!$ada_keterkaitan) {
        $sql_cek_absensi = "SELECT COUNT(*) as jumlah_absensi FROM absensi WHERE id_projek = ?";
        $stmt_cek_absensi = mysqli_prepare($koneksi, $sql_cek_absensi);
        if ($stmt_cek_absensi) {
            mysqli_stmt_bind_param($stmt_cek_absensi, "i", $id_projek_hapus);
            mysqli_stmt_execute($stmt_cek_absensi);
            $result_absensi = mysqli_stmt_get_result($stmt_cek_absensi);
            $data_absensi = mysqli_fetch_assoc($result_absensi);
            mysqli_stmt_close($stmt_cek_absensi);
            if ($data_absensi['jumlah_absensi'] > 0) {
                $ada_keterkaitan = true;
                $_SESSION['pesan_error_crud'] = "Proyek tidak dapat dihapus karena masih memiliki " . $data_absensi['jumlah_absensi'] . " data absensi terkait.";
            }
        } else {
            $ada_keterkaitan = true; // Anggap ada keterkaitan jika query cek gagal
            $_SESSION['pesan_error_crud'] = "Terjadi kesalahan saat memeriksa data absensi terkait proyek.";
        }
    }
    
    // 5. Ambil nama proyek untuk pesan notifikasi (sebelum dihapus)
    $nama_proyek_target = "Proyek (ID: " . $id_projek_hapus . ")"; // Default
    if (!$ada_keterkaitan) { // Hanya ambil nama jika memang akan dihapus
        $q_get_nama = mysqli_prepare($koneksi, "SELECT namaprojek FROM projek WHERE id_projek = ?");
        if($q_get_nama){
            mysqli_stmt_bind_param($q_get_nama, "i", $id_projek_hapus);
            mysqli_stmt_execute($q_get_nama);
            $res_nama = mysqli_stmt_get_result($q_get_nama);
            if($data_nama = mysqli_fetch_assoc($res_nama)){
                $nama_proyek_target = htmlspecialchars($data_nama['namaprojek']);
            }
            mysqli_stmt_close($q_get_nama);
        }
    }


    // 6. Jika tidak ada keterkaitan data, lanjutkan proses penghapusan permanen
    if (!$ada_keterkaitan) {
        $sql_hapus = "DELETE FROM projek WHERE id_projek = ?";
        $stmt_hapus = mysqli_prepare($koneksi, $sql_hapus);

        if ($stmt_hapus) {
            mysqli_stmt_bind_param($stmt_hapus, "i", $id_projek_hapus);

            if (mysqli_stmt_execute($stmt_hapus)) {
                if (mysqli_stmt_affected_rows($stmt_hapus) > 0) {
                    $_SESSION['pesan_sukses'] = "Data proyek '" . $nama_proyek_target . "' berhasil dihapus permanen!";
                } else {
                    $_SESSION['pesan_error_crud'] = "Data proyek tidak ditemukan atau mungkin sudah dihapus sebelumnya.";
                }
            } else {
                $_SESSION['pesan_error_crud'] = "GAGAL menghapus proyek. Terjadi kesalahan database: " . mysqli_stmt_error($stmt_hapus);
                // error_log("Error DELETE Proyek: " . mysqli_stmt_error($stmt_hapus));
            }
            mysqli_stmt_close($stmt_hapus);
        } else {
            $_SESSION['pesan_error_crud'] = "Terjadi kesalahan pada sistem (gagal mempersiapkan statement SQL untuk hapus proyek).";
            // error_log("Error PREPARE DELETE Proyek: " . mysqli_error($koneksi));
        }
    }
    // Jika ada keterkaitan, pesan error sudah di-set di atas.
    
    // 7. Redirect kembali ke halaman daftar proyek
    header('Location: ' . BASE_URL . 'proyek/');
    exit;
}
else {
    $_SESSION['pesan_error_crud'] = "Aksi tidak valid atau tidak dikenali untuk modul proyek.";
    header('Location: ' . BASE_URL . 'proyek/'); 
    exit;
}
?>