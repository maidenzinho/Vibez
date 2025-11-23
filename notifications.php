<?php

require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!is_logged_in()) {
    redirect('login.php');
}

$db = Database::getInstance();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];

// Marcar como visualizadas
$conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$user_id]);

// Buscar notificações
$stmt = $conn->prepare("
    SELECT n.*, 
           u.username AS from_user, 
           u.profile_pic, 
           p.content AS post_content,
           p.id AS post_id
    FROM notifications n
    LEFT JOIN users u ON n.from_user_id = u.id
    LEFT JOIN posts p ON n.post_id = p.id
    WHERE n.user_id = ?
    ORDER BY n.created_at DESC
");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
?>

<div class="main-container">
    <?php include 'includes/sidebar.php'; ?>
    <?php include 'includes/menumobile.php'; ?>
    
    <h2 class="text">Notificações</h2>
    <div class="content-container">
        <div class="notifications-wrapper">
            <?php if (empty($notifications)): ?>
                <div class="no-notifications">
                    <p>Você não tem notificaões no momento.</p>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $n): ?>
                    <div class="notification <?= !$n['is_read'] ? 'unread' : ''; ?>">
                        <div class="notif-avatar">
                            <img src="<?= getProfilePic($n['profile_pic']); ?>" alt="Foto de perfil">
                        </div>
                        <div class="notif-content">
                            <p>
                                <strong><?= htmlspecialchars($n['from_user']); ?></strong>
                                <?php
                                    if ($n['type'] === 'like') {
                                        echo ' curtiu seu post.';
                                    } elseif ($n['type'] === 'comment') {
                                        echo ' comentou em seu post.';
                                    } elseif ($n['type'] === 'follow') {
                                        echo ' comeou a te seguir.';
                                    } else {
                                        echo ' interagiu com você.';
                                    }
                                ?>
                                <?php if ($n['post_id']): ?>
                                    <br>
                                    <a href="post.php?id=<?= $n['post_id']; ?>">
                                        <small>"<?= substr($n['post_content'], 0, 60); ?>..."</small>
                                    </a>
                                <?php endif; ?>
                            </p>
                            <span class="notif-time"><?= date('d/m/Y H:i', strtotime($n['created_at'])); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

 <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/notifications.css">
<?php require_once 'includes/footer.php'; ?>
