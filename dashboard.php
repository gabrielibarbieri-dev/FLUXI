<?php
declare(strict_types=1);

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'use_strict_mode' => true,
]);

if (!isset($_SESSION['usuario_id'], $_SESSION['usuario_nome'])) {
    header('Location: login.php');
    exit;
}
if (($_SESSION['usuario_tipo'] ?? 'aluno') === 'professor') {
    header('Location: professor.php');
    exit;
}
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/classes/Conexao.php';
require_once __DIR__ . '/classes/Jogo.php';
$resumo = null;
$erroBanco = null;
try {
    $resumo = (new Jogo(Conexao::obter()))->obterResumo((int) $_SESSION['usuario_id']);
} catch (PDOException $erro) {
    error_log('FLUX: falha ao carregar dashboard: ' . $erro->getMessage());
    $erroBanco = 'Não foi possível carregar seu progresso. Confira a conexão com o banco de dados.';
} catch (RuntimeException $erro) {
    http_response_code(404);
    $erroBanco = $erro->getMessage();
}
$nome = htmlspecialchars((string) $_SESSION['usuario_nome'], ENT_QUOTES, 'UTF-8');
$tipoUsuario = $_SESSION['usuario_tipo'] ?? 'aluno';
if (!in_array($tipoUsuario, ['aluno', 'professor'], true)) {
    $tipoUsuario = 'aluno';
}
$tipoUsuarioLabel = $tipoUsuario === 'professor' ? 'Professor' : 'Aluno';
$progresso = $resumo === null ? 0 : min(100, (int) round($resumo['fases_concluidas'] / $resumo['fases_totais'] * 100));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sua aventura — FLUX</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Nunito:wght@500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="dashboard-shell">
    <header class="site-header">
        <a class="brand" href="index.php"><span class="brand-mark">F</span> FLUX<span class="brand-period">.</span></a>
        <nav class="dashboard-nav" aria-label="Menu do jogador">
            <a href="perfil.php"><i class="bi bi-person" aria-hidden="true"></i> Meu Perfil</a>
            <a href="progresso.php"><i class="bi bi-bar-chart-line" aria-hidden="true"></i> Meu Progresso</a>
            <form action="logout.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                <button class="text-button" type="submit"><i class="bi bi-box-arrow-right" aria-hidden="true"></i> Sair</button>
            </form>
        </nav>
    </header>
    <main class="hub-main">
        <section class="hub-welcome">
            <div>
                <span class="eyebrow"><span class="eyebrow-dot"></span> SUA JORNADA DE APRENDIZADO</span>
                <h1>Olá, <?= $nome ?>! <i class="bi bi-hand-thumbs-up" aria-hidden="true"></i></h1>
                <p>Acesso como <strong><?= htmlspecialchars($tipoUsuarioLabel, ENT_QUOTES, 'UTF-8') ?></strong>. A Flor já separou algumas aventuras para você. Qual vai ser a próxima?</p>
            </div>
            <div class="hub-fox" role="img" aria-label="Flor, a raposa">🦊</div>
        </section>

        <?php if ($erroBanco !== null): ?>
            <div class="alert alert-error hub-alert" role="alert"><?= htmlspecialchars($erroBanco, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if ($resumo !== null): ?>
            <section class="stats-grid" aria-label="Resumo do jogador">
                <article class="stat-card"><span class="stat-icon stat-purple"><i class="bi bi-lightning-charge-fill"></i></span><span class="stat-label">Nível</span><strong><?= $resumo['nivel'] ?></strong><small><?= htmlspecialchars($resumo['titulo_nivel'], ENT_QUOTES, 'UTF-8') ?> · <?= $resumo['experiencia'] ?> XP</small></article>
                <article class="stat-card"><span class="stat-icon stat-yellow"><i class="bi bi-star-fill"></i></span><span class="stat-label">Estrelas</span><strong><?= $resumo['estrelas'] ?></strong><small>conquistas totais</small></article>
                <article class="stat-card"><span class="stat-icon stat-aqua"><i class="bi bi-controller"></i></span><span class="stat-label">Partidas</span><strong><?= $resumo['partidas'] ?></strong><small>aventuras concluídas</small></article>
                <article class="stat-card"><span class="stat-icon stat-peach"><i class="bi bi-trophy-fill"></i></span><span class="stat-label">Melhor resultado</span><strong><?= $resumo['melhor_resultado'] ?></strong><small>pontos em uma partida</small></article>
            </section>

            <section class="overall-progress" aria-label="Progresso geral">
                <div class="progress-heading"><div><span class="eyebrow">MAPA DA AVENTURA</span><h2>Seu progresso</h2></div><strong><?= $resumo['fases_concluidas'] ?> <span>/ <?= $resumo['fases_totais'] ?> fases</span></strong></div>
                <div class="progress-track"><span style="width: <?= $progresso ?>%"></span></div>
                <p><?= $progresso ?>% do mapa explorado. Continue assim!</p>
                <div class="level-progress-copy"><span><?= htmlspecialchars($resumo['titulo_nivel'], ENT_QUOTES, 'UTF-8') ?> · <?= $resumo['experiencia_no_nivel'] ?> XP<?= $resumo['experiencia_para_proximo_nivel'] === null ? '' : ' / ' . $resumo['experiencia_para_proximo_nivel'] . ' XP' ?></span><strong><?= $resumo['progresso_nivel'] ?>% para o próximo nível</strong></div>
                <div class="progress-track"><span style="width: <?= $resumo['progresso_nivel'] ?>%"></span></div>
            </section>
        <?php endif; ?>

        <div class="section-title-row"><div><span class="eyebrow">ESCOLHA SUA PRÓXIMA MISSÃO</span><h2>Qual aventura vamos jogar?</h2></div><span class="title-sparkle" aria-hidden="true"><i class="bi bi-stars"></i></span></div>
        <section class="subject-grid">
            <article class="subject-card subject-math">
                <div class="subject-card-top"><span class="subject-icon" aria-hidden="true"><i class="bi bi-calculator"></i></span><span class="subject-tag">LÓGICA &amp; NÚMEROS</span></div>
                <div><h3>Matemática</h3><p>Resolva desafios, teste seus conhecimentos e evolua.</p></div>
                <a class="button button-primary" href="jogos/matematica.php">Jogar Matemática <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                <span class="subject-decoration" aria-hidden="true">×</span>
            </article>
            <article class="subject-card subject-portuguese">
                <div class="subject-card-top"><span class="subject-icon" aria-hidden="true"><i class="bi bi-book-half"></i></span><span class="subject-tag">PALAVRAS &amp; HISTÓRIAS</span></div>
                <div><h3>Português</h3><p>Desafie seus conhecimentos sobre palavras e língua portuguesa.</p></div>
                <a class="button button-primary" href="jogos/portugues.php">Jogar Português <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                <span class="subject-decoration" aria-hidden="true">✧</span>
            </article>
        </section>

        <aside class="flor-tip"><span class="tip-fox" aria-hidden="true">🦊</span><p><strong>A Flor lembra:</strong> cada tentativa ensina algo novo. O importante é continuar curioso!</p><span class="tip-star" aria-hidden="true"><i class="bi bi-stars"></i></span></aside>
    </main>
</body>
</html>
