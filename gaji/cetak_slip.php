<?php
// File: proyek_jaya/gaji/cetak_slip.php
// Deskripsi: Halaman final untuk menampilkan dan mencetak slip gaji per pekerja.

require_once '../config.php';

// 1. Autentikasi & Autorisasi: HANYA Super Admin dan Admin
if (!isset($_SESSION['user_id'])) { header('Location: ' . BASE_URL . 'auth/login.php?error=2'); exit; }
$user_role = $_SESSION['role'];
if (!in_array($user_role, ['super_admin', 'admin'])) {
    // Di halaman cetak, kita cukup hentikan eksekusi daripada redirect
    die("Akses ditolak. Anda tidak memiliki izin untuk melihat halaman ini.");
}

// 2. Validasi & Ambil Data Gaji yang Spesifik
if (!isset($_GET['id_gaji']) || !is_numeric($_GET['id_gaji'])) {
    die("Error: ID Gaji tidak valid atau tidak ditemukan.");
}
$id_gaji = intval($_GET['id_gaji']);

// 3. Query untuk mengambil semua detail yang dibutuhkan untuk slip gaji
// ...existing code...
$sql = "SELECT
            g.*,
            p.namapekerja,
            j.namajabatan
        FROM gaji g
        JOIN pekerja p ON g.id_pekerja = p.id_pekerja
        JOIN jabatan j ON p.id_jabatan = j.id_jabatan
        WHERE g.id_gaji = ?";
// ...existing code...

$stmt = mysqli_prepare($koneksi, $sql);
$slip_data = null;
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $id_gaji);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $slip_data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

if (!$slip_data) {
    die("Data slip gaji dengan ID #$id_gaji tidak ditemukan.");
}

// Fungsi sederhana untuk format Rupiah
function format_rupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slip Gaji - <?php echo htmlspecialchars($slip_data['namapekerja']); ?> - <?php echo date('d M Y', strtotime($slip_data['periode_end'])); ?></title>
    <style>
        /* Gaya dasar untuk tampilan di layar dan cetak */
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; background-color: #f4f4f4; color: #333; }
        .page-container { display: flex; flex-direction: column; align-items: center; padding: 20px; }
        .slip-wrapper { width: 800px; background: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .header {
            display: flex;
            align-items: center;
            border-bottom: 2px solid #eee;
            padding-bottom: 20px;
            margin-bottom: 20px;
            /* gap: 20px; <--- Hapus atau komentar baris ini */
            position: relative; /* TAMBAHKAN INI */
            justify-content: flex-start; /* TAMBAHKAN INI, memastikan logo di kiri */
        }
        .header img {
            height: 50px;
            width: auto;
            border-radius: 4px;
            background-color: #fff;
            box-shadow: 0 0 4px rgba(0,0,0,0.1);
        }
        .header-text {
            display: flex;
            flex-direction: column;
            text-align: center; /* TAMBAHKAN INI untuk meratakan teks di dalamnya */
            position: absolute; /* TAMBAHKAN INI */
            left: 50%; /* TAMBAHKAN INI */
            transform: translateX(-50%); /* TAMBAHKAN INI */
            width: fit-content; /* TAMBAHKAN INI */
        }
        .header h1 { margin: 0; font-size: 24px; color: #1a202c; }
        .header p { margin: 5px 0 0; color: #718096; }
        .info-section { display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 14px; }
        .info-section div { flex-basis: 48%; }
        .info-section strong { display: inline-block; width: 120px; }
        .details-section { display: flex; justify-content: space-between; gap: 40px; border-top: 1px solid #eee; padding-top: 20px; }
        .details-section div { flex-basis: 48%; }
        .details-section h3 { font-size: 16px; color: #2d3748; border-bottom: 1px solid #eee; padding-bottom: 8px; margin-top: 0; }
        .details-section table { width: 100%; border-collapse: collapse; font-size: 14px; }
        .details-section td { padding: 8px 0; }
        .details-section .label { color: #4a5568; }
        .details-section .amount { text-align: right; font-weight: 500; }
        .total { font-weight: bold; color: #1a202c; }
        .footer { border-top: 1px solid #eee; margin-top: 30px; padding-top: 20px; text-align: center; }
        .gaji-bersih { background-color: #f7fafc; border: 1px solid #e2e8f0; padding: 15px; text-align: center; margin-bottom: 30px; }
        .gaji-bersih h2 { margin: 0; font-size: 16px; color: #4a5568; text-transform: uppercase; }
        .gaji-bersih p { margin: 5px 0 0; font-size: 28px; font-weight: bold; color: #2c5282; }
        .signatures { display: flex; justify-content: space-around; margin-top: 60px; }
        .signatures div { text-align: center; width: 200px; }
        .signatures .name { margin-top: 70px; border-top: 1px solid #ccc; padding-top: 5px; }
        .print-button { padding: 10px 20px; background-color: #2c5282; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; margin-bottom: 20px; }

        /* Gaya khusus untuk mode cetak */
        @media print {
            body { background-color: #fff; }
            .page-container { padding: 0; }
            .print-button { display: none; }
            .slip-wrapper { box-shadow: none; border: 1px solid #ccc; border-radius: 0; width: 100%; }
            .header {
                border-bottom: 1px solid #ccc;
                /* gap: 10px; <--- Hapus atau komentar baris ini */
                padding-bottom: 10px;
                margin-bottom: 15px;
                justify-content: flex-start; /* Tetap flex-start untuk cetak */
            }
            .header img {
                height: 40px;
            }
             .header-text { /* Pastikan centering juga berlaku untuk cetak */
                position: absolute;
                left: 50%;
                transform: translateX(-50%);
                text-align: center;
                width: fit-content;
            }
        }
    </style>
</head>
<body>
    <div class="page-container">
        <button onclick="window.print()" class="print-button">Cetak Halaman Ini</button>
        <div class="slip-wrapper">
            <div class="header">
                <img src="../assets/img/azrina_logo.png" alt="Logo projek Jaya Konstruksi minimalis with white background and subtle shadow" />
                <div class="header-text">
                    <h1>SLIP GAJI KARYAWAN</h1>
                </div>
            </div>
            <div class="info-section">
                <div>
                    <p><strong>Nama</strong>: <?php echo htmlspecialchars($slip_data['namapekerja']); ?></p>
                    <p><strong>Jabatan</strong>: <?php echo htmlspecialchars($slip_data['namajabatan']); ?></p>
                </div>
                <div>
                    <p><strong>Periode Gaji</strong>: <?php echo date('d M Y', strtotime($slip_data['periode_start'])) . ' - ' . date('d M Y', strtotime($slip_data['periode_end'])); ?></p>
                    <p><strong>Tanggal Cetak</strong>: <?php echo date('d M Y'); ?></p>
                </div>
            </div>
            <div class="details-section">
                <div>
                    <h3>RINCIAN PENDAPATAN</h3>
                    <table>
                        <tr><td class="label">Gaji Pokok (<?php echo $slip_data['total_hari_hadir']; ?> hari)</td><td class="amount"><?php echo format_rupiah($slip_data['gaji_pokok_bayar']); ?></td></tr>
                        <tr><td class="label">Upah Lembur (<?php echo $slip_data['total_lembur']; ?> hari)</td><td class="amount"><?php echo format_rupiah($slip_data['lembur_pay']); ?></td></tr>
                        <tr><td class="label">Tunjangan Transport</td><td class="amount"><?php echo format_rupiah($slip_data['tunjangan_transport_manual']); ?></td></tr>
                        <tr><td class="label">Tunjangan Kesehatan</td><td class="amount"><?php echo format_rupiah($slip_data['tunjangan_kesehatan_manual']); ?></td></tr>
                        <tr><td class="label">Tunjangan Lainnya</td><td class="amount"><?php echo format_rupiah($slip_data['tunjangan_rumah_manual']); ?></td></tr>
                        <tr class="total"><td class="label">Total Pendapatan (A)</td><td class="amount"><?php echo format_rupiah($slip_data['total_pendapatan_bruto']); ?></td></tr>
                    </table>
                </div>
                <div>
                    <h3>RINCIAN POTONGAN</h3>
                     <table>
                        <tr><td class="label">Potongan</td><td class="amount">(<?php echo format_rupiah($slip_data['total_potongan_manual']); ?>)</td></tr>
                        <tr class="total"><td class="label">Total Potongan (B)</td><td class="amount">(<?php echo format_rupiah($slip_data['total_potongan_manual']); ?>)</td></tr>
                    </table>
                </div>
            </div>
            <div class="footer">
                <div class="gaji-bersih">
                    <h2>GAJI BERSIH DITERIMA (A - B)</h2>
                    <p><?php echo format_rupiah($slip_data['total_gaji_netto']); ?></p>
                </div>
                <div class="signatures">
                    <div>
                        <p>Diterima oleh,</p>
                        <p class="name">( <?php echo htmlspecialchars($slip_data['namapekerja']); ?> )</p>
                    </div>
                    <div>
                        <p>Dibayarkan oleh,</p>
                        <p class="name">( <?php echo htmlspecialchars($_SESSION['username']); ?> )</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>