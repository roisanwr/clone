<?php
// File: proyek_jaya/users/proses.php (Fokus Aksi Tambah)

// 1. Panggil Konfigurasi dan pastikan session sudah dimulai
require_once '../config.php'; 

// 2. Autentikasi: Pastikan pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'auth/login.php?error=2');
    exit;
}

// 3. Autorisasi Super Ketat: HANYA Super Admin yang boleh melakukan aksi di file ini
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    $_SESSION['pesan_error_crud'] = "AKSES DITOLAK: Anda tidak memiliki izin untuk melakukan tindakan ini.";
    header('Location: ' . BASE_URL . 'dashboard.php'); 
    exit;
}

// --- LOGIKA UNTUK AKSI TAMBAH PENGGUNA ---
if (isset($_GET['aksi']) && $_GET['aksi'] == 'tambah') {

    // Pastikan request yang datang adalah metode POST
    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        // Ambil dan sanitasi data dari form
        $username = isset($_POST['username']) ? mysqli_real_escape_string($koneksi, trim($_POST['username'])) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : ''; // Tidak di-trim dulu, validasi panjang setelahnya
        $konfirmasi_password = isset($_POST['konfirmasi_password']) ? $_POST['konfirmasi_password'] : '';
        $role = isset($_POST['role']) ? mysqli_real_escape_string($koneksi, $_POST['role']) : '';
        // id_pekerja_ref hanya diambil jika peran adalah 'mandor' dan ada nilainya
        $id_pekerja_ref = (isset($_POST['role']) && $_POST['role'] === 'mandor' && isset($_POST['id_pekerja_ref']) && !empty($_POST['id_pekerja_ref'])) ? intval($_POST['id_pekerja_ref']) : NULL;
        $is_active = isset($_POST['is_active']) ? intval($_POST['is_active']) : 0; // Default ke 0 (Tidak Aktif) jika tidak dikirim, lalu validasi

        // --- Validasi Input ---
        $errors = []; // Array untuk menampung pesan error

        if (empty($username)) {
            $errors[] = "Username wajib diisi.";
        } elseif (strlen($username) < 4 || strlen($username) > 50) {
            $errors[] = "Username harus terdiri dari 4 hingga 50 karakter.";
        } else {
            // Cek keunikan username
            $sql_cek_username = "SELECT id_user FROM users WHERE username = ?";
            $stmt_cek_username = mysqli_prepare($koneksi, $sql_cek_username);
            mysqli_stmt_bind_param($stmt_cek_username, "s", $username);
            mysqli_stmt_execute($stmt_cek_username);
            $result_cek_username = mysqli_stmt_get_result($stmt_cek_username);
            if (mysqli_num_rows($result_cek_username) > 0) {
                $errors[] = "Username '" . htmlspecialchars($username) . "' sudah digunakan. Pilih username lain.";
            }
            mysqli_stmt_close($stmt_cek_username);
        }

        if (empty($password)) {
            $errors[] = "Password wajib diisi.";
        } elseif (strlen($password) < 6) { // Contoh validasi panjang minimal password
            $errors[] = "Password minimal harus 6 karakter.";
        }

        if (empty($konfirmasi_password)) {
            $errors[] = "Konfirmasi password wajib diisi.";
        } elseif ($password !== $konfirmasi_password) {
            $errors[] = "Password dan Konfirmasi Password tidak cocok.";
        }

        $allowed_roles_option = ['admin', 'mandor']; // Peran yang bisa dibuat dari form
        if (empty($role) || !in_array($role, $allowed_roles_option)) {
            $errors[] = "Peran (Role) yang dipilih tidak valid.";
        }

        if ($role === 'mandor' && empty($id_pekerja_ref)) {
            $errors[] = "Jika peran adalah Mandor, data pekerja terkait wajib dipilih.";
        } elseif ($role === 'admin' && $id_pekerja_ref !== NULL) {
            // Jika Admin, pastikan id_pekerja_ref adalah NULL (atau kita set NULL di sini)
            $id_pekerja_ref = NULL; 
        }

        if (!in_array($is_active, [0, 1])) { // Pastikan status valid
            $errors[] = "Status akun tidak valid.";
        }

        // Jika ada error validasi, redirect kembali ke form tambah
        if (!empty($errors)) {
            $_SESSION['pesan_error_crud'] = implode("<br>", $errors);
            $_SESSION['form_data'] = $_POST; // Simpan data inputan untuk sticky form
            header('Location: ' . BASE_URL . 'users/tambah.php');
            exit;
        }

        // Jika lolos validasi, HASH PASSWORDNYA!
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Siapkan query INSERT
        $sql_insert = "INSERT INTO users (username, password, role, id_pekerja_ref, is_active) 
                       VALUES (?, ?, ?, ?, ?)"; 

        $stmt_insert = mysqli_prepare($koneksi, $sql_insert);

        if ($stmt_insert) {
            // Bind parameter: s(tring), s(tring), s(tring), i(nteger untuk id_pekerja_ref atau NULL), i(nteger untuk is_active)
            mysqli_stmt_bind_param($stmt_insert, "sssis", 
                $username, 
                $hashed_password, 
                $role,
                $id_pekerja_ref, // Akan jadi NULL jika peran bukan mandor atau tidak dipilih
                $is_active
            );

            if (mysqli_stmt_execute($stmt_insert)) {
                $_SESSION['pesan_sukses'] = "Pengguna '" . htmlspecialchars($username) . "' berhasil ditambahkan!";
                unset($_SESSION['form_data']); 
            } else {
                $_SESSION['pesan_error_crud'] = "GAGAL menambahkan pengguna. Error database: " . mysqli_stmt_error($stmt_insert);
                $_SESSION['form_data'] = $_POST; 
            }
            mysqli_stmt_close($stmt_insert);
        } else {
            $_SESSION['pesan_error_crud'] = "Terjadi kesalahan pada sistem (gagal mempersiapkan statement SQL): " . mysqli_error($koneksi);
            $_SESSION['form_data'] = $_POST;
        }

        // Redirect kembali
        if (isset($_SESSION['pesan_sukses'])) {
             header('Location: ' . BASE_URL . 'users/');
        } else {
             header('Location: ' . BASE_URL . 'users/tambah.php');
        }
        exit;

    } else { 
        $_SESSION['pesan_error_crud'] = "Metode request tidak valid untuk menambah pengguna.";
        header('Location: ' . BASE_URL . 'users/tambah.php');
        exit;
    }
} 
// --- NANTI KITA TAMBAHKAN BLOK 'ELSE IF' UNTUK AKSI EDIT DAN HAPUS_PERMANEN PENGGUNA DI SINI ---
// logic edit

elseif (isset($_GET['aksi']) && $_GET['aksi'] == 'edit' && isset($_GET['id'])) {
    
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        
        $id_user_edit = intval($_GET['id']); // Ambil ID user dari URL

        // Ambil dan sanitasi data dari form edit
        $username_baru = isset($_POST['username']) ? mysqli_real_escape_string($koneksi, trim($_POST['username'])) : '';
        $password_baru = isset($_POST['password']) ? $_POST['password'] : ''; // Password baru (opsional)
        $konfirmasi_password_baru = isset($_POST['konfirmasi_password']) ? $_POST['konfirmasi_password'] : '';
        $role_baru = isset($_POST['role']) ? mysqli_real_escape_string($koneksi, $_POST['role']) : '';
        $id_pekerja_ref_baru = (isset($_POST['role']) && $_POST['role'] === 'mandor' && isset($_POST['id_pekerja_ref']) && !empty($_POST['id_pekerja_ref'])) ? intval($_POST['id_pekerja_ref']) : NULL;
        $is_active_baru = isset($_POST['is_active']) ? intval($_POST['is_active']) : 0;

        // --- Validasi Input ---
        $errors = [];

        if (empty($username_baru)) {
            $errors[] = "Username wajib diisi.";
        } elseif (strlen($username_baru) < 4 || strlen($username_baru) > 50) {
            $errors[] = "Username harus terdiri dari 4 hingga 50 karakter.";
        } else {
            // Cek keunikan username BARU, tapi KECUALIKAN user yang sedang diedit
            $sql_cek_username_edit = "SELECT id_user FROM users WHERE username = ? AND id_user != ?";
            $stmt_cek_username_edit = mysqli_prepare($koneksi, $sql_cek_username_edit);
            mysqli_stmt_bind_param($stmt_cek_username_edit, "si", $username_baru, $id_user_edit);
            mysqli_stmt_execute($stmt_cek_username_edit);
            $result_cek_username_edit = mysqli_stmt_get_result($stmt_cek_username_edit);
            if (mysqli_num_rows($result_cek_username_edit) > 0) {
                $errors[] = "Username '" . htmlspecialchars($username_baru) . "' sudah digunakan oleh pengguna lain.";
            }
            mysqli_stmt_close($stmt_cek_username_edit);
        }

        // Validasi password BARU jika diisi
        if (!empty($password_baru)) { // Hanya validasi jika password baru diisi
            if (strlen($password_baru) < 6) {
                $errors[] = "Password baru minimal harus 6 karakter.";
            }
            if ($password_baru !== $konfirmasi_password_baru) {
                $errors[] = "Password baru dan Konfirmasi Password baru tidak cocok.";
            }
        }

        $allowed_roles_option_edit = ['admin', 'mandor'];
        // Jika yang diedit adalah Super Admin itu sendiri, perannya tidak boleh diubah dari 'super_admin'
        if ($id_user_edit == $_SESSION['user_id'] && $_SESSION['role'] == 'super_admin') {
            if ($role_baru !== 'super_admin') {
                 $errors[] = "Super Admin tidak dapat mengubah perannya sendiri menjadi lebih rendah.";
            }
             // Pastikan status aktif tidak diubah menjadi tidak aktif untuk diri sendiri
            if ($is_active_baru == 0) {
                $errors[] = "Super Admin tidak dapat menonaktifkan akunnya sendiri.";
                $is_active_baru = 1; // Paksa tetap aktif
            }
        } elseif (!in_array($role_baru, $allowed_roles_option_edit)) {
             // Jika bukan mengedit diri sendiri sebagai Super Admin, maka role harus 'admin' atau 'mandor'
            $errors[] = "Peran (Role) yang dipilih tidak valid.";
        }


        if ($role_baru === 'mandor' && empty($id_pekerja_ref_baru)) {
            $errors[] = "Jika peran adalah Mandor, data pekerja terkait wajib dipilih.";
        } elseif ($role_baru === 'admin' && $id_pekerja_ref_baru !== NULL) {
            $id_pekerja_ref_baru = NULL; 
        }
        
        if (!in_array($is_active_baru, [0, 1])) {
            $errors[] = "Status akun tidak valid.";
        }

        if (!empty($errors)) {
            $_SESSION['pesan_error_crud'] = implode("<br>", $errors);
            $_SESSION['form_data'] = $_POST; 
            header('Location: ' . BASE_URL . 'users/edit.php?id=' . $id_user_edit);
            exit;
        }

        // Persiapan Query UPDATE
        $kolom_update = [];
        $tipe_data_bind = "";
        $nilai_bind = [];

        $kolom_update[] = "username = ?";
        $tipe_data_bind .= "s";
        $nilai_bind[] = $username_baru;

        // Jika password baru diisi, tambahkan ke query update dan hash passwordnya
        if (!empty($password_baru)) {
            $hashed_password_baru = password_hash($password_baru, PASSWORD_DEFAULT);
            $kolom_update[] = "password = ?";
            $tipe_data_bind .= "s";
            $nilai_bind[] = $hashed_password_baru;
        }

        $kolom_update[] = "role = ?";
        $tipe_data_bind .= "s";
        $nilai_bind[] = $role_baru;

        $kolom_update[] = "id_pekerja_ref = ?";
        $tipe_data_bind .= "i"; // Integer, bisa NULL jika $id_pekerja_ref_baru adalah NULL
        $nilai_bind[] = $id_pekerja_ref_baru;
        
        $kolom_update[] = "is_active = ?";
        $tipe_data_bind .= "i";
        $nilai_bind[] = $is_active_baru;
        
        // Tambahkan id_user ke akhir array nilai_bind untuk klausa WHERE
        $nilai_bind[] = $id_user_edit;
        $tipe_data_bind .= "i";

        $sql_update = "UPDATE users SET " . implode(", ", $kolom_update) . " WHERE id_user = ?";
        
        $stmt_update = mysqli_prepare($koneksi, $sql_update);

        if ($stmt_update) {
            // Gunakan call_user_func_array untuk mysqli_stmt_bind_param karena jumlah parameter dinamis
            mysqli_stmt_bind_param($stmt_update, $tipe_data_bind, ...$nilai_bind);

            if (mysqli_stmt_execute($stmt_update)) {
                $_SESSION['pesan_sukses'] = "Data pengguna '" . htmlspecialchars($username_baru) . "' berhasil diperbarui!";
                unset($_SESSION['form_data']); 
            } else {
                $_SESSION['pesan_error_crud'] = "GAGAL memperbarui data pengguna. Error database: " . mysqli_stmt_error($stmt_update);
                $_SESSION['form_data'] = $_POST; 
            }
            mysqli_stmt_close($stmt_update);
        } else {
            $_SESSION['pesan_error_crud'] = "Terjadi kesalahan pada sistem (gagal mempersiapkan statement SQL): " . mysqli_error($koneksi);
            $_SESSION['form_data'] = $_POST;
        }
        
        if (isset($_SESSION['pesan_sukses'])) {
             header('Location: ' . BASE_URL . 'users/');
        } else {
             header('Location: ' . BASE_URL . 'users/edit.php?id=' . $id_user_edit);
        }
        exit;

    } else { 
        $_SESSION['pesan_error_crud'] = "Metode request tidak valid untuk mengedit pengguna.";
        header('Location: ' . BASE_URL . 'users/');
        exit;
    }
}
// logic hapus

elseif (isset($_GET['aksi']) && $_GET['aksi'] == 'hapus_permanen' && isset($_GET['id'])) {
    
    // Karena sudah ada pengecekan $current_user_role !== 'super_admin' di awal file,
    // kita sudah yakin di titik ini yang menjalankan adalah Super Admin.

    $id_user_hapus = intval($_GET['id']);

    // 1. Super Admin tidak boleh menghapus akunnya sendiri
    if ($id_user_hapus == $_SESSION['user_id']) {
        $_SESSION['pesan_error_crud'] = "TIDAK DAPAT MENGHAPUS DIRI SENDIRI: Anda tidak bisa menghapus akun Super Admin yang sedang Anda gunakan.";
        header('Location: ' . BASE_URL . 'users/');
        exit;
    }

    // 2. Ambil detail user yang akan dihapus (untuk nama & pengecekan dependensi jika Mandor)
    $sql_get_user_detail = "SELECT username, role, id_pekerja_ref FROM users WHERE id_user = ?";
    $stmt_get_user = mysqli_prepare($koneksi, $sql_get_user_detail);
    $user_to_delete = null; 
    if ($stmt_get_user) {
        mysqli_stmt_bind_param($stmt_get_user, "i", $id_user_hapus);
        mysqli_stmt_execute($stmt_get_user);
        $result_user_detail = mysqli_stmt_get_result($stmt_get_user);
        $user_to_delete = mysqli_fetch_assoc($result_user_detail);
        mysqli_stmt_close($stmt_get_user);
    }

    if (!$user_to_delete) {
        $_SESSION['pesan_error_crud'] = "Pengguna (ID: $id_user_hapus) tidak ditemukan.";
        header('Location: ' . BASE_URL . 'users/');
        exit;
    }
    $username_target = htmlspecialchars($user_to_delete['username']);

    // 3. Pengecekan Keterkaitan Data KRITIS jika pengguna adalah Mandor dan memiliki id_pekerja_ref
    $ada_keterkaitan_kritis = false;
    if ($user_to_delete['role'] === 'mandor' && !empty($user_to_delete['id_pekerja_ref'])) {
        $id_pekerja_terkait_mandor = $user_to_delete['id_pekerja_ref'];
        
        $dependensi_pekerja_mandor = [
            "projek sebagai Mandor PJ" => "SELECT COUNT(*) as jumlah FROM projek WHERE id_mandor_pekerja = ?",
            "proyek_pekerja sebagai Penugas" => "SELECT COUNT(*) as jumlah FROM proyek_pekerja WHERE created_by_mandor_id = ?",
            "absensi sebagai Pencatat" => "SELECT COUNT(*) as jumlah FROM absensi WHERE id_mandor = ?"
        ];

        foreach ($dependensi_pekerja_mandor as $info_cek => $sql_cek_dep) {
            $stmt_cek_dep = mysqli_prepare($koneksi, $sql_cek_dep);
            if ($stmt_cek_dep) {
                mysqli_stmt_bind_param($stmt_cek_dep, "i", $id_pekerja_terkait_mandor);
                mysqli_stmt_execute($stmt_cek_dep);
                $result_dep = mysqli_stmt_get_result($stmt_cek_dep);
                $data_dep = mysqli_fetch_assoc($result_dep);
                mysqli_stmt_close($stmt_cek_dep);
                if ($data_dep['jumlah'] > 0) {
                    $ada_keterkaitan_kritis = true;
                    $_SESSION['pesan_error_crud'] = "Pengguna Mandor '$username_target' tidak dapat dihapus permanen karena data pekerjanya masih terkait sebagai $info_cek. Harap nonaktifkan akun melalui menu Edit atau pindahkan tanggung jawabnya terlebih dahulu di modul terkait.";
                    break; 
                }
            } else {
                $ada_keterkaitan_kritis = true; 
                $_SESSION['pesan_error_crud'] = "Gagal memeriksa keterkaitan data untuk Mandor '$username_target'. Penghapusan permanen dibatalkan demi keamanan.";
                break;
            }
        }
    }

    // 4. Jika ada keterkaitan kritis untuk Mandor, jangan lanjutkan hapus permanen
    if ($ada_keterkaitan_kritis) {
        header('Location: ' . BASE_URL . 'users/');
        exit;
    }

    // 5. JIKA AMAN, LANJUTKAN HAPUS PERMANEN dari tabel 'users'.
    $sql_hapus_permanen = "DELETE FROM users WHERE id_user = ?";
    $stmt_hapus = mysqli_prepare($koneksi, $sql_hapus_permanen);

    if ($stmt_hapus) {
        mysqli_stmt_bind_param($stmt_hapus, "i", $id_user_hapus);
        if (mysqli_stmt_execute($stmt_hapus)) {
            if (mysqli_stmt_affected_rows($stmt_hapus) > 0) {
                $_SESSION['pesan_sukses'] = "Pengguna '$username_target' berhasil dihapus secara permanen!";
            } else {
                $_SESSION['pesan_error_crud'] = "Pengguna '$username_target' tidak ditemukan atau gagal dihapus.";
            }
        } else {
            $_SESSION['pesan_error_crud'] = "GAGAL menghapus pengguna '$username_target' secara permanen. Error: " . mysqli_stmt_error($stmt_hapus);
        }
        mysqli_stmt_close($stmt_hapus);
    } else {
        $_SESSION['pesan_error_crud'] = "Gagal mempersiapkan statement SQL untuk hapus permanen pengguna.";
    }
    
    header('Location: ' . BASE_URL . 'users/');
    exit;
}

else {
    $_SESSION['pesan_error_crud'] = "Aksi tidak valid atau tidak dikenali untuk modul pengguna.";
    header('Location: ' . BASE_URL . 'users/'); 
    exit;
}
?>