<?php
require_once 'includes/config.php';

// Limpar todos os dados da sessão
$_SESSION = array();

// Se desejar destruir a sessão completamente, apague também o cookie de sessão
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destruir a sessão
session_destroy();

// Limpar cookie "Lembrar de mim"
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Redirecionar para a página de login
redirect('login.php');
?>