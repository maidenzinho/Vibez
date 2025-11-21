<?php

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (!is_logged_in()) {
    header('Location: ../login.php');
    exit();
}

$db = Database::getInstance();
$conn = $db->getConnection();

$stmt = $conn->prepare("SELECT id, username, email, full_name, bio, profile_pic, banner, password FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize_input($_POST['full_name'] ?? '');
    $bio = sanitize_input($_POST['bio'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $update_fields = [
        'full_name' => $full_name,
        'bio' => $bio
    ];

    // Upload da imagem de perfil
    if (!empty($_FILES['profile_pic']['name'])) {
        $upload = upload_image($_FILES['profile_pic']);
        if ($upload['success']) {
            $update_fields['profile_pic'] = $upload['filename'];
            if ($user['profile_pic'] !== 'default-profile.png') {
                @unlink(__DIR__ . '/../uploads/' . $user['profile_pic']);
            }
        } else {
            $errors['profile_pic'] = $upload['error'];
        }
    }

    // Upload do banner
    if (!empty($_FILES['banner']['name'])) {
        $upload = upload_image($_FILES['banner']);
        if ($upload['success']) {
            $update_fields['banner'] = $upload['filename'];
            if (!empty($user['banner'])) {
                @unlink(__DIR__ . '/../uploads/' . $user['banner']);
            }
        } else {
            $errors['banner'] = $upload['error'];
        }
    }

    // Atualização de senha
    if (!empty($current_password)) {
        if (password_verify($current_password, $user['password'])) {
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

    if (empty($errors)) {
        try {
            $set_clause = implode(', ', array_map(fn($field) => "$field = ?", array_keys($update_fields)));
            $values = array_values($update_fields);
            $values[] = $_SESSION['user_id'];

            $stmt = $conn->prepare("UPDATE users SET $set_clause WHERE id = ?");
            $stmt->execute($values);

            // Atualiza sessões de imagem de perfil e banner
            $_SESSION['user_profile_pic'] = $update_fields['profile_pic'] ?? $user['profile_pic'];
            $_SESSION['user_banner'] = $update_fields['banner'] ?? $user['banner'];

            $_SESSION['success'] = 'Perfil atualizado com sucesso!';
            header('Location: index.php');
            exit();
        } catch (PDOException $e) {
            $errors['database'] = 'Erro ao atualizar perfil: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/menumobile.php';
?>

<div class="main-content">
    <div class="settings-container">
        <h2>Editar Perfil</h2>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <?php if (!empty($errors['database'])): ?>
            <div class="alert alert-danger"><?php echo $errors['database']; ?></div>
        <?php endif; ?>

        <form action="edit.php" method="POST" enctype="multipart/form-data">
            <div class="form-section">
                <h3>Informações Básicas</h3>

                <div class="form-group">
                    <label for="full_name">Nome Completo</label>
                    <input type="text" id="full_name" name="full_name" 
                           value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="bio">Biografia</label>
                    <textarea id="bio" name="bio" rows="3"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="profile_pic">Foto de Perfil</label>
                    <div class="profile-pic-upload">
                        <input type="file" id="profile_pic" name="profile_pic" accept="image/*">
                        <?php if (isset($errors['profile_pic'])): ?>
                            <span class="error-message"><?php echo $errors['profile_pic']; ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="banner">Banner de Perfil</label>
                    <input type="file" id="banner" name="banner" accept="image/*">
                    <?php if (isset($errors['banner'])): ?>
                        <span class="error-message"><?php echo $errors['banner']; ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <button type="submit" class="save-btn">Salvar Alteraçes</button>
        </form>
    </div>
</div>

<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
