<?php
session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config.php";

if (!isset($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Dados do usuário logado (a senha NÃO é consultada)
$sql = "SELECT id, nome, email, criado_em
        FROM usuarios
        WHERE id = :id
        LIMIT 1";

$stmt = $pdo->prepare($sql);
$stmt->execute([":id" => $_SESSION["usuario_id"]]);
$usuario = $stmt->fetch();

// Conta não existe mais: encerra a sessão
if (!$usuario) {
    session_unset();
    session_destroy();

    header("Location: login.php");
    exit;
}

// Saudação conforme a hora (if / elseif / else do Tópico 06)
$hora = (int) date("H");

if ($hora < 12) {
    $saudacao = "Bom dia";
} elseif ($hora < 18) {
    $saudacao = "Boa tarde";
} else {
    $saudacao = "Boa noite";
}

// Mensagens do sistema, escolhidas pelo código da URL (?msg=)
$avisos = [
    "atualizado"   => "Dados atualizados com sucesso!",
    "erro_excluir" => "Não foi possível excluir a conta. Tente novamente."
];

// Erros da edição: aparecem dentro do modal, que reabre sozinho
$errosEdicao = [
    "campos"         => "Preencha nome e e-mail.",
    "nome_longo"     => "O nome deve ter no máximo 100 caracteres.",
    "email_invalido" => "Informe um e-mail válido.",
    "email_repetido" => "Este e-mail já está em uso por outra conta.",
    "senha_curta"    => "A nova senha precisa ter pelo menos 8 caracteres.",
    "senha_atual"    => "A senha atual está incorreta.",
    "csrf"           => "Solicitação inválida. Atualize a página e tente novamente.",
    "erro"           => "Não foi possível salvar as alterações."
];

$status = $_GET["msg"] ?? "";
$status = is_string($status) ? $status : "";

$aviso = "";
$tipoAviso = "sucesso";
$erroEdicao = "";

if (isset($errosEdicao[$status])) {
    $erroEdicao = $errosEdicao[$status];
} elseif (isset($avisos[$status])) {
    $aviso = $avisos[$status];
    $tipoAviso = ($status === "atualizado") ? "sucesso" : "erro";
}

$nome = htmlspecialchars($usuario["nome"], ENT_QUOTES, "UTF-8");
$email = htmlspecialchars($usuario["email"], ENT_QUOTES, "UTF-8");
$inicial = htmlspecialchars(
    mb_strtoupper(mb_substr($usuario["nome"], 0, 1)),
    ENT_QUOTES,
    "UTF-8"
);
$desde = date("d/m/Y", strtotime($usuario["criado_em"]));
$token = htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8");
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Painel · Vitrola</title>
</head>
<body class="dashboard-page">

    <header class="topo">
        <div class="topo-marca">
            <span class="disco disco-pequeno" aria-hidden="true"></span>
            <strong>Vitrola</strong>
        </div>

        <div class="topo-usuario">
            <span class="topo-nome"><?= $nome ?></span>
            <a class="btn btn-sec btn-pequeno" href="logout.php">Sair</a>
        </div>
    </header>

    <main class="conteudo">

        <h1><?= $saudacao ?>, <?= $nome ?>.</h1>

        <?php if ($aviso !== ""): ?>
            <p class="alerta <?= $tipoAviso ?>">
                <?= htmlspecialchars($aviso, ENT_QUOTES, "UTF-8") ?>
            </p>
        <?php endif; ?>

        <section class="card">
            <div class="card-titulo">
                <div class="avatar"><?= $inicial ?></div>
                <h2>Minha conta</h2>
            </div>

            <dl class="dados">
                <div>
                    <dt>Nome</dt>
                    <dd><?= $nome ?></dd>
                </div>
                <div>
                    <dt>E-mail</dt>
                    <dd><?= $email ?></dd>
                </div>
                <div>
                    <dt>Membro desde</dt>
                    <dd><?= $desde ?></dd>
                </div>
            </dl>

            <div class="acoes">
                <button type="button" class="btn" id="abrir-editar">
                    Editar dados
                </button>

                <form
                    method="POST"
                    action="excluir_conta.php"
                    onsubmit="return confirm('Tem certeza que deseja excluir sua conta? Essa ação não pode ser desfeita.')"
                >
                    <input type="hidden" name="csrf_token" value="<?= $token ?>">
                    <button type="submit" class="btn btn-perigo">
                        Excluir conta
                    </button>
                </form>
            </div>
        </section>

        <section class="card" id="playlist" data-uid="<?= (int) $usuario["id"] ?>">
            <h2>Minhas playlists</h2>
            <p class="subtitulo">As músicas ficam salvas só neste navegador.</p>

            <form id="form-musica" class="form-musica" autocomplete="off">
                <input
                    type="text"
                    id="titulo"
                    placeholder="Título da música"
                    aria-label="Título da música"
                    maxlength="80"
                    required
                >
                <input
                    type="text"
                    id="artista"
                    placeholder="Artista"
                    aria-label="Artista"
                    maxlength="80"
                    required
                >
                <button type="submit" class="btn" id="btn-salvar-musica">
                    Adicionar
                </button>
                <button type="button" class="btn btn-sec" id="btn-cancelar-musica" hidden>
                    Cancelar
                </button>
            </form>

            <ul class="musicas" id="lista-musicas"></ul>
            <p class="vazio" id="vazio">
                Nenhuma música ainda. Adicione a primeira acima.
            </p>
        </section>

    </main>

    <dialog
        id="modal-editar"
        data-abrir="<?= $erroEdicao !== "" ? "1" : "0" ?>"
    >
        <form method="POST" action="editar_perfil.php">
            <h2>Editar dados</h2>

            <?php if ($erroEdicao !== ""): ?>
                <p class="alerta erro">
                    <?= htmlspecialchars($erroEdicao, ENT_QUOTES, "UTF-8") ?>
                </p>
            <?php endif; ?>

            <input type="hidden" name="csrf_token" value="<?= $token ?>">

            <label for="edit-nome">Nome</label>
            <input
                type="text"
                id="edit-nome"
                name="nome"
                maxlength="100"
                value="<?= $nome ?>"
                required
            >

            <label for="edit-email">E-mail</label>
            <input
                type="email"
                id="edit-email"
                name="email"
                maxlength="150"
                value="<?= $email ?>"
                required
            >

            <label for="edit-nova">Nova senha</label>
            <input
                type="password"
                id="edit-nova"
                name="nova_senha"
                minlength="8"
                autocomplete="new-password"
            >
            <small>Deixe em branco para manter a senha atual.</small>

            <label for="edit-atual">Senha atual</label>
            <input
                type="password"
                id="edit-atual"
                name="senha_atual"
                autocomplete="current-password"
            >
            <small>Obrigatória somente se for trocar a senha.</small>

            <div class="modal-acoes">
                <button type="button" class="btn btn-sec" id="cancelar-edicao">
                    Cancelar
                </button>
                <button type="submit" class="btn">Salvar</button>
            </div>
        </form>
    </dialog>

    <script src="painel.js"></script>
</body>
</html>
