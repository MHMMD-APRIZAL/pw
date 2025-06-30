<?php
session_start();
require 'supabase.php';

if (!isset($_SESSION['user_id'])) header('Location: index.php');
$user_id = $_SESSION['user_id'];

// Ambil data lama
$data = supabase_request("GET", "/rest/v1/pendaftaran?user_id=eq.$user_id");
$profile = $data[0];

// Handle update profile
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $update = [
        'nama_lengkap'     => $_POST['nama_lengkap'],
        'kategori'         => $_POST['kategori'],
        'ttl'              => $_POST['ttl'],
        'jenis_kelamin'    => $_POST['jenis_kelamin'],
        'alamat'           => $_POST['alamat'],
        'no_hp'            => $_POST['no_hp'],
        'email'            => $_POST['email'],
        'pendidikan'       => $_POST['pendidikan'],
        'jurusan'          => $_POST['jurusan'],
        'jenjang'          => $_POST['jenjang'],
        'sistem_kuliah'    => $_POST['sistem_kuliah'],
        'nama_ortu'        => $_POST['nama_ortu'],
        'pendapatan_ortu'  => $_POST['pendapatan_ortu']
    ];
    supabase_request("PATCH", "/rest/v1/pendaftaran?user_id=eq.$user_id", $update);
    header("Location: edit_profile.php?sukses=1");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Profile</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<h2>Edit Profile</h2>
<?php if(isset($_GET['sukses'])) echo "<b>Data berhasil diupdate!</b>"; ?>
<form method="post">
    <label>Nama Lengkap</label><br>
    <input type="text" name="nama_lengkap" value="<?= htmlspecialchars($profile['nama_lengkap']) ?>" required><br><br>

    <label>Tempat, Tanggal Lahir</label><br>
    <input type="text" name="ttl" value="<?= htmlspecialchars($profile['ttl']) ?>"><br><br>

    <label>Jenis Kelamin</label><br>
    <select name="jenis_kelamin">
        <option value="Laki-laki" <?= $profile['jenis_kelamin']=='Laki-laki'?'selected':'' ?>>Laki-laki</option>
        <option value="Perempuan" <?= $profile['jenis_kelamin']=='Perempuan'?'selected':'' ?>>Perempuan</option>
    </select><br><br>

    <label>Alamat</label><br>
    <textarea name="alamat"><?= htmlspecialchars($profile['alamat']) ?></textarea><br><br>

    <label>No HP</label><br>
    <input type="text" name="no_hp" value="<?= htmlspecialchars($profile['no_hp']) ?>"><br><br>

    <label>Email</label><br>
    <input type="email" name="email" value="<?= htmlspecialchars($profile['email']) ?>"><br><br>

    <label>Pendidikan Terakhir:</label><br>
    <select name="pendidikan" required>
        <option value="">--Pilih--</option>
        <option value="SMK/SMA Sederajat">SMK/SMA Sederajat</option>
        <option value="D1">D1</option>
        <option value="D2">D2</option>
        <option value="D3">D3</option>
        <option value="S1">S1</option>
        <option value="S2">S2</option>
    </select><br>

    <label>Jurusan:</label><br>
    <select name="jurusan" required>
        <option value="">--Pilih--</option>
        <option value="Informatika">Informatika</option>
        <option value="Sistem Informasi">Sistem Informasi</option>
        <option value="RPL">RPL</option>
        <option value="Manajemen">Manajemen</option>
        <option value="Kewirausahaan">Kewirausahaan</option>
    </select><br>

    <label>Jenjang:</label><br>
    <select name="jenjang" required>
        <option value="">--Pilih--</option>
        <option value="D1">D1</option>
        <option value="D2">D2</option>
        <option value="D3">D3</option>
        <option value="S1">S1</option>
        <option value="S2">S2</option>
        <option value="S2">S3</option>
    </select><br>

    <label>Sistem Kuliah:</label><br>
    <select name="sistem_kuliah" required>
        <option value="">--Pilih--</option>
        <option value="Kelas Reguler">Kelas Reguler</option>
        <option value="Kelas Karyawan Malam">Kelas Karyawan Malam</option>
        <option value="Karyawan Jumat Sabtu">Karyawan Jumat Sabtu</option>
    </select><br>

    <label>Kategori:</label><br>
    <select name="kategori" required>
        <option value="">--Pilih Kategori--</option>
        <option value="umum">Umum</option>
        <option value="beasiswa">Beasiswa</option>
    </select><br>

    <label>Nama Orang Tua</label><br>
    <input type="text" name="nama_ortu" value="<?= htmlspecialchars($profile['nama_ortu']) ?>"><br><br>

    <label>Pendapatan Orang Tua</label><br>
    <input type="text" name="pendapatan_ortu" value="<?= htmlspecialchars($profile['pendapatan_ortu']) ?>"><br><br>
  <h1>Upload Gambar ke Supabase Storage</h1>

  <input type="file" id="uploadFile" accept="image/*" />
  <button id="uploadBtn">Upload</button>

  <div id="result" style="margin-top: 20px;"></div>

  <script type="module">
    import { createClient } from 'https://esm.sh/@supabase/supabase-js'

    // Ganti ini dengan milikmu
    const supabaseUrl = 'https://mxajscgaszabmustddfq.supabase.co'
    const supabaseKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Im14YWpzY2dhc3phYm11c3RkZGZxIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDk1Mjg5MTQsImV4cCI6MjA2NTEwNDkxNH0.jyueIxwoYJf3sbDra98uN3vD6MYrvX_ZWN6hwyPzD38'
    const supabase = createClient(supabaseUrl, supabaseKey)

    document.getElementById('uploadBtn').addEventListener('click', async () => {
      const fileInput = document.getElementById('uploadFile')
      const resultDiv = document.getElementById('result')
      const file = fileInput.files[0]

      if (!file) {
        resultDiv.textContent = "⚠️ Silakan pilih file gambar terlebih dahulu."
        return
      }

      const fileName = `${Date.now()}_${file.name}`

      const { data, error } = await supabase
        .storage
        .from('berkas.pendaftar') // Ganti dengan nama bucket kamu
        .upload(fileName, file)

      if (error) {
        console.error("Upload gagal:", error.message)
        resultDiv.textContent = "❌ Upload gagal: " + error.message
      } else {
        const { data: publicUrlData } = supabase
          .storage
          .from('images')
          .getPublicUrl(fileName)

        resultDiv.innerHTML = `
          ✅ Upload berhasil!<br>
          <a href="${publicUrlData.publicUrl}" target="_blank">${publicUrlData.publicUrl}</a><br>
          <img src="${publicUrlData.publicUrl}" alt="Uploaded Image" style="max-width: 300px; margin-top: 10px;" />
        `
      }
    })
  </script>
    <button type="submit">Simpan</button>
</form>
<a href="dashboard.php">Kembali</a>
</body>
</html>
