<?php

$host = 'localhost';
$dbname = '#'; // Nome do banco de dados
$username = '#'; // Usuário do banco de dados
$password = '#'; // Senha do banco de dados

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}

?>