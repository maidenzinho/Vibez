<?php
session_start();
require_once "../includes/config.php";
require_once "../includes/functions.php";

if (!isset($_SESSION['user_id']) || !isset($_GET['user_id'])) {
    http_response_code(403);
    exit;
}

$currentUser = (int) $_SESSION['user_id'];
$otherUser   = (int) $_GET['user_id'];

$stmt = $pdo->prepare("
    SELECT 
        m.id,
        m.sender_id,
        m.receiver_id,
        m.content       AS message,
        m.message_type,
        m.file_path,
        m.created_at,
        u.username,
        u.profile_pic
    FROM messages m
    JOIN users u ON u.id = m.sender_id
    WHERE (m.sender_id = :current AND m.receiver_id = :other)
       OR (m.sender_id = :other   AND m.receiver_id = :current)
    ORDER BY m.created_at ASC
");
$stmt->execute([
    ':current' => $currentUser,
    ':other'   => $otherUser
]);

$rows     = $stmt->fetchAll(PDO::FETCH_ASSOC);
$messages = [];

foreach ($rows as $row) {
    $messages[] = [
        'id'          => (int) $row['id'],
        'sender_id'   => (int) $row['sender_id'],
        'receiver_id' => (int) $row['receiver_id'],
        'message'     => $row['message'],
        'message_type'=> $row['message_type'] ?: 'text',
        'file_path'   => $row['file_path'] ? '/' . ltrim($row['file_path'], '/') : null,
        'created_at'  => $row['created_at'],
        'username'    => $row['username'],
        'profile_pic' => getProfilePic($row['sender_id'], $row['profile_pic']),
    ];
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($messages);
