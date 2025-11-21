<?php
// /admin/users.php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!is_logged_in() || !is_admin()) {
    redirect('../index.php');
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Lógica para deletar usuário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user_id'])) {
    $deleteId = (int) $_POST['delete_user_id'];
    if ($deleteId !== $_SESSION['user_id']) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$deleteId]);
        header("Location: users.php");
        exit;
    }
}

$stmt = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
?>

<div class="admin-container">
    <div class="admin-sidebar">
        <h3>Admin Panel</h3>
        <nav class="admin-nav">
            <a href="https://vibez.allsocial.com.br" class="active">Voltar para o site</a>
            <a href="index.php" class="active">Dashboard</a>
            <a href="users.php">Usuários</a>
            <a href="posts.php">Posts</a>
            <a href="comments.php">Comentários</a>
            <a href="reports.php">Reports</a>
            <a href="settings.php">Configurações</a>
        </nav>
    </div>

<div class="admin-container">
    <?php include 'sidebar.php'; ?>
    <div class="admin-content">
        <h2>Gerenciar Usuários</h2>
        <div class="admin-section">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Tipo</th>
                        <th>Data de Registro</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= $user['id']; ?></td>
                            <td><?= htmlspecialchars($user['username']); ?></td>
                            <td><?= htmlspecialchars($user['email']); ?></td>
                            <td><?= $user['is_admin'] ? 'Admin' : 'Usuário'; ?></td>
                            <td><?= date('d/m/Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                    <form method="post" onsubmit="return confirm('Tem certeza que deseja deletar este usuário?');" style="display:inline;">
                                        <input type="hidden" name="delete_user_id" value="<?= $user['id']; ?>">
                                        <button type="submit" class="action-btn delete">Deletar</button>
                                    </form>
                                    <a href="edit_user.php?id=<?= $user['id']; ?>" class="action-btn edit">Editar</a>
                                <?php else: ?>
                                    <span style="color: gray;">Você</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>


<?php
// /admin/posts.php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!is_logged_in() || !is_admin()) {
    redirect('../index.php');
}

$db = Database::getInstance();
$conn = $db->getConnection();

$stmt = $conn->query("SELECT posts.id, posts.content, users.username, posts.created_at FROM posts JOIN users ON posts.user_id = users.id ORDER BY posts.created_at DESC");
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
?>
<div class="admin-container">
    <?php include 'sidebar.php'; ?>
    <div class="admin-content">
        <h2>Gerenciar Posts</h2>
        <div class="admin-section">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuário</th>
                        <th>Conteúdo</th>
                        <th>Data</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($posts as $post): ?>
                        <tr>
                            <td><?= $post['id']; ?></td>
                            <td><?= htmlspecialchars($post['username']); ?></td>
                            <td><?= strlen($post['content']) > 50 ? substr($post['content'], 0, 50) . '...' : $post['content']; ?></td>
                            <td><?= date('d/m/Y', strtotime($post['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>


<?php
// /admin/reports.php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!is_logged_in() || !is_admin()) {
    redirect('../index.php');
}

$db = Database::getInstance();
$conn = $db->getConnection();

$stmt = $conn->query("SELECT posts.id, posts.content, users.username, COUNT(reports.id) as report_count FROM posts JOIN users ON posts.user_id = users.id JOIN reports ON posts.id = reports.post_id GROUP BY posts.id ORDER BY report_count DESC");
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
?>
<div class="admin-container">
    <?php include 'sidebar.php'; ?>
    <div class="admin-content">
        <h2>Posts Reportados</h2>
        <div class="admin-section">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuário</th>
                        <th>Conteúdo</th>
                        <th>Quantidade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reports as $report): ?>
                        <tr>
                            <td><?= $report['id']; ?></td>
                            <td><?= htmlspecialchars($report['username']); ?></td>
                            <td><?= strlen($report['content']) > 50 ? substr($report['content'], 0, 50) . '...' : $report['content']; ?></td>
                            <td><?= $report['report_count']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>


<?php
// /admin/settings.php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!is_logged_in() || !is_admin()) {
    redirect('../index.php');
}

require_once '../includes/header.php';
?>
<div class="admin-container">
    <?php include 'sidebar.php'; ?>
    <div class="admin-content">
        <h2>Configurações do Admin</h2>
        <div class="admin-section">
            <form method="post" action="save_settings.php">
                <label for="site_name">Nome do site:</label>
                <input type="text" name="site_name" id="site_name" class="admin-input" placeholder="Ex: Vibez Social">

                <label for="maintenance">Modo de Manutenção:</label>
                <select name="maintenance" id="maintenance" class="admin-input">
                    <option value="0">Desativado</option>
                    <option value="1">Ativado</option>
                </select>

                <button type="submit" class="action-btn edit">Salvar Configuraçes</button>
            </form>
        </div>
    </div>
</div>

<link rel="stylesheet" href="../assets/css/admin.css">

<?php require_once '../includes/footer.php'; ?>