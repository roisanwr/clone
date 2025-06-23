<?php
// File: auth/proses_login.php (Versi Revisi - Tanpa Tabel Users)

require_once '../config.php'; // Panggil config di awal

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Ambil data dari form
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password_input = $_POST['password'];

    // 2. Validasi dasar
    if (empty($username) || empty($password_input)) {
        header('Location: ' . BASE_URL . 'auth/login.php?error=3'); // error=3 artinya field kosong
        exit;
    }

    // --- PERUBAHAN UTAMA DIMULAI DI SINI ---

    // 3. Query baru: Cari di tabel `pekerja` dan JOIN ke `jabatan`
    // Kita butuh `namapekerja` dan `namajabatan` untuk disimpan ke session.
    $sql = "SELECT 
                p.id_pekerja, p.username, p.password, p.namapekerja, p.is_active,
                j.namajabatan
            FROM pekerja p
            JOIN jabatan j ON p.id_jabatan = j.id_jabatan
            WHERE p.username = ?";
    
    $stmt = mysqli_prepare($koneksi, $sql);

    if ($stmt) {
        // 4. Bind parameter dan eksekusi
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $pekerja_data = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        // 5. Verifikasi user dan password
        if ($pekerja_data) {
            // Pekerja dengan username tersebut ditemukan. Sekarang verifikasi password.
            // Gunakan password_verify() untuk mencocokkan input dengan hash di database.
            if (password_verify($password_input, $pekerja_data['password'])) {
                // Password cocok!
                
                // 6. Cek status aktif pekerja
                if ($pekerja_data['is_active'] == 1) {
                    // Pekerja aktif, proses login berhasil. Set session.

                    // Hapus session lama jika ada, untuk kebersihan
                    session_unset();
                    session_destroy();
                    session_start();

                    // Set session baru berdasarkan data dari tabel `pekerja` dan `jabatan`
                    $_SESSION['user_id'] = $pekerja_data['id_pekerja']; // ID sekarang adalah id_pekerja
                    $_SESSION['username'] = $pekerja_data['username'];
                    
                    // PENTING: Role sekarang diambil dari nama jabatan.
                    // Pastikan nama jabatanmu konsisten (misal: 'super admin', 'admin', 'mandor')
                    // Kita ubah jadi huruf kecil dan ganti spasi dengan underscore agar konsisten.
                    $_SESSION['role'] = strtolower(str_replace(' ', '_', $pekerja_data['namajabatan']));
                    
                    // Simpan juga nama lengkap dan ID jabatan untuk referensi
                    $_SESSION['nama_lengkap'] = $pekerja_data['namapekerja'];
                    $_SESSION['id_jabatan'] = $pekerja_data['id_jabatan']; // Mungkin berguna nanti
                    
                    // Redirect berdasarkan role (nama jabatan)
                    if ($_SESSION['role'] === 'mandor') {
                        header('Location: ' . BASE_URL . 'absensi/catat.php');
                    } else {
                        // Untuk semua role lain (admin, super_admin, dll)
                        header('Location: ' . BASE_URL . 'dashboard.php');
                    }
                    exit;

                } else {
                    // Pekerja ditemukan tapi tidak aktif
                    $_SESSION['pesan_error_login'] = "Akun Anda saat ini tidak aktif. Hubungi Administrator.";
                    header('Location: ' . BASE_URL . 'auth/login.php?error=4'); // error=4 artinya akun tidak aktif
                    exit;
                }
            } else {
                // Password tidak cocok
                $_SESSION['pesan_error_login'] = "Username atau password salah.";
                header('Location: ' . BASE_URL . 'auth/login.php?error=1');
                exit;
            }
        } else {
            // Pekerja dengan username tersebut tidak ditemukan
            $_SESSION['pesan_error_login'] = "Username atau password salah.";
            header('Location: ' . BASE_URL . 'auth/login.php?error=1');
            exit;
        }
    } else {
        // Gagal mempersiapkan query
        $_SESSION['pesan_error_login'] = "Terjadi kesalahan pada sistem. Silakan coba lagi nanti.";
        header('Location: ' . BASE_URL . 'auth/login.php?error=99'); // error=99 artinya error sistem
        exit;
    }

} else {
    // Jika halaman diakses bukan dengan metode POST
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}

?>
