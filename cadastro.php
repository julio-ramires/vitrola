<?php
require_once "config.php";

$mensagem = "";
$nome = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = trim(campo_post("nome"));
    $email = trim(campo_post("email"));
    $senha = campo_post("senha");

    if ($nome === "" || $email === "" || $senha === "") {
        $mensagem = "Preencha todos os campos.";
    } elseif (mb_strlen($nome) > 100) {
        $mensagem = "O nome deve ter no máximo 100 caracteres.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
        $mensagem = "Informe um e-mail válido.";
    } elseif (strlen($senha) < 8) {
        $mensagem = "A senha precisa ter pelo menos 8 caracteres.";
    } else {
        // A senha nunca vai para o banco em texto puro: só o hash
        $hash = password_hash($senha, PASSWORD_DEFAULT);

        try {
            $sql = "INSERT INTO usuarios (nome, email, senha)
                    VALUES (:nome, :email, :senha)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ":nome" => $nome,
                ":email" => $email,
                ":senha" => $hash
            ]);

            header("Location: login.php?msg=cadastrado");
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() === "23000") {
                $mensagem = "Este e-mail já está cadastrado.";
            } else {
                error_log($e->getMessage());
                $mensagem = "Não foi possível concluir o cadastro.";
            }
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
    <title>Cadastro · Vitrola</title>
</head>
<body class="auth-page">

    <main class="auth-card">
        <div class="brand">
            <span class="disco" aria-hidden="true"></span>
            <h1>Vitrola</h1>
            <p>Suas playlists, do seu jeito.</p>
        </div>

        <nav class="tabs">
            <a class="tab" href="login.php">Login</a>
            <a class="tab active" href="cadastro.php">Cadastro</a>
        </nav>

        <?php if ($mensagem !== ""): ?>
            <p class="alerta erro">
                <?= htmlspecialchars($mensagem, ENT_QUOTES, "UTF-8") ?>
            </p>
        <?php endif; ?>

        <form method="POST" action="cadastro.php">
            <label for="nome">Nome</label>
            <input
                type="text"
                id="nome"
                name="nome"
                maxlength="100"
                autocomplete="name"
                value="<?= htmlspecialchars($nome, ENT_QUOTES, "UTF-8") ?>"
                required
            >

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
                minlength="8"
                autocomplete="new-password"
                required
            >
            <small>Mínimo de 8 caracteres.</small>

            <button type="submit" class="btn">Criar conta</button>
        </form>
    </main>

</body>
</html>
