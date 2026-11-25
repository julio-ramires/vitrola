<?php
session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config.php";

// Volta ao painel levando um código de aviso (?msg=), como no Tópico 08
function voltar($codigo)
{
    header("Location: painel.php?msg=" . $codigo);
    exit;
}

// Arquivo aberto direto pelo navegador (GET): só volta ao painel
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: painel.php");
    exit;
}

$token = campo_post("csrf_token");

if (!isset($_SESSION["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $token)) {
    voltar("csrf");
}

$nome = trim(campo_post("nome"));
$email = trim(campo_post("email"));
$senhaAtual = campo_post("senha_atual");
$novaSenha = campo_post("nova_senha");

if ($nome === "" || $email === "") {
    voltar("campos");
}

if (mb_strlen($nome) > 100) {
    voltar("nome_longo");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
    voltar("email_invalido");
}

// A senha só muda se o campo "nova senha" for preenchido
$trocarSenha = ($novaSenha !== "");

if ($trocarSenha) {
    if (strlen($novaSenha) < 8) {
        voltar("senha_curta");
    }

    $sql = "SELECT senha FROM usuarios WHERE id = :id LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([":id" => $_SESSION["usuario_id"]]);
    $dados = $stmt->fetch();

    if (!$dados || !password_verify($senhaAtual, $dados["senha"])) {
        voltar("senha_atual");
    }
}

try {
    if ($trocarSenha) {
        $sql = "UPDATE usuarios
                SET nome = :nome, email = :email, senha = :senha
                WHERE id = :id";

        $parametros = [
            ":nome" => $nome,
            ":email" => $email,
            ":senha" => password_hash($novaSenha, PASSWORD_DEFAULT),
            ":id" => $_SESSION["usuario_id"]
        ];
    } else {
        $sql = "UPDATE usuarios
                SET nome = :nome, email = :email
                WHERE id = :id";

        $parametros = [
            ":nome" => $nome,
            ":email" => $email,
            ":id" => $_SESSION["usuario_id"]
        ];
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($parametros);

    if ($trocarSenha) {
        session_regenerate_id(true);
    }

    voltar("atualizado");
} catch (PDOException $e) {
    if ($e->getCode() === "23000") {
        voltar("email_repetido");
    }

    error_log($e->getMessage());
    voltar("erro");
}
