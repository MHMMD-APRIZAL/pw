<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

function send_email($to, $subject, $body) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // sesuaikan
        $mail->SMTPAuth = true;
        $mail->Username = 'faishalkahfi041004@gmail.com';
        $mail->Password = 'yvco skgn rdjq ozes';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('faishalkahfi041004@gmail.com', 'Admin Pendaftaran');
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->send();
    } catch (Exception $e) {
        // error handling (log / tampilkan)
    }
}
?>
