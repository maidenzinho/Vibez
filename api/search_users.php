<?php
session_start();
require_once "../includes/config.php";

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit;
}

$search = $_GET['q'] ?? '';
$search = trim($search);
$user_id = $_SESSION['user_id'];

if ($search === '') {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("SELECT id, username FROM users WHERE username LIKE ? AND id != ? LIMIT 10");
$stmt->execute(["%$search%", $user_id]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($results);
