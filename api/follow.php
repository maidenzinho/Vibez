<?php

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/follow_error.log');

ob_clean();

require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado.']);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);
$targetId = isset($data['user_id']) ? (int) $data['user_id'] : 0;
$currentUserId = $_SESSION['user_id'];

if ($targetId <= 0 || $targetId === $currentUserId) {
    echo json_encode(['success' => false, 'message' => 'ID inválido.']);
    exit();
}

$db = Database::getInstance();
$conn = $db->getConnection();

$stmt = $conn->prepare("SELECT * FROM followers WHERE follower_id = ? AND following_id = ?");
$stmt->execute([$currentUserId, $targetId]);

if ($stmt->rowCount() > 0) {
    echo json_encode(['success' => false, 'message' => 'Você já segue esse usuário.']);
    exit();
}

$stmt = $conn->prepare("INSERT INTO followers (follower_id, following_id) VALUES (?, ?)");
$success = $stmt->execute([$currentUserId, $targetId]);

echo json_encode(['success' => $success]);
