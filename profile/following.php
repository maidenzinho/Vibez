<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/menumobile.php';

if (!isset($_GET['user'])) {
    header("Location: " . SITE_URL);
    exit;
}

$username = $_GET['user'];
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$username]);
$profile_user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profile_user) {
    echo "Usuário não encontrado.";
    exit;
}

$profile_user_id = $profile_user['id'];

$stmt = $pdo->prepare("
    SELECT users.id, users.username, users.profile_pic 
    FROM follows
    JOIN users ON follows.following_id = users.id
    WHERE follows.follower_id = ?
");
$stmt->execute([$profile_user_id]);
$following = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Seguindo - <?= htmlspecialchars($username) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/main.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap">
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background: var(--background-color, #f9f9f9);
      margin: 0;
      padding: 20px;
    }
    .user-card {
      display: flex;
      align-items: center;
      gap: 12px;
      background: white;
      padding: 12px;
      border-radius: 12px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.05);
      margin-bottom: 12px;
      transition: transform 0.2s;
    }
    .user-card:hover {
      transform: translateY(-2px);
    }
    .user-card img {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      object-fit: cover;
    }
    .user-card span {
      font-weight: 600;
      color: #333;
    }

    @media (max-width: 600px) {
      .user-card {
        flex-direction: row;
        padding: 10px;
      }
      .user-card img {
        width: 45px;
        height: 45px;
      }
    }
  </style>
</head>
<body>
  <h2>@<?= htmlspecialchars($username) ?> está seguindo</h2>

  <?php if (count($following)): ?>
    <?php foreach ($following as $user): ?>
      <div class="user-card">
        <img src="<?= htmlspecialchars($user['profile_pic']) ?>" alt="Foto">
        <span>@<?= htmlspecialchars($user['username']) ?></span>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <p>Este usuário ainda não segue ninguém.</p>
  <?php endif; ?>
</body>
</html>
