"use strict";

const perfilForm = document.querySelector("#profile-edit");

if (perfilForm) {
    const nome = perfilForm.querySelector("#profile-name-input");
    const mensagem = perfilForm.querySelector("#profile-edit-message");
    const titulo = document.querySelector("#profile-name");
    const botao = perfilForm.querySelector("button[type='submit']");

    perfilForm.addEventListener("submit", async (evento) => {
        evento.preventDefault();
        mensagem.hidden = false;
        mensagem.className = "profile-edit-message";
        mensagem.textContent = "Salvando seu nome…";
        botao.disabled = true;

        try {
            const resposta = await fetch(perfilForm.dataset.endpoint, {
                method: "PUT",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-Token": perfilForm.dataset.csrf
                },
                credentials: "same-origin",
                body: JSON.stringify({ nome: nome.value })
            });
            const resultado = await resposta.json();
            if (!resposta.ok) {
                throw new Error(resultado.erro || "Não foi possível atualizar seu nome.");
            }
            nome.value = resultado.perfil.nome;
            titulo.textContent = resultado.perfil.nome;
            mensagem.classList.add("edit-success");
            mensagem.textContent = resultado.mensagem;
        } catch (erro) {
            mensagem.classList.add("edit-error");
            mensagem.textContent = erro.message;
        } finally {
            botao.disabled = false;
        }
    });
}

document.querySelectorAll(".student-unlink-form").forEach((form) => {
    form.addEventListener("submit", (event) => {
        const confirmado = window.confirm(
            "Deseja se desvincular do professor? Seu progresso será mantido e você poderá escolher outro."
        );
        if (!confirmado) {
            event.preventDefault();
        }
    });
});
