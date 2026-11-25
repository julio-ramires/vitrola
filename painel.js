// ===== Modal "Editar dados" =====
const modal = document.getElementById("modal-editar");

if (modal) {
    const formModal = modal.querySelector("form");

    document.getElementById("abrir-editar").addEventListener("click", function () {
        modal.showModal();
    });

    function fecharModal() {
        formModal.reset();   // volta os campos aos valores salvos
        modal.close();
    }

    document.getElementById("cancelar-edicao").addEventListener("click", fecharModal);

    // Clicar fora do quadro também cancela
    modal.addEventListener("click", function (evento) {
        if (evento.target === modal) {
            fecharModal();
        }
    });

    // Se o PHP devolveu um erro da edição, reabre o modal já mostrando o aviso
    if (modal.dataset.abrir === "1") {
        modal.showModal();
    }
}

// ===== Minhas playlists (localStorage, não usa o banco) =====
const secao = document.getElementById("playlist");

if (secao) {
    // Uma lista por usuário, para contas diferentes no mesmo navegador não se misturarem
    const chave = "vitrola_playlist_" + secao.dataset.uid;

    const form = document.getElementById("form-musica");
    const campoTitulo = document.getElementById("titulo");
    const campoArtista = document.getElementById("artista");
    const botaoSalvar = document.getElementById("btn-salvar-musica");
    const botaoCancelar = document.getElementById("btn-cancelar-musica");
    const lista = document.getElementById("lista-musicas");
    const vazio = document.getElementById("vazio");

    let musicas = carregar();   // array de {id, titulo, artista}
    let editandoId = null;      // null = adicionando; senão, o id da música em edição

    function carregar() {
        try {
            const dados = JSON.parse(localStorage.getItem(chave));
            return Array.isArray(dados) ? dados : [];
        } catch (erro) {
            return [];
        }
    }

    function salvar() {
        try {
            localStorage.setItem(chave, JSON.stringify(musicas));
        } catch (erro) {
            // armazenamento bloqueado ou cheio: a lista segue só na página
        }
    }

    function sairDaEdicao() {
        editandoId = null;
        form.reset();
        botaoSalvar.textContent = "Adicionar";
        botaoCancelar.hidden = true;
    }

    function criarBotao(texto, classe, acao) {
        const botao = document.createElement("button");
        botao.type = "button";
        botao.className = "btn btn-sec btn-pequeno " + classe;
        botao.textContent = texto;
        botao.addEventListener("click", acao);
        return botao;
    }

    function desenhar() {
        lista.innerHTML = "";
        vazio.hidden = musicas.length > 0;

        musicas.forEach(function (musica) {
            const item = document.createElement("li");

            // textContent (e não innerHTML) evita que o texto digitado vire HTML
            const texto = document.createElement("div");
            const titulo = document.createElement("strong");
            const artista = document.createElement("span");
            titulo.textContent = musica.titulo;
            artista.textContent = musica.artista;
            texto.append(titulo, artista);

            const acoes = document.createElement("div");
            acoes.className = "musica-acoes";
            acoes.append(
                criarBotao("Editar", "", function () {
                    editandoId = musica.id;
                    campoTitulo.value = musica.titulo;
                    campoArtista.value = musica.artista;
                    botaoSalvar.textContent = "Salvar";
                    botaoCancelar.hidden = false;
                    campoTitulo.focus();
                }),
                criarBotao("Remover", "remover", function () {
                    musicas = musicas.filter(function (m) {
                        return m.id !== musica.id;
                    });
                    if (editandoId === musica.id) {
                        sairDaEdicao();
                    }
                    salvar();
                    desenhar();
                })
            );

            item.append(texto, acoes);
            lista.append(item);
        });
    }

    form.addEventListener("submit", function (evento) {
        evento.preventDefault();

        const titulo = campoTitulo.value.trim();
        const artista = campoArtista.value.trim();

        if (titulo === "" || artista === "") {
            return;
        }

        if (editandoId === null) {
            musicas.push({
                id: Date.now() + "-" + Math.random().toString(36).slice(2, 7),
                titulo: titulo,
                artista: artista
            });
        } else {
            musicas.forEach(function (musica) {
                if (musica.id === editandoId) {
                    musica.titulo = titulo;
                    musica.artista = artista;
                }
            });
        }

        salvar();
        sairDaEdicao();
        desenhar();
        campoTitulo.focus();
    });

    botaoCancelar.addEventListener("click", sairDaEdicao);

    desenhar();
}
