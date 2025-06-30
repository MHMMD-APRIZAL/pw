<?php
session_start();
require 'supabase.php';
date_default_timezone_set('Asia/Jakarta'); // Pastikan default timezone sudah benar

if (!isset($_SESSION['user_id'])) header('Location: index.php');
$role = $_SESSION['role'];

if ($role == 'admin') {
    if ($_SERVER['REQUEST_METHOD']=='POST') {
        $id = $_POST['id'];
        $jadwal = $_POST['jadwal'];
        if ($jadwal) {
            // Ambil input sebagai Asia/Jakarta, konversi ke UTC sebelum simpan
            $dt = new DateTime($jadwal, new DateTimeZone('Asia/Jakarta'));
            $dt->setTimezone(new DateTimeZone('UTC'));
            $jadwal_utc = $dt->format('Y-m-d\TH:i:sP');
        } else {
            $jadwal_utc = null;
        }
        supabase_request("PATCH", "/rest/v1/pendaftaran?id=eq.$id", ["jadwal_seleksi" => $jadwal_utc]);
    }
    $peserta = supabase_request("GET", "/rest/v1/pendaftaran");
} else {
    $peserta = supabase_request("GET", "/rest/v1/pendaftaran?user_id=eq.".$_SESSION['user_id']);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Jadwal Seleksi</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<h2>Jadwal Seleksi</h2>
<a href="dashboard.php">Kembali</a>
<table border="1" cellpadding="5">
    <tr><th>Nama</th><th>Jadwal</th>
    <?php if($role=='admin') echo '<th>Aksi</th>'; ?>
    </tr>
    <?php foreach($peserta as $p): ?>
    <tr>
        <td><?= htmlspecialchars($p['nama_lengkap']) ?></td>
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
        <?php if($role=='admin'): ?>
        <td>
            <form method="post" style="display:inline">
                <input type="hidden" name="id" value="<?= $p['id'] ?>" />
                <input type="datetime-local" name="jadwal" value="<?= $p['jadwal_seleksi'] ? (new DateTime($p['jadwal_seleksi'], new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Asia/Jakarta'))->format('Y-m-d\TH:i') : '' ?>" />
                <button type="submit">Update</button>
            </form>
        </td>
        <?php endif; ?>
    </tr>
    <?php endforeach; ?>
</table>
</body>
</html>
