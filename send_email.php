<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'Exception.php';
require 'PHPMailer.php';
require 'SMTP.php';

function sendEmail($fullName, $email, $message, $subject) {
    $mail = new PHPMailer(true);

    try {
        // Set mailer to use SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.mailgun.org';
        $mail->SMTPAuth = true;
        $mail->Username = 'support@cashstack.ng'; 
        $mail->Password = 'Security247%'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587; 

        // Set sender and recipient information
        $mail->setFrom('support@cashstack.ng', 'Cashstack'); // From email address
        $mail->addAddress($email); // Recipient email address
        $mail->addReplyTo($email, "$fullName");

        // Optional: Add CC and BCC
        // $mail->addCC('cc@example.com');
        // $mail->addBCC('bcc@example.com');

        // Set email format to HTML
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = "Dear $fullName <br><br>$message"; // HTML message body
        $mail->AltBody = "Dear $fullName\n\n$message"; // Plain text message body

        // Send the email
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>
