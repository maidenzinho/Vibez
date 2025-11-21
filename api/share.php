<?php
require '../includes/config.php';
session_start();

header('Content-Type: application/json');
ob_clean();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método inválido']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado']);
    exit;
}

$userId = $_SESSION['user_id'];
$data = json_decode(file_get_contents("php://input"), true);
$originalPostId = $data['post_id'] ?? null;

if (!$originalPostId) {
    echo json_encode(['success' => false, 'error' => 'Post inválido']);
    exit;
}

try {
    // Pega o conteúdo do post original
    $stmt = $pdo->prepare("SELECT content, image FROM posts WHERE id = ?");
    $stmt->execute([$originalPostId]);
    $originalPost = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$originalPost) {
        echo json_encode(['success' => false, 'error' => 'Post original não encontrado']);
        exit;
    }

    // Insere o novo post como compartilhamento
    $query = "INSERT INTO posts (user_id, content, image, shared_post_id, created_at) 
              VALUES (:user_id, :content, :image, :shared_post_id, NOW())";
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ':user_id' => $userId,
        ':content' => $originalPost['content'],
        ':image' => $originalPost['image'],
        ':shared_post_id' => $originalPostId
    ]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
