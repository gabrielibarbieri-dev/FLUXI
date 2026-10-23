"use strict";

(() => {
    const storageKey = "flux-accessibility-settings";
    const defaults = { fontScale: 100, highContrast: false };
    const settings = { ...defaults };

    try {
        const savedSettings = JSON.parse(localStorage.getItem(storageKey) || "null");
        if (savedSettings && typeof savedSettings === "object") {
            if ([100, 110, 120, 130, 140, 150].includes(savedSettings.fontScale)) {
                settings.fontScale = savedSettings.fontScale;
            }
            if (typeof savedSettings.highContrast === "boolean") {
                settings.highContrast = savedSettings.highContrast;
            }
        }
    } catch (error) {
        console.error("Não foi possível carregar as configurações de acessibilidade.", error);
    }

    const widget = document.createElement("div");
    widget.className = "accessibility-widget";
    widget.innerHTML = `
        <section class="accessibility-panel" id="accessibility-panel" aria-labelledby="accessibility-title" hidden>
            <h2 id="accessibility-title">Acessibilidade</h2>
            <div class="accessibility-control">
                <span id="font-size-label">Tamanho das letras: <strong>100%</strong></span>
                <div class="accessibility-button-group" aria-labelledby="font-size-label">
                    <button class="accessibility-option" type="button" data-font-step="-10" aria-label="Diminuir tamanho das letras">A−</button>
                    <button class="accessibility-option" type="button" data-font-step="10" aria-label="Aumentar tamanho das letras">A+</button>
                </div>
            </div>
            <button class="accessibility-option accessibility-contrast" type="button" aria-pressed="false">
                <i class="bi bi-circle-half" aria-hidden="true"></i>
                <span>Alto contraste</span>
            </button>
            <button class="accessibility-option accessibility-reset" type="button">
                <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                <span>Restaurar padrão</span>
            </button>
            <p class="accessibility-status" role="status" aria-live="polite"></p>
        </section>
        <button class="accessibility-toggle" type="button" aria-expanded="false" aria-controls="accessibility-panel">
            <i class="bi bi-universal-access" aria-hidden="true"></i>
            <span>Acessibilidade</span>
        </button>
    `;
    document.body.append(widget);

    const panel = widget.querySelector(".accessibility-panel");
    const toggle = widget.querySelector(".accessibility-toggle");
    const fontLabel = widget.querySelector("#font-size-label strong");
    const contrastButton = widget.querySelector(".accessibility-contrast");
    const status = widget.querySelector(".accessibility-status");
    const fontButtons = widget.querySelectorAll("[data-font-step]");

    function applySettings() {
        document.documentElement.style.fontSize = `${settings.fontScale}%`;
        document.body.classList.toggle("flux-high-contrast", settings.highContrast);
        fontLabel.textContent = `${settings.fontScale}%`;
        contrastButton.setAttribute("aria-pressed", String(settings.highContrast));
        fontButtons.forEach((button) => {
            const step = Number(button.dataset.fontStep);
            button.disabled = step > 0
                ? settings.fontScale >= 150
                : settings.fontScale <= 100;
        });
    }

    function saveSettings() {
        try {
            localStorage.setItem(storageKey, JSON.stringify(settings));
        } catch (error) {
            console.error("Não foi possível salvar as configurações de acessibilidade.", error);
            status.textContent = "Não foi possível salvar as preferências neste navegador.";
        }
    }

    toggle.addEventListener("click", () => {
        const isOpen = toggle.getAttribute("aria-expanded") === "true";
        toggle.setAttribute("aria-expanded", String(!isOpen));
        panel.hidden = isOpen;
        if (!isOpen) {
            panel.querySelector("button:not(:disabled)")?.focus();
        }
    });

    fontButtons.forEach((button) => {
        button.addEventListener("click", () => {
            settings.fontScale += Number(button.dataset.fontStep);
            applySettings();
            saveSettings();
            status.textContent = `Tamanho das letras: ${settings.fontScale}%.`;
        });
    });

    contrastButton.addEventListener("click", () => {
        settings.highContrast = !settings.highContrast;
        applySettings();
        saveSettings();
        status.textContent = settings.highContrast
            ? "Alto contraste ativado."
            : "Alto contraste desativado.";
    });

    widget.querySelector(".accessibility-reset").addEventListener("click", () => {
        Object.assign(settings, defaults);
        applySettings();
        saveSettings();
        status.textContent = "Configurações padrão restauradas.";
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && !panel.hidden) {
            panel.hidden = true;
            toggle.setAttribute("aria-expanded", "false");
            toggle.focus();
        }
    });

    applySettings();
})();
