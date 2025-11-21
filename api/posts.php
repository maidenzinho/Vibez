<?php

session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require '../includes/config.php';

header('Content-Type: application/json');
ob_clean();

try {
    $lastId = isset($_GET['lastId']) ? (int)$_GET['lastId'] : PHP_INT_MAX;

    $query = "
        SELECT 
            p.id, p.content, p.image, p.created_at, p.like_count, 
            p.shared_post_id,
            u.username, u.profile_pic,

            sp.id AS original_id, sp.content AS original_content, sp.image AS original_image, sp.created_at AS original_created_at,
            ou.username AS original_username, ou.profile_pic AS original_profile_pic

        FROM posts p
        JOIN users u ON p.user_id = u.id
        LEFT JOIN posts sp ON p.shared_post_id = sp.id
        LEFT JOIN users ou ON sp.user_id = ou.id
        WHERE p.id < :lastId
        ORDER BY p.id DESC
        LIMIT 10
    ";

    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':lastId', $lastId, PDO::PARAM_INT);
    $stmt->execute();

    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($posts as &$post) {
        $post['image'] = !empty($post['image']) ? '/' . ltrim($post['image'], '/') : null;

        // Verifica se o usuário logado curtiu esse post
        $post['user_liked'] = false;

        if (isset($_SESSION['user_id'])) {
            $checkLike = $pdo->prepare("SELECT 1 FROM likes WHERE post_id = ? AND user_id = ?");
            $checkLike->execute([$post['id'], $_SESSION['user_id']]);
            $post['user_liked'] = $checkLike->fetchColumn() ? true : false;
        }

        // Se for compartilhamento, trata imagem e conteúdo original
        if ($post['shared_post_id']) {
            $post['original_image'] = !empty($post['original_image']) ? '/' . ltrim($post['original_image'], '/') : null;
        }
    }

    echo json_encode($posts);
    exit;
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}
