<?php
session_start();
require 'supabase.php';
require 'mailer.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') header('Location: index.php');

// FILTER
$filter = $_GET['filter'] ?? '';
$status = $_GET['status'] ?? '';
$kategori = $_GET['kategori'] ?? '';
$endpoint = "/rest/v1/pendaftaran";
$params = [];
if($filter) $params[] = "kategori=ilike.*$filter*";
if($status) $params[] = "status=eq.$status";
if($kategori) $params[] = "kategori=eq.$kategori";
if($params) $endpoint .= "?" . implode("&", $params);

$pendaftar = supabase_request("GET", $endpoint);

// Update status & email notifikasi, atau hapus peserta
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    if (isset($_POST['update_status'])) {
        $status = $_POST['status'];
        supabase_request("PATCH", "/rest/v1/pendaftaran?id=eq.$id", ["status"=>$status]);
        $pendaftaran = supabase_request("GET", "/rest/v1/pendaftaran?id=eq.$id");
        $user = supabase_request("GET", "/rest/v1/users?id=eq.".$pendaftaran[0]['user_id']);
        send_email($user[0]['email'], "Status Pendaftaran", "Status pendaftaran Anda menjadi: $status");
    } elseif (isset($_POST['hapus_peserta'])) {
        supabase_request("DELETE", "/rest/v1/pendaftaran?id=eq.$id");
        // Jika ingin sekalian hapus user, aktifkan baris berikut:
        // supabase_request("DELETE", "/rest/v1/users?id=eq.".$user_id);
    }
    header('Location: peserta.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Data Peserta (Admin)</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<h2>Data Peserta</h2>
<a href="export.php">Export Excel</a> | <a href="jadwal.php">Jadwal Seleksi</a> | <a href="logout.php">Logout</a>
<form method="get" style="margin-bottom:10px;">
    <input type="text" name="filter" placeholder="Cari kategori..." value="<?= htmlspecialchars($filter) ?>" />
    <select name="kategori">
        <option value="">Semua Kategori</option>
        <option value="umum" <?= ($kategori=='umum')?'selected':'' ?>>Umum</option>
        <option value="beasiswa" <?= ($kategori=='beasiswa')?'selected':'' ?>>Beasiswa</option>
    </select>
    <select name="status">
        <option value="">Semua Status</option>
        <option <?= $status=='Baru'?'selected':'' ?>>Baru</option>
        <option <?= $status=='Diverifikasi'?'selected':'' ?>>Diverifikasi</option>
        <option <?= $status=='Lulus'?'selected':'' ?>>Lulus</option>
        <option <?= $status=='Gagal'?'selected':'' ?>>Gagal</option>
    </select>
    <button type="submit">Filter</button>
</form>
<table border="1" cellpadding="5">
    <tr>
        <th>Nama</th>
        <th>Kategori</th>
        <th>Status</th>
        <th>Jadwal</th>
        <th>Aksi</th>
    </tr>
    <?php foreach($pendaftar as $p): ?>
    <tr>
        <td><?= htmlspecialchars($p['nama_lengkap']) ?></td>
        <td><?= htmlspecialchars($p['kategori']) ?></td>
        <td><?= htmlspecialchars($p['status']) ?></td>
        <td>
        <?php
        if (empty($p['jadwal_seleksi'])) {
            echo '-';
        } else {
            $dt = new DateTime($p['jadwal_seleksi'], new DateTimeZone('UTC'));
            $dt->setTimezone(new DateTimeZone('Asia/Jakarta'));
            echo $dt->format('Y-m-d h:i A');
        }
        ?>
        </td>
        <td>
            <!-- Update status -->
            <form method="post" style="display:inline">
                <input type="hidden" name="id" value="<?= $p['id'] ?>" />
                <select name="status">
                    <option <?= $p['status']=='Baru'?'selected':'' ?>>Baru</option>
                    <option <?= $p['status']=='Diverifikasi'?'selected':'' ?>>Diverifikasi</option>
                    <option <?= $p['status']=='Lulus'?'selected':'' ?>>Lulus</option>
                    <option <?= $p['status']=='Gagal'?'selected':'' ?>>Gagal</option>
                </select>
                <button type="submit" name="update_status">Update</button>
            </form>
            <!-- Hapus peserta -->
            <form method="post" style="display:inline" onsubmit="return confirm('Yakin hapus peserta ini?');">
                <input type="hidden" name="id" value="<?= $p['id'] ?>" />
                <button type="submit" name="hapus_peserta" style="background:red;color:white;">Hapus</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
</body>
</html>
