<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (is_logged_in()) {
    header("Location: index.php");
    exit();
}

$error    = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Preencha usuário/email e senha.';
    } else {
        $db   = Database::getInstance();
        $conn = $db->getConnection();

        try {
            $stmt = $conn->prepare(
                "SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1"
            );
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $stored = $user['password'] ?? '';
                $valid  = false;

                // 1) Senhas já no formato password_hash (bcrypt, argon2, etc.)
                if ($stored !== '' && $stored[0] === '$') {
                    $valid = password_verify($password, $stored);

                // 2) Senhas antigas em SHA-256 (64 caracteres hexadecimais)
                } elseif (strlen($stored) === 64 && ctype_xdigit($stored)) {
                    $hashDigitado = hash('sha256', $password);
                    $valid        = hash_equals($stored, $hashDigitado);

                    // Se o login com SHA-256 deu certo, migra para password_hash()
                    if ($valid) {
                        $novoHash = password_hash($password, PASSWORD_DEFAULT);

                        $update = $conn->prepare(
                            "UPDATE users SET password = ? WHERE id = ?"
                        );
                        $update->execute([$novoHash, $user['id']]);

                        // Atualiza em memória
                        $stored = $novoHash;
                    }

                // 3) Último fallback: senha salva em texto puro
                } else {
                    $valid = hash_equals((string) $stored, $password);
                }

                if ($valid) {
                    // Verifica se 2FA está habilitado
                    $twofaEnabled = !empty($user['twofa_enabled'] ?? 0)
                                    && !empty($user['2fa_secret'] ?? '');

                    if ($twofaEnabled) {
                        $_SESSION['temp_user'] = $user;
                        header("Location: 2fa/verify-2fa.php");
                        exit();
                    }

                    // Login normal sem 2FA
                    $_SESSION['user_id']      = $user['id'];
                    $_SESSION['username']     = $user['username'];
                    $_SESSION['profile_pic']  = $user['profile_pic'] ?? 'default-profile.png';
                    $_SESSION['theme']        = $user['theme_preference'] ?? 'light';
                    $_SESSION['is_admin']     = $user['is_admin'] ?? 0;
                    $_SESSION['2fa_verified'] = true;

                    header("Location: index.php");
                    exit();
                } else {
                    $error = 'Credenciais inválidas.';
                }
            } else {
                $error = 'Credenciais inválidas.';
            }
        } catch (Exception $e) {
            $error = 'Erro ao processar o login. Tente novamente mais tarde.';
            error_log('Erro no login: ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="<?php echo htmlspecialchars($_SESSION['theme'] ?? 'light'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Vibez</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/<?php echo htmlspecialchars($_SESSION['theme'] ?? 'light'); ?>-theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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

        .auth-remember-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11px;
            margin-bottom: 4px;
            color: var(--text-secondary);
        }

        .auth-remember {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .auth-remember input {
            width: 12px;
            height: 12px;
            border-radius: 4px;
            border: 1px solid rgba(148, 163, 184, 0.8);
            background: transparent;
        }

        .auth-remember span {
            color: var(--text-secondary);
        }

        .forgot-password {
            font-size: 11px;
            color: #a5b4fc;
            text-decoration: none;
        }

        .forgot-password:hover {
            text-decoration: underline;
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

        .auth-button i {
            font-size: 14px;
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
                    <span>Encontre pessoas, compartilhe momentos</span>
                </div>
            </div>

            <div class="auth-hero">
                <h1 class="auth-hero-title">
                    Entre na sua <span>vibe</span><br>e se conecte com o mundo.
                </h1>
                <p class="auth-hero-subtitle">
                    O Vibez foi pensado para você compartilhar sua rotina, descobrir novas pessoas
                    e criar conexões reais em um feed inteligente e personalizável.
                </p>

                <div class="auth-hero-badges">
                    <div class="auth-badge">
                        <div class="auth-badge-icon">
                            <i class="fas fa-lock"></i>
                        </div>
                        <div class="auth-badge-text">
                            <span class="auth-badge-title">Segurança em primeiro lugar</span>
                            <span class="auth-badge-desc">Criptografia, 2FA e controle de sessão.</span>
                        </div>
                    </div>

                    <div class="auth-badge">
                        <div class="auth-badge-icon" style="background: radial-gradient(circle at 30% 20%, #a855f7, #a855f733);">
                            <i class="fas fa-wand-magic-sparkles"></i>
                        </div>
                        <div class="auth-badge-text">
                            <span class="auth-badge-title">Timeline inteligente</span>
                            <span class="auth-badge-desc">Conteúdo organizado do jeito que você gosta.</span>
                        </div>
                    </div>

                    <div class="auth-badge">
                        <div class="auth-badge-icon" style="background: radial-gradient(circle at 30% 20%, #3b82f6, #3b82f633);">
                            <i class="fas fa-palette"></i>
                        </div>
                        <div class="auth-badge-text">
                            <span class="auth-badge-title">Tema claro e escuro</span>
                            <span class="auth-badge-desc">Visual que acompanha seu estilo e horário.</span>
                        </div>
                    </div>

                    <div class="auth-badge">
                        <div class="auth-badge-icon" style="background: radial-gradient(circle at 30% 20%, #f97316, #f9731633);">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="auth-badge-text">
                            <span class="auth-badge-title">Comunidade ativa</span>
                            <span class="auth-badge-desc">Interaja com pessoas em tempo real.</span>
                        </div>
                    </div>
                </div>

                <div class="auth-hero-footer">
                    <strong>Dica:</strong> Ative a autenticação em duas etapas (2FA)
                    para proteger ainda mais a sua conta.
                </div>
            </div>
        </div>

        <div class="auth-right">
            <div class="auth-card">
                <div class="auth-card-header">
                    <h2 class="auth-card-title">Bem-vindo de volta</h2>
                    <p class="auth-card-subtitle">
                        Acesse sua conta para continuar explorando o Vibez.
                    </p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="auth-alert-error">
                        <i class="fas fa-circle-exclamation"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="auth-form-group">
                        <div class="auth-label-row">
                            <label class="auth-label" for="username">Usuário ou e-mail</label>
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
                                placeholder="ex: seunome ou voce@exemplo.com"
                                value="<?php echo htmlspecialchars($username ?? ''); ?>"
                                required
                            >
                        </div>
                    </div>

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
                                placeholder="Digite sua senha"
                                required
                            >
                            <span class="auth-input-icon-right" onclick="togglePassword()">
                                <i class="fas fa-eye" id="togglePasswordIcon"></i>
                            </span>
                        </div>
                    </div>

                    <div class="auth-remember-row">
                        <label class="auth-remember">
                            <input type="checkbox" name="remember">
                            <span>Permanecer conectado</span>
                        </label>
                        <a href="forgot-password.php" class="forgot-password">Esqueceu a senha?</a>
                    </div>

                    <button type="submit" class="auth-button">
                        <i class="fas fa-sign-in-alt"></i>
                        Entrar
                    </button>
                </form>

                <div class="auth-divider">
                    ou
                </div>

                <div class="auth-footer">
                    Não tem uma conta? <a href="/auth/register.php">Registre-se</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const icon = document.getElementById('togglePasswordIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
