<?php
include_once '../includes/config.php';
include_once '../includes/functions.php';

$type = $_GET['type'] ?? 'followers';
$username = $_GET['user'] ?? null;

if (!$username) {
    echo "Usuário não especificado.";
    exit;
}

// Pega o ID do usuário com base no nome de usuário
$stmt = $pdo->prepare("SELECT id, name FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user) {
    echo "Usuário não encontrado.";
    exit;
}

$userId = $user['id'];
$pageTitle = $type === 'following' ? "Seguindo" : "Seguidores";

// Busca seguidores ou seguindo
if ($type === 'following') {
    $query = "SELECT u.id, u.name, u.username, u.profile_photo FROM follows f
              JOIN users u ON f.following_id = u.id
              WHERE f.follower_id = ?";
} else {
    $query = "SELECT u.id, u.name, u.username, u.profile_photo FROM follows f
              JOIN users u ON f.follower_id = u.id
              WHERE f.following_id = ?";
}

$stmt = $pdo->prepare($query);
$stmt->execute([$userId]);
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($pageTitle) ?> de <?= htmlspecialchars($user['name']) ?></title>
  <link rel="stylesheet" href="/assets/main.css">
  <link rel="stylesheet" href="/assets/connections.css">
</head>
<body>
  <h2><?= htmlspecialchars($pageTitle) ?> de <?= htmlspecialchars($user['name']) ?></h2>

  <?php if (count($users) > 0): ?>
    <div class="users-container">
      <?php foreach ($users as $u): ?>
        <div class="user-card">
          <a href="/profile/<?= htmlspecialchars($u['username']) ?>">
            <img src="<?= htmlspecialchars($u['profile_photo'] ?: '/assets/default.png') ?>" alt="Foto de perfil">
            <h3><?= htmlspecialchars($u['name']) ?></h3>
            <span>@<?= htmlspecialchars($u['username']) ?></span>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="empty-message">Nenhum usuário encontrado.</div>
  <?php endif; ?>
</body>
</html>