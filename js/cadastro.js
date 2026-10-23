"use strict";

const campoConviteProfessor = document.querySelector("#convite-professor-field");
const codigoConviteProfessor = document.querySelector("#codigo_convite");
const campoProfessorAluno = document.querySelector("#professor-aluno-field");
const opcoesTipoConta = document.querySelectorAll('input[name="tipo_conta"]');

function atualizarCampoConvite() {
    const tipoProfessor = document.querySelector('input[name="tipo_conta"]:checked')?.value === "professor";
    campoConviteProfessor.hidden = !tipoProfessor;
    campoProfessorAluno.hidden = tipoProfessor;
    codigoConviteProfessor.required = tipoProfessor;
}

opcoesTipoConta.forEach((opcao) => {
    opcao.addEventListener("change", atualizarCampoConvite);
});

atualizarCampoConvite();
