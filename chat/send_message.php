<?php
session_start();
require_once "../includes/config.php";

header("Content-Type: application/json; charset=utf-8");

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Não autorizado"]);
    exit;
}

$sender_id = (int) $_SESSION['user_id'];

// Detecta se veio JSON (versão antiga) ou formulário (com arquivo)
$receiver_id = 0;
$content     = '';

if (!empty($_POST) || !empty($_FILES)) {
    // multipart/form-data
    $receiver_id = isset($_POST['receiver_id']) ? (int) $_POST['receiver_id'] : 0;
    $content     = isset($_POST['message']) ? trim($_POST['message']) : '';
} else {
    // application/json (versão antiga do chat.js)
    $data        = json_decode(file_get_contents("php://input"), true) ?? [];
    $receiver_id = isset($data['receiver_id']) ? (int) $data['receiver_id'] : 0;
    $content     = isset($data['message']) ? trim($data['message']) : '';
}

if ($receiver_id <= 0) {
    http_response_code(400);
    echo json_encode(["error" => "Destinatário inválido"]);
    exit;
}

$message_type = 'text';
$file_path    = null;

/**
 * Upload opcional de arquivo (imagem / áudio / vídeo)
 */
if (!empty($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/chat/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }

    $tmpName      = $_FILES['attachment']['tmp_name'];
    $originalName = basename($_FILES['attachment']['name']);
    $ext          = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    $imageExts = ['jpg','jpeg','png','gif','webp'];
    $audioExts = ['mp3','wav','ogg','m4a'];
    $videoExts = ['mp4','webm','avi','mkv','mov'];

    if (in_array($ext, $imageExts)) {
        $message_type = 'image';
    } elseif (in_array($ext, $audioExts)) {
        $message_type = 'audio';
    } elseif (in_array($ext, $videoExts)) {
        $message_type = 'video';
    } else {
        http_response_code(400);
        echo json_encode(["error" => "Tipo de arquivo não suportado"]);
        exit;
    }

    $safeName = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $originalName);
    $newName  = time() . '_' . $safeName;
    $destPath = $uploadDir . $newName;

    if (!move_uploaded_file($tmpName, $destPath)) {
        http_response_code(500);
        echo json_encode(["error" => "Falha ao salvar o arquivo"]);
        exit;
    }

    // caminho relativo salvo no banco
    $file_path = 'uploads/chat/' . $newName;
}

// Se for só texto, não deixa mandar vazio
if ($message_type === 'text' && $content === '') {
    http_response_code(400);
    echo json_encode(["error" => "Mensagem vazia"]);
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO messages (sender_id, receiver_id, content, message_type, file_path)
    VALUES (?, ?, ?, ?, ?)
");
$stmt->execute([$sender_id, $receiver_id, $content, $message_type, $file_path]);

echo json_encode([
    "success"      => true,
    "message_id"   => (int) $pdo->lastInsertId(),
    "message_type" => $message_type,
    "file_path"    => $file_path ? '/' . $file_path : null
]);
