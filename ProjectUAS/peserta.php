<?php
session_start();
require 'supabase.php';
require 'mailer.php';
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$success_message = $_SESSION['success_message'] ?? null;
unset($_SESSION['success_message']);
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['error_message']);

$daftar_status_administratif = [
    'Menunggu Verifikasi',
    'Berkas Tidak Lengkap',
    'Lulus Administrasi',
    'Lulus',
    'Tidak Lulus'
];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $redirect_url = $_SERVER['PHP_SELF'] . '?' . http_build_query(array_filter($_GET));

    // AKSI 1: Luluskan administrasi peserta yang dipilih (bulk)
    if (isset($_POST['luluskan_terpilih'])) {
        $ids_to_update = $_POST['pendaftar_ids'] ?? [];
        if (!empty($ids_to_update)) {
            $quoted_ids = [];
            foreach ($ids_to_update as $id) {
                $quoted_ids[] = '"' . trim($id) . '"';
            }
            $id_filter_string = implode(',', $quoted_ids);
            $get_endpoint = "/rest/v1/pendaftaran?id=in.($id_filter_string)&select=nama_lengkap,email";
            $peserta_to_notify_result = supabase_request("GET", $get_endpoint);
            $patch_endpoint = "/rest/v1/pendaftaran?id=in.($id_filter_string)";
            $update_result = supabase_request("PATCH", $patch_endpoint, ['status' => 'Lulus Administrasi']);
            if (!isset($update_result['error'])) {
                if ($peserta_to_notify_result['data']) {
                    $subject = "Selamat! Anda Lulus Seleksi Administrasi";
                    foreach ($peserta_to_notify_result['data'] as $p) {
                        if (!empty($p['email'])) {
                            send_email($p['email'], $subject, "<p>Yth. ".htmlspecialchars($p['nama_lengkap']).",</p><p>Selamat! Berkas Anda telah diverifikasi dan Anda dinyatakan <b>Lulus Seleksi Administrasi</b>. Silakan tunggu informasi jadwal ujian selanjutnya.</p>");
                        }
                    }
                }
                $_SESSION['success_message'] = count($ids_to_update) . " peserta berhasil diluluskan administrasinya.";
            } else {
                $_SESSION['error_message'] = "Gagal mengupdate status: " . ($update_result['error']['message'] ?? 'Unknown Error');
            }
        } else {
            $_SESSION['error_message'] = "Tidak ada peserta yang dipilih.";
        }
    }

    // AKSI 2: Mengubah status administrasi per individu
    elseif (isset($_POST['update_status_individu'])) {
        $id = $_POST['update_status_individu'];
        $allowed_statuses = ['Menunggu Verifikasi', 'Berkas Tidak Lengkap', 'Lulus Administrasi'];

        if (isset($_POST['status_individu'][$id]) && in_array($_POST['status_individu'][$id], $allowed_statuses)) {
            $new_status = $_POST['status_individu'][$id];
            $peserta_result = supabase_request("GET", "/rest/v1/pendaftaran?id=eq.$id&select=nama_lengkap,email");
            $peserta_data = $peserta_result['data'][0] ?? null;
            $update_result = supabase_request("PATCH", "/rest/v1/pendaftaran?id=eq.$id", ['status' => $new_status]);

            if (!isset($update_result['error'])) {
                $_SESSION['success_message'] = "Status untuk ".htmlspecialchars($peserta_data['nama_lengkap'] ?? 'peserta')." berhasil diubah menjadi '$new_status'. Notifikasi email telah dikirim.";
                if ($peserta_data && !empty($peserta_data['email'])) {
                    $subject = "Pembaruan Status Pendaftaran Anda";
                    $body = "<p>Yth. " . htmlspecialchars($peserta_data['nama_lengkap']) . ",</p>" .
                            "<p>Status pendaftaran Anda telah diperbarui oleh admin menjadi: <strong>" . htmlspecialchars($new_status) . "</strong>.</p>" .
                            "<p>Silakan login ke dashboard Anda untuk melihat detailnya. Terima kasih.</p>";
                    send_email($peserta_data['email'], $subject, $body);
                }
            } else {
                $_SESSION['error_message'] = "Gagal mengubah status: " . ($update_result['error']['message'] ?? 'Unknown Error');
            }
        } else {
            $_SESSION['error_message'] = "Status yang dipilih tidak valid atau tidak ditemukan.";
        }
    }

    // AKSI 3: Mengupdate nilai individu
    elseif (isset($_POST['update_nilai_individu'])) {
        $id = $_POST['update_nilai_individu'];
        $nilai = $_POST['nilai'][$id] === '' ? null : floatval($_POST['nilai'][$id]);

        $pendaftaran_result = supabase_request("GET", "/rest/v1/pendaftaran?id=eq.$id&select=*,jadwal_seleksi(*),email,nama_lengkap");

        if ($pendaftaran_result['data']) {
            $p = $pendaftaran_result['data'][0];
            $jadwal = $p['jadwal_seleksi'] ?? null;
            $jadwal_id = $p['jadwal_id'] ?? null;

            if (!$jadwal && $nilai !== null) {
                $kategori = $p['kategori'];
                $endpoint_jadwal = "/rest/v1/jadwal_seleksi?kategori=eq.$kategori&order=tanggal.desc&limit=1";
                $jadwal_result = supabase_request("GET", $endpoint_jadwal);
                if ($jadwal_result['data']) {
                    $jadwal = $jadwal_result['data'][0];
                    $jadwal_id = $jadwal['id'];
                }
            }

            $status = 'Sudah Diuji';
            if ($jadwal && isset($jadwal['nilai_min_lulus']) && $nilai !== null) {
                $status = $nilai >= floatval($jadwal['nilai_min_lulus']) ? 'Lulus' : 'Tidak Lulus';
            }

            $update_data = ['nilai' => $nilai, 'status' => $status];
            if ($jadwal_id && $jadwal_id !== $p['jadwal_id']) {
                $update_data['jadwal_id'] = $jadwal_id;
            }

            supabase_request("PATCH", "/rest/v1/pendaftaran?id=eq.$id", $update_data);

            if (!empty($p['email'])) {
                $subject = "Pembaruan Hasil Seleksi Anda";
                $body = "<p>Yth. <strong>".htmlspecialchars($p['nama_lengkap'])."</strong>,</p><p>Hasil seleksi Anda telah diperbarui. Nilai Akhir: <strong>".($nilai ?? 'N/A')."</strong>, Status Kelulusan: <strong>".htmlspecialchars($status)."</strong>.</p>";
                send_email($p['email'], $subject, $body);
                $_SESSION['success_message'] = "Nilai untuk ".htmlspecialchars($p['nama_lengkap'])." berhasil diupdate dan notifikasi dikirim.";
            } else {
                $_SESSION['success_message'] = "Nilai untuk ".htmlspecialchars($p['nama_lengkap'])." berhasil diupdate.";
            }
        } else {
            $_SESSION['error_message'] = "Gagal menemukan data peserta.";
        }
    }
    
    // AKSI 4: Menghapus peserta
    elseif (isset($_POST['hapus_peserta'])) {
        $id = $_POST['hapus_peserta'];
        supabase_request("DELETE", "/rest/v1/pendaftaran?id=eq.$id");
        $_SESSION['success_message'] = "Peserta berhasil dihapus.";
    }

    header('Location: ' . $redirect_url);
    exit;
}

// Logika untuk filter dan paginasi
$items_per_page = 10;
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;
$filter_values = ['filter' => trim($_GET['filter'] ?? ''), 'kategori' => trim($_GET['kategori'] ?? ''), 'status' => trim($_GET['status'] ?? '')];
$filter_params = [];
if($filter_values['filter']) $filter_params[] = "nama_lengkap=ilike.*{$filter_values['filter']}*";
if($filter_values['kategori']) $filter_params[] = "kategori=eq.{$filter_values['kategori']}";
if ($filter_values['status']) {
    $encoded_status = urlencode($filter_values['status']);
    $filter_params[] = "status=eq.$encoded_status";
}
$query_string = implode('&', $filter_params);
$count_header = ['Prefer: count=exact'];
$count_result = supabase_request_with_headers("GET", "/rest/v1/pendaftaran?" . $query_string, null, $count_header);
$total_items = $count_result['count'] ?? 0;
$total_pages = ceil($total_items / $items_per_page);
$range_header = ["Range: $offset-" . ($offset + $items_per_page - 1)];
$pendaftar_result = supabase_request_with_headers("GET", "/rest/v1/pendaftaran?select=*,jadwal_seleksi(nama,tanggal)&" . $query_string . "&order=created_at.desc", null, $range_header);
$pendaftar = $pendaftar_result['data'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Data Peserta (Admin)</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<h2>Data Peserta</h2>
<div class="page-actions">
    <a href="admin_dashboard.php" class="button-secondary">⬅️ Dashboard Admin</a>
    <a href="export.php" class="button-success">⬇️ Export Excel</a>
</div>

<?php if ($success_message): ?><div class='message success'><?= htmlspecialchars($success_message) ?></div><?php endif; ?>
<?php if ($error_message): ?><div class='message error'><?= htmlspecialchars($error_message) ?></div><?php endif; ?>

<div class="filter-container">
    <form method="get" action="peserta.php" class="filter-form">
        <div class="filter-group">
            <input type="text" name="filter" placeholder="Cari berdasarkan nama..." value="<?= htmlspecialchars($filter_values['filter']) ?>" />
            <select name="status">
                <option value="">Semua Status</option>
                <?php foreach($daftar_status_administratif as $s): ?>
                    <option value="<?= $s ?>" <?= ($filter_values['status'] == $s) ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-actions">
            <button type="submit" class="button-primary">Filter</button>
            <a href="peserta.php" class="button-reset">Reset</a>
        </div>
    </form>
</div>

<form method="post" action="peserta.php?<?= http_build_query($_GET) ?>">
    <button type="submit" name="luluskan_terpilih" onclick="return confirm('Yakin ingin meluluskan administrasi semua peserta yang dipilih?')">
        Luluskan Administrasi untuk yang Terpilih
    </button>
    <hr style="margin: 15px 0;">
    <table>
        <thead>
            <tr>
                <th style="width: 4%;"></th>
                <th style="width: 21%;">Nama Peserta</th>
                <th style="width: 15%;">Status Saat Ini</th>
                <th style="width: 60%;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($pendaftar && count($pendaftar) > 0): ?>
                <?php foreach($pendaftar as $p): ?>
                <tr>
                    <td>
                        <input type="checkbox" name="pendaftar_ids[]" value="<?= $p['id'] ?>" class="pilih-satu">
                    </td>
                    <td>
                        <?= htmlspecialchars($p['nama_lengkap']) ?>
                        <small>Kategori: <?= htmlspecialchars($p['kategori']) ?></small>
                    </td>
                    <td>
                        <strong><?= htmlspecialchars($p['status']) ?></strong>
                        <?php if(isset($p['nilai'])): ?>
                            <small>Nilai: <?= htmlspecialchars($p['nilai']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="action-container">
                            <div class="action-group">
                                <label>Ubah Status</label>
                                <?php
                                $current_status = $p['status'];
                                $is_administrative = in_array($current_status, ['Menunggu Verifikasi', 'Berkas Tidak Lengkap', 'Lulus Administrasi']);
                                if ($is_administrative):
                                ?>
                                    <div class="input-with-button">
                                        <select name="status_individu[<?= $p['id'] ?>]">
                                            <option value="Menunggu Verifikasi" <?= ($current_status == 'Menunggu Verifikasi') ? 'selected' : '' ?>>Menunggu Verifikasi</option>
                                            <option value="Berkas Tidak Lengkap" <?= ($current_status == 'Berkas Tidak Lengkap') ? 'selected' : '' ?>>Berkas Tdk Lengkap</option>
                                            <option value="Lulus Administrasi" <?= ($current_status == 'Lulus Administrasi') ? 'selected' : '' ?>>Lulus Administrasi</option>
                                        </select>
                                        <button type="submit" name="update_status_individu" value="<?= $p['id'] ?>" title="Simpan Status">Simpan</button>
                                    </div>
                                <?php else: ?>
                                <?php endif; ?>
                            </div>
                            <div class="action-group">
                                <label>Input Nilai</label>
                                <div class="input-with-button">
                                    <input type="number" step="0.01" name="nilai[<?= $p['id'] ?>]" value="<?= htmlspecialchars($p['nilai'] ?? '') ?>" placeholder="0.00">
                                    <button type="submit" name="update_nilai_individu" value="<?= $p['id'] ?>">Simpan</button>
                                </div>
                            </div>
                            <div class="action-group">
                                <label>Lainnya</label>
                                <div class="input-with-button">
                                    <?php if (!empty($p['berkas_url'])): ?>
                                        <button type="button" class="lihat-berkas-btn" data-berkas="<?= htmlspecialchars($p['berkas_url']) ?>">Lihat Berkas</button>
                                    <?php endif; ?>
                                    <button type="submit" name="hapus_peserta" value="<?= $p['id'] ?>" onclick="return confirm('Yakin ingin menghapus peserta ini secara permanen?');" class="button-danger" title="Hapus Peserta">Hapus</button>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="4" style="text-align:center;">Tidak ada data yang cocok dengan filter.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</form>

<div class="pagination">
    <?php
    $filter_query_for_pagination = http_build_query($filter_values);
    ?>
    <?php if ($current_page > 1): ?>
        <a href="?page=<?= $current_page - 1 ?>&<?= $filter_query_for_pagination ?>">&laquo; Sebelumnya</a>
    <?php endif; ?>
    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <a href="?page=<?= $i ?>&<?= $filter_query_for_pagination ?>" class="<?= $i == $current_page ? 'active' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>
    <?php if ($current_page < $total_pages): ?>
        <a href="?page=<?= $current_page + 1 ?>&<?= $filter_query_for_pagination ?>">Selanjutnya &raquo;</a>
    <?php endif; ?>
</div>

<div id="berkasModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h4>Daftar Berkas</h4>
        <div id="berkasList"></div>
    </div>
</div>

<script src="assets/js/script.js"></script>
</body>
</html>