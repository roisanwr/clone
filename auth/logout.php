<?php
// File: auth/logout.php

// Panggil config.php untuk memastikan session sudah dimulai (via session_start() di config.php)
// dan untuk menggunakan konstanta BASE_URL untuk redirect.
require_once '../config.php';

// 1. Hapus semua variabel session yang sudah ter-set.
// Cara paling umum dan bersih adalah mengosongkan array $_SESSION.
$_SESSION = array();

// Alternatif lain (lebih tua, tapi masih valid): session_unset();
// session_unset(); // Ini akan menghapus semua variabel di dalam session saat ini.

// 2. Hancurkan session.
// Ini akan menghapus session itu sendiri dari server.
if (session_destroy()) {
    // 3. Jika session berhasil dihancurkan, redirect pengguna ke halaman login.
    // Kita bisa tambahkan parameter ?logout=1 untuk memberi tahu halaman login
    // bahwa pengguna baru saja logout (berguna jika mau menampilkan pesan "Anda berhasil logout").
    header('Location: ' . BASE_URL . 'auth/login.php?logout=1');
    exit;
} else {
    // Ini jarang terjadi, tapi sebagai jaga-jaga jika session_destroy() gagal.
    // Kamu bisa redirect ke halaman error atau kembali ke dashboard dengan pesan error.
    // Untuk sekarang, kita tampilkan pesan sederhana saja.
    echo "Error: Tidak dapat melakukan logout. Silakan coba lagi.";
    // Atau, jika session masih ada, mungkin redirect kembali ke dashboard:
    // header('Location: ' . BASE_URL . 'dashboard.php?error_logout=1');
    exit;
}

// Tidak ada kode HTML di sini, karena halaman ini murni untuk proses dan redirect.
?>