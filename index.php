<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Aprenda Matemática e Língua Portuguesa em uma aventura cheia de desafios, estrelas e descobertas.">
    <title>FLUX — Aprender pode ser uma aventura</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Nunito:wght@500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/style.css">
    <script src="js/acessibilidade.js" defer></script>
    <script src="js/script.js" defer></script>
</head>
<body>
    <header class="site-header">
        <a class="brand" href="index.php" aria-label="FLUX, página inicial"><span class="brand-mark">F</span> FLUX<span class="brand-period">.</span></a>
        <nav class="main-nav" aria-label="Navegação principal">
            <a href="#sobre"><i class="bi bi-info-circle me-1"></i> O que é</a>
            <a href="#como-funciona"><i class="bi bi-compass me-1"></i> Como funciona</a>
            <a href="#flor"><i class="bi bi-heart me-1"></i> Conheça a Flor</a>
        </nav>
        <a class="button button-small button-outline" href="login.php">Entrar <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
    </header>

    <main>
        <section class="hero">
            <div class="hero-copy">
                <span class="eyebrow"><span class="eyebrow-dot"></span> SUA PRÓXIMA FASE COMEÇA AQUI</span>
                <h1>Aprender pode ser a sua próxima <span class="highlight">aventura.</span></h1>
                <p>Desafie sua mente, conquiste estrelas e evolua enquanto joga em um ambiente dinâmico e interativo.</p>
                <div class="hero-actions">
                    <a class="button button-primary" href="login.php">Começar agora <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                    <span class="hero-note"><i class="bi bi-stars" aria-hidden="true"></i> Aprenda no seu ritmo</span>
                </div>
                <div class="hero-subjects" aria-label="Matérias disponíveis">
                    <span class="subject-pill"><i class="bi bi-calculator" aria-hidden="true"></i> Matemática</span>
                    <span class="subject-pill"><i class="bi bi-book-half" aria-hidden="true"></i> Português</span>
                </div>
            </div>

            <div class="hero-art" aria-label="Flor, a raposa mascote do FLUX">
                <div class="orbit orbit-one"></div>
                <div class="orbit orbit-two"></div>
                <span class="floating-star star-one" aria-hidden="true"><i class="bi bi-star-fill"></i></span>
                <span class="floating-star star-two" aria-hidden="true"><i class="bi bi-stars"></i></span>
                <span class="floating-star star-three" aria-hidden="true"><i class="bi bi-star-fill"></i></span>
                <div class="mascot-card">
                    <div class="mascot-face" role="img" aria-label="Raposa laranja sorridente">🦊</div>
                    <span class="mascot-name">Oi, eu sou a Flor!</span>
                    <span class="mascot-caption">Sua parceira de aventuras</span>
                </div>
                <div class="game-chip chip-level"><span class="chip-icon"><i class="bi bi-lightning-charge-fill" aria-hidden="true"></i></span><span><strong>Nível 1</strong><small>Explorador</small></span></div>
                <div class="game-chip chip-stars"><span class="chip-icon"><i class="bi bi-star-fill" aria-hidden="true"></i></span><span><strong>Novas fases</strong><small>Novas conquistas</small></span></div>
            </div>
        </section>

        <div class="trust-strip">
            <span>UM JEITO MAIS LEVE DE APRENDER</span>
            <span class="strip-sparkle"><i class="bi bi-stars" aria-hidden="true"></i></span>
            <span>NO SEU TEMPO</span>
            <span class="strip-sparkle"><i class="bi bi-stars" aria-hidden="true"></i></span>
            <span>DO SEU JEITO</span>
        </div>

        <section class="content-section about-section" id="sobre">
            <div class="section-icon icon-lilac" aria-hidden="true"><i class="bi bi-mortarboard-fill"></i></div>
            <span class="eyebrow">APRENDER JOGANDO</span>
            <h2>O que é o <span class="text-lilac">FLUX</span>?</h2>
            <p class="section-lead">O FLUX transforma conteúdos escolares em desafios interativos. Você aprende enquanto joga, acompanha sua evolução e descobre tudo o que é capaz de conquistar.</p>
            <div class="feature-grid">
                <article class="feature-card">
                    <span class="feature-icon feature-yellow" aria-hidden="true"><i class="bi bi-lightbulb-fill"></i></span>
                    <h3>Desafios que ensinam</h3>
                    <p>Matemática e Português viram missões feitas para aprender de um jeito divertido e memorável.</p>
                </article>
                <article class="feature-card">
                    <span class="feature-icon feature-aqua" aria-hidden="true"><i class="bi bi-trophy-fill"></i></span>
                    <h3>Cada conquista conta</h3>
                    <p>Ganhe estrelas e veja seu progresso crescer a cada nova descoberta e vitória conquistada.</p>
                </article>
                <article class="feature-card">
                    <span class="feature-icon feature-lilac" aria-hidden="true"><i class="bi bi-graph-up-arrow"></i></span>
                    <h3>No seu ritmo</h3>
                    <p>Avance fase por fase, desbloqueie novos mundos e celebre cada passo da sua jornada escolar.</p>
                </article>
            </div>
        </section>

        <section class="journey-section" id="como-funciona">
            <div class="journey-heading">
                <span class="eyebrow">SUA JORNADA EM QUATRO PASSOS</span>
                <h2>Como funciona?</h2>
                <p>É fácil entrar no jogo. O próximo passo é seu.</p>
            </div>
            <div class="steps-grid">
                <article class="step-card"><span class="step-number">01</span><span class="step-icon" aria-hidden="true"><i class="bi bi-person-plus-fill"></i></span><h3>Entre</h3><p>Crie sua conta e prepare-se para começar em segundos.</p></article>
                <article class="step-card"><span class="step-number">02</span><span class="step-icon" aria-hidden="true"><i class="bi bi-collection-play-fill"></i></span><h3>Escolha</h3><p>Escolha a matéria que quer explorar hoje.</p></article>
                <article class="step-card"><span class="step-number">03</span><span class="step-icon" aria-hidden="true"><i class="bi bi-controller"></i></span><h3>Jogue</h3><p>Resolva desafios estimulantes e aprenda na prática.</p></article>
                <article class="step-card"><span class="step-number">04</span><span class="step-icon" aria-hidden="true"><i class="bi bi-star-fill"></i></span><h3>Evolua</h3><p>Conquiste estrelas, ganhe XP e suba de nível!</p></article>
            </div>
        </section>

        <section class="flor-section" id="flor">
            <div class="flor-illustration" role="img" aria-label="Flor, uma raposa curiosa e sorridente">
                <span class="flor-sparkle sparkle-left" aria-hidden="true"><i class="bi bi-stars"></i></span>
                <span class="flor-emoji">🦊</span>
                <span class="flor-sparkle sparkle-right" aria-hidden="true"><i class="bi bi-star-fill"></i></span>
            </div>
            <div class="flor-copy">
                <span class="eyebrow">UMA AMIGA EM CADA FASE</span>
                <h2>Conheça a <span class="text-lilac">Flor!</span></h2>
                <p>Uma raposa inteligente e esperta que vai acompanhar você durante toda a sua jornada de aprendizado.</p>
                <div class="speech-bubble"><i class="bi bi-chat-heart-fill" aria-hidden="true"></i> Curiosidade é o começo de toda grande aventura!</div>
            </div>
        </section>

        <section class="final-cta">
            <span class="cta-star" aria-hidden="true"><i class="bi bi-stars"></i></span>
            <span class="eyebrow">SEU PRÓXIMO DESAFIO ESTÁ ESPERANDO</span>
            <h2>Pronto para começar sua aventura?</h2>
            <p>Entre para o FLUX e descubra até onde sua curiosidade pode levar você.</p>
            <a class="button button-primary" href="login.php">Começar agora <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            <span class="cta-decoration" aria-hidden="true"><i class="bi bi-star-fill"></i></span>
        </section>
    </main>

    <footer class="site-footer">
        <a class="brand" href="index.php"><span class="brand-mark">F</span> FLUX<span class="brand-period">.</span></a>
        <span>Aprender, jogar, evoluir. <i class="bi bi-stars" aria-hidden="true"></i></span>
        <span>Feito para curiosos.</span>
    </footer>
</body>
</html>
