<?php
// File: proyek_jaya/penugasan/proses.php (Versi Perbaikan)

require_once '../config.php';

// 1. Autentikasi
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'auth/login.php?error=2');
    exit;
}
$current_user_role = $_SESSION['role'];
$current_id_pekerja_ref_mandor = $_SESSION['id_pekerja_ref'] ?? null;

// 2. Routing Aksi
if (isset($_GET['aksi'])) {
    
    // ===================================================================
    // --- AKSI TAMBAH PENUGASAN (MULTIPLE WORKERS) ---
    // ===================================================================
    if (($_GET['aksi'] == 'tambah' || $_GET['aksi'] == 'tambah_multiple') && isset($_GET['id_projek']) && is_numeric($_GET['id_projek'])) {
        
        $id_projek_penugasan = intval($_GET['id_projek']);

        // Otorisasi: Pastikan pengguna berhak menugaskan pekerja ke proyek ini
        // ... (Logika otorisasi $can_assign tetap sama seperti kodemu, sudah bagus) ...
        $sql_cek_proyek_auth = "SELECT id_mandor_pekerja FROM projek WHERE id_projek = ?";
        $stmt_cek_auth = mysqli_prepare($koneksi, $sql_cek_proyek_auth);
        $can_assign = false;
        if ($stmt_cek_auth) {
            mysqli_stmt_bind_param($stmt_cek_auth, "i", $id_projek_penugasan);
            mysqli_stmt_execute($stmt_cek_auth);
            $proyek_auth_detail = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_cek_auth));
            mysqli_stmt_close($stmt_cek_auth);
            if ($proyek_auth_detail) {
                if (in_array($current_user_role, ['super_admin', 'admin']) || ($current_user_role === 'mandor' && $proyek_auth_detail['id_mandor_pekerja'] == $current_id_pekerja_ref_mandor)) {
                    $can_assign = true;
                }
            }
        }
        if (!$can_assign) {
            $_SESSION['pesan_error_crud'] = "ANDA TIDAK BERHAK MENUGASKAN PEKERJA KE PROYEK INI.";
            header('Location: ' . BASE_URL . 'penugasan/detail_tim.php?id_projek=' . $id_projek_penugasan);
            exit;
        }

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            // ... (Kode untuk ambil data POST dan validasi awal tetap sama) ...
            $tanggal_mulai = isset($_POST['tanggal_mulai_penugasan']) && !empty($_POST['tanggal_mulai_penugasan']) ? $_POST['tanggal_mulai_penugasan'] : null;
            $tanggal_akhir = isset($_POST['tanggal_akhir_penugasan']) && !empty($_POST['tanggal_akhir_penugasan']) ? $_POST['tanggal_akhir_penugasan'] : null;
            $array_id_pekerja = isset($_POST['id_pekerja']) && is_array($_POST['id_pekerja']) ? array_filter($_POST['id_pekerja']) : [];
            if (empty($tanggal_mulai) || empty($array_id_pekerja) || ($tanggal_mulai && $tanggal_akhir && strtotime($tanggal_akhir) <= strtotime($tanggal_mulai))) {
                $_SESSION['pesan_error_crud'] = "Data tidak valid. Pastikan tanggal mulai dan minimal satu pekerja dipilih, serta urutan tanggal benar.";
                header('Location: ' . BASE_URL . 'penugasan/tambah.php?id_projek=' . $id_projek_penugasan);
                exit;
            }

            $created_by = ($current_user_role === 'mandor') ? $current_id_pekerja_ref_mandor : NULL;
            $berhasil_ditambahkan = 0;
            $pesan_gagal = [];

            // Siapkan SEMUA statement SQL di luar loop
            $sql_insert_penugasan = "INSERT INTO proyek_pekerja (id_projek, id_pekerja, tanggal_mulai_penugasan, tanggal_akhir_penugasan, created_by_mandor_id, is_active) VALUES (?, ?, ?, ?, ?, 1)";
            $stmt_insert = mysqli_prepare($koneksi, $sql_insert_penugasan);
            
            $sql_cek_bentrok = "SELECT pr.namaprojek FROM proyek_pekerja pp JOIN projek pr ON pp.id_projek = pr.id_projek WHERE pp.id_pekerja = ? AND pp.is_active = 1 AND ? <= IFNULL(pp.tanggal_akhir_penugasan, '9999-12-31') AND IFNULL(?, '1970-01-01') > pp.tanggal_mulai_penugasan";
            $stmt_cek_bentrok = mysqli_prepare($koneksi, $sql_cek_bentrok);
            
            // --- PERBAIKAN DI SINI ---
            // Kita siapkan statement untuk mengambil nama pekerja di luar loop juga.
            $sql_get_nama = "SELECT namapekerja FROM pekerja WHERE id_pekerja = ?";
            $stmt_get_nama = mysqli_prepare($koneksi, $sql_get_nama);

            if ($stmt_insert && $stmt_cek_bentrok && $stmt_get_nama) { // Pastikan semua statement berhasil dibuat
                foreach ($array_id_pekerja as $id_pek) {
                    $id_pekerja_saat_ini = intval($id_pek);
                    if (empty($id_pekerja_saat_ini)) continue; 

                    // Ambil nama pekerja untuk pesan yang lebih baik
                    $nama_pekerja_saat_ini = "Pekerja (ID: $id_pekerja_saat_ini)";
                    // Kita gunakan statement yang sudah disiapkan
                    mysqli_stmt_bind_param($stmt_get_nama, "i", $id_pekerja_saat_ini);
                    mysqli_stmt_execute($stmt_get_nama);
                    $result_nama = mysqli_stmt_get_result($stmt_get_nama);
                    if ($data_nama = mysqli_fetch_assoc($result_nama)) {
                        $nama_pekerja_saat_ini = $data_nama['namapekerja'];
                    }
                    // Tidak perlu close stmt_get_nama di sini, kita pakai ulang di loop

                    // CEK JADWAL BENTROK
                    $tanggal_akhir_untuk_cek = $tanggal_akhir ?? '9999-12-31';
                    mysqli_stmt_bind_param($stmt_cek_bentrok, "iss", $id_pekerja_saat_ini, $tanggal_mulai, $tanggal_akhir_untuk_cek);
                    mysqli_stmt_execute($stmt_cek_bentrok);
                    $result_bentrok = mysqli_stmt_get_result($stmt_cek_bentrok);
                    if ($data_bentrok = mysqli_fetch_assoc($result_bentrok)) {
                        $pesan_gagal[] = "<strong>" . htmlspecialchars($nama_pekerja_saat_ini) . "</strong>: sudah ada tugas di Proyek '" . htmlspecialchars($data_bentrok['namaprojek']) . "' pada periode yang tumpang tindih.";
                        continue; 
                    }

                    // Jika tidak bentrok, lakukan INSERT
                    mysqli_stmt_bind_param($stmt_insert, "iissi", $id_projek_penugasan, $id_pekerja_saat_ini, $tanggal_mulai, $tanggal_akhir, $created_by);
                    if (mysqli_stmt_execute($stmt_insert)) {
                        $berhasil_ditambahkan++;
                    } else {
                         $pesan_gagal[] = "<strong>" . htmlspecialchars($nama_pekerja_saat_ini) . "</strong>: Gagal ditugaskan karena error database.";
                    }
                }
                // Tutup SEMUA statement setelah loop selesai
                mysqli_stmt_close($stmt_insert);
                mysqli_stmt_close($stmt_cek_bentrok);
                mysqli_stmt_close($stmt_get_nama); // Tutup statement get_nama di sini

                // Siapkan pesan notifikasi final
                if ($berhasil_ditambahkan > 0) {
                    $_SESSION['pesan_sukses'] = "$berhasil_ditambahkan pekerja berhasil ditugaskan.";
                }
                if (!empty($pesan_gagal)) {
                    $pesan_gagal_html = "Beberapa penugasan GAGAL:<ul class='list-disc list-inside mt-2 text-sm'>";
                    foreach ($pesan_gagal as $detail_error) {
                        $pesan_gagal_html .= "<li>$detail_error</li>";
                    }
                    $pesan_gagal_html .= "</ul>";
                    $_SESSION['pesan_error_crud'] = ($_SESSION['pesan_error_crud'] ?? '') . $pesan_gagal_html;
                }

            } else {
                $_SESSION['pesan_error_crud'] = "Terjadi kesalahan sistem (gagal mempersiapkan salah satu statement SQL).";
            }
            
            header('Location: ' . BASE_URL . 'penugasan/detail_tim.php?id_projek=' . $id_projek_penugasan);
            exit;
        }
    } 
    // ===================================================================
    // --- LOGIKA UNTUK AKSI EDIT PENUGASAN ---
    // ===================================================================
    elseif ($_GET['aksi'] == 'edit' && isset($_GET['id_penugasan']) && is_numeric($_GET['id_penugasan'])) {
        
        // Autorisasi khusus untuk edit (sudah kamu buat, tetap sama)
        // ...

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            
            $id_penugasan_edit = intval($_GET['id_penugasan']);
            // Ambil juga id_projek dari GET untuk redirect kembali
            $id_projek_terkait = intval($_GET['id_projek']);

            // Ambil data dari form edit
            $tanggal_mulai_baru = isset($_POST['tanggal_mulai_penugasan']) && !empty($_POST['tanggal_mulai_penugasan']) ? $_POST['tanggal_mulai_penugasan'] : null;
            $tanggal_akhir_baru = isset($_POST['tanggal_akhir_penugasan']) && !empty($_POST['tanggal_akhir_penugasan']) ? $_POST['tanggal_akhir_penugasan'] : null;
            $is_active_baru = isset($_POST['is_active']) ? intval($_POST['is_active']) : 0;
            
            // Validasi dasar
            if (empty($tanggal_mulai_baru) || !in_array($is_active_baru, [0, 1])) {
                $_SESSION['pesan_error_crud'] = "Tanggal mulai dan status penugasan wajib diisi dengan benar.";
                header('Location: ' . BASE_URL . 'penugasan/edit.php?id_penugasan=' . $id_penugasan_edit . '&id_projek=' . $id_projek_terkait);
                exit;
            }
            if ($tanggal_mulai_baru && $tanggal_akhir_baru && strtotime($tanggal_akhir_baru) <= strtotime($tanggal_mulai_baru)) {
                $_SESSION['pesan_error_crud'] = "Tanggal akhir tugas tidak boleh sebelum tanggal mulai.";
                header('Location: ' . BASE_URL . 'penugasan/edit.php?id_penugasan=' . $id_penugasan_edit . '&id_projek=' . $id_projek_terkait);
                exit;
            }

            // --- AWAL MODIFIKASI: CEK JADWAL BENTROK SEBELUM UPDATE ---

            // 1. Dapatkan dulu id_pekerja dari penugasan yang sedang diedit ini
            $sql_get_pekerja_id = "SELECT id_pekerja FROM proyek_pekerja WHERE id_penugasan = ?";
            $stmt_get_pek_id = mysqli_prepare($koneksi, $sql_get_pekerja_id);
            mysqli_stmt_bind_param($stmt_get_pek_id, "i", $id_penugasan_edit);
            mysqli_stmt_execute($stmt_get_pek_id);
            $result_pek_id = mysqli_stmt_get_result($stmt_get_pek_id);
            $data_pekerja = mysqli_fetch_assoc($result_pek_id);
            mysqli_stmt_close($stmt_get_pek_id);

            if (!$data_pekerja) {
                $_SESSION['pesan_error_crud'] = "Penugasan yang akan diedit tidak valid.";
                header('Location: ' . BASE_URL . 'penugasan/detail_tim.php?id_projek=' . $id_projek_terkait);
                exit;
            }
            $id_pekerja_diedit = $data_pekerja['id_pekerja'];

            // 2. Cek jadwal bentrok dengan penugasan LAIN (id_penugasan != id_penugasan_edit)
            $tanggal_akhir_untuk_cek_edit = $tanggal_akhir_baru ?? '9999-12-31';
            $sql_cek_bentrok_edit = "SELECT pr.namaprojek FROM proyek_pekerja pp JOIN projek pr ON pp.id_projek = pr.id_projek WHERE pp.id_pekerja = ? AND pp.id_penugasan != ? AND pp.is_active = 1 AND ? <= IFNULL(pp.tanggal_akhir_penugasan, '9999-12-31') AND ? > pp.tanggal_mulai_penugasan";
            $stmt_cek_bentrok_edit = mysqli_prepare($koneksi, $sql_cek_bentrok_edit);
            mysqli_stmt_bind_param($stmt_cek_bentrok_edit, "iiss", $id_pekerja_diedit, $id_penugasan_edit, $tanggal_mulai_baru, $tanggal_akhir_untuk_cek_edit);
            mysqli_stmt_execute($stmt_cek_bentrok_edit);
            $result_bentrok_edit = mysqli_stmt_get_result($stmt_cek_bentrok_edit);
            
            if ($data_bentrok_edit = mysqli_fetch_assoc($result_bentrok_edit)) {
                $_SESSION['pesan_error_crud'] = "Jadwal GAGAL diupdate. Jadwal baru tumpang tindih dengan tugas di Proyek '" . htmlspecialchars($data_bentrok_edit['namaprojek']) . "'.";
                header('Location: ' . BASE_URL . 'penugasan/edit.php?id_penugasan=' . $id_penugasan_edit . '&id_projek=' . $id_projek_terkait);
                exit;
            }
            mysqli_stmt_close($stmt_cek_bentrok_edit);

            // --- AKHIR MODIFIKASI: CEK JADWAL BENTROK SEBELUM UPDATE ---

            // 3. Jika tidak ada jadwal bentrok, baru lakukan UPDATE
            $sql_update = "UPDATE proyek_pekerja SET tanggal_mulai_penugasan = ?, tanggal_akhir_penugasan = ?, is_active = ? WHERE id_penugasan = ?";
            $stmt_update = mysqli_prepare($koneksi, $sql_update);
            if ($stmt_update) {
                mysqli_stmt_bind_param($stmt_update, "ssii", $tanggal_mulai_baru, $tanggal_akhir_baru, $is_active_baru, $id_penugasan_edit);
                if (mysqli_stmt_execute($stmt_update)) {
                    $_SESSION['pesan_sukses'] = "Data penugasan berhasil diperbarui!";
                } else {
                    $_SESSION['pesan_error_crud'] = "GAGAL memperbarui data penugasan.";
                }
                mysqli_stmt_close($stmt_update);
            } else {
                $_SESSION['pesan_error_crud'] = "Terjadi kesalahan pada sistem (gagal mempersiapkan statement SQL untuk update penugasan).";
            }
            
            header('Location: ' . BASE_URL . 'penugasan/detail_tim.php?id_projek=' . $id_projek_terkait);
            exit;
        }
    }
    // ===================================================================
    // --- LOGIKA UNTUK AKSI HAPUS (BATALKAN) PENUGASAN ---
    // ===================================================================
    elseif (isset($_GET['aksi']) && $_GET['aksi'] == 'hapus' && isset($_GET['id_penugasan']) && is_numeric($_GET['id_penugasan'])) {
        
        $id_penugasan_hapus = intval($_GET['id_penugasan']);
        // Kita butuh id_projek untuk redirect kembali ke halaman detail tim yang benar
        $id_projek_redirect = isset($_GET['id_projek']) ? intval($_GET['id_projek']) : 0;

        // Otorisasi: Pastikan pengguna berhak menghapus penugasan ini
        // Kita perlu tahu id_projek dan id_mandor_pekerja dari penugasan ini
        $sql_auth_hapus = "SELECT pr.id_projek, pr.id_mandor_pekerja FROM proyek_pekerja pp JOIN projek pr ON pp.id_projek = pr.id_projek WHERE pp.id_penugasan = ?";
        $stmt_auth_hapus = mysqli_prepare($koneksi, $sql_auth_hapus);
        $can_delete = false;
        if ($stmt_auth_hapus) {
            mysqli_stmt_bind_param($stmt_auth_hapus, "i", $id_penugasan_hapus);
            mysqli_stmt_execute($stmt_auth_hapus);
            $penugasan_auth_detail = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_auth_hapus));
            mysqli_stmt_close($stmt_auth_hapus);

            if ($penugasan_auth_detail) {
                if ($id_projek_redirect === 0) $id_projek_redirect = $penugasan_auth_detail['id_projek']; // Fallback untuk id_projek
                if (in_array($current_user_role, ['super_admin', 'admin']) || ($current_user_role === 'mandor' && $penugasan_auth_detail['id_mandor_pekerja'] == $current_id_pekerja_ref_mandor)) {
                    $can_delete = true;
                }
            }
        }

        if (!$can_delete) {
            $_SESSION['pesan_error_crud'] = "ANDA TIDAK BERHAK MEMBATALKAN PENUGASAN INI.";
            header('Location: ' . ($id_projek_redirect > 0 ? BASE_URL . 'penugasan/detail_tim.php?id_projek=' . $id_projek_redirect : BASE_URL . 'dashboard.php'));
            exit;
        }

        // Jika lolos otorisasi, lanjutkan proses menonaktifkan
        $sql_batal = "UPDATE proyek_pekerja SET is_active = 0 WHERE id_penugasan = ?";
        $stmt_batal = mysqli_prepare($koneksi, $sql_batal);
        if ($stmt_batal) {
            mysqli_stmt_bind_param($stmt_batal, "i", $id_penugasan_hapus);
            if (mysqli_stmt_execute($stmt_batal) && mysqli_stmt_affected_rows($stmt_batal) > 0) {
                $_SESSION['pesan_sukses'] = "Penugasan pekerja berhasil dibatalkan/dinonaktifkan.";
            } else {
                $_SESSION['pesan_error_crud'] = "GAGAL membatalkan penugasan atau data tidak ditemukan.";
            }
            mysqli_stmt_close($stmt_batal);
        } else {
             $_SESSION['pesan_error_crud'] = "Terjadi kesalahan sistem (gagal mempersiapkan statement SQL untuk hapus penugasan).";
        }
        
        // Redirect kembali ke halaman detail tim
        if ($id_projek_redirect > 0) {
            header('Location: ' . BASE_URL . 'penugasan/detail_tim.php?id_projek=' . $id_projek_redirect);
        } else {
            // Fallback jika id_projek tidak bisa ditentukan, kembali ke dashboard
            header('Location: ' . BASE_URL . 'dashboard.php');
        }
        exit;
    }
    else {
        // Jika aksi tidak dikenali
        $_SESSION['pesan_error_crud'] = "Aksi tidak valid atau tidak dikenali.";
        header('Location: ' . BASE_URL . 'dashboard.php');
        exit;
    }
} else { 
    $_SESSION['pesan_error_crud'] = "Permintaan tidak lengkap atau tidak valid.";
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}
?>