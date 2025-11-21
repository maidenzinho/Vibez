<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/menumobile.php';

if (!isset($_GET['user'])) {
    echo "Usuário não especificado.";
    exit;
}

$username = $_GET['user'];

// Buscar ID do usuário
$stmt = $pdo->prepare("SELECT id, username FROM users WHERE username = ?");
$stmt->execute([$username]);
$profile_user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profile_user) {
    echo "Usuário não encontrado.";
    exit;
}

// Buscar seguidores (quem segue esse usuário)
$stmt = $pdo->prepare("
    SELECT u.id, u.username, u.profile_pic
    FROM followers f
    JOIN users u ON f.follower_id = u.id
    WHERE f.following_id = ?
");
$stmt->execute([$profile_user['id']]);
$followers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Seguidores de <?= htmlspecialchars($username) ?> | Vibez</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/assets/css/main.css">
  <link rel="stylesheet" href="/assets/css/connections.css">
  <link rel="stylesheet" href="assets/css/<?php echo $_SESSION['theme']; ?>-theme.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="<?= htmlspecialchars($theme ?? 'light') ?>">

<?php include '../includes/sidebar.php'; ?>

<div class="followers-container">
  <h2>Seguidores de @<?= htmlspecialchars($username) ?></h2>

  <?php if (count($followers)): ?>
    <?php foreach ($followers as $follower): ?>
      <div class="follower">
        <img src="<?= htmlspecialchars($follower['profile_pic']) ?>" alt="Foto de perfil">
        <a href="<?= SITE_URL ?>/profile/?user=<?= urlencode($follower['username']) ?>">
          <span>@<?= htmlspecialchars($follower['username']) ?></span>
        </a>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <p>Nenhum seguidor encontrado.</p>
  <?php endif; ?>
</div>

</body>
</html>
