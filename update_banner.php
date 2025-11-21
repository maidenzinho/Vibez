<?php
session_start();
require_once 'config.php'; // conexão com banco

$user_id = $_SESSION['user_id'];

if (isset($_FILES['banner']) && $_FILES['banner']['error'] == 0) {
    $fileTmpPath = $_FILES['banner']['tmp_name'];
    $fileName = $_FILES['banner']['name'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

    if (in_array($fileExtension, $allowedExtensions)) {
        $newFileName = uniqid() . '.' . $fileExtension;
        $uploadPath = 'uploads/banners/' . $newFileName;

        if (!is_dir('uploads/banners')) {
            mkdir('uploads/banners', 0777, true);
        }

        if (move_uploaded_file($fileTmpPath, $uploadPath)) {
            // salva no banco de dados
            $stmt = $pdo->prepare("UPDATE users SET banner = :banner WHERE id = :id");
            $stmt->execute([
                ':banner' => $uploadPath,
                ':id' => $user_id
            ]);

            header('Location: settings.php?banner=sucesso');
            exit();
        } else {
            echo "Erro ao mover o arquivo.";
        }
    } else {
        echo "Formato de imagem inválido.";
    }
}
?>
