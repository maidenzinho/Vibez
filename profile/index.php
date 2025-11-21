<?php

session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (!is_logged_in()) {
    header('Location: ../login.php');
    exit();
}

$profile_username = isset($_GET['user']) ? sanitize_input($_GET['user']) : $_SESSION['username'];
$is_own_profile = ($profile_username === $_SESSION['username']);

$db = Database::getInstance();
$conn = $db->getConnection();

$stmt = $conn->prepare("SELECT id, username, email, full_name, bio, profile_pic, created_at FROM users WHERE username = ?");
$stmt->execute([$profile_username]);
$profile_user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profile_user) {
    $_SESSION['error'] = 'Usuário não encontrado.';
    header('Location: ../index.php');
    exit();
}

// Verificar se segue o perfil
$is_following = false;
$is_followed_back = false;

if (!$is_own_profile) {
    $stmt = $conn->prepare("SELECT 1 FROM followers WHERE follower_id = ? AND following_id = ?");
    $stmt->execute([$_SESSION['user_id'], $profile_user['id']]);
    $is_following = (bool)$stmt->fetch();

    $stmt = $conn->prepare("SELECT 1 FROM followers WHERE follower_id = ? AND following_id = ?");
    $stmt->execute([$profile_user['id'], $_SESSION['user_id']]);
    $is_followed_back = (bool)$stmt->fetch();
}

// Contagens
$stmt = $conn->prepare("
    SELECT 
        (SELECT COUNT(*) FROM posts WHERE user_id = ?) as post_count,
        (SELECT COUNT(*) FROM followers WHERE follower_id = ?) as following_count,
        (SELECT COUNT(*) FROM followers WHERE following_id = ?) as followers_count
");
$stmt->execute([$profile_user['id'], $profile_user['id'], $profile_user['id']]);
$counts = $stmt->fetch(PDO::FETCH_ASSOC);

// Posts
$stmt = $conn->prepare("
    SELECT p.*, u.username, u.profile_pic, 
           (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
           (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count,
           (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?) as user_liked
    FROM posts p
    JOIN users u ON p.user_id = u.id
    WHERE p.user_id = ?
    ORDER BY p.created_at DESC
");
$stmt->execute([$_SESSION['user_id'], $profile_user['id']]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/menumobile.php';
?>

<div class="main-content">
    <div class="profile-banner-wrapper">
        <div class="profile-banner" style="background-image: url('<?php echo getBanner($profile_user['id']); ?>');"></div>
        <div class="profile-pic-container">
            <img class="profile-pic" src="<?php echo getProfilePic($profile_user['id'], $profile_user['profile_pic']); ?>" alt="Perfil">
        </div>
    </div>

    <br><div class="profile-info">
        <div class="profile-details">
            <h1><?php echo !empty($profile_user['full_name']) ? htmlspecialchars($profile_user['full_name']) : htmlspecialchars($profile_user['username']); ?></h1>
            <span class="profile-username">@<?php echo htmlspecialchars($profile_user['username']); ?></span>

            <?php if (!empty($profile_user['bio'])): ?>
                <p class="profile-bio"><?php echo parse_hashtags(htmlspecialchars($profile_user['bio'])); ?></p>
            <?php endif; ?>

            <div class="profile-stats">
                <div class="stat">
                    <span class="stat-count"><?php echo $counts['post_count']; ?></span>
                    <span class="stat-label">Posts</span>
                </div>

                <a href="<?php echo SITE_URL; ?>/profile/followers.php?user=<?php echo urlencode($profile_user['username']); ?>" class="stat">
                    <span class="stat-count"><?php echo $counts['followers_count']; ?></span>
                    <span class="stat-label">Seguidores</span>
                </a>

                <a href="<?php echo SITE_URL; ?>/profile/following.php?user=<?php echo urlencode($profile_user['username']); ?>" class="stat">
                    <span class="stat-count"><?php echo $counts['following_count']; ?></span>
                    <span class="stat-label">Seguindo</span>
                </a>
            </div>

            <div class="profile-joined">
                <i class="fas fa-calendar-alt"></i>
                <span>Entrou em <?php echo date('d/m/Y', strtotime($profile_user['created_at'])); ?></span>
            </div>
            <br>
            <?php if ($is_own_profile): ?>
                <a href="edit.php" class="edit-profile-btn">Editar perfil</a>
            <?php else: ?>
                <button class="follow-btn <?php echo $is_following ? 'following' : ''; ?>" 
                        data-user-id="<?php echo $profile_user['id']; ?>" 
                        data-action="<?php echo $is_following ? 'unfollow' : 'follow'; ?>">
                    <?php echo $is_following ? 'Seguindo' : 'Seguir'; ?>
                </button>

                <?php if ($is_following && $is_followed_back): ?>
                    <span class="mutual-follow"> Seguem-se</span>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <br><div class="profile-content">
        <div class="profile-tabs">
            <button class="tab-btn active" data-tab="posts">Posts</button>
            <button class="tab-btn" data-tab="media">Mídia</button>
            <button class="tab-btn" data-tab="likes">Curtidas</button>
        </div>

        <div class="tab-content active" id="posts-tab">
            <?php if (!empty($posts)): ?>
                <div class="posts">
                    <?php foreach ($posts as $post): ?>
                        <div class="post" data-post-id="<?php echo $post['id']; ?>">
                            <div class="post-header">
                                <img src="<?php echo getProfilePic($post['user_id'], $post['profile_pic']); ?>" 
                                     alt="<?php echo $post['username']; ?>" class="post-profile-pic">
                                <div class="post-user-info">
                                    <a href="<?php echo SITE_URL; ?>/profile/?user=<?php echo urlencode($post['username']); ?>" 
                                       class="post-username"><?php echo htmlspecialchars($post['username']); ?></a>
                                    <span class="post-time"><?php echo time_elapsed_string($post['created_at']); ?></span>
                                </div>
                            </div>

                            <div class="post-content">
                                <p><?php echo parse_hashtags($post['content']); ?></p>
                                <?php if ($post['image']): ?>
                                    <img src="/<?php echo $post['image']; ?>" alt="Post image" class="post-image">
                                <?php endif; ?>
                            </div>

                            <div class="post-actions">
                                <button class="like-btn" data-user-id="<?php echo $_SESSION['user_id']; ?>"
                                        data-post-id="<?php echo $post['id']; ?>">
                                    <i class="fas fa-heart"></i>
                                    <span class="like-count"><?php echo $post['like_count']; ?></span>
                                </button>
                                
                                <button class="comment-btn" data-post-id="<?php echo $post['id']; ?>">
                                    <i class="fas fa-comment"></i>
                                    <span class="comment-count"><?php echo $post['comment_count']; ?></span>
                                </button>
                                
                                <button class="share-btn" data-post-id="<?php echo $post['id']; ?>">
                                    <i class="fas fa-share"></i>
                                </button>
                            </div>

                            <div class="comments-section" id="comments-<?php echo $post['id']; ?>" style="display: none;"></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
        <div class="empty-state">
                    <h3>Nenhum post ainda</h3>
                    <p><?php echo $is_own_profile ? 'Quando você postar algo, aparecerá aqui.' : 'Este usuário ainda não fez nenhum post.'; ?></p>
                </div>
            <?php endif; ?>
        </div>

        <div class="tab-content" id="media-tab">
            <div class="empty-state">
                <h3>Nenhuma mídia ainda</h3>
                <p><?php echo $is_own_profile ? 'Quando você postar fotos ou vídeos, aparecerão aqui.' : 'Este usuário ainda não postou mdia.'; ?></p>
            </div>
        </div>

        <div class="tab-content" id="likes-tab">
            <div class="empty-state">
                <h3>Nenhuma curtida ainda</h3>
                <p><?php echo $is_own_profile ? 'Os posts que voc curtir aparecerão aqui.' : 'Este usurio ainda não curtiu nenhum post.'; ?></p>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="/assets/css/comments.css">
<link rel="stylesheet" href="/assets/css/profile.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<script src="https://cdn.tailwindcss.com"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
