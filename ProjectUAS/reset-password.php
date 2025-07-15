<?php
session_start();
require 'supabase.php';

$token = $_GET['token'] ?? null;
if (!$token) {
    $_SESSION['error_message'] = "Token reset tidak ditemukan.";
    header('Location: index.php');
    exit;
}

$error = '';

$token_hash = hash('sha256', $token);
$user_result = supabase_request("GET", "/rest/v1/users?reset_token=eq.$token_hash&limit=1");

if (!$user_result['data'] || count($user_result['data']) == 0) {
    $_SESSION['error_message'] = "Token tidak valid atau sudah digunakan.";
    header('Location: index.php');
    exit;
}
$user = $user_result['data'][0];

$expires_at = new DateTime($user['reset_token_expires_at']);
if (new DateTime() > $expires_at) {
    $_SESSION['error_message'] = "Token sudah kedaluwarsa. Silakan ajukan permintaan reset kembali.";
    header('Location: lupa-password.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    if (strlen($password) < 6) {
        $error = "Password minimal harus 6 karakter.";
    } elseif ($password !== $password_confirm) {
        $error = "Konfirmasi password tidak cocok.";
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        supabase_request("PATCH", "/rest/v1/users?id=eq.{$user['id']}", [
            'password_hash' => $password_hash,
            'reset_token' => null,
            'reset_token_expires_at' => null
        ]);
        
        $_SESSION['success_message'] = "Password berhasil diubah! Silakan login dengan password baru Anda.";
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
    <div class="auth-container">
        <h2>Atur Password Baru</h2>

        <?php if ($error): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="post" class="auth-form" action="reset-password.php?token=<?= htmlspecialchars($token) ?>">
            <div class="input-wrapper">
                <input 
                    type="password" 
                    name="password" 
                    id="password" 
                    placeholder="Password" 
                    required 
                    minlength="6"
                    title="Password harus terdiri dari minimal 6 karakter">
                <i class="fas fa-eye password-toggle"></i>
            </div>
            <div class="input-wrapper">
                <input type="password" name="password_confirm" id="password_confirm" placeholder="Konfirmasi Password" required />
                <i class="fas fa-eye password-toggle"></i>
            </div>
            <button type="submit">Reset Password</button>
        </form>
    </div>
</body>
</html>