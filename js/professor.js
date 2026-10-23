"use strict";

const tabs = Array.from(document.querySelectorAll('.teacher-tabs-header [role="tab"]'));
const panels = tabs
    .map((tab) => document.getElementById(tab.getAttribute("aria-controls")))
    .filter((panel) => panel instanceof HTMLElement);

function selectTab(tab, focusTab = false) {
    tabs.forEach((item) => {
        const selected = item === tab;
        item.setAttribute("aria-selected", String(selected));
        item.tabIndex = selected ? 0 : -1;

        const panel = document.getElementById(item.getAttribute("aria-controls"));
        if (panel) {
            panel.hidden = !selected;
            panel.classList.toggle("is-active", selected);
        }
    });

    if (focusTab) {
        tab.focus();
    }

    const prefersReducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    window.scrollTo({
        top: 0,
        behavior: prefersReducedMotion ? "auto" : "smooth",
    });
}

tabs.forEach((tab, index) => {
    tab.addEventListener("click", () => selectTab(tab));
    tab.addEventListener("keydown", (event) => {
        let nextIndex;
        if (event.key === "ArrowRight" || event.key === "ArrowDown") {
            nextIndex = (index + 1) % tabs.length;
        } else if (event.key === "ArrowLeft" || event.key === "ArrowUp") {
            nextIndex = (index - 1 + tabs.length) % tabs.length;
        } else if (event.key === "Home") {
            nextIndex = 0;
        } else if (event.key === "End") {
            nextIndex = tabs.length - 1;
        } else {
            return;
        }

        event.preventDefault();
        selectTab(tabs[nextIndex], true);
    });
});

panels.forEach((panel) => {
    if (!panel.hidden) {
        panel.classList.add("is-active");
    }
});

document.querySelectorAll(".teacher-remove-student").forEach((form) => {
    form.addEventListener("submit", (event) => {
        const nome = form.dataset.studentName || "este aluno";
        const confirmado = window.confirm(
            `Desvincular ${nome} da sua turma? A conta e o histórico serão preservados.`
        );
        if (!confirmado) {
            event.preventDefault();
        }
    });
});
