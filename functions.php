<?php
require_once 'config.php';
require_once __DIR__ . '/../2fa/PHPGangsta/GoogleAuthenticator.php';

$user = get_user_info($_SESSION['user_id']);

// Função para redirecionar com mensagem
function redirect($url, $message = null) {
    if ($message) {
        $_SESSION['flash_message'] = $message;
    }
    header("Location: $url");
    exit;
}

// Função para exibir mensagem flash
function display_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return "<div class='flash-message'>$message</div>";
    }
    return '';
}

// Função para formatar o tempo (ex: "há 5 minutos")
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'ano',
        'm' => 'mês',
        'w' => 'semana',
        'd' => 'dia',
        'h' => 'hora',
        'i' => 'minuto',
        's' => 'segundo',
    );
    
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' atrás' : 'agora mesmo';
}

// Função para processar hashtags no texto
function parse_hashtags($text) {
    return preg_replace('/#(\w+)/', '<a href="'.SITE_URL.'/search?q=%23$1" class="hashtag">#$1</a>', $text);
}

// Função para verificar se o usuário está logado
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Função para verificar se o usuário é admin
function is_admin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
}

// Função para exigências de input
function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Função para fazer upload de imagem
function upload_image($file, $path = 'uploads/') {
    $target_dir = $_SERVER['DOCUMENT_ROOT'] . '/' . $path;
    $imageFileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = uniqid() . '.' . $imageFileType;
    $target_file = $target_dir . $filename;

    // Verificar se é uma imagem real
    $check = getimagesize($file['tmp_name']);
    if ($check === false) {
        return ['success' => false, 'error' => 'O arquivo no é uma imagem.'];
    }

    // Verificar tamanho do arquivo (max 5MB)
    if ($file['size'] > 5000000) {
        return ['success' => false, 'error' => 'A imagem é muito grande. Tamanho máximo: 5MB.'];
    }

    // Permitir apenas certos formatos
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($imageFileType, $allowed_types)) {
        return ['success' => false, 'error' => 'Apenas JPG, JPEG, PNG e GIF são permitidos.'];
    }

    // Tentar fazer upload
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return ['success' => true, 'filename' => $filename];
    } else {
        return ['success' => false, 'error' => 'Houve um erro ao fazer upload da imagem.'];
    }
}

// Função para obter informações do usuário
function get_user_info($user_id) {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("SELECT id, username, email, full_name, bio, profile_pic FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Imagens de perfil
function getProfilePic($user_id, $profile_pic = null) {
    // Se uma imagem de perfil já foi passada (evita nova consulta ao banco)
    if (!empty($profile_pic) && is_string($profile_pic)) {
        $profile_pic_path = '/uploads/' . $profile_pic;
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . $profile_pic_path)) {
            return $profile_pic_path; // Retorna a imagem personalizada se existir
        }
    }

    // Pega conexão com o banco
    $db = Database::getInstance();
    $conn = $db->getConnection();

    try {
        $stmt = $conn->prepare("SELECT profile_pic FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Se o banco de dados retornar uma imagem de perfil válida
        if ($result && !empty($result['profile_pic']) && is_string($result['profile_pic'])) {
            $profile_pic_path = '/uploads/' . $result['profile_pic'];
            // Verifica se o arquivo existe no sistema
            if (file_exists($_SERVER['DOCUMENT_ROOT'] . $profile_pic_path)) {
                return $profile_pic_path; // Retorna a imagem personalizada do banco
            }
        }
    } catch (PDOException $e) {
        error_log("Erro ao buscar imagem de perfil: " . $e->getMessage());
    }

    // Caso contrário, retorna a imagem padrão
    return '/assets/images/default-profile.png';
}

// 2FA Autheticator APP
function generate2FASecret() {
    $g = new PHPGangsta_GoogleAuthenticator();
    return $g->createSecret();
}

function get2FAQRCodeUrl($username, $secret) {
    $g = new PHPGangsta_GoogleAuthenticator();
    return $g->getQRCodeGoogleUrl("VibezNetwork - $username", $secret);
}

function verify2FACode($secret, $code) {
    $g = new PHPGangsta_GoogleAuthenticator();
    return $g->verifyCode($secret, $code, 2); // tolerância de 2x30s
}

function getBanner($user_id) {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("SELECT banner FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result && !empty($result['banner'])) {
        return '/uploads/' . $result['banner'];
    }

    // Caminho do banner padrão se não tiver um definido
    return '/assets/images/default-banner.png';
}

function getTrendingHashtags($limit = 10, $interval = '1 DAY') {
    $pdo = db(); // ou use Database::getInstance()->getConnection();

    $sql = "SELECT content FROM posts WHERE created_at >= NOW() - INTERVAL $interval";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $hashtags = [];

    foreach ($posts as $post) {
        preg_match_all('/#(\w+)/u', $post['content'], $matches);
        foreach ($matches[1] as $tag) {
            $tag = strtolower($tag);
            if (!isset($hashtags[$tag])) {
                $hashtags[$tag] = 0;
            }
            $hashtags[$tag]++;
        }
    }

    arsort($hashtags);
    return array_slice($hashtags, 0, $limit, true);
}


?>