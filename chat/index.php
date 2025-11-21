<?php
session_start();
require_once "../includes/config.php";
require_once "../includes/functions.php";

require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/menumobile.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Busca todos os usuários com quem já teve conversa
$stmt = $pdo->prepare("
    SELECT u.id, u.username, u.profile_pic
    FROM users u
    INNER JOIN (
        SELECT 
            CASE
                WHEN sender_id = :me THEN receiver_id
                ELSE sender_id
            END AS user_id,
            MAX(created_at) AS last_message
        FROM messages
        WHERE sender_id = :me OR receiver_id = :me
        GROUP BY user_id
    ) m ON u.id = m.user_id
    ORDER BY m.last_message DESC
");
$stmt->execute(['me' => $user_id]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Chat | Vibez</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="/assets/css/chat.css">
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/dark-theme.css">
    <link rel="stylesheet" href="/assets/css/light-theme.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="<?= htmlspecialchars($theme ?? 'light') ?>">

    <div class="chat-container">
        <aside class="chat-sidebar">
            <div class="chat-sidebar-header">
                <h2>Conversas</h2>
                <div class="search-box">
                    <input type="text" id="search-user" placeholder="🔍 Buscar usuário...">
                </div>
            </div>

            <ul id="user-list">
                <?php foreach ($users as $user): ?>
                    <li class="user-item" data-id="<?= (int)$user['id'] ?>">
                        <img
                            src="<?= getProfilePic($user['id'], $user['profile_pic'] ?? null) ?>"
                            class="chat-avatar"
                            alt="<?= htmlspecialchars($user['username']) ?>"
                        >
                        <span class="chat-username">
                            <?= htmlspecialchars($user['username']) ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </aside>

        <main class="chat-main">
            <header class="chat-header">
                <h3 id="chat-username">Selecione uma conversa</h3>
            </header>

            <div class="chat-messages" id="chat-messages">
                <p class="no-chat">Nenhuma conversa selecionada</p>
            </div>

            <!-- Barra de entrada do chat -->
            <div class="chat-input-bar">
                <form id="chat-form" enctype="multipart/form-data">
                    <div class="chat-input-row">
                        <input
                            type="text"
                            id="message-input"
                            name="message"
                            placeholder="Digite sua mensagem..."
                            autocomplete="off"
                        >

                        <input
                            type="file"
                            id="attachment"
                            name="attachment"
                            accept="image/*,audio/*,video/*"
                            style="display:none;"
                        >

                        <button type="button" id="btn-attachment" class="chat-attach-btn">
                            📎
                        </button>

                        <button type="submit" id="btn-send" class="chat-send-btn">
                            Enviar
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        const LOGGED_IN_USER_ID = <?= (int)$_SESSION['user_id'] ?>;
    </script>
    <script src="/assets/js/chat.js"></script>
</body>
</html>
