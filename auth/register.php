<?php
// SINCRONIZAR COM O EMAIL DE CONFIRMAÇÃO!
// http://localhost/vibez/auth/register.php
// Ativa a exibição de erros para desenvolvimento:

session_start();

require_once('../includes/config.php');
require_once(__DIR__ . '/../includes/functions.php');
require_once __DIR__ . '/../emailconfirma.php';

if (is_logged_in()) {
    header("Location: /../index.php");
    exit();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifica se os campos foram enviados com sucesso e se não existir envia ''.
    // Trim para limpar espaços em branco.
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    // Não havendo erros, conecta ao banco de dados.
    if (empty($errors)) {
        try {
            // Retorna uma instância da conexão do bd, e o objeto PDO para consultas.
            $db = Database::getInstance();
            $conn = $db->getConnection(); 

            // Verifica existência de usuário/email.
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?"); // SQL query.
            $stmt->execute([$username, $email]); // Injetando username e email na query.

            if ($stmt->fetch()) {
                $errors['general'] = 'Nome de usuário ou email já está em uso.';
            } else { // Se usuário não existe, criptografa a senha com password_hash.                
                // Insere os valores digitados pelo usuário no bd por meio da query.
                $stmt = $conn->prepare('INSERT INTO users (username, email, password, verification_token, is_verified) VALUES (?, ?, ?, ?, 0)');
                $stmt->execute([$username, $email, $password, $verification_token]);
                
                sendVerificationEmail($email, $username, $verification_token);
                echo "
                <div style='text-align:center; padding: 50px; color:white; font-family:sans-serif;'>
                    <h2>Verifique seu e-mail</h2>
                    <p>Enviamos um link de verificação para <strong>$email</strong>. 
                    Verifique sua caixa de entrada ou spam para ativar sua conta.</p>
                    <a href='login.php' style='color: #00bfff;'>Voltar ao login</a>
                </div>";
                exit();

                // Login automático após registro.
                $_SESSION['user_id'] = $conn->lastInsertId(); // Armazena id gerado pelo bd para o novo user na sessão.
                $_SESSION['username'] = $username; // Armazena nome de usuário na sessão.
                // Foto de perfil padrão.
                $_SESSION['user_profile_pic'] = 'default-profile.png';
                $_SESSION['theme'] = 'light';
                
                // Redireciona para página principal.
                echo 'Registro bem sucedido!';
                header("Location: index.php");
                exit();
            }
        // Caso ocorra erro na consulta do banco de dados lança exceção e armazena erro:
        } catch (PDOException $e) {
            $errors['general'] = 'Erro no sistema: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang ="pt-BR" data-theme="<?php echo $_SESSION['theme'] ?? 'light'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro | Conta</title>
    <!-- Estilos Globais: -->
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/register.css">
    <!-- Temas (Light/Dark): -->
    <link rel="stylesheet" href="../assets/css/<?php echo $_SESSION['theme'] ?? 'light'; ?>-theme.css">
    <!-- Font Awesome: -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Importando Crypto JS e arquivo .js para fazer o hash da senha: -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.0.0/crypto-js.min.js" defer></script>
    <script src="../assets/js/register.js" defer></script>
</head>

<body>
    <div class="auth-container">
        <div class="auth-card">
            <h2>Registrar</h2>

            <!-- Exibe erros caso existam. -->
            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-danger"><?php echo $errors['general']; ?></div>
            <?php endif; ?>

            <!-- Formulário de registro com método POST para o arquivo register.php. -->
            <form action="register.php" method="POST">

                <!-- Username. -->
                <div class="form-group">
                    <label for="username">Nome de Usuário</label>
                    <input type="text" id="username" name="username" required>
                    <!-- Se há erro em $errors, exibe mensagem. -->
                    <?php if (isset($errors['username'])): ?>
                        <span class="error-message"><?php echo $errors['username']; ?></span>
                    <?php endif; ?>
                </div>

                <!-- E-mail. -->
                <div class="form-group">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <!-- Senha -->
                <div class="form-group">
                    <label for="password">Senha</label>
                    <input type="password" id="password" name="password" required>
                    <?php if (isset($errors['password'])): ?>
                        <span class="error-message"><?php echo $errors['password']; ?></span>
                    <?php endif; ?>
                </div>       

                <!-- Confirmar senha. -->
                <div class="form-group">
                    <label for="confirm_password">Confirmar senha</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    <!-- Se há erro em $errors, exibe mensagem. -->
                    <?php if (isset($errors['confirm_password'])): ?>
                        <span class="error-message"><?php echo $errors['confirm_password']; ?></span>
                    <?php endif; ?> 
                </div>

                <!-- Botão registrar. -->
                <button type="submit" class="auth-button">Registrar</button>
            </form>

            <div class="auth-footer">
                Já tem uma conta? <a href="login.php">Faça login</a>
            </div>
        </div>    
    </div>
</body>
</html>