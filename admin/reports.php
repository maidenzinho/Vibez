<?php
// /admin/reports.php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!is_logged_in() || !is_admin()) {
    redirect('../index.php');
}

$db = Database::getInstance();
$conn = $db->getConnection();

$stmt = $conn->query("SELECT p.id, p.content, u.username, COUNT(r.id) as report_count FROM posts p JOIN users u ON p.user_id = u.id LEFT JOIN reports r ON p.id = r.post_id GROUP BY p.id HAVING report_count > 0 ORDER BY report_count DESC");
$reported_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
?>

<div class="admin-container">
    <div class="admin-sidebar">
        <h3>Admin Panel</h3>
        <nav class="admin-nav">
            <a href="https://vibez.allsocial.com.br" class="active">Voltar para o site</a>
            <a href="index.php" class="active">Dashboard</a>
            <a href="users.php">Usurios</a>
            <a href="posts.php">Posts</a>
            <a href="comments.php">Comentários</a>
            <a href="reports.php">Reports</a>
            <a href="settings.php">Configurações</a>
        </nav>
    </div>

<div class="admin-container">
    <?php include 'sidebar.php'; ?>
    <div class="admin-content">
        <h2>Posts Reportados</h2>
        <div class="admin-section">
            <?php if (count($reported_posts) > 0): ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Conteúdo</th>
                            <th>Usuário</th>
                            <th>Reports</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reported_posts as $post): ?>
                            <tr>
                                <td><?= $post['id']; ?></td>
                                <td><?= strlen($post['content']) > 50 ? substr($post['content'], 0, 50) . '...' : $post['content']; ?></td>
                                <td><?= htmlspecialchars($post['username']); ?></td>
                                <td><?= $post['report_count']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Nenhum post reportado.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<link rel="stylesheet" href="../assets/css/admin.css">

<?php require_once '../includes/footer.php'; ?>