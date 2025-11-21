<?php
// /admin/posts.php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!is_logged_in() || !is_admin()) {
    redirect('../index.php');
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Deletar post
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post_id'])) {
    $deleteId = (int) $_POST['delete_post_id'];
    $stmt = $conn->prepare("DELETE FROM posts WHERE id = ?");
    $stmt->execute([$deleteId]);
    header("Location: posts.php");
    exit;
}

// Editar post
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_post_id'], $_POST['edit_content'])) {
    $editId = (int) $_POST['edit_post_id'];
    $editContent = trim($_POST['edit_content']);
    if (!empty($editContent)) {
        $stmt = $conn->prepare("UPDATE posts SET content = ? WHERE id = ?");
        $stmt->execute([$editContent, $editId]);
        header("Location: posts.php");
        exit;
    }
}

$stmt = $conn->query("SELECT posts.*, users.username FROM posts JOIN users ON posts.user_id = users.id ORDER BY posts.created_at DESC");
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

    <?php include 'sidebar.php'; ?>
    <div class="admin-content">
        <h2>Todos os Posts</h2>
        <div class="admin-section">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Conteúdo</th>
                        <th>Usuário</th>
                        <th>Data</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($posts as $post): ?>
                        <tr>
                            <td><?= $post['id']; ?></td>
                            <td><?= strlen($post['content']) > 50 ? substr($post['content'], 0, 50) . '...' : $post['content']; ?></td>
                            <td><?= htmlspecialchars($post['username']); ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($post['created_at'])); ?></td>
                            <td>
                                <!-- Botão Editar -->
                                <button onclick="toggleEditForm(<?= $post['id']; ?>)" class="action-btn edit">Editar</button>

                                <!-- Formulário Editar -->
                                <form method="post" class="edit-form" id="edit-form-<?= $post['id']; ?>" style="display:none; margin-top:5px;">
                                    <input type="hidden" name="edit_post_id" value="<?= $post['id']; ?>">
                                    <textarea name="edit_content" rows="3" style="width:100%;"><?= htmlspecialchars($post['content']); ?></textarea>
                                    <button type="submit" class="action-btn edit" style="margin-top:5px;">Salvar</button>
                                </form>

                                <!-- Botão Deletar -->
                                <form method="post" onsubmit="return confirm('Tem certeza que deseja deletar este post?');" style="display:inline;">
                                    <input type="hidden" name="delete_post_id" value="<?= $post['id']; ?>">
                                    <button type="submit" class="action-btn delete">Deletar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<link rel="stylesheet" href="../assets/css/admin.css">

<script>
    function toggleEditForm(postId) {
        const form = document.getElementById('edit-form-' + postId);
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
    }
</script>

<?php require_once '../includes/footer.php'; ?>
