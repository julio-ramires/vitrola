<?php
session_start();
require_once "config.php";

// Quem já está logado vai direto para o painel
if (isset($_SESSION["usuario_id"])) {
    header("Location: painel.php");
    exit;
}

// Avisos que chegam pela URL (padrão do Tópico 08: ?msg=codigo)
$avisos = [
    "cadastrado" => "Cadastro realizado com sucesso! Faça login para entrar.",
    "excluida"   => "Sua conta foi excluída."
];

$mensagem = "";
$tipo = "erro";
$email = "";

$status = $_GET["msg"] ?? "";
if (is_string($status) && isset($avisos[$status])) {
    $mensagem = $avisos[$status];
    $tipo = "sucesso";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim(campo_post("email"));
    $senha = campo_post("senha");

    if ($email === "" || $senha === "") {
        $mensagem = "Preencha todos os campos.";
    } else {
        $sql = "SELECT id, senha
                FROM usuarios
                WHERE email = :email
                LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([":email" => $email]);
        $dados = $stmt->fetch();

        if ($dados && password_verify($senha, $dados["senha"])) {
            session_regenerate_id(true);

            $_SESSION["usuario_id"] = $dados["id"];

            header("Location: painel.php");
            exit;
        } else {
            $mensagem = "E-mail ou senha inválidos.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Entrar · Vitrola</title>
</head>
<body class="auth-page">

    <main class="auth-card">
        <div class="brand">
            <span class="disco" aria-hidden="true"></span>
            <h1>Vitrola</h1>
            <p>Suas playlists, do seu jeito.</p>
        </div>

        <nav class="tabs">
            <a class="tab active" href="login.php">Login</a>
            <a class="tab" href="cadastro.php">Cadastro</a>
        </nav>

        <?php if ($mensagem !== ""): ?>
            <p class="alerta <?= $tipo ?>">
                <?= htmlspecialchars($mensagem, ENT_QUOTES, "UTF-8") ?>
            </p>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <label for="email">E-mail</label>
            <input
                type="email"
                id="email"
                name="email"
                maxlength="150"
                autocomplete="email"
                value="<?= htmlspecialchars($email, ENT_QUOTES, "UTF-8") ?>"
                required
            >

            <label for="senha">Senha</label>
            <input
                type="password"
                id="senha"
                name="senha"
                autocomplete="current-password"
                required
            >

            <button type="submit" class="btn">Entrar</button>
        </form>
    </main>

</body>
</html>
