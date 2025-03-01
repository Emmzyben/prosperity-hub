<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'Exception.php';
require 'PHPMailer.php';
require 'SMTP.php';

function sendEmail($fullName, $email, $message, $subject) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp-pulse.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'emmco96@gmail.com';
        $mail->Password = 'Nikido886';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;

        $mail->setFrom('info@cokellimited.uk', 'Cokel Limited');
        $mail->addAddress($email);
        $mail->addReplyTo($email, "$fullName ");
        $mail->addCC('cc@example.com');
        $mail->addBCC('bcc@example.com');

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = "Dear $fullName <br><br>$message";
        $mail->AltBody = "Dear $fullName\n\n$message";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>
