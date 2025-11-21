<?php
// /admin/comments.php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!is_logged_in() || !is_admin()) {
    redirect('../index.php');
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Apagar comentário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_comment_id'])) {
    $commentId = (int) $_POST['delete_comment_id'];
    $stmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
    $stmt->execute([$commentId]);
    header("Location: comments.php");
    exit;
}

// Editar comentário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_comment_id'], $_POST['edited_content'])) {
    $commentId = (int) $_POST['edit_comment_id'];
    $editedContent = trim($_POST['edited_content']);

    if (!empty($editedContent)) {
        $stmt = $conn->prepare("UPDATE comments SET content = ? WHERE id = ?");
        $stmt->execute([$editedContent, $commentId]);
        header("Location: comments.php");
        exit;
    }
}

$stmt = $conn->query("SELECT comments.*, users.username, posts.content AS post_content FROM comments 
                      JOIN users ON comments.user_id = users.id 
                      JOIN posts ON comments.post_id = posts.id 
                      ORDER BY comments.created_at DESC");
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
?>

<div class="admin-container">
    <?php include 'sidebar.php'; ?>
    <div class="admin-content">
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
        <h2>Gerenciar Comentários</h2>
        <div class="admin-section">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Comentário</th>
                        <th>Usuário</th>
                        <th>Post</th>
                        <th>Data</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($comments as $comment): ?>
                        <tr>
                            <td><?= $comment['id']; ?></td>
                            <td>
                                <form method="post" style="display:flex; flex-direction:column;">
                                    <textarea name="edited_content" rows="2" style="resize:vertical;"><?= htmlspecialchars($comment['content']); ?></textarea>
                                    <input type="hidden" name="edit_comment_id" value="<?= $comment['id']; ?>">
                                    <button type="submit" class="action-btn edit" style="margin-top:5px;">Salvar</button>
                                </form>
                            </td>
                            <td><?= htmlspecialchars($comment['username']); ?></td>
                            <td><?= strlen($comment['post_content']) > 30 ? substr($comment['post_content'], 0, 30) . '...' : $comment['post_content']; ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($comment['created_at'])); ?></td>
                            <td>
                                <form method="post" onsubmit="return confirm('Tem certeza que deseja deletar este comentário?');">
                                    <input type="hidden" name="delete_comment_id" value="<?= $comment['id']; ?>">
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
<?php require_once '../includes/footer.php'; ?>
