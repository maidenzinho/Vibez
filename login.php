<?php

require_once 'includes/config.php';
require_once 'includes/functions.php';

if (is_logged_in()) {
    header("Location: index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $db = Database::getInstance();
    $conn = $db->getConnection();

    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Se 2FA estiver ativado, redireciona para verificação
            if ($user['twofa_enabled'] && !empty($user['2fa_secret'])) {
                $_SESSION['temp_user'] = $user;
                header("Location: 2fa/verify-2fa.php");
                exit();
            }

            // Login normal sem 2FA
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['profile_pic'] = $user['profile_pic'];
            $_SESSION['theme'] = $user['theme_preference'];
            $_SESSION['is_admin'] = $user['is_admin'];
            $_SESSION['2fa_verified'] = true;
            header("Location: index.php");
            exit();
        } else {
            $error = 'Credenciais inválidas.';
        }
    } catch (PDOException $e) {
        $error = 'Erro no sistema: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR" data-theme="<?php echo $_SESSION['theme'] ?? 'light'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Vibez</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/<?php echo $_SESSION['theme'] ?? 'light'; ?>-theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: var(--sidebar-bg);
            margin: 0;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <h2>Login</h2>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php echo display_flash_message(); ?>

            <form action="login.php" method="POST">
                <div class="form-group">
                    <label for="username">Usuário ou Email</label>
                    <input type="text" id="username" name="username" required>
                </div>

                <div class="form-group">
                    <label for="password">Senha</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="form-options">
                    <label class="checkbox-container">
                        <input type="checkbox" name="remember">
                        <span class="checkmark"></span>
                        Lembrar de mim
                        </label>

                    <a href="forgot-password.php" class="forgot-password">Esqueceu a senha?</a>
                </div>

                <button type="submit" class="auth-button">Entrar</button>
            </form>

            <div class="auth-footer">
                Não tem uma conta? <a href="/auth/register.php">Registre-se</a>
            </div>
        </div>
    </div>
</body>
</html>
