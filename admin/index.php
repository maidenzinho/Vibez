<?php

require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!is_logged_in() || !is_admin()) {
    redirect('../index.php');
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Estatísticas
$stmt = $conn->query("SELECT COUNT(*) as total_users FROM users");
$total_users = $stmt->fetchColumn();

$stmt = $conn->query("SELECT COUNT(*) as total_posts FROM posts");
$total_posts = $stmt->fetchColumn();

$stmt = $conn->query("SELECT COUNT(*) as total_comments FROM comments");
$total_comments = $stmt->fetchColumn();

// Últimos usurios registrados
$stmt = $conn->query("SELECT id, username, email, created_at FROM users ORDER BY created_at DESC LIMIT 5");
$recent_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Posts reportados
$stmt = $conn->query("
    SELECT p.id, p.content, u.username, COUNT(r.id) as report_count
    FROM posts p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN reports r ON p.id = r.post_id
    GROUP BY p.id
    HAVING report_count > 0
    ORDER BY report_count DESC
    LIMIT 5
");
$reported_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    
    <div class="admin-content">
        <h2>Dashboard</h2>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Usuários</h3>
                <p><?php echo $total_users; ?></p>
            </div>
            
            <div class="stat-card">
                <h3>Posts</h3>
                <p><?php echo $total_posts; ?></p>
            </div>
            
            <div class="stat-card">
                <h3>Comentários</h3>
                <p><?php echo $total_comments; ?></p>
            </div>
        </div>
        
        <div class="admin-section">
            <h3>Últimos Usuários</h3>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usurio</th>
                        <th>Email</th>
                        <th>Data</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <a href="users.php?action=view&id=<?php echo $user['id']; ?>" class="action-btn view">Ver</a>
                                <a href="users.php?action=edit&id=<?php echo $user['id']; ?>" class="action-btn edit">Editar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="admin-section">
            <h3>Posts Reportados</h3>
            <?php if (count($reported_posts) > 0): ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Conteúdo</th>
                            <th>Usuário</th>
                            <th>Reports</th>
                            <th>Açes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reported_posts as $post): ?>
                            <tr>
                                <td><?php echo $post['id']; ?></td>
                                <td><?php echo strlen($post['content']) > 50 ? substr($post['content'], 0, 50) . '...' : $post['content']; ?></td>
                                <td><?php echo htmlspecialchars($post['username']); ?></td>
                                <td><?php echo $post['report_count']; ?></td>
                                <td>
                                    <a href="posts.php?action=view&id=<?php echo $post['id']; ?>" class="action-btn view">Ver</a>
                                    <a href="posts.php?action=delete&id=<?php echo $post['id']; ?>" class="action-btn delete">Excluir</a>
                                </td>
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