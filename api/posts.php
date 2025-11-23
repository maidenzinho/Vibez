<?php

require_once 'includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Garante que não tenha nada antes do JSON
if (ob_get_length()) {
    ob_clean();
}

try {
    // Só aceita POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'error' => 'Método inválido']);
        exit;
    }

    // Usuário precisa estar logado
    if (empty($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Usuário não autenticado']);
        exit;
    }

    $content   = trim($_POST['content'] ?? '');
    $imagePath = null;

    // Verifica se veio imagem
    $temImagem = !empty($_FILES['image']) && !empty($_FILES['image']['name']);

    if ($content === '' && !$temImagem) {
        echo json_encode(['success' => false, 'error' => 'O post não pode estar vazio']);
        exit;
    }

    // Upload da imagem (se existir)
    if ($temImagem) {
        $uploadDirFs  = __DIR__ . '/uploads/'; // caminho físico
        $uploadDirRel = 'uploads/';            // caminho que vai pro banco

        if (!is_dir($uploadDirFs)) {
            mkdir($uploadDirFs, 0775, true);
        }

        $imageName = time() . '_' . basename($_FILES['image']['name']);
        $destFs    = $uploadDirFs . $imageName;
        $imagePath = $uploadDirRel . $imageName;

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $destFs)) {
            echo json_encode(['success' => false, 'error' => 'Erro ao salvar a imagem']);
            exit;
        }
    }

    // Usa o singleton de conexão (mesmo do index/login)
    $db   = Database::getInstance();
    $conn = $db->getConnection();

    $query = "INSERT INTO posts (user_id, content, image, created_at)
              VALUES (:user_id, :content, :image, NOW())";

    $stmt = $conn->prepare($query);
    $stmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':content' => $content,
        ':image'   => $imagePath
    ]);

    $lastId = $conn->lastInsertId();

    echo json_encode([
        'success' => true,
        'id'      => $lastId,
        'image'   => $imagePath
    ]);
} catch (Throwable $e) {
    error_log('Erro em post.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erro interno ao criar post.']);
}

exit;
