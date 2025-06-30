<?php
require 'supabase.php';
require 'mailer.php';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $username = $_POST["username"];
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);

    $check = supabase_request("GET", "/rest/v1/users?email=eq.$email");
    if ($check && count($check) > 0) {
        $err = "Email sudah terdaftar!";
    } else {
        $data = [
            'username' => $username,
            'email' => $email,
            'password_hash' => $password,
            'role' => 'pendaftar'
        ];
        $res = supabase_request("POST", "/rest/v1/users", $data);
        send_email($email, "Akun Berhasil Dibuat", "Akun Anda sudah aktif. Silakan login.");
        header("Location: index.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Registrasi Akun</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="register-page">
    <div class="auth-container">
        <h2>Daftar Akun Baru</h2>
        <form method="post" class="auth-form">
            <input type="text" name="username" placeholder="Nama Lengkap" required />
            <input type="email" name="email" placeholder="Email" required />
            <input type="password" name="password" placeholder="Password" required />
            <button type="submit">Daftar</button>
        </form>
        <?php if(isset($err)) echo "<div class='error'>$err</div>"; ?>
        <div class="auth-footer">
            Sudah punya akun? <a href="index.php">Login</a>
        </div>
    </div>
</body>
</html>
