<?php
session_start();
require 'supabase.php';
if (!isset($_SESSION['user_id'])) header('Location: index.php');
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
if ($role == 'admin') header('Location: peserta.php');
$pendaftaran = supabase_request("GET", "/rest/v1/pendaftaran?user_id=eq.$user_id");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Pendaftar</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-page">
    <div class="content-wrapper">
        <div class="dashboard-header">
            <h2>Dashboard Pendaftar</h2>
        </div>
        
        <div class="dashboard-nav">
            <a href="edit_profile.php">Edit Profile</a>
            <a href="daftar.php">Isi Pendaftaran</a>
            <a href="jadwal.php">Lihat Jadwal Seleksi</a>
            <a href="logout.php">Logout</a>
        </div>

        <?php if($pendaftaran && count($pendaftaran)>0): 
        $p = $pendaftaran[0];?>
            <div class="dashboard-info">
                <div class="info-row">
                    <span class="info-label">Status Pendaftaran:</span>
                    <span class="info-value"><?= htmlspecialchars($p['status']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Nama:</span>
                    <span class="info-value"><?= htmlspecialchars($p['nama_lengkap']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Kategori:</span>
                    <span class="info-value"><?= htmlspecialchars($p['kategori']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Jadwal Seleksi:</span>
                    <span class="info-value">
                        <?php
                        if (empty($p['jadwal_seleksi'])) {
                            echo '-';
                        } else {
                            $dt = new DateTime($p['jadwal_seleksi'], new DateTimeZone('UTC'));
                            $dt->setTimezone(new DateTimeZone('Asia/Jakarta'));
                            echo $dt->format('Y-m-d h:i A');
                        }
                        ?>
                    </span>
                </div>
            </div>
        <?php else: ?>
            <div class="dashboard-info">
                <p class="text-center">Anda belum mengisi pendaftaran.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
