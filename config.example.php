<?php

// Fuso horário usado pelas funções de data (Tópico 06)
date_default_timezone_set("America/Sao_Paulo");

$host = "127.0.0.1";
$banco = "sistema_login";
$usuario = "SEU_USUARIO";
$senha = "SUA_SENHA";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$banco;charset=utf8mb4",
        $usuario,
        $senha,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit("Não foi possível conectar ao banco de dados.");
}

// Lê um campo do formulário (POST) sempre como texto.
// Evita erro caso alguém envie um array no lugar de uma string.
function campo_post($nome)
{
    return isset($_POST[$nome]) && is_string($_POST[$nome])
        ? $_POST[$nome]
        : "";
}
