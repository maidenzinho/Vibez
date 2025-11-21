<?php
session_start();
require '../../includes/config.php';
header('Content-Type: application/json');

// Valida o post_id vindo via GET
$postId = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
if ($postId <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID do post inválido']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $pdo->prepare("
            SELECT c.*, u.username, u.profile_pic
            FROM comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.post_id = :post_id
            ORDER BY c.created_at ASC
        ");

        $stmt->execute([':post_id' => $postId]);
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'comments' => $comments]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Usuário não autenticado']);
        exit;
    }

    $data = json_decode(file_get_contents("php://input"), true);
    $comment = trim($data['comment'] ?? '');

    if (empty($comment)) {
        echo json_encode(['success' => false, 'error' => 'Comentário vazio']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO comments (post_id, user_id, content, created_at) 
            VALUES (:post_id, :user_id, :content, NOW())
        ");
        $stmt->execute([
            ':post_id' => $postId,
            ':user_id' => $_SESSION['user_id'],
            ':content' => $comment
        ]);

        // Retorna nova contagem de comentários
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE post_id = ?");
        $countStmt->execute([$postId]);
        $commentCount = $countStmt->fetchColumn();

        echo json_encode(['success' => true, 'commentCount' => $commentCount]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Método inválido']);
