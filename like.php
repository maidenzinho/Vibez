<?php

require_once __DIR__ . '/includes/config.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

$postId = intval($data['post_id'] ?? 0);
$userId = intval($data['user_id'] ?? 0); // opcional

$response = ['success' => false];

if ($postId > 0 && $userId > 0) {
    // Verifica se o usuário já curtiu o post (impede likes duplicados)
    $checkSql = "SELECT id FROM likes WHERE post_id = ? AND user_id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("ii", $postId, $userId);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows === 0) {
        // Insere novo like
        $insertSql = "INSERT INTO likes (post_id, user_id) VALUES (?, ?)";
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->bind_param("ii", $postId, $userId);
        $insertStmt->execute();
    } else {
        $response['message'] = 'Você já curtiu esse post.';
    }

    // Conta total de likes do post
    $countSql = "SELECT COUNT(*) AS total FROM likes WHERE post_id = ?";
    $countStmt = $conn->prepare($countSql);
    $countStmt->bind_param("i", $postId);
    $countStmt->execute();
    $result = $countStmt->get_result();
    $row = $result->fetch_assoc();

    $response['success'] = true;
    $response['like_count'] = $row['total'];
} else {
    $response['message'] = 'ID inválido.';
}

echo json_encode($response);

?>