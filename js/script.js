"use strict";

const revealItems = document.querySelectorAll(
    ".feature-card, .step-card, .flor-section, .final-cta"
);

if ("IntersectionObserver" in window) {
    const revealObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add("is-visible");
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.12 });

    revealItems.forEach((item) => {
        item.setAttribute("data-reveal", "");
        revealObserver.observe(item);
    });
} else {
    revealItems.forEach((item) => item.classList.add("is-visible"));
}
