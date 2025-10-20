<?php

//Import PHPMailer classes into the global namespace
//These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/phpmailer/src/Exception.php';
require_once __DIR__ . '/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/phpmailer/src/SMTP.php';

function sendMail($to_email, $to_name, $subject, $message, $from_email = 'cedricjade13@gmail.com', $from_name = 'Clinic Management System') {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'cedricjade13@gmail.com'; // SMTP account for authentication
        $mail->Password   = 'bxmreoqsoimztxlf'; // You need to set this
        $mail->SMTPSecure = 'ssl';
        $mail->Port       = 465;

        // Sender: The person who filled out the form
        $mail->setFrom($from_email, $from_name);
        // Receiver: cedricjade13@gmail.com
        $mail->addAddress($to_email, $to_name);
        $mail->addReplyTo($from_email, $from_name);

        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body    = $message;

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Optionally log error: $mail->ErrorInfo
        return false;
    }
}

// Retain the form handler for manual testing (optional)
if (isset($_POST["send"])) {
    sendMail(
        $_POST["to_email"],
        $_POST["name"] ?? '',
        $_POST["subject"],
        $_POST["message"],
        $_POST["email"] ?? 'jaynujangad03@gmail.com',
        $_POST["name"] ?? 'Clinic Management System'
    );
    echo "<script>showSuccessModal('Message was sent successfully!', 'Success'); setTimeout(function() { document.location.href = 'index.php'; }, 1200);</script>";
}
?>