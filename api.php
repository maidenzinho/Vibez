<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in for protected endpoints
$protectedEndpoints = ['/posts', '/like', '/follow', '/comments', '/messages', '/set-theme', '/delete-account'];
if (in_array(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), $protectedEndpoints) && !isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

$request = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($request, PHP_URL_PATH);

// Remove base path if present
$basePath = '/social-network/api';
if (strpos($path, $basePath) === 0) {
    $path = substr($path, strlen($basePath));
}

// Main API router
switch ($path) {
    case '/posts':
        handlePosts($conn, $method);
        break;
        
    case '/like':
        handleLikes($conn, $method);
        break;
        
    case '/follow':
        handleFollows($conn, $method);
        break;
        
    case preg_match('/^\/comments(?:\/(\d+))?$/', $path, $matches) ? $path : '':
        handleComments($conn, $method, $matches[1] ?? null);
        break;
        
    case preg_match('/^\/messages(?:\/(\d+))?$/', $path, $matches) ? $path : '':
        handleMessages($conn, $method, $matches[1] ?? null);
        break;
        
    case '/search-users':
        handleUserSearch($conn, $method);
        break;
        
    case '/set-theme':
        handleThemeChange($conn, $method);
        break;
        
    case '/delete-account':
        handleAccountDeletion($conn, $method);
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
        break;
}

// Handler functions for each endpoint
function handlePosts($conn, $method) {
    if ($method === 'GET') {
        $lastId = isset($_GET['lastId']) ? (int)$_GET['lastId'] : 0;
        
        $stmt = $conn->prepare("
            SELECT p.*, u.username, u.profile_pic, 
                   (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
                   (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count,
                   (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = :user_id) as user_liked
            FROM posts p
            JOIN users u ON p.user_id = u.id
            WHERE p.id < :last_id
            ORDER BY p.created_at DESC
            LIMIT 10
        ");
        $stmt->bindParam(':last_id', $lastId);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } 
    elseif ($method === 'POST') {
        $content = sanitize_input($_POST['content'] ?? '');
        $image = null;
        
        if (!empty($_FILES['image']['name'])) {
            $upload = upload_image($_FILES['image']);
            if (!$upload['success']) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => $upload['error']]);
                return;
            }
            $image = $upload['filename'];
        }
        
        try {
            $conn->beginTransaction();
            
            $stmt = $conn->prepare("INSERT INTO posts (user_id, content, image) VALUES (?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $content, $image]);
            $post_id = $conn->lastInsertId();
            
            // Process hashtags
            preg_match_all('/#(\w+)/', $content, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $tag) {
                    $hashtag = "#$tag";
                    
                    // Get or create hashtag
                    $stmt = $conn->prepare("SELECT id FROM hashtags WHERE name = ?");
                    $stmt->execute([$hashtag]);
                    $hashtag_id = $stmt->fetchColumn();
                    
                    if (!$hashtag_id) {
                        $stmt = $conn->prepare("INSERT INTO hashtags (name) VALUES (?)");
                        $stmt->execute([$hashtag]);
                        $hashtag_id = $conn->lastInsertId();
                    }
                    
                    // Link hashtag to post
                    $stmt = $conn->prepare("INSERT INTO post_hashtags (post_id, hashtag_id) VALUES (?, ?)");
                    $stmt->execute([$post_id, $hashtag_id]);
                    
                    // Update trending
                    $stmt = $conn->prepare("
                        INSERT INTO trending_topics (hashtag_id, count) 
                        VALUES (?, 1)
                        ON DUPLICATE KEY UPDATE count = count + 1, last_updated = NOW()
                    ");
                    $stmt->execute([$hashtag_id]);
                }
            }
            
            $conn->commit();
            echo json_encode(['success' => true, 'post_id' => $post_id]);
        } catch (Exception $e) {
            $conn->rollBack();
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
}

function handleLikes($conn, $method) {
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $post_id = (int)($data['post_id'] ?? 0);
        
        try {
            // Check if like exists
            $stmt = $conn->prepare("SELECT id FROM likes WHERE post_id = ? AND user_id = ?");
            $stmt->execute([$post_id, $_SESSION['user_id']]);
            
            if ($stmt->fetch()) {
                // Unlike
                $stmt = $conn->prepare("DELETE FROM likes WHERE post_id = ? AND user_id = ?");
                $stmt->execute([$post_id, $_SESSION['user_id']]);
                $liked = false;
            } else {
                // Like
                $stmt = $conn->prepare("INSERT INTO likes (post_id, user_id) VALUES (?, ?)");
                $stmt->execute([$post_id, $_SESSION['user_id']]);
                $liked = true;
            }
            
            // Get updated like count
            $stmt = $conn->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ?");
            $stmt->execute([$post_id]);
            $like_count = $stmt->fetchColumn();
            
            echo json_encode(['success' => true, 'likeCount' => $like_count, 'liked' => $liked]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
}

function handleFollows($conn, $method) {
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $user_id = (int)($data['user_id'] ?? 0);
        
        try {
            // Check if already following
            $stmt = $conn->prepare("SELECT id FROM followers WHERE follower_id = ? AND following_id = ?");
            $stmt->execute([$_SESSION['user_id'], $user_id]);
            
            if ($stmt->fetch()) {
                // Unfollow
                $stmt = $conn->prepare("DELETE FROM followers WHERE follower_id = ? AND following_id = ?");
                $stmt->execute([$_SESSION['user_id'], $user_id]);
                $following = false;
            } else {
                // Follow
                $stmt = $conn->prepare("INSERT INTO followers (follower_id, following_id) VALUES (?, ?)");
                $stmt->execute([$_SESSION['user_id'], $user_id]);
                $following = true;
            }
            
            echo json_encode(['success' => true, 'isFollowing' => $following]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
}

function handleComments($conn, $method, $post_id) {
    if (!$post_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Post ID required']);
        return;
    }

    if ($method === 'GET') {
        $stmt = $conn->prepare("
            SELECT c.*, u.username, u.profile_pic
            FROM comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.post_id = ?
            ORDER BY c.created_at ASC
        ");
        $stmt->execute([$post_id]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } 
    elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $content = sanitize_input($data['comment'] ?? '');
        
        try {
            $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
            $stmt->execute([$post_id, $_SESSION['user_id'], $content]);
            $comment_id = $conn->lastInsertId();
            
            // Get the new comment with user info
            $stmt = $conn->prepare("
                SELECT c.*, u.username, u.profile_pic
                FROM comments c
                JOIN users u ON c.user_id = u.id
                WHERE c.id = ?
            ");
            $stmt->execute([$comment_id]);
            $comment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get updated comment count
            $stmt = $conn->prepare("SELECT COUNT(*) FROM comments WHERE post_id = ?");
            $stmt->execute([$post_id]);
            $comment_count = $stmt->fetchColumn();
            
            echo json_encode(['success' => true, 'comment' => $comment, 'commentCount' => $comment_count]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
}

function handleMessages($conn, $method, $receiver_id) {
    if ($method === 'GET') {
        if ($receiver_id) {
            // Get messages for a specific conversation
            $last_id = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;
            
            $stmt = $conn->prepare("
                SELECT m.*, u.username, u.profile_pic
                FROM messages m
                JOIN users u ON m.sender_id = u.id
                WHERE ((m.sender_id = :sender_id AND m.receiver_id = :receiver_id)
                OR (m.sender_id = :receiver_id AND m.receiver_id = :sender_id))
                AND m.id > :last_id
                ORDER BY m.created_at ASC
            ");
            $stmt->bindParam(':sender_id', $_SESSION['user_id']);
            $stmt->bindParam(':receiver_id', $receiver_id);
            $stmt->bindParam(':last_id', $last_id);
            $stmt->execute();
            
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Mark messages as read
            if (!empty($messages)) {
                $stmt = $conn->prepare("
                    UPDATE messages 
                    SET is_read = TRUE 
                    WHERE receiver_id = ? AND sender_id = ? AND is_read = FALSE
                ");
                $stmt->execute([$_SESSION['user_id'], $receiver_id]);
            }
            
            echo json_encode($messages);
        } else {
            // Get all conversations
            $stmt = $conn->prepare("
                SELECT 
                    u.id as user_id,
                    u.username,
                    u.profile_pic,
                    m.content as last_message,
                    m.created_at as last_message_time,
                    (SELECT COUNT(*) FROM messages WHERE sender_id = u.id AND receiver_id = ? AND is_read = FALSE) as unread
                FROM messages m
                JOIN users u ON (
                    (m.sender_id = u.id AND m.receiver_id = ?) OR 
                    (m.receiver_id = u.id AND m.sender_id = ?)
                )
                WHERE m.id IN (
                    SELECT MAX(id) FROM messages 
                    WHERE sender_id = ? OR receiver_id = ?
                    GROUP BY LEAST(sender_id, receiver_id), GREATEST(sender_id, receiver_id)
                )
                AND u.id != ?
                ORDER BY m.created_at DESC
            ");
            $stmt->execute([
                $_SESSION['user_id'],
                $_SESSION['user_id'],
                $_SESSION['user_id'],
                $_SESSION['user_id'],
                $_SESSION['user_id'],
                $_SESSION['user_id']
            ]);
            
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        }
    } 
    elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $receiver_id = (int)($data['receiver_id'] ?? 0);
        $content = sanitize_input($data['content'] ?? '');
        
        try {
            $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, content) VALUES (?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $receiver_id, $content]);
            $message_id = $conn->lastInsertId();
            
            // Get the new message with user info
            $stmt = $conn->prepare("
                SELECT m.*, u.username, u.profile_pic
                FROM messages m
                JOIN users u ON m.sender_id = u.id
                WHERE m.id = ?
            ");
            $stmt->execute([$message_id]);
            $message = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'message' => $message]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
}

function handleUserSearch($conn, $method) {
    if ($method === 'GET') {
        $query = isset($_GET['q']) ? sanitize_input($_GET['q']) : '';
        
        if (strlen($query) < 2) {
            echo json_encode([]);
            return;
        }
        
        $stmt = $conn->prepare("
            SELECT id, username, profile_pic 
            FROM users 
            WHERE (username LIKE ? OR full_name LIKE ?)
            AND id != ?
            LIMIT 10
        ");
        $search_term = "%$query%";
        $stmt->execute([$search_term, $search_term, $_SESSION['user_id']]);
        
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
}

function handleThemeChange($conn, $method) {
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $theme = in_array($data['theme'] ?? '', ['light', 'dark']) ? $data['theme'] : 'light';
        
        try {
            $stmt = $conn->prepare("UPDATE users SET theme_preference = ? WHERE id = ?");
            $stmt->execute([$theme, $_SESSION['user_id']]);
            
            // Update session
            $_SESSION['theme'] = $theme;
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
}

function handleAccountDeletion($conn, $method) {
    if ($method === 'POST') {
        try {
            $conn->beginTransaction();
            
            // Delete all user-related data
            $tables = [
                'posts' => 'user_id',
                'comments' => 'user_id',
                'likes' => 'user_id',
                'followers' => 'follower_id OR following_id',
                'messages' => 'sender_id OR receiver_id'
            ];
            
            foreach ($tables as $table => $condition) {
                $stmt = $conn->prepare("DELETE FROM $table WHERE $condition = ?");
                $stmt->execute([$_SESSION['user_id']]);
            }
            
            // Finally delete the user
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            
            $conn->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $conn->rollBack();
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
}
?>