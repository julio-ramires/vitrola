<?php
session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config.php";

// Só exclui por POST (botão do painel). Aberto direto (GET): só volta.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: painel.php");
    exit;
}

$token = campo_post("csrf_token");

if (!isset($_SESSION["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $token)) {
    header("Location: painel.php?msg=erro_excluir");
    exit;
}

try {
    $sql = "DELETE FROM usuarios WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([":id" => $_SESSION["usuario_id"]]);

    if ($stmt->rowCount() === 1) {
        // Mesmo encerramento de sessão do logout.php
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                "",
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();

        header("Location: login.php?msg=excluida");
        exit;
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
}

header("Location: painel.php?msg=erro_excluir");
exit;
