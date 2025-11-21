<?php

// Importa as classes do PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';
require 'phpmailer/src/Exception.php';

// Inclui configuração do banco de dados
require 'includes/config.php';

// Verifica se foi enviado via POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Pega o e-mail do formulário
    $email = $_POST['email'] ?? '';

    // Valida o e-mail
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        exit('E-mail inválido!');
    }

    // Conecta ao banco
    $conn = Database::getInstance()->getConnection();

    // Busca o usuário com esse e-mail
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Gera token de 64 caracteres
        $token = bin2hex(random_bytes(32));

        // Salva o token no banco
        $stmt = $conn->prepare("UPDATE users SET reset_token = ? WHERE email = ?");
        $stmt->execute([$token, $email]);

        // Cria link de redefiniço
        $link = "https://vibez.allsocial.com.br/reset_password.php?token=$token";

        // Instancia o PHPMailer
        $mail = new PHPMailer(true);

        try {
            // Configurações do SMTP
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'suportevibez@gmail.com';       // Seu Gmail
            $mail->Password   = 'ukfc cqav taqm ntgy';          // Senha de app do Gmail
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            // Remetente
            $mail->setFrom('suportevibez@gmail.com', 'Vibez');

            // Destinatário
            $mail->addAddress($email, $user['username']);

            // Conteúdo do e-mail
            $mail->isHTML(true);
            $mail->Subject = 'Redefinição de Senha - Vibez';
            $mail->Body = "
                Olá <b>{$user['username']}</b>,<br><br>
                Você solicitou uma redefinição de senha.<br>
                Clique no link abaixo para criar uma nova senha:<br><br>
                <a href='$link'>$link</a><br><br>
                Se você não solicitou isso, apenas ignore este e-mail.
            ";
            $mail->AltBody = "Olá {$user['username']}, acesse este link para redefinir sua senha: $link";

            // Envia o e-mail
            $mail->send();

            // Exibe mensagem bonita em HTML após o envio
            echo "
            <!DOCTYPE html>
            <html lang='pt-BR'>
            <head>
                <meta charset='UTF-8'>
                <title>Recuperação de Senha</title>
                <style>
                    body {
                        background-color: #121d2f;
                        color: #fff;
                        font-family: 'Segoe UI', sans-serif;
                        text-align: center;
                        padding-top: 100px;
                    }
                    .msg {
                        background: #1c2833;
                        padding: 30px;
                        border-radius: 10px;
                        display: inline-block;
                    }
                    a {
                        color: #00bfff;
                        text-decoration: none;
                        display: block;
                        margin-top: 20px;
                    }
                </style>
            </head>
            <body>
                <div class='msg'>
                    <h2>E-mail de recuperação enviado com sucesso!</h2>
                    <p>Verifique sua caixa de entrada (ou spam) e siga o link para redefinir sua senha.</p>
                    <a href='login.php'>Voltar ao login</a>
                </div>
            </body>
            </html>
            ";

        } catch (Exception $e) {
            // Em caso de erro no envio
            echo "Erro ao enviar e-mail: {$mail->ErrorInfo}";
        }

    } else {
        // E-mail no encontrado
        echo "E-mail não encontrado!";
    }

} else {
    // Se não for POST
    echo "Método invlido!";
}
?>
