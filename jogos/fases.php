<?php
declare(strict_types=1);

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'use_strict_mode' => true,
]);
if (!isset($_SESSION['usuario_id'], $_SESSION['usuario_nome'])) {
    header('Location: ../login.php');
    exit;
}
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
require_once __DIR__ . '/../classes/Conexao.php';
require_once __DIR__ . '/../classes/Jogo.php';
if (!defined('FLUX_MATERIA') || !isset(Jogo::MATERIAS[FLUX_MATERIA])) {
    http_response_code(404);
    exit('Matéria não encontrada.');
}

$materia = FLUX_MATERIA;
$nomeMateria = Jogo::MATERIAS[$materia];
$faseSolicitada = filter_input(INPUT_GET, 'fase', FILTER_VALIDATE_INT);
$faseSolicitada = $faseSolicitada === false ? null : $faseSolicitada;
$faseAtual = null;
$fases = [];
$resumo = null;
$erro = null;
try {
    $jogo = new Jogo(Conexao::obter());
    $fases = $jogo->listarFases((int) $_SESSION['usuario_id'], $materia);
    $resumo = $jogo->obterResumo((int) $_SESSION['usuario_id']);
    if ($faseSolicitada !== null) {
        if (!isset(Jogo::FASES[$faseSolicitada])) {
            http_response_code(404);
            $erro = 'Essa fase não existe.';
        } elseif (empty($fases[$faseSolicitada - 1]['desbloqueada'])) {
            http_response_code(403);
            $erro = 'Conclua a fase anterior para desbloquear esta aventura.';
        } else {
            $faseAtual = $faseSolicitada;
        }
    }
} catch (PDOException $falha) {
    error_log('FLUX: falha ao carregar fases: ' . $falha->getMessage());
    $erro = 'Não foi possível carregar as fases. Confira a conexão com o banco de dados.';
} catch (RuntimeException $falha) {
    http_response_code(404);
    $erro = $falha->getMessage();
}
$escapar = static fn (string $valor): string => htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
$fasesTexto = [
    'matematica' => [
        1 => 'Operações básicas',
        2 => 'Cálculos e problemas',
        3 => 'Frações, potências e lógica',
        4 => 'Desafio matemático',
    ],
    'portugues' => [
        1 => 'Sons, sílabas e palavras',
        2 => 'Ortografia e classes gramaticais',
        3 => 'Interpretação e formação de palavras',
        4 => 'Desafio da língua',
    ],
];
$dadosFase = $faseAtual === null ? null : Jogo::FASES[$faseAtual];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $faseAtual === null ? 'Fases de ' . $escapar($nomeMateria) : 'Fase ' . $faseAtual . ' — ' . $escapar($nomeMateria) ?> · FLUX</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Nunito:wght@500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <?php if ($faseAtual !== null && $erro === null): ?><script src="../js/jogo.js" defer></script><?php endif; ?>
</head>
<body>
    <header class="site-header">
        <a class="brand" href="../index.php"><span class="brand-mark">F</span> FLUX<span class="brand-period">.</span></a>
        <nav class="dashboard-nav">
            <a href="../dashboard.php"><i class="bi bi-speedometer2" aria-hidden="true"></i> Início</a>
            <a href="../perfil.php"><i class="bi bi-person" aria-hidden="true"></i> Meu Perfil</a>
            <a href="../progresso.php"><i class="bi bi-bar-chart-line" aria-hidden="true"></i> Meu Progresso</a>
            <form action="../logout.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= $escapar($_SESSION['csrf_token']) ?>">
                <button class="text-button" type="submit"><i class="bi bi-box-arrow-right" aria-hidden="true"></i> Sair</button>
            </form>
        </nav>
    </header>
    <main class="<?= $faseAtual === null ? 'inner-main phase-page' : 'inner-main play-page' ?>"
        <?php if ($faseAtual !== null && $erro === null): ?>
            data-materia="<?= $escapar($materia) ?>"
            data-fase="<?= $faseAtual ?>"
            data-csrf="<?= $escapar($_SESSION['csrf_token']) ?>"
            data-api-perguntas="../api/perguntas.php"
            data-api-partida="../api/partida.php"
        <?php endif; ?>>
        <a class="back-link" href="<?= $faseAtual === null ? '../dashboard.php' : ($materia === 'matematica' ? 'matematica.php' : 'portugues.php') ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> <?= $faseAtual === null ? 'Voltar ao painel' : 'Voltar às fases' ?></a>
        <?php if ($erro !== null): ?>
            <div class="alert alert-error phase-error" role="alert"><?= $escapar($erro) ?> <a href="<?= $materia === 'matematica' ? 'matematica.php' : 'portugues.php' ?>">Ver fases</a></div>
        <?php elseif ($faseAtual === null): ?>
            <section class="phase-intro">
                <span class="eyebrow"><?= $materia === 'matematica' ? 'LÓGICA, ESTRATÉGIA E DESCOBERTAS' : 'PALAVRAS, HISTÓRIAS E DESCOBERTAS' ?></span>
                <h1>Fases de <span class="text-lilac"><?= $escapar($nomeMateria) ?></span></h1>
                <p>Complete uma fase para abrir o próximo caminho. A Flor torce por você!</p>
                <?php if ($resumo !== null): ?>
                    <div class="phase-stats">
                        <span><i class="bi bi-lightning-charge-fill" aria-hidden="true"></i> Nível <?= $resumo['nivel'] ?></span>
                        <span><i class="bi bi-star-fill text-warning" aria-hidden="true"></i> <?= $resumo['estrelas'] ?> estrelas</span>
                    </div>
                <?php endif; ?>
            </section>
            <?php if ($erro === null): ?>
                <section class="phase-map" aria-label="Seleção de fases">
                    <?php foreach ($fases as $fase): ?>
                        <article class="phase-card <?= $fase['desbloqueada'] ? 'phase-open' : 'phase-locked' ?> <?= $fase['concluida'] ? 'phase-done' : '' ?>">
                            <div class="phase-card-heading">
                                <span class="phase-medal">
                                    <?php if ($fase['concluida']): ?>
                                        <i class="bi bi-trophy-fill text-warning"></i>
                                    <?php elseif ($fase['desbloqueada']): ?>
                                        <i class="bi bi-stars text-primary"></i>
                                    <?php else: ?>
                                        <i class="bi bi-lock-fill"></i>
                                    <?php endif; ?>
                                </span>
                                <span class="phase-number">FASE 0<?= $fase['numero'] ?></span>
                                <span class="phase-stars">
                                    <?php if ($fase['concluida']): ?>
                                        <i class="bi bi-star-fill text-warning"></i><i class="bi bi-star-fill text-warning"></i><i class="bi bi-star-fill text-warning"></i>
                                    <?php else: ?>
                                        <i class="bi bi-star"></i><i class="bi bi-star"></i><i class="bi bi-star"></i>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <span class="phase-difficulty difficulty-<?= $fase['numero'] ?>">NÍVEL <?= $escapar($fase['nome']) ?></span>
                            <h2><?= $fase['numero'] === 1 ? 'Primeiros passos' : ($fase['numero'] === 2 ? 'Novos caminhos' : ($fase['numero'] === 3 ? 'Mente afiada' : 'O grande desafio')) ?></h2>
                            <p><?= $escapar($fasesTexto[$materia][$fase['numero']]) ?></p>
                            <div class="phase-card-bottom">
                                <?php if ($fase['desbloqueada']): ?>
                                    <span><?= $fase['concluida'] ? 'Melhor: ' . $fase['melhor_pontuacao'] . ' pts' : '5 perguntas · múltipla escolha' ?></span>
                                    <a class="phase-play" href="?fase=<?= $fase['numero'] ?>"><?= $fase['concluida'] ? 'Jogar de novo' : 'Jogar fase' ?> <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                                <?php else: ?>
                                    <span>Conclua a fase anterior para desbloquear</span>
                                    <span class="phase-locked-label"><i class="bi bi-lock-fill" aria-hidden="true"></i> Bloqueada</span>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>
        <?php else: ?>
            <section class="play-heading">
                <div><span class="eyebrow">FASE 0<?= $faseAtual ?> · <?= $escapar($dadosFase['nome']) ?></span><h1><?= $escapar($nomeMateria) ?> <span class="text-lilac">· <?= $escapar($dadosFase['nome']) ?></span></h1></div>
                <div class="play-total-stars"><i class="bi bi-star-fill text-warning" aria-hidden="true"></i> <strong><?= $resumo['estrelas'] ?? 0 ?></strong><span>estrelas</span></div>
            </section>
            <section class="game-shell" id="game-shell" aria-live="polite">
                <div class="game-topline"><span id="question-counter">Preparando sua aventura…</span><span class="score-display"><i class="bi bi-stars"></i> <strong id="live-score">0</strong> pontos</span></div>
                <div class="game-progress-track"><span id="question-progress"></span></div>
                <div class="game-content" id="game-content">
                    <div class="game-fox" role="img" aria-label="Flor, a raposa mascote">🦊</div>
                    <div class="flor-dialog" id="flor-message">A Flor está preparando as perguntas…</div>
                    <div class="question-timer" id="question-timer" role="timer" aria-label="Tempo restante para responder">
                        <div class="question-timer-heading">
                            <span><i class="bi bi-clock" aria-hidden="true"></i> Tempo para responder</span>
                            <strong id="question-timer-value">00:30</strong>
                        </div>
                        <div class="question-timer-track" role="progressbar" aria-label="Tempo restante" aria-valuemin="0" aria-valuemax="30" aria-valuenow="30">
                            <span id="question-timer-progress"></span>
                        </div>
                    </div>
                    <h2 id="question-text">Carregando o desafio</h2>
                    <div class="answer-list" id="answer-list"></div>
                    <div class="answer-feedback" id="answer-feedback" role="status" hidden></div>
                    <button class="button button-primary next-question" id="next-question" type="button" hidden>Próxima pergunta <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
                </div>
                <div class="game-error" id="game-error" role="alert" hidden></div>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
