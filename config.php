<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// File: config.php (Simpan di folder root Proyek Jaya)

// --- 1. Pengaturan Database ---
// Sesuaikan nilai-nilai ini dengan pengaturan database MySQL kamu.
define('DB_HOST', 'localhost');         // Biasanya 'localhost' jika database di server yang sama.
define('DB_NAME', 'u951570841_kontraktor');              // Nama database kita yang sudah kita sepakati!
define('DB_USER', 'u951570841_ridwan');              // Ganti dengan username database MySQL kamu.
define('DB_PASS', 'Azrinaj4y4#');                  // Ganti dengan password database MySQL kamu (kosongkan jika tidak ada).

// Buat koneksi ke database menggunakan MySQLi
$koneksi = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Cek koneksi
if (!$koneksi) {
    // Jika koneksi gagal, tampilkan pesan error dan hentikan skrip.
    // Untuk tahap development, ini membantu kita tahu masalahnya.
    // Untuk production nanti, error handling-nya bisa lebih canggih (misal, dicatat ke log).
    die("KONEKSI KE DATABASE GAGAL: " . mysqli_connect_error() . " (Error Code: " . mysqli_connect_errno() . ")");
}

// Set karakter set koneksi ke utf8mb4 (direkomendasikan untuk mendukung berbagai karakter)
mysqli_set_charset($koneksi, "utf8mb4");

// --- 2. Pengaturan Dasar Aplikasi ---
// BASE_URL: Alamat dasar website Proyek Jaya kamu.
// Penting untuk membuat link, redirect, dan memanggil aset (CSS/JS/gambar) dengan benar.
// Sesuaikan dengan alamat di mana kamu akan mengakses proyek ini di browser.
// Contoh: jika diakses via http://localhost/proyek_jaya/, maka BASE_URL-nya seperti di bawah.
// JANGAN LUPA tanda slash ('/') di akhir!
define('BASE_URL', 'https://kontraktor.moku-meubel.com/'); // GANTI SESUAI ALAMAT PROYEKMU!

// --- 3. Session ---
// Memulai session jika belum ada session yang aktif.
// Session digunakan untuk menyimpan data pengguna yang login (seperti user_id, role, dll).
// File header.php dan dashboard.php kamu sudah punya session_start(),
// tapi menaruh ini di config.php (yang di-include pertama) memastikan session selalu siap.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- 4. Pengaturan Zona Waktu (Opsional tapi Baik) ---
// Mengatur zona waktu default untuk fungsi-fungsi tanggal dan waktu di PHP.
date_default_timezone_set('Asia/Jakarta');


/*
// (Opsional) Untuk debugging awal, kamu bisa uncomment baris di bawah ini.
// Setelah itu, comment atau hapus lagi.
if ($koneksi) {
    echo "File config.php berhasil dimuat! Koneksi ke database 'jaya' SUKSES! BASE_URL: " . BASE_URL;
} else {
    echo "File config.php berhasil dimuat, TAPI koneksi database GAGAL!";
}
*/

?>