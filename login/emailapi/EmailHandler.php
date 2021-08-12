<?php
include_once ("/home/sirhhfai/vendor/autoload.php");
use PHPMailer\PHPMailer\PHPMailer;

function MailInfo_New($to, $subject, $body, $headers)
{
    $mail = new PHPMailer;

    $mail->isMail(); //$mail->isSMTP(); // Set mailer to use SMTP
    $mail->Host = 'mail.privateemail.com'; // Specify main and backup SMTP servers
    //$mail->SMTPDebug = 2;
    $mail->SMTPAuth = true; // Enable SMTP authentication
    $mail->Username = 'noreply@sirhurt.net'; // SMTP username
    $mail->Password = 'b!5Z]?HM,n<Rc5kxOdh:]oz<mRrh]B'; // SMTP password
    $mail->SMTPSecure = 'tls'; // Enable TLS encryption, `ssl` also accepted
    $mail->Port = 587;

    $mail->setFrom('noreply@sirhurt.net', 'noreply@sirhurt.net');
    $mail->addAddress($to); // Name is optional
    $mail->addReplyTo('noreply@sirhurt.net', 'noreply@sirhurt.net');

    $mail->isHTML(true);

    $mail->Subject = $subject;
    $mail->Body = $body;
    //$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

    if (!$mail->send())
    {
        return false;
    }
    else
    {
        return true;
    }
}
?>