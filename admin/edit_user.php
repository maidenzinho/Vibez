<?php
// /admin/edit_user.php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!is_logged_in() || !is_admin()) {
    redirect('../index.php');
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect('users.php');
}

$userId = (int) $_GET['id'];
$db = Database::getInstance();
$conn = $db->getConnection();

// Buscar dados do usuário
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "Usuário não encontrado.";
    exit;
}

// Atualizar usuário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;

    if (!empty($username) && !empty($email)) {
        $update = $conn->prepare("UPDATE users SET username = ?, email = ?, is_admin = ? WHERE id = ?");
        $update->execute([$username, $email, $is_admin, $userId]);
        header("Location: users.php");
        exit;
    } else {
        $error = "Todos os campos são obrigatórios.";
    }
}

require_once '../includes/header.php';
?>

<div class="admin-container">
    <?php include 'sidebar.php'; ?>
    <div class="admin-content">
        <h2>Editar Usuário</h2>

        <?php if (isset($error)): ?>
            <p style="color:red;"><?= htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form method="post" class="admin-form">
            <label for="username">Nome de Usuário:</label>
            <input type="text" name="username" id="username" value="<?= htmlspecialchars($user['username']); ?>" required class="admin-input">

            <label for="email">Email:</label>
            <input type="email" name="email" id="email" value="<?= htmlspecialchars($user['email']); ?>" required class="admin-input">

            <label for="is_admin">
                <input type="checkbox" name="is_admin" id="is_admin" <?= $user['is_admin'] ? 'checked' : ''; ?>>
                Administrador
            </label>

            <button type="submit" class="action-btn edit">Salvar Alterações</button>
        </form>
    </div>
</div>

<link rel="stylesheet" href="../assets/css/admin.css">
<?php require_once '../includes/footer.php'; ?>
