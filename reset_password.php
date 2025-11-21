<?php
// Inicia sessão
session_start();

// Inclui o config e conecta ao banco
require 'includes/config.php';

// Verifica se o token foi enviado pela URL
$token = $_GET['token'] ?? '';

if (!$token) {
    exit('Token inválido!');
}

// Conexão com o banco
$conn = Database::getInstance()->getConnection();

// Verifica se o token existe no banco
$stmt = $conn->prepare("SELECT * FROM users WHERE reset_token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Se o token for inválido ou não encontrado
if (!$user) {
    exit('Token inválido ou expirado!');
}

// Se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $novaSenha = $_POST['nova_senha'] ?? '';
    $confirmarSenha = $_POST['confirmar_senha'] ?? '';

    // Valida as senhas
    if (strlen($novaSenha) < 6) {
        echo "A senha deve ter no mnimo 6 caracteres.";
    } elseif ($novaSenha !== $confirmarSenha) {
        echo "As senhas no coincidem.";
    } else {
        // Criptografa a nova senha
        $senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);

        // Atualiza a senha no banco e remove o token
        $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL WHERE id = ?");
        $stmt->execute([$senhaHash, $user['id']]);

        echo "
        <!DOCTYPE html>
        <html lang='pt-BR'>
        <head>
            <meta charset='UTF-8'>
            <title>Senha Redefinida</title>
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
                <h2>Senha redefinida com sucesso!</h2>
                <p>Agora você pode fazer login com sua nova senha.</p>
                <a href='login.php'>Ir para o login</a>
            </div>
        </body>
        </html>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Redefinir Senha</title>
    <style>
        body {
            background-color: #121d2f;
            color: white;
            font-family: 'Segoe UI', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        form {
            background-color: #1c2833;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 0 10px rgba(0,0,0,0.5);
        }
        input[type="password"], button {
            width: 90%;
            padding: 12px;
            margin: 10px 0;
            border-radius: 8px;
            border: none;
            outline: none;
        }
        input[type="password"] {
            background-color: #2c3e50;
            color: white;
        }
        button {
            background-color: #00bfff;
            color: white;
            font-weight: bold;
            cursor: pointer;
        }
        button:hover {
            background-color: #009acd;
        }
    </style>
</head>
<body>
    <form method="POST">
        <h2>Redefinir Senha</h2>
        <input type="password" name="nova_senha" placeholder="Nova Senha" required>
        <input type="password" name="confirmar_senha" placeholder="Confirmar Senha" required>
        <button type="submit">Salvar Nova Senha</button>
    </form>
</body>
</html>
