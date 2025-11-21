<?php

session_start();

require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../2fa/PHPGangsta/GoogleAuthenticator.php';

if (!isset($_SESSION['temp_user'])) {
    header("Location: ../login.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['code'] ?? '';
    $user = $_SESSION['temp_user'];
    $secret = $user['2fa_secret'];

    $ga = new PHPGangsta_GoogleAuthenticator();
    $checkResult = $ga->verifyCode($secret, $code, 2); // tolerância de 2 códigos (±60s)

    if ($checkResult) {
        // 2FA passou, agora sim define as sessões
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['profile_pic'] = $user['profile_pic'];
        $_SESSION['theme'] = $user['theme_preference'];
        $_SESSION['is_admin'] = $user['is_admin'];
        $_SESSION['2fa_verified'] = true;

        unset($_SESSION['temp_user']); // limpeza de segurança

        header("Location: ../index.php");
        exit();
    } else {
        $error = 'Código inválido. Tente novamente.';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR" data-theme="<?php echo $_SESSION['theme'] ?? 'light'; ?>">
<head>
    <meta charset="UTF-8">
    <title>Verificação 2FA | Vibez</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/<?php echo $_SESSION['theme'] ?? 'light'; ?>-theme.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <h2>Verificação em Dois Fatores</h2>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="form-group">
                    <label for="code">Código do Autenticador</label>
                    <input type="text" id="code" name="code" required autofocus>
                </div>

                <button type="submit" class="auth-button">Verificar</button>
            </form>
        </div>
    </div>
</body>
</html>
