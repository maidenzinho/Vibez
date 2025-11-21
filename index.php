<?php

require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!is_logged_in()) {
    header("Location: login.php");
    exit();
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Feed de posts
$stmt = $conn->prepare("
    SELECT p.*, u.username, u.profile_pic, 
           (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
           (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count,
           (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = :user_id) as user_liked
    FROM posts p
    JOIN users u ON p.user_id = u.id
    WHERE p.user_id IN (SELECT following_id FROM followers WHERE follower_id = :user_id)
    OR p.user_id = :user_id
    ORDER BY p.created_at DESC
    LIMIT 20
");
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Sugestões
$stmt = $conn->prepare("
    SELECT u.id, u.username, u.profile_pic
    FROM users u
    WHERE u.id != :user_id
    AND u.id NOT IN (SELECT following_id FROM followers WHERE follower_id = :user_id)
    ORDER BY RAND()
    LIMIT 5
");
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// TRENDING TOPICS
$stmt = $conn->query("SELECT content FROM posts ORDER BY created_at DESC LIMIT 100");
$contents = $stmt->fetchAll(PDO::FETCH_COLUMN);

$hashtagCounts = [];

foreach ($contents as $content) {
    preg_match_all('/#(\w+)/u', $content, $matches);
    foreach ($matches[1] as $hashtag) {
        $hashtag = mb_strtolower($hashtag);
        $hashtagCounts[$hashtag] = ($hashtagCounts[$hashtag] ?? 0) + 1;
    }
}

arsort($hashtagCounts);
$trending = array_slice($hashtagCounts, 0, 5, true);
?>

<!DOCTYPE html>
<html lang="pt-BR" data-theme="<?php echo $_SESSION['theme']; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vibez</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/comments.css">
    <link rel="stylesheet" href="assets/css/<?php echo $_SESSION['theme']; ?>-theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<div class="container">

    <?php include 'includes/sidebar.php'; ?>
    <?php include 'includes/menumobile.php'; ?>

    <div class="main-content">
        <div class="post-form">
            <form id="create-post-form" enctype="multipart/form-data">
                <textarea name="content" placeholder="O que você está pensando?" required></textarea>
                <div class="post-actions">
                    <label for="post-image" class="image-upload"><i class="fas fa-image"></i>
                        <input type="file" id="post-image" name="image" accept="image/*">
                    </label>
                    <button type="submit" class="post-button">Postar</button>
                </div>
            </form>
        </div>

        <div class="posts" id="posts-container">
            <?php foreach ($posts as $post): ?>
                <div class="post" data-post-id="<?php echo $post['id']; ?>">
                    <div class="post-header">
                        <img src="<?php echo getProfilePic($post['user_id'], $post['profile_pic']); ?>" alt="<?php echo $post['username']; ?>" class="post-profile-pic">
                        <div class="post-user-info">
                            <a href="profile/?user=<?php echo $post['username']; ?>" class="post-username"><?php echo $post['username']; ?></a>
                            <span class="post-time"><?php echo time_elapsed_string($post['created_at']); ?></span>
                        </div>
                    </div>
                    <div class="post-content">
                        <p><?php echo parse_hashtags($post['content']); ?></p>
                        <?php if ($post['image']): ?>
                            <img src="<?php echo $post['image']; ?>" alt="Post image" class="post-image">
                        <?php endif; ?>
                    </div>
                    <div class="post-actions">
                        <button class="like-btn" data-post-id="<?php echo $post['id']; ?>" data-user-id="<?php echo $_SESSION['user_id']; ?>">
                            <i class="fas fa-heart"></i>
                            <span class="like-count"><?php echo $post['like_count']; ?></span>
                        </button>
                        <button class="comment-btn" data-post-id="<?php echo $post['id']; ?>"><i class="fas fa-comment"></i><span class="comment-count"><?php echo $post['comment_count']; ?></span></button>
                        <button class="share-btn" data-post-id="<?php echo $post['id']; ?>"><i class="fas fa-share"></i></button>
                    </div>
                    <div class="comments-section" id="comments-<?php echo $post['id']; ?>" style="display: none;"></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- TRENDING E SUGESTÕES -->
    <div class="right-sidebar">
        <div class="trending-topics">
            <h3>Trending Topics</h3>
            <ul>
                <?php foreach ($trending as $tag => $count): ?>
                    <li><a href="search.php?q=%23<?php echo urlencode($tag); ?>">#<?php echo htmlspecialchars($tag); ?></a> (<?php echo $count; ?>)</li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="suggestions">
            <h3>Quem seguir</h3>
            <ul>
                <?php foreach ($suggestions as $user): ?>
                    <li>
                        <img class="suggestion-pic" src="<?php echo getProfilePic($user['id'], $user['profile_pic'] ?? null); ?>" alt="Perfil">
                        <div class="suggestion-info">
                            <a href="profile/?user=<?php echo $user['username']; ?>" class="suggestion-username"><?php echo $user['username']; ?></a>
                            <button class="follow-btn" data-user-id="<?php echo $user['id']; ?>">Seguir</button>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

</div>

<script src="https://cdn.tailwindcss.com"></script>
<script src="assets/js/main.js"></script>
<script src="assets/js/theme.js"></script>
<script src="assets/js/posts.js"></script>

<script>
document.querySelectorAll('.like-btn').forEach(button => {
    button.addEventListener('click', () => {
        const postId = button.getAttribute('data-post-id');
        fetch('like.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ post_id: postId })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const countSpan = button.querySelector('.like-count');
                countSpan.textContent = data.like_count;
            }
        });
    });
});

document.querySelectorAll('.follow-btn').forEach(button => {
    button.addEventListener('click', () => {
        const userId = button.getAttribute('data-user-id');
        fetch('follow.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                button.textContent = 'Seguindo';
                button.disabled = true;
            }
        });
    });
});
</script>
</body>
</html>
