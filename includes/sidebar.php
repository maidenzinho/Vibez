<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

if (!is_logged_in()) {
    return;
}

$user_id = $_SESSION['user_id'];

// Pegando informações do usuário logado
$db = Database::getInstance();
$conn = $db->getConnection();
$stmt = $conn->prepare("SELECT username, profile_pic FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

$username = htmlspecialchars($profile['username']);
$profile_pic = getProfilePic($user_id, $profile['profile_pic']);
?>

<link rel="stylesheet" href="/assets/css/profile.css">
<div class="sidebar">
    <div class="sidebar-header">
        <!-- Logo como link para a home -->
        <a href="<?php echo SITE_URL; ?>" class="logo-link">
            <img src="<?php echo SITE_URL; ?>/assets/images/logo.png" alt="Logo" class="sidebar-logo">
        </a>
        <span class="sidebar-title">Vibez</span>
    </div>

    <nav class="sidebar-nav">
        <a href="<?php echo SITE_URL; ?>" class="sidebar-link">
            <i class="fas fa-home sidebar-icon"></i>
            <span>Home</span>
        </a>
        <a href="<?php echo SITE_URL; ?>/chat/index.php" class="sidebar-link">
            <i class="fas fa-comments sidebar-icon"></i>
            <span>Chat</span>
        </a>
        <a href="<?php echo SITE_URL; ?>/search.php" class="sidebar-link">
            <i class="fas fa-hashtag sidebar-icon"></i>
            <span>Explorar</span>
        </a>
        <a href="<?php echo SITE_URL; ?>/notifications.php" class="sidebar-link">
            <i class="fas fa-bell sidebar-icon"></i>
            <span>Notificações</span>
        </a>
    </nav>

    <div class="sidebar-profile">
        <a href="<?php echo SITE_URL; ?>/profile/?user=<?php echo urlencode($profile['username']); ?>" class="profile-link">
            <img src="<?php echo $profile_pic; ?>" alt="Perfil" class="profile-pic">
            <span><?php echo $username; ?></span>
        </a>
    </div>

    <a href="<?php echo SITE_URL; ?>/settings.php" class="sidebar-link">
        <i class="fas fa-cog sidebar-icon"></i>
        <span>Configurações</span>
    </a>
</div>
