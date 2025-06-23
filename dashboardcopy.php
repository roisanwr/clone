<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'auth/login.php?error=2'); 
    exit;
}

$allowed_roles = ['super_admin', 'admin'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    $_SESSION['pesan_error'] = "MAAF, ANDA TIDAK MEMILIKI HAK AKSES KE HALAMAN MANAJEMEN KLIEN.";
    header('Location: ' . BASE_URL . 'absensi/index.php');
    exit;
}

$user_role = $_SESSION['role'];


$page_title = "Dashboard";

// Query untuk Quick Stats
// 1. Pekerja hadir hari ini
$query_hadir = "SELECT COUNT(DISTINCT a.id_pekerja) as hadir,
                (SELECT COUNT(*) FROM pekerja WHERE is_active = 1) as total_pekerja
                FROM absensi a 
                WHERE a.tanggal = CURDATE() AND a.status_hadir = 1";
$result_hadir = mysqli_query($conn, $query_hadir);
$data_hadir = mysqli_fetch_assoc($result_hadir);

// 2. Proyek aktif
$query_proyek_aktif = "SELECT COUNT(*) as total FROM projek WHERE status = 'active'";
$result_proyek = mysqli_query($conn, $query_proyek_aktif);
$proyek_aktif = mysqli_fetch_assoc($result_proyek)['total'];

// 3. Proyek belum diabsen hari ini
$query_belum_absen = "SELECT COUNT(DISTINCT p.id_projek) as total
                      FROM projek p
                      WHERE p.status = 'active'
                      AND p.id_projek NOT IN (
                          SELECT DISTINCT id_projek 
                          FROM absensi 
                          WHERE tanggal = CURDATE()
                      )";
$result_belum_absen = mysqli_query($conn, $query_belum_absen);
$belum_absen = mysqli_fetch_assoc($result_belum_absen)['total'];

// 4. Total klien
$query_klien = "SELECT COUNT(*) as total FROM klien";
$result_klien = mysqli_query($conn, $query_klien);
$total_klien = mysqli_fetch_assoc($result_klien)['total'];

// 5. Proyek selesai bulan ini
$query_selesai = "SELECT COUNT(*) as total FROM projek 
                  WHERE status = 'completed' 
                  AND MONTH(tanggal_selesai_projek) = MONTH(CURDATE()) 
                  AND YEAR(tanggal_selesai_projek) = YEAR(CURDATE())";
$result_selesai = mysqli_query($conn, $query_selesai);
$proyek_selesai = mysqli_fetch_assoc($result_selesai)['total'];

// Query untuk Alert/Warning
$alerts = [];

// Alert 1: Proyek mendekati deadline (7 hari)
$query_deadline = "SELECT namaprojek, DATEDIFF(tanggal_selesai_projek, CURDATE()) as sisa_hari 
                   FROM projek 
                   WHERE status = 'active' 
                   AND tanggal_selesai_projek IS NOT NULL
                   AND DATEDIFF(tanggal_selesai_projek, CURDATE()) BETWEEN 0 AND 7
                   ORDER BY sisa_hari ASC";
$result_deadline = mysqli_query($conn, $query_deadline);
while ($row = mysqli_fetch_assoc($result_deadline)) {
    $alerts[] = [
        'type' => 'warning',
        'message' => "Proyek {$row['namaprojek']} deadline {$row['sisa_hari']} hari lagi"
    ];
}

// Alert 2: Pekerja belum punya penugasan
$query_no_assignment = "SELECT COUNT(*) as total FROM pekerja p
                        WHERE p.is_active = 1
                        AND p.id_pekerja NOT IN (
                            SELECT id_pekerja FROM proyek_pekerja 
                            WHERE is_active = 1
                        )";
$result_no_assignment = mysqli_query($conn, $query_no_assignment);
$no_assignment = mysqli_fetch_assoc($result_no_assignment)['total'];
if ($no_assignment > 0) {
    $alerts[] = [
        'type' => 'info',
        'message' => "{$no_assignment} pekerja belum punya penugasan aktif"
    ];
}

// Alert 3: Pekerja tidak masuk 3 hari berturut-turut
$query_absent = "SELECT p.namapekerja, 
                 COUNT(DISTINCT DATE(a.tanggal)) as hari_masuk
                 FROM pekerja p
                 JOIN proyek_pekerja pp ON p.id_pekerja = pp.id_pekerja
                 LEFT JOIN absensi a ON p.id_pekerja = a.id_pekerja 
                     AND a.tanggal >= DATE_SUB(CURDATE(), INTERVAL 3 DAY)
                     AND a.tanggal < CURDATE()
                     AND a.status_hadir = 1
                 WHERE pp.is_active = 1
                 GROUP BY p.id_pekerja
                 HAVING hari_masuk = 0";
$result_absent = mysqli_query($conn, $query_absent);
while ($row = mysqli_fetch_assoc($result_absent)) {
    $alerts[] = [
        'type' => 'danger',
        'message' => "{$row['namapekerja']} sudah 3 hari berturut-turut tidak masuk"
    ];
}

// Query untuk Tabel Proyek Aktif
$query_proyek_list = "SELECT p.*, k.nama_klien, 
                      pk.namapekerja as nama_mandor,
                      (SELECT COUNT(DISTINCT id_pekerja) FROM proyek_pekerja 
                       WHERE id_projek = p.id_projek AND is_active = 1) as jumlah_pekerja,
                      (SELECT COUNT(DISTINCT a.id_pekerja) FROM absensi a 
                       WHERE a.id_projek = p.id_projek AND a.tanggal = CURDATE() AND a.status_hadir = 1) as hadir_hari_ini
                      FROM projek p
                      LEFT JOIN klien k ON p.id_klien = k.id_klien
                      LEFT JOIN pekerja pk ON p.id_mandor_pekerja = pk.id_pekerja
                      WHERE p.status = 'active'
                      ORDER BY p.tanggal_mulai_projek DESC
                      LIMIT 5";
$result_proyek_list = mysqli_query($conn, $query_proyek_list);

// Query untuk Top 5 Klien
$query_top_klien = "SELECT k.nama_klien, COUNT(p.id_projek) as jumlah_proyek,
                    SUM(CASE WHEN p.status = 'active' THEN 1 ELSE 0 END) as proyek_aktif
                    FROM klien k
                    LEFT JOIN projek p ON k.id_klien = p.id_klien
                    GROUP BY k.id_klien
                    HAVING jumlah_proyek > 0
                    ORDER BY proyek_aktif DESC, jumlah_proyek DESC
                    LIMIT 5";
$result_top_klien = mysqli_query($conn, $query_top_klien);

// Query untuk Pekerja dengan kehadiran rendah
$query_low_attendance = "SELECT p.namapekerja, 
                         COUNT(DISTINCT CASE WHEN a.status_hadir = 1 THEN a.tanggal END) as hari_hadir,
                         COUNT(DISTINCT a.tanggal) as hari_kerja,
                         ROUND((COUNT(DISTINCT CASE WHEN a.status_hadir = 1 THEN a.tanggal END) / COUNT(DISTINCT a.tanggal)) * 100, 1) as persentase
                         FROM pekerja p
                         JOIN absensi a ON p.id_pekerja = a.id_pekerja
                         WHERE a.tanggal >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                         GROUP BY p.id_pekerja
                         HAVING persentase < 80 AND hari_kerja >= 5
                         ORDER BY persentase ASC
                         LIMIT 5";
$result_low_attendance = mysqli_query($conn, $query_low_attendance);

// Query untuk Absensi 7 hari terakhir
$query_7days = "SELECT tanggal,
                COUNT(DISTINCT CASE WHEN status_hadir = 1 THEN id_pekerja END) as hadir,
                COUNT(DISTINCT CASE WHEN status_hadir = 0 THEN id_pekerja END) as tidak_hadir,
                COUNT(DISTINCT id_pekerja) as total
                FROM absensi
                WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                AND tanggal <= CURDATE()
                GROUP BY tanggal
                ORDER BY tanggal DESC";
$result_7days = mysqli_query($conn, $query_7days);

require_once '/includes/header.php'; 
if ($user_role == 'super_admin') {
    require_once '/includes/sidebar_super_admin.php';
} elseif ($user_role == 'admin') {
    require_once '/includes/sidebar_admin.php';
}
?>




<div class="container mx-auto px-4 py-6">
    <!-- Header Dashboard -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Dashboard</h1>
        <p class="text-gray-600 mt-2">Selamat datang, <?php echo $_SESSION['nama']; ?>!</p>
    </div>

    <!-- Quick Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        <!-- Card 1: Pekerja Hadir -->
        <a href="absensi/index.php" class="block">
            <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
                <div class="flex items-center">
                    <div class="p-3 bg-green-100 rounded-full">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h2 class="text-2xl font-bold text-gray-800"><?php echo $data_hadir['hadir'] ?? 0; ?>/<?php echo $data_hadir['total_pekerja'] ?? 0; ?></h2>
                        <p class="text-gray-600 text-sm">Pekerja Hadir</p>
                        <p class="text-gray-500 text-xs">Hari Ini</p>
                    </div>
                </div>
            </div>
        </a>

        <!-- Card 2: Proyek Aktif -->
        <a href="proyek/index.php" class="block">
            <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-100 rounded-full">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h2 class="text-2xl font-bold text-gray-800"><?php echo $proyek_aktif; ?></h2>
                        <p class="text-gray-600 text-sm">Proyek Aktif</p>
                        <p class="text-gray-500 text-xs">Sedang Jalan</p>
                    </div>
                </div>
            </div>
        </a>

        <!-- Card 3: Belum Diabsen -->
        <a href="absensi/index.php" class="block">
            <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
                <div class="flex items-center">
                    <div class="p-3 bg-yellow-100 rounded-full">
                        <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h2 class="text-2xl font-bold text-gray-800"><?php echo $belum_absen; ?></h2>
                        <p class="text-gray-600 text-sm">Belum Diabsen</p>
                        <p class="text-gray-500 text-xs">Proyek Hari Ini</p>
                    </div>
                </div>
            </div>
        </a>

        <!-- Card 4: Total Pekerja -->
        <a href="pekerja/index.php" class="block">
            <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
                <div class="flex items-center">
                    <div class="p-3 bg-purple-100 rounded-full">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h2 class="text-2xl font-bold text-gray-800"><?php echo $data_hadir['total_pekerja'] ?? 0; ?></h2>
                        <p class="text-gray-600 text-sm">Total Pekerja</p>
                        <p class="text-gray-500 text-xs">Terdaftar</p>
                    </div>
                </div>
            </div>
        </a>

        <!-- Card 5: Total Klien -->
        <a href="klien/index.php" class="block">
            <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
                <div class="flex items-center">
                    <div class="p-3 bg-indigo-100 rounded-full">
                        <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h2 class="text-2xl font-bold text-gray-800"><?php echo $total_klien; ?></h2>
                        <p class="text-gray-600 text-sm">Total Klien</p>
                        <p class="text-gray-500 text-xs">Perusahaan</p>
                    </div>
                </div>
            </div>
        </a>

        <!-- Card 6: Proyek Selesai -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 bg-teal-100 rounded-full">
                    <svg class="w-8 h-8 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <h2 class="text-2xl font-bold text-gray-800"><?php echo $proyek_selesai; ?></h2>
                    <p class="text-gray-600 text-sm">Selesai Bulan Ini</p>
                    <p class="text-gray-500 text-xs">Proyek</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert/Warning Box -->
    <?php if (!empty($alerts)): ?>
    <div class="mb-8 space-y-2">
        <?php foreach ($alerts as $alert): ?>
        <div class="alert alert-<?php echo $alert['type']; ?> p-4 rounded-lg flex items-center
            <?php 
            switch($alert['type']) {
                case 'warning': echo 'bg-yellow-100 text-yellow-800'; break;
                case 'danger': echo 'bg-red-100 text-red-800'; break;
                case 'info': echo 'bg-blue-100 text-blue-800'; break;
            }
            ?>">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <?php echo htmlspecialchars($alert['message']); ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <div class="mb-8 flex gap-4">
        <a href="proyek/tambah.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Tambah Proyek Baru
        </a>
        <a href="absensi/index.php" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
            </svg>
            Kelola Absensi
        </a>
        <a href="pekerja/tambah.php" class="bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
            </svg>
            Tambah Pekerja
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Tabel Proyek Aktif -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b">
                <h2 class="text-xl font-bold text-gray-800">Proyek Aktif</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Proyek</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mandor</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Tim</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Absensi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php while ($proyek = mysqli_fetch_assoc($result_proyek_list)): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($proyek['namaprojek']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($proyek['nama_klien']); ?></div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo htmlspecialchars($proyek['nama_mandor'] ?? '-'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-900">
                                <?php echo $proyek['jumlah_pekerja']; ?> orang
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <?php if ($proyek['hadir_hari_ini'] > 0): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        ✅ <?php echo $proyek['hadir_hari_ini']; ?>/<?php echo $proyek['jumlah_pekerja']; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                        ❌ Belum
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top 5 Klien -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b">
                <h2 class="text-xl font-bold text-gray-800">Top 5 Klien Aktif</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Klien</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Total Proyek</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Proyek Aktif</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php while ($klien = mysqli_fetch_assoc($result_top_klien)): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($klien['nama_klien']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-900">
                                <?php echo $klien['jumlah_proyek']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                    <?php echo $klien['proyek_aktif']; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Pekerja dengan Kehadiran Rendah -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b">
                <h2 class="text-xl font-bold text-gray-800">Pekerja dengan Kehadiran Rendah (30 Hari)</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Kehadiran</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Persentase</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php 
                        $has_low_attendance = false;
                        while ($pekerja = mysqli_fetch_assoc($result_low_attendance)): 
                            $has_low_attendance = true;
                        ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($pekerja['namapekerja']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-900">
                                <?php echo $pekerja['hari_hadir']; ?>/<?php echo $pekerja['hari_kerja']; ?> hari
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                    <?php echo $pekerja['persentase']; ?>%
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if (!$has_low_attendance): ?>
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">
                                Semua pekerja memiliki kehadiran baik (>80%)
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Statistik Absensi 7 Hari -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b">
                <h2 class="text-xl font-bold text-gray-800">Absensi 7 Hari Terakhir</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Hadir</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Tidak Hadir</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">%</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php while ($hari = mysqli_fetch_assoc($result_7days)): 
                            $persentase = $hari['total'] > 0 ? round(($hari['hadir'] / $hari['total']) * 100, 1) : 0;
                        ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php 
                                $hari_indonesia = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                                $tanggal = date('d/m', strtotime($hari['tanggal']));
                                $nama_hari = $hari_indonesia[date('w', strtotime($hari['tanggal']))];
                                echo $nama_hari . ', ' . $tanggal;
                                ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-900">
                                <?php echo $hari['hadir']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-900">
                                <?php echo $hari['tidak_hadir']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?php echo $persentase >= 80 ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                    <?php echo $persentase; ?>%
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
