<?php

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once '2fa/phpqrcode/qrlib.php';
require_once '2fa/PHPGangsta/GoogleAuthenticator.php';

if (!is_logged_in() || !isset($_SESSION['user_id'])) {
    redirect('login.php');
}

$db = Database::getInstance();
$conn = $db->getConnection();

$stmt = $conn->prepare("SELECT email, theme_preference, twofa_enabled, 2fa_secret FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$errors = [];
$success = false;
$ga = new PHPGangsta_GoogleAuthenticator();

if ($user['twofa_enabled'] && empty($user['2fa_secret'])) {
    $new_secret = $ga->createSecret();
    $stmt = $conn->prepare("UPDATE users SET 2fa_secret = ? WHERE id = ?");
    $stmt->execute([$new_secret, $_SESSION['user_id']]);
    $user['2fa_secret'] = $new_secret;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email']);
    $theme = sanitize_input($_POST['theme']);
    $twofa_enabled = isset($_POST['twofa_enabled']) ? 1 : 0;
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    $update_fields = [
        'theme_preference' => $theme,
        'twofa_enabled' => $twofa_enabled
    ];

    if ($email !== $user['email']) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Por favor, insira um email válido.';
        } else {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $_SESSION['user_id']]);
            if ($stmt->fetch()) {
                $errors['email'] = 'Este email já está em uso.';
            } else {
                $update_fields['email'] = $email;
            }
        }
    }

    if (!empty($current_password)) {
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $db_password = $stmt->fetchColumn();

        if (password_verify($current_password, $db_password)) {
            if ($new_password === $confirm_password) {
                if (strlen($new_password) >= 6) {
                    $update_fields['password'] = password_hash($new_password, PASSWORD_DEFAULT);
                } else {
                    $errors['new_password'] = 'A nova senha deve ter pelo menos 6 caracteres.';
                }
            } else {
                $errors['confirm_password'] = 'As senhas não coincidem.';
            }
        } else {
            $errors['current_password'] = 'Senha atual incorreta.';
        }
    }

    if ($twofa_enabled && empty($user['2fa_secret'])) {
        $update_fields['2fa_secret'] = $ga->createSecret();
    }

    if (!$twofa_enabled) {
        $update_fields['2fa_secret'] = null;
    }

    if (empty($errors)) {
        $set_clause = implode(', ', array_map(fn($field) => "$field = ?", array_keys($update_fields)));
        $values = array_values($update_fields);
        $values[] = $_SESSION['user_id'];

        $stmt = $conn->prepare("UPDATE users SET $set_clause WHERE id = ?");
        $stmt->execute($values);

        $_SESSION['theme'] = $theme;

        if (isset($update_fields['email'])) {
            $_SESSION['email'] = $update_fields['email'];
            $user['email'] = $update_fields['email'];
        }

        $user['theme_preference'] = $theme;
        $user['twofa_enabled'] = $twofa_enabled;
        if (isset($update_fields['2fa_secret'])) {
            $user['2fa_secret'] = $update_fields['2fa_secret'];
        }

        $success = true;
    }
}

require_once 'includes/header.php';
require_once 'includes/sidebar.php';
require_once 'includes/menumobile.php';
?>

<div class="main-content">
    <div class="settings-container">
        <h2>Configurações</h2>

        <?php if ($success): ?>
            <div class="alert alert-success">Configurações atualizadas com sucesso!</div>
        <?php endif; ?>

        <form action="settings.php" method="POST">
            <div class="form-section">
                <h3>Preferências</h3>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($user['email']); ?>">
                    <?php if (isset($errors['email'])): ?>
                        <span class="error-message"><?php echo $errors['email']; ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="theme">Tema</label>
                    <select id="theme" name="theme">
                        <option value="light" <?php echo $user['theme_preference'] === 'light' ? 'selected' : ''; ?>>Claro</option>
                        <option value="dark" <?php echo $user['theme_preference'] === 'dark' ? 'selected' : ''; ?>>Escuro</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="twofa_enabled" <?php echo $user['twofa_enabled'] ? 'checked' : ''; ?>>
                        Ativar autenticação de dois fatores (2FA)
                    </label>
                </div>

                <?php if ($user['twofa_enabled'] && $user['2fa_secret']): ?>
                    <div class="form-group">
                        <p>Escaneie este QR Code com o Google Authenticator:</p>
                        <?php
                            $qr_text = "otpauth://totp/VibezNetwork ({$_SESSION['username']})?secret={$user['2fa_secret']}&issuer=VibezNetwork";
                            $qr_file = 'temp/qrcode_' . $_SESSION['user_id'] . '.png';
                            QRcode::png($qr_text, $qr_file, QR_ECLEVEL_L, 4);
                            echo "<img src='$qr_file' alt='QR Code 2FA'>";
                        ?>
                        <p>Ou use este código manualmente: <strong><?php echo $user['2fa_secret']; ?></strong></p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="form-section">
                <h3>Alterar Senha</h3>
                <!-- campos de senha -->
                <div class="form-group">
                    <label for="current_password">Senha Atual</label>
                    <input type="password" id="current_password" name="current_password">
                    <?php if (isset($errors['current_password'])): ?>
                        <span class="error-message"><?php echo $errors['current_password']; ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="new_password">Nova Senha</label>
                    <input type="password" id="new_password" name="new_password">
                    <?php if (isset($errors['new_password'])): ?>
                        <span class="error-message"><?php echo $errors['new_password']; ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirmar Nova Senha</label>
                    <input type="password" id="confirm_password" name="confirm_password">
                    <?php if (isset($errors['confirm_password'])): ?>
                        <span class="error-message"><?php echo $errors['confirm_password']; ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <button type="submit" class="save-btn">Salvar Alterações</button>
        </form>

        <div class="danger-zone">
            <h3>Zona de Perigo</h3>
            <button class="delete-account-btn" id="delete-account">Excluir Conta</button>
        </div>
    </div>
</div>

<script>
document.getElementById('delete-account').addEventListener('click', () => {
    if (confirm('Tem certeza que deseja excluir sua conta? Esta ação não pode ser desfeita.')) {
        fetch('/api/delete-account', { method: 'POST' })
        .then(res => res.json())
        .then(data => {
            if (data.success) window.location.href = '/logout.php';
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
