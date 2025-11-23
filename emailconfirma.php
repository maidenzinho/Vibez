<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/phpmailer/src/SMTP.php';
require_once __DIR__ . '/phpmailer/src/Exception.php';
require_once __DIR__ . '/includes/load_env.php';
loadEnv(__DIR__ . '/.env');

function sendVerificationEmail($email, $username, $token) {
    $verificationLink = "https://vibez.allsocial.com.br/verify_email.php?token=$token";

    $mail = new PHPMailer(true);

    try {
        // Configurações do SMTP a partir do .env
        $mail->isSMTP();
        $mail->Host       = $_ENV['MAIL_HOST'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['MAIL_USERNAME'];
        $mail->Password   = $_ENV['MAIL_PASSWORD'];
        $mail->SMTPSecure = $_ENV['MAIL_SECURE'];
        $mail->Port       = $_ENV['MAIL_PORT'];

        // Remetente e destinatário
        $mail->setFrom($_ENV['MAIL_FROM'], $_ENV['MAIL_FROM_NAME']);
        $mail->addAddress($email, $username);

        // Conteúdo do e-mail
        $mail->isHTML(true);
        $mail->Subject = 'Verifique seu e-mail - Vibez';
        $mail->Body = "
            Olá <b>$username</b>,<br><br>
            Obrigado por se registrar no Vibez!<br>
            Clique no link abaixo para verificar seu e-mail:<br><br>
            <a href='$verificationLink'>$verificationLink</a><br><br>
            Se você não criou uma conta, apenas ignore esta mensagem.
        ";
        $mail->AltBody = "Olá $username, verifique seu e-mail acessando este link: $verificationLink";

        $mail->send();

    } catch (Exception $e) {
        throw new Exception("Erro ao enviar email de verificação: {$mail->ErrorInfo}");
    }
}
