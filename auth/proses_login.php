<?php
// File: proyek_jaya/auth/proses_login.php (Versi Upgrade dengan Cek Status Aktif)

require_once '../config.php'; // Untuk koneksi DB, BASE_URL, dan session_start()

// Pastikan request yang datang adalah metode POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Ambil data dari form dan lakukan sanitasi dasar
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password_input_pengguna = $_POST['password']; 

    // 2. Validasi dasar: pastikan input tidak kosong
    if (empty($username) || empty($password_input_pengguna)) {
        header('Location: ' . BASE_URL . 'auth/login.php?error=3'); // error=3 artinya field kosong
        exit;
    }

    // 3. Siapkan query untuk mengambil data user berdasarkan username
    // PASTIKAN KITA JUGA MENGAMBIL KOLOM 'is_active' DARI TABEL USERS
    // Berdasarkan skema jaya.sql terakhirmu, tabel 'users' TIDAK punya 'nama_lengkap' dan 'email' secara langsung.
    // Jadi, kita hanya SELECT kolom yang ada.
    $sql = "SELECT id_user, username, password, role, id_pekerja_ref, is_active 
            FROM users 
            WHERE username = ?";
    
    $stmt = mysqli_prepare($koneksi, $sql);

    if ($stmt) {
        // 4. Bind parameter username ke statement
        mysqli_stmt_bind_param($stmt, "s", $username);

        // 5. Eksekusi statement
        mysqli_stmt_execute($stmt);

        // 6. Ambil hasil query
        $result = mysqli_stmt_get_result($stmt);
        
        // 7. Fetch data user sebagai array asosiatif
        $user_data_dari_db = mysqli_fetch_assoc($result);

        mysqli_stmt_close($stmt); // Tutup statement setelah fetch

        // 8. Cek apakah user ditemukan
        if ($user_data_dari_db) {
            // User ditemukan, sekarang verifikasi password
            if (password_verify($password_input_pengguna, $user_data_dari_db['password'])) {
                // Password COCOK!
                
                // --- INI DIA PENAMBAHAN LOGIKA PENTINGNYA! ---
                // 9. CEK STATUS AKTIF PENGGUNA
                if ($user_data_dari_db['is_active'] == 1) {
                    // Pengguna AKTIF, lanjutkan proses login dan set session

                    $_SESSION['user_id'] = $user_data_dari_db['id_user'];
                    $_SESSION['username'] = $user_data_dari_db['username'];
                    $_SESSION['role'] = $user_data_dari_db['role'];
                    $_SESSION['id_pekerja_ref'] = $user_data_dari_db['id_pekerja_ref']; 
                    
                    // Sesuai kesepakatan terakhir, UI akan menampilkan $_SESSION['username'] dan $_SESSION['role']
                    // jadi tidak perlu set $_SESSION['name'] atau $_SESSION['user_email'] di sini
                    // kecuali jika template header/sidebar nanti membutuhkannya dari sumber lain.

                    // 10. Redirect ke halaman dashboard
                    if ($_SESSION['role'] === 'mandor') {
                            // Jika yang login adalah Mandor, langsung arahkan ke halaman Catat Absensi
                            header('Location: ' . BASE_URL . 'absensi/catat.php');
                        } else {
                            // Jika perannya bukan Mandor (yaitu Super Admin atau Admin),
                            // arahkan ke halaman Dashboard seperti biasa.
                            header('Location: ' . BASE_URL . 'dashboard.php');
                        }
                        exit; // Hentikan eksekusi skrip setelah redirect.
                } else {
                    // Pengguna ditemukan, password cocok, TAPI STATUSNYA TIDAK AKTIF (is_active = 0)
                    $_SESSION['pesan_error_login'] = "Akun Anda saat ini tidak aktif. Silakan hubungi Administrator."; // Pesan ini bisa ditampilkan di login.php
                    header('Location: ' . BASE_URL . 'auth/login.php?error=4'); // error=4 artinya akun tidak aktif
                    exit;
                }
                // --- AKHIR PENAMBAHAN LOGIKA PENTING ---

            } else {
                // Password TIDAK COCOK
                $_SESSION['pesan_error_login'] = "Username atau password salah.";
                header('Location: ' . BASE_URL . 'auth/login.php?error=1'); 
                exit;
            }
        } else {
            // User TIDAK DITEMUKAN dengan username tersebut
            $_SESSION['pesan_error_login'] = "Username atau password salah.";
            header('Location: ' . BASE_URL . 'auth/login.php?error=1'); 
            exit;
        }
    } else {
        // Terjadi error saat mempersiapkan SQL statement
        $_SESSION['pesan_error_login'] = "Terjadi kesalahan pada sistem. Silakan coba lagi nanti.";
        header('Location: ' . BASE_URL . 'auth/login.php?error=99'); // error=99 artinya error sistem
        exit;
    }

} else {
    // Jika halaman ini diakses bukan dengan metode POST, redirect ke halaman login
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}

?>