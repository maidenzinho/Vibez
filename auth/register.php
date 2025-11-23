<?php
// Registro de usuário com verificação por e-mail

session_start();

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../emailconfirma.php';

if (is_logged_in()) {
    header("Location: /index.php");
    exit();
}

$errors = [];
$username = '';
$email    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Trim para limpar espaços
    $username         = isset($_POST['username']) ? trim($_POST['username']) : '';
    $email            = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validações básicas
    if ($username === '') {
        $errors['username'] = 'Informe um nome de usuário.';
    } elseif (mb_strlen($username) < 3) {
        $errors['username'] = 'O nome de usuário deve ter pelo menos 3 caracteres.';
    }

    if ($email === '') {
        $errors['email'] = 'Informe um e-mail.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Informe um e-mail válido.';
    }

    if ($password === '') {
        $errors['password'] = 'Informe uma senha.';
    }

    if ($confirm_password === '') {
        $errors['confirm_password'] = 'Confirme sua senha.';
    } elseif ($password !== $confirm_password) {
        $errors['confirm_password'] = 'As senhas não conferem.';
    }

    // Se não houver erros de validação, continua
    if (empty($errors)) {
        try {
            $db   = Database::getInstance();
            $conn = $db->getConnection();

            // Verifica se username ou email já existem
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
            $stmt->execute([$username, $email]);

            if ($stmt->fetch()) {
                $errors['general'] = 'Nome de usuário ou e-mail já está em uso.';
            } else {
                // IMPORTANTE:
                // Aqui estamos armazenando a senha EXATAMENTE como recebida.
                // Se o register.js estiver aplicando SHA-256 com CryptoJS,
                // o valor salvo será o hash em hex (64 caracteres).
                // O login.php já trata:
                // - hashes com password_hash (começam com '$')
                // - SHA-256 (64 chars hex)
                // - texto puro (fallback)
                $verification_token   = bin2hex(random_bytes(32));
                $verification_expires = (new DateTime('+1 day'))->format('Y-m-d H:i:s');

                $stmt = $conn->prepare("
                    INSERT INTO users (
                        username,
                        email,
                        password,
                        verification_token,
                        verification_expires,
                        is_verified
                    ) VALUES (?, ?, ?, ?, ?, 0)
                ");
                $stmt->execute([
                    $username,
                    $email,
                    $password,
                    $verification_token,
                    $verification_expires
                ]);

                // Envia e-mail de verificação
                sendVerificationEmail($email, $username, $verification_token);

                // Tela de confirmação simples
                echo "
                <!DOCTYPE html>
                <html lang='pt-BR'>
                <head>
                    <meta charset='UTF-8'>
                    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                    <title>Verifique seu e-mail</title>
                    <style>
                        body {
                            margin: 0;
                            min-height: 100vh;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                            background: #020617;
                            color: #e5e7eb;
                        }
                        .box {
                            max-width: 420px;
                            padding: 32px 24px;
                            border-radius: 16px;
                            background: #020617;
                            border: 1px solid rgba(148, 163, 184, 0.5);
                            box-shadow:
                                0 20px 60px rgba(15, 23, 42, 0.95),
                                0 0 0 1px rgba(15, 23, 42, 0.9);
                            text-align: center;
                        }
                        h2 {
                            margin-top: 0;
                            margin-bottom: 10px;
                            font-size: 22px;
                        }
                        p {
                            font-size: 14px;
                            color: #9ca3af;
                        }
                        a {
                            display: inline-block;
                            margin-top: 18px;
                            padding: 9px 16px;
                            border-radius: 999px;
                            text-decoration: none;
                            font-size: 13px;
                            font-weight: 500;
                            color: #f9fafb;
                            background: linear-gradient(135deg, #6366f1, #ec4899);
                        }
                    </style>
                </head>
                <body>
                    <div class='box'>
                        <h2>Verifique seu e-mail</h2>
                        <p>Enviamos um link de verificação para <strong>{$email}</strong>.<br>
                        Verifique sua caixa de entrada (ou spam) para ativar sua conta.</p>
                        <a href='/login.php'>Voltar ao login</a>
                    </div>
                </body>
                </html>";
                exit();
            }
        } catch (Exception $e) {
            $errors['general'] = 'Erro ao registrar. Tente novamente mais tarde.';
            error_log('Erro no registro: ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang ="pt-BR" data-theme="<?php echo htmlspecialchars($_SESSION['theme'] ?? 'light'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro | Conta</title>

    <!-- Estilos Globais: -->
    <link rel="stylesheet" href="../assets/css/main.css">
    <!-- Temas (Light/Dark): -->
    <link rel="stylesheet" href="../assets/css/<?php echo htmlspecialchars($_SESSION['theme'] ?? 'light'); ?>-theme.css">
    <!-- Font Awesome: -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- CryptoJS + JS de registro (para hash de senha, se você estiver usando) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.0.0/crypto-js.min.js" defer></script>
    <script src="../assets/js/register.js" defer></script>

    <style>
        :root {
            --bg-main: #020617;
            --bg-gradient: linear-gradient(135deg, #020617, #020617);
            --panel-bg: #020617;
            --panel-alt: #030712;
            --card-bg: #020617;
            --text-primary: #e5e7eb;
            --text-secondary: #9ca3af;
            --text-muted: #6b7280;
            --border-soft: rgba(148, 163, 184, 0.35);
            --border-strong: rgba(148, 163, 184, 0.5);
            --accent-1: #6366f1;
            --accent-2: #ec4899;
            --accent-3: #22c55e;
            --error-border: rgba(248, 113, 113, 0.75);
            --error-bg: rgba(248, 113, 113, 0.15);
            --error-text: #fecaca;
        }

        * {
            box-sizing: border-box;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: var(--bg-gradient);
            color: var(--text-primary);
        }

        a {
            color: #a5b4fc;
        }

        a:hover {
            color: #c7d2fe;
        }

        .auth-container {
            display: flex;
            width: 100%;
            max-width: 1000px;
            background: rgba(3, 7, 18, 0.98);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.85);
            border: 1px solid rgba(15, 23, 42, 0.9);
        }

        .auth-left {
            flex: 1.1;
            background:
                radial-gradient(circle at top left, rgba(56, 189, 248, 0.16), transparent 60%),
                radial-gradient(circle at bottom, rgba(236, 72, 153, 0.16), transparent 60%),
                linear-gradient(145deg, #020617, #020617);
            padding: 40px 32px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }

        .auth-left::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at 10% 20%, rgba(56, 189, 248, 0.08), transparent 55%),
                radial-gradient(circle at 80% 40%, rgba(129, 140, 248, 0.12), transparent 55%),
                radial-gradient(circle at 20% 80%, rgba(45, 212, 191, 0.12), transparent 60%);
            opacity: 0.9;
            pointer-events: none;
        }

        .auth-brand {
            position: relative;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .auth-logo-circle {
            width: 40px;
            height: 40px;
            border-radius: 20px;
            background: radial-gradient(circle at 20% 20%, #f97316, #ec4899);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow:
                0 0 0 1px rgba(255, 255, 255, 0.1),
                0 18px 45px rgba(0, 0, 0, 0.8);
        }

        .auth-logo-circle i {
            font-size: 20px;
            color: #fefce8;
        }

        .auth-brand-text {
            display: flex;
            flex-direction: column;
        }

        .auth-brand-text span:first-child {
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .auth-brand-text span:last-child {
            font-size: 12px;
            color: var(--text-secondary);
        }

        .auth-hero {
            position: relative;
            margin-top: 56px;
        }

        .auth-hero-title {
            font-size: 32px;
            line-height: 1.2;
            font-weight: 800;
            margin-bottom: 16px;
        }

        .auth-hero-title span {
            background: linear-gradient(135deg, var(--accent-3), #a855f7, var(--accent-2));
            -webkit-background-clip: text;
            color: transparent;
        }

        .auth-hero-subtitle {
            font-size: 14px;
            color: var(--text-secondary);
            max-width: 320px;
        }

        .auth-hero-badges {
            position: relative;
            margin-top: 32px;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .auth-badge {
            background: rgba(15, 23, 42, 0.96);
            border-radius: 16px;
            padding: 12px 14px;
            border: 1px solid var(--border-soft);
            display: flex;
            align-items: center;
            gap: 10px;
            backdrop-filter: blur(22px);
            box-shadow: 0 12px 35px rgba(15, 23, 42, 0.95);
        }

        .auth-badge-icon {
            width: 28px;
            height: 28px;
            border-radius: 999px;
            background: radial-gradient(circle at 30% 20%, var(--accent-3), rgba(34, 197, 94, 0.3));
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .auth-badge-icon i {
            font-size: 14px;
            color: #bbf7d0;
        }

        .auth-badge-text {
            display: flex;
            flex-direction: column;
        }

        .auth-badge-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .auth-badge-desc {
            font-size: 11px;
            color: var(--text-muted);
        }

        .auth-hero-footer {
            position: relative;
            margin-top: 40px;
            font-size: 11px;
            color: var(--text-muted);
        }

        .auth-hero-footer strong {
            color: #a5b4fc;
        }

        .auth-right {
            flex: 0.95;
            padding: 40px 32px;
            background:
                radial-gradient(circle at top, rgba(15, 23, 42, 0.9), #020617);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .auth-card {
            width: 100%;
            max-width: 360px;
            background: var(--card-bg);
            border-radius: 20px;
            padding: 26px 22px 24px;
            border: 1px solid var(--border-strong);
            box-shadow:
                0 18px 60px rgba(15, 23, 42, 0.95),
                0 0 0 1px rgba(15, 23, 42, 0.8);
            position: relative;
            overflow: hidden;
        }

        .auth-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at 0 0, rgba(56, 189, 248, 0.08), transparent 55%),
                radial-gradient(circle at 100% 100%, rgba(236, 72, 153, 0.08), transparent 55%);
            pointer-events: none;
        }

        .auth-card-header {
            position: relative;
            margin-bottom: 20px;
        }

        .auth-card-title {
            font-size: 22px;
            font-weight: 700;
            margin: 0 0 6px;
            color: var(--text-primary);
        }

        .auth-card-subtitle {
            font-size: 12px;
            color: var(--text-secondary);
        }

        .auth-alert-error {
            position: relative;
            margin-bottom: 16px;
            padding: 9px 10px;
            border-radius: 10px;
            border: 1px solid var(--error-border);
            background: var(--error-bg);
            color: var(--error-text);
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .auth-alert-error i {
            font-size: 13px;
            flex-shrink: 0;
        }

        .error-message {
            display: block;
            margin-top: 4px;
            font-size: 11px;
            color: var(--error-text);
        }

        .auth-form-group {
            position: relative;
            margin-bottom: 16px;
        }

        .auth-label-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 6px;
        }

        .auth-label {
            font-size: 12px;
            color: var(--text-secondary);
        }

        .auth-input-wrapper {
            position: relative;
        }

        .auth-input {
            width: 100%;
            padding: 10px 34px 10px 32px;
            border-radius: 12px;
            border: 1px solid rgba(51, 65, 85, 0.95);
            background: #020617;
            color: var(--text-primary);
            font-size: 13px;
            outline: none;
            transition: all 0.18s ease;
        }

        .auth-input::placeholder {
            color: rgba(148, 163, 184, 0.75);
        }

        .auth-input:focus {
            border-color: var(--accent-1);
            box-shadow:
                0 0 0 1px rgba(99, 102, 241, 0.9),
                0 0 0 10px rgba(79, 70, 229, 0.16);
            background: #020617;
        }

        .auth-input-icon-left,
        .auth-input-icon-right {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            font-size: 14px;
            color: var(--text-muted);
        }

        .auth-input-icon-left {
            left: 10px;
        }

        .auth-input-icon-right {
            right: 10px;
            cursor: pointer;
        }

        .auth-button {
            width: 100%;
            margin-top: 8px;
            padding: 10px 14px;
            border-radius: 999px;
            border: none;
            outline: none;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            background: linear-gradient(135deg, var(--accent-1), var(--accent-2));
            color: #f9fafb;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow:
                0 14px 40px rgba(79, 70, 229, 0.8),
                0 0 0 1px rgba(15, 23, 42, 0.9);
            transition: transform 0.12s ease, box-shadow 0.12s ease, filter 0.12s;
        }

        .auth-button:hover {
            transform: translateY(-1px);
            filter: brightness(1.05);
            box-shadow:
                0 18px 55px rgba(79, 70, 229, 0.9),
                0 0 0 1px rgba(15, 23, 42, 1);
        }

        .auth-button:active {
            transform: translateY(0);
            box-shadow:
                0 10px 32px rgba(79, 70, 229, 0.9),
                0 0 0 1px rgba(15, 23, 42, 1);
        }

        .auth-divider {
            margin: 18px 0 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 11px;
            color: var(--text-muted);
        }

        .auth-divider::before,
        .auth-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: linear-gradient(
                to right,
                transparent,
                rgba(148, 163, 184, 0.75),
                transparent
            );
        }

        .auth-footer {
            position: relative;
            margin-top: 10px;
            font-size: 11px;
            text-align: center;
            color: var(--text-secondary);
        }

        .auth-footer a {
            color: #a5b4fc;
            text-decoration: none;
            font-weight: 500;
        }

        .auth-footer a:hover {
            text-decoration: underline;
        }

        @media (max-width: 840px) {
            .auth-container {
                max-width: 420px;
            }

            .auth-left {
                display: none;
            }

            .auth-right {
                padding: 22px;
            }

            .auth-card {
                padding: 22px 18px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-left">
            <div class="auth-brand">
                <div class="auth-logo-circle">
                    <i class="fas fa-bolt"></i>
                </div>
                <div class="auth-brand-text">
                    <span>VIBEZ</span>
                    <span>Crie sua conta e entre na vibe</span>
                </div>
            </div>

            <div class="auth-hero">
                <h1 class="auth-hero-title">
                    Comece a <span>postar</span><br>e se conectar hoje.
                </h1>
                <p class="auth-hero-subtitle">
                    Com uma conta Vibez você pode compartilhar seus momentos, seguir amigos
                    e manter tudo organizado em um só lugar.
                </p>

                <div class="auth-hero-badges">
                    <div class="auth-badge">
                        <div class="auth-badge-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div class="auth-badge-text">
                            <span class="auth-badge-title">Conta verificada</span>
                            <span class="auth-badge-desc">Confirmação por e-mail para mais segurança.</span>
                        </div>
                    </div>

                    <div class="auth-badge">
                        <div class="auth-badge-icon" style="background: radial-gradient(circle at 30% 20%, #a855f7, #a855f733);">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <div class="auth-badge-text">
                            <span class="auth-badge-title">100% responsivo</span>
                            <span class="auth-badge-desc">Funciona bem no PC, tablet e celular.</span>
                        </div>
                    </div>

                    <div class="auth-badge">
                        <div class="auth-badge-icon" style="background: radial-gradient(circle at 30% 20%, #3b82f6, #3b82f633);">
                            <i class="fas fa-palette"></i>
                        </div>
                        <div class="auth-badge-text">
                            <span class="auth-badge-title">Tema escuro</span>
                            <span class="auth-badge-desc">Interface confortável para uso prolongado.</span>
                        </div>
                    </div>

                    <div class="auth-badge">
                        <div class="auth-badge-icon" style="background: radial-gradient(circle at 30% 20%, #f97316, #f9731633);">
                            <i class="fas fa-user-friends"></i>
                        </div>
                        <div class="auth-badge-text">
                            <span class="auth-badge-title">Comunidade</span>
                            <span class="auth-badge-desc">Conecte-se com pessoas reais.</span>
                        </div>
                    </div>
                </div>

                <div class="auth-hero-footer">
                    <strong>Privacidade:</strong> Seus dados são usados apenas para autenticação
                    e funcionamento da plataforma.
                </div>
            </div>
        </div>

        <div class="auth-right">
            <div class="auth-card">
                <div class="auth-card-header">
                    <h2 class="auth-card-title">Criar conta</h2>
                    <p class="auth-card-subtitle">
                        Preencha os dados abaixo para criar sua conta no Vibez.
                    </p>
                </div>

                <?php if (!empty($errors['general'])): ?>
                    <div class="auth-alert-error">
                        <i class="fas fa-circle-exclamation"></i>
                        <span><?php echo htmlspecialchars($errors['general']); ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <!-- Nome de usuário -->
                    <div class="auth-form-group">
                        <div class="auth-label-row">
                            <label class="auth-label" for="username">Nome de usuário</label>
                        </div>
                        <div class="auth-input-wrapper">
                            <span class="auth-input-icon-left">
                                <i class="fas fa-user"></i>
                            </span>
                            <input
                                type="text"
                                id="username"
                                name="username"
                                class="auth-input"
                                placeholder="ex: seunome"
                                value="<?php echo htmlspecialchars($username); ?>"
                                required
                            >
                        </div>
                        <?php if (!empty($errors['username'])): ?>
                            <span class="error-message"><?php echo htmlspecialchars($errors['username']); ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Email -->
                    <div class="auth-form-group">
                        <div class="auth-label-row">
                            <label class="auth-label" for="email">E-mail</label>
                        </div>
                        <div class="auth-input-wrapper">
                            <span class="auth-input-icon-left">
                                <i class="fas fa-envelope"></i>
                            </span>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="auth-input"
                                placeholder="voce@exemplo.com"
                                value="<?php echo htmlspecialchars($email); ?>"
                                required
                            >
                        </div>
                        <?php if (!empty($errors['email'])): ?>
                            <span class="error-message"><?php echo htmlspecialchars($errors['email']); ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Senha -->
                    <div class="auth-form-group">
                        <div class="auth-label-row">
                            <label class="auth-label" for="password">Senha</label>
                        </div>
                        <div class="auth-input-wrapper">
                            <span class="auth-input-icon-left">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="auth-input"
                                placeholder="Crie uma senha"
                                required
                            >
                            <span class="auth-input-icon-right" onclick="togglePassword('password', 'togglePasswordIcon')">
                                <i class="fas fa-eye" id="togglePasswordIcon"></i>
                            </span>
                        </div>
                        <?php if (!empty($errors['password'])): ?>
                            <span class="error-message"><?php echo htmlspecialchars($errors['password']); ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Confirmar senha -->
                    <div class="auth-form-group">
                        <div class="auth-label-row">
                            <label class="auth-label" for="confirm_password">Confirmar senha</label>
                        </div>
                        <div class="auth-input-wrapper">
                            <span class="auth-input-icon-left">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input
                                type="password"
                                id="confirm_password"
                                name="confirm_password"
                                class="auth-input"
                                placeholder="Repita a senha"
                                required
                            >
                            <span class="auth-input-icon-right" onclick="togglePassword('confirm_password', 'toggleConfirmIcon')">
                                <i class="fas fa-eye" id="toggleConfirmIcon"></i>
                            </span>
                        </div>
                        <?php if (!empty($errors['confirm_password'])): ?>
                            <span class="error-message"><?php echo htmlspecialchars($errors['confirm_password']); ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Botão registrar -->
                    <button type="submit" class="auth-button">
                        <i class="fas fa-user-plus"></i>
                        Registrar
                    </button>
                </form>

                <div class="auth-divider">
                    ou
                </div>

                <div class="auth-footer">
                    Já tem uma conta? <a href="/login.php">Faça login</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon  = document.getElementById(iconId);

            if (!input || !icon) return;

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
