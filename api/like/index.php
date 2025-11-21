<?php
require_once __DIR__ . '/../../includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Lê o corpo JSON da requisição
$data = json_decode(file_get_contents('php://input'), true);

// Verifica se o post_id está presente
if (!isset($data['post_id']) || !is_numeric($data['post_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID do post inválido']);
    exit;
}

$post_id = (int) $data['post_id'];
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

try {
    // Verifica se já curtiu
    $stmt = $pdo->prepare("SELECT * FROM likes WHERE post_id = ? AND user_id = ?");
    $stmt->execute([$post_id, $user_id]);

    if ($stmt->rowCount() > 0) {
        // Já curtiu, então remove
        $pdo->prepare("DELETE FROM likes WHERE post_id = ? AND user_id = ?")->execute([$post_id, $user_id]);
    } else {
        // Ainda não curtiu, então insere
        $pdo->prepare("INSERT INTO likes (post_id, user_id) VALUES (?, ?)")->execute([$post_id, $user_id]);
    }

    // Retorna a nova contagem de curtidas
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ?");
    $stmt->execute([$post_id]);
    $likeCount = $stmt->fetchColumn();

    echo json_encode(['success' => true, 'new_like_count' => $likeCount]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro no banco de dados', 'error' => $e->getMessage()]);
}
