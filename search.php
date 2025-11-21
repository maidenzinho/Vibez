<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

// Buscar usuários, hashtags e posts
$users = [];
$hashtags = [];
$posts = [];

if (!empty($search)) {
    $searchLike = "%$search%";

    // Buscar usuários
    $stmt = $pdo->prepare("SELECT id, username, profile_pic FROM users WHERE username LIKE ? AND id != ?");
    $stmt->execute([$searchLike, $userId]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Buscar hashtags
    $stmt = $pdo->prepare("SELECT DISTINCT hashtag FROM posts WHERE hashtag LIKE ?");
    $stmt->execute([$searchLike]);
    $hashtags = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Buscar posts de pessoas que o usuário não segue
    $stmt = $pdo->prepare("
        SELECT posts.*, users.username, users.profile_pic
        FROM posts
        JOIN users ON posts.user_id = users.id
        WHERE users.id != :uid
        AND users.id NOT IN (
            SELECT following_id FROM follows WHERE follower_id = :uid
        )
        AND (
            LOWER(posts.content) LIKE LOWER(:search) OR LOWER(posts.hashtag) LIKE LOWER(:search)
        )
        ORDER BY posts.created_at DESC
    ");
    $stmt->execute([
        'uid' => $userId,
        'search' => $searchLike
    ]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<?php include 'includes/sidebar.php'; ?>
<?php include 'includes/menumobile.php'; ?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Buscar | Vibez</title>
  <link rel="stylesheet" href="assets/css/main.css">
  <link rel="stylesheet" href="assets/css/chat.css">
  <link rel="stylesheet" href="assets/css/search.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="<?= htmlspecialchars($theme ?? 'light') ?>">
  <div class="container">
    <form class="search-bar" method="GET" action="search.php">
      <input type="text" name="q" placeholder="Buscar por usuários, hashtags ou posts..." value="<?= htmlspecialchars($search) ?>">
    </form>

    <?php if (!empty($search)): ?>
      <div class="section-title">Usuários</div>
      <?php if (count($users)): ?>
        <?php foreach ($users as $user): ?>
          <div class="user">
            <img src="<?= htmlspecialchars($user['profile_pic']) ?>" alt="Foto de perfil">
            <span>@<?= htmlspecialchars($user['username']) ?></span>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="user">Nenhum usuário encontrado.</div>
      <?php endif; ?>

      <div class="section-title">Hashtags</div>
      <?php if (count($hashtags)): ?>
        <?php foreach ($hashtags as $tag): ?>
          <div class="hashtag">
            <span>#<?= htmlspecialchars($tag['hashtag']) ?></span>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="hashtag">Nenhuma hashtag encontrada.</div>
      <?php endif; ?>

      <div class="section-title">Posts</div>
      <?php if (count($posts)): ?>
        <?php foreach ($posts as $post): ?>
          <div class="post">
            <img class="profile" src="<?= htmlspecialchars($post['profile_pic']) ?>" alt="Perfil">
            <div class="content">
              <div class="username">@<?= htmlspecialchars($post['username']) ?></div>
              <div class="text"><?= nl2br(htmlspecialchars($post['content'])) ?></div>
              <?php if (!empty($post['hashtag'])): ?>
                <div class="text" style="color:#007bff">#<?= htmlspecialchars($post['hashtag']) ?></div>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="post">Nenhum post encontrado.</div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
  
</body>
</html>

<script src="https://cdn.tailwindcss.com"></script>

<?php require_once 'includes/footer.php'; ?>
