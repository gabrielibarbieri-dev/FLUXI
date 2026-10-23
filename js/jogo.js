"use strict";

const jogo = document.querySelector(".play-page[data-materia]");

if (jogo) {
    const endpointPerguntas = jogo.dataset.apiPerguntas;
    const endpointPartida = jogo.dataset.apiPartida;
    const tokenCsrf = jogo.dataset.csrf;
    const materia = jogo.dataset.materia;
    const fase = Number(jogo.dataset.fase);
    const contador = document.querySelector("#question-counter");
    const pontuacao = document.querySelector("#live-score");
    const barraProgresso = document.querySelector("#question-progress");
    const mensagemFlor = document.querySelector("#flor-message");
    const textoPergunta = document.querySelector("#question-text");
    const listaAlternativas = document.querySelector("#answer-list");
    const feedback = document.querySelector("#answer-feedback");
    const botaoProxima = document.querySelector("#next-question");
    const cronometro = document.querySelector("#question-timer");
    const valorCronometro = document.querySelector("#question-timer-value");
    const barraCronometro = document.querySelector("#question-timer-progress");
    const trilhoCronometro = cronometro.querySelector('[role="progressbar"]');
    const mensagemErro = document.querySelector("#game-error");
    const conteudo = document.querySelector("#game-content");
    const TEMPO_POR_QUESTAO_MS = 30_000;

    let perguntas = [];
    let indice = 0;
    let podeResponder = false;
    let finalizando = false;
    let intervaloCronometro = null;
    let prazoLocal = 0;

    async function requisicao(url, dados) {
        const resposta = await fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-Token": tokenCsrf
            },
            body: JSON.stringify(dados),
            credentials: "same-origin"
        });
        const resultado = await resposta.json();
        if (!resposta.ok) {
            const erro = new Error(resultado.erro || "Não foi possível concluir essa ação.");
            erro.dados = resultado;
            throw erro;
        }
        return resultado;
    }

    function pararCronometro() {
        if (intervaloCronometro !== null) {
            window.clearInterval(intervaloCronometro);
            intervaloCronometro = null;
        }
    }

    function atualizarCronometro() {
        const restante = Math.max(0, prazoLocal - Date.now());
        const segundos = Math.ceil(restante / 1000);
        const minutosTexto = String(Math.floor(segundos / 60)).padStart(2, "0");
        const segundosTexto = String(segundos % 60).padStart(2, "0");
        const porcentagem = Math.max(0, (restante / TEMPO_POR_QUESTAO_MS) * 100);

        valorCronometro.textContent = `${minutosTexto}:${segundosTexto}`;
        barraCronometro.style.width = `${porcentagem}%`;
        trilhoCronometro.setAttribute("aria-valuenow", String(segundos));
        cronometro.classList.toggle("is-warning", segundos <= 15 && segundos > 0);

        if (restante <= 0) {
            pararCronometro();
            cronometro.classList.add("is-expired");
            enviarTempoEsgotado(perguntas[indice]);
        }
    }

    function iniciarCronometro(tempoRestanteMs = TEMPO_POR_QUESTAO_MS) {
        pararCronometro();
        prazoLocal = Date.now() + Math.min(TEMPO_POR_QUESTAO_MS, Math.max(0, tempoRestanteMs));
        cronometro.classList.remove("is-warning", "is-expired");
        atualizarCronometro();
        intervaloCronometro = window.setInterval(atualizarCronometro, 100);
    }

    function mostrarErro(mensagem, aoTentarNovamente = null) {
        mensagemErro.hidden = false;
        mensagemErro.textContent = mensagem;
        const link = document.createElement("a");
        link.href = materia === "matematica" ? "matematica.php" : "portugues.php";
        link.textContent = " Voltar às fases";
        mensagemErro.append(link);
        if (aoTentarNovamente) {
            const tentarNovamente = document.createElement("button");
            tentarNovamente.className = "text-button";
            tentarNovamente.type = "button";
            tentarNovamente.textContent = "Tentar salvar novamente";
            tentarNovamente.addEventListener("click", () => {
                mensagemErro.hidden = true;
                conteudo.hidden = false;
                aoTentarNovamente();
            });
            mensagemErro.append(tentarNovamente);
        }
        conteudo.hidden = true;
    }

    function desenharPergunta(tempoRestanteMs = TEMPO_POR_QUESTAO_MS) {
        const pergunta = perguntas[indice];
        if (!pergunta) {
            finalizarPartida();
            return;
        }
        podeResponder = true;
        cronometro.hidden = false;
        feedback.hidden = true;
        botaoProxima.hidden = true;
        listaAlternativas.replaceChildren();
        contador.textContent = `Questão ${indice + 1} de ${perguntas.length}`;
        barraProgresso.style.width = `${(indice / perguntas.length) * 100}%`;
        textoPergunta.textContent = pergunta.pergunta;

        Object.entries(pergunta.alternativas).forEach(([letra, texto]) => {
            const botao = document.createElement("button");
            botao.className = "answer-option";
            botao.type = "button";
            botao.dataset.resposta = letra;
            const identificador = document.createElement("span");
            identificador.className = "answer-letter";
            identificador.textContent = letra;
            const resposta = document.createElement("span");
            resposta.textContent = texto;
            botao.append(identificador, resposta);
            botao.addEventListener("click", () => responder(pergunta, letra, botao));
            listaAlternativas.append(botao);
        });
        iniciarCronometro(tempoRestanteMs);
    }

    function mostrarResultadoResposta(resultado, botaoEscolhido = null) {
        pararCronometro();
        podeResponder = false;
        mensagemErro.hidden = true;
        pontuacao.textContent = resultado.pontuacao;
        mensagemFlor.textContent = resultado.mensagem_flor;
        mensagemFlor.classList.toggle("flor-happy", resultado.acertou);
        mensagemFlor.classList.toggle("flor-try-again", !resultado.acertou);

        listaAlternativas.querySelectorAll("button").forEach((botao) => {
            botao.disabled = true;
        });
        const alternativaCorreta = listaAlternativas.querySelector(
            `[data-resposta="${resultado.resposta_correta}"]`
        );
        if (alternativaCorreta) {
            alternativaCorreta.classList.add("answer-correct");
        }
        if (resultado.expirada) {
            listaAlternativas.querySelectorAll(".answer-selected").forEach((botao) => {
                botao.classList.remove("answer-selected");
            });
        }
        if (!resultado.acertou && botaoEscolhido && !resultado.expirada) {
            botaoEscolhido.classList.add("answer-wrong");
        }

        feedback.hidden = false;
        feedback.className = `answer-feedback ${resultado.acertou ? "feedback-correct" : "feedback-wrong"}`;
        const titulo = document.createElement("strong");
        titulo.textContent = resultado.expirada
            ? `Tempo esgotado. A resposta certa era ${resultado.resposta_correta}. `
            : (resultado.acertou
                ? `+${resultado.pontos_ganhos} pontos! `
                : `A resposta certa era ${resultado.resposta_correta}. `);
        const explicacao = document.createElement("span");
        explicacao.textContent = resultado.explicacao;
        feedback.replaceChildren(titulo, explicacao);

        indice = resultado.questoes_respondidas;
        barraProgresso.style.width = `${(indice / perguntas.length) * 100}%`;
        botaoProxima.innerHTML = indice === perguntas.length
            ? `Ver resultado <i class="bi bi-arrow-right"></i>`
            : `Próxima pergunta <i class="bi bi-arrow-right"></i>`;
        botaoProxima.hidden = false;
        botaoProxima.focus();
    }

    async function responder(pergunta, letra, botaoEscolhido) {
        if (!podeResponder) {
            return;
        }
        podeResponder = false;
        pararCronometro();
        listaAlternativas.querySelectorAll("button").forEach((botao) => {
            botao.disabled = true;
        });
        botaoEscolhido.classList.add("answer-selected");

        try {
            const resultado = await requisicao(endpointPartida, {
                acao: "responder",
                pergunta_id: pergunta.id,
                resposta: letra
            });
            mostrarResultadoResposta(resultado, botaoEscolhido);
        } catch (erro) {
            podeResponder = true;
            listaAlternativas.querySelectorAll("button").forEach((botao) => {
                botao.disabled = false;
            });
            if (Date.now() >= prazoLocal) {
                pararCronometro();
                enviarTempoEsgotado(pergunta);
            } else {
                intervaloCronometro = window.setInterval(atualizarCronometro, 100);
                mostrarErro(erro.message, () => responder(pergunta, letra, botaoEscolhido));
            }
        }
    }

    async function enviarTempoEsgotado(pergunta) {
        if (!pergunta || !podeResponder) {
            return;
        }
        podeResponder = false;
        pararCronometro();
        listaAlternativas.querySelectorAll("button").forEach((botao) => {
            botao.disabled = true;
        });

        try {
            const resultado = await requisicao(endpointPartida, {
                acao: "tempo_esgotado",
                pergunta_id: pergunta.id
            });
            mostrarResultadoResposta(resultado);
        } catch (erro) {
            podeResponder = true;
            if (erro.dados && Number.isFinite(erro.dados.tempo_restante_ms)) {
                prazoLocal = Date.now() + erro.dados.tempo_restante_ms;
                cronometro.classList.remove("is-expired");
                intervaloCronometro = window.setInterval(atualizarCronometro, 100);
                return;
            }
            mostrarErro(erro.message, () => {
                podeResponder = true;
                enviarTempoEsgotado(pergunta);
            });
        }
    }

    async function finalizarPartida() {
        if (finalizando) {
            return;
        }
        finalizando = true;
        cronometro.hidden = true;
        botaoProxima.disabled = true;
        try {
            const resultado = await requisicao(endpointPartida, { acao: "finalizar" });
            barraProgresso.style.width = "100%";
            desenharResultado(resultado);
        } catch (erro) {
            finalizando = false;
            botaoProxima.disabled = false;
            mostrarErro(erro.message, finalizarPartida);
        }
    }

    function desenharResultado(resultado) {
        contador.textContent = "Fase concluída!";
        textoPergunta.textContent = "Mandou bem, explorador!";
        mensagemFlor.textContent = resultado.mensagem_flor;
        mensagemFlor.classList.add("flor-happy");
        listaAlternativas.replaceChildren();
        feedback.hidden = false;
        feedback.className = "answer-feedback feedback-correct";
        const linhaPontuacao = document.createElement("strong");
        linhaPontuacao.textContent = `${resultado.pontuacao} pontos · ${resultado.acertos}/${resultado.total} acertos`;
        const linhaEstrelas = document.createElement("span");
        linhaEstrelas.innerHTML = `<i class="bi bi-star-fill text-warning"></i> +${resultado.estrelas_ganhas} ${resultado.estrelas_ganhas === 1 ? "estrela conquistada" : "estrelas conquistadas"}`;
        feedback.replaceChildren(linhaPontuacao, linhaEstrelas);

        const voltar = document.createElement("a");
        voltar.className = "button button-primary";
        voltar.href = materia === "matematica" ? "matematica.php" : "portugues.php";
        voltar.innerHTML = `<i class="bi bi-arrow-left"></i> Voltar às fases`;
        const inicio = document.createElement("a");
        inicio.className = "button button-outline";
        inicio.href = "../dashboard.php";
        inicio.innerHTML = `<i class="bi bi-speedometer2"></i> Ir ao painel`;
        botaoProxima.replaceWith(voltar, inicio);
    }

    botaoProxima.addEventListener("click", () => {
        if (indice >= perguntas.length) {
            finalizarPartida();
        } else {
            botaoProxima.disabled = true;
            const pergunta = perguntas[indice];
            requisicao(endpointPartida, {
                acao: "iniciar_questao",
                pergunta_id: pergunta.id
            }).then((resultado) => {
                botaoProxima.disabled = false;
                desenharPergunta(resultado.tempo_restante_ms);
            }).catch((erro) => {
                botaoProxima.disabled = false;
                mostrarErro(erro.message, () => botaoProxima.click());
            });
        }
    });

    async function iniciar() {
        try {
            const resultado = await requisicao(endpointPerguntas, { materia, fase });
            perguntas = resultado.perguntas;
            mensagemFlor.textContent = resultado.mensagem_flor;
            desenharPergunta(resultado.tempo_restante_ms);
        } catch (erro) {
            mostrarErro(erro.message);
        }
    }

    iniciar();
}
