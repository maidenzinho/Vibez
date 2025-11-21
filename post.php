<?php

session_start(); // Você precisa da sessão ativa para acessar o user_id

require 'includes/config.php';

header('Content-Type: application/json');
ob_clean();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método inválido']);
    exit;
}

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado']);
    exit;
}

$content = trim($_POST['content'] ?? '');
$imagePath = null; 

if (empty($content) && empty($_FILES['image']['name'])) {
    echo json_encode(['success' => false, 'error' => 'O post não pode estar vazio']);
    exit;
}

// Upload da imagem
if (!empty($_FILES['image']['name'])) {
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $imageName = time() . '_' . basename($_FILES['image']['name']);
    $imagePath = $uploadDir . $imageName;
    
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
        echo json_encode(['success' => false, 'error' => 'Erro ao salvar a imagem']);
        exit;
    }
}

// INSERE no banco usando o ID do usuário logado
$query = "INSERT INTO posts (user_id, content, image, created_at) VALUES (:user_id, :content, :image, NOW())";
$stmt = $pdo->prepare($query);
$stmt->execute([
    ':user_id' => $_SESSION['user_id'],
    ':content' => $content,
    ':image' => $imagePath
]);

$lastId = $pdo->lastInsertId();

echo json_encode(['success' => true, 'id' => $lastId, 'image' => $imagePath]);
exit;
