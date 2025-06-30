<?php
session_start();
require 'supabase.php';
if (isset($_SESSION['user_id'])) header('Location: dashboard.php');
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $pass = $_POST["password"];
    $result = supabase_request("GET", "/rest/v1/users?email=eq.$email");
    if ($result && count($result) > 0 && password_verify($pass, $result[0]['password_hash'])) {
        $_SESSION['user_id'] = $result[0]['id'];
        $_SESSION['role'] = $result[0]['role'];
        $_SESSION['username'] = $result[0]['username'];
        header("Location: dashboard.php"); exit();
    } else {
        $error = "Email/password salah!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login Pendaftaran Online</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
    <div class="auth-container">
        <h2>Login</h2>
        <form method="post" class="auth-form">
            <input type="email" name="email" placeholder="Email" required />
            <input type="password" name="password" placeholder="Password" required />
            <button type="submit">Login</button>
        </form>
        <?php if(isset($error)) echo "<div class='error'>$error</div>"; ?>
        <div class="auth-footer">
            Belum punya akun? <a href="register.php">Daftar</a>
        </div>
    </div>
</body>
</html>
