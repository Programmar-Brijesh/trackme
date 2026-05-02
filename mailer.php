<?php

use PHPMailer\PHPMailer\PHPMailer;

require __DIR__ . '/vendor/autoload.php';

function sendMailAlert($subject, $body, $config)
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $config['email']['user'];
        $mail->Password = $config['email']['pass'];
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom($config['email']['user'], 'BTC Tracker');
        $mail->addAddress($config['email']['to']);

        $mail->Subject = $subject;
        $mail->Body = $body;

        $mail->send();

    } catch (Exception $e) {
        file_put_contents("logs/error.log", $mail->ErrorInfo . "\n", FILE_APPEND);
    }
}