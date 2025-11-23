<?php

require_once 'includes/config.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    http_response_code(400);
    $message = "Token de verificação inválido.";
} else {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();

        // Verifica se o token existe
        $stmt = $conn->prepare("SELECT id, is_verified FROM users WHERE verification_token = ?");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if ($user['is_verified']) {
                $message = "Sua conta já foi verificada anteriormente.";
            } else {
                // Atualiza o status da conta para verificada
                $update = $conn->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = ?");
                $update->execute([$user['id']]);
                $message = "Conta verificada com sucesso! Você já pode fazer login.";
            }
        } else {
            http_response_code(404);
            $message = "Token inválido ou expirado.";
        }
    } catch (PDOException $e) {
        http_response_code(500);
        $message = "Erro ao verificar a conta: " . htmlspecialchars($e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Verificação de Email | Vibez</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        body {
            background-color: #111;
            color: black;
            font-family: sans-serif;
            text-align: center;
            padding: 80px 20px;
        }

        .container {
            background-color: #1a1a1a;
            padding: 40px;
            border-radius: 12px;
            display: inline-block;
            max-width: 500px;
            width: 100%;
        }

        a {
            color: black;
            text-decoration: none;
            font-weight: bold;
        }

        a:hover {
            text-decoration: underline;
        }

        h2 {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2><?php echo htmlspecialchars($message); ?></h2>
        <p><a href="login.php">Ir para o login</a></p>
    </div>
</body>
</html>
