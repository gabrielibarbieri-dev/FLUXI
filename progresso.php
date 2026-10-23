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
require_once __DIR__ . '/classes/Conexao.php';
require_once __DIR__ . '/classes/Jogo.php';
$resumo = null;
$fases = [];
$partidas = [];
$erroBanco = null;
try {
    $jogo = new Jogo(Conexao::obter());
    $usuarioId = (int) $_SESSION['usuario_id'];
    $resumo = $jogo->obterResumo($usuarioId);
    foreach (Jogo::MATERIAS as $chave => $rotulo) {
        $fases[$chave] = $jogo->listarFases($usuarioId, $chave);
    }
    $partidas = $jogo->listarPartidas($usuarioId);
} catch (PDOException $erro) {
    error_log('FLUX: falha ao carregar progresso: ' . $erro->getMessage());
    $erroBanco = 'Não foi possível carregar o progresso. Confira a conexão com o banco de dados.';
} catch (RuntimeException $erro) {
    http_response_code(404);
    $erroBanco = $erro->getMessage();
}
$escapar = static fn (string $valor): string => htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Meu Progresso — FLUX</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Nunito:wght@500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/style.css">
    <script src="js/acessibilidade.js" defer></script>
</head>
<body>
    <header class="site-header">
        <a class="brand" href="index.php"><span class="brand-mark">F</span> FLUX<span class="brand-period">.</span></a>
        <nav class="dashboard-nav">
            <a href="dashboard.php"><i class="bi bi-speedometer2" aria-hidden="true"></i> Início</a>
            <a href="perfil.php"><i class="bi bi-person" aria-hidden="true"></i> Meu Perfil</a>
            <form action="logout.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= $escapar($_SESSION['csrf_token'] ?? '') ?>">
                <button class="text-button" type="submit"><i class="bi bi-box-arrow-right" aria-hidden="true"></i> Sair</button>
            </form>
        </nav>
    </header>
    <main class="inner-main progress-page">
        <a class="back-link" href="dashboard.php"><i class="bi bi-arrow-left" aria-hidden="true"></i> Voltar ao painel</a>
        <div class="page-heading"><span class="eyebrow">CADA DESAFIO CONTA</span><h1>Meu progresso</h1><p>Olha só tudo o que você já conquistou, <?= $escapar((string) $_SESSION['usuario_nome']) ?>!</p></div>
        <?php if ($erroBanco !== null): ?><div class="alert alert-error" role="alert"><?= $escapar($erroBanco) ?></div><?php endif; ?>
        <?php if ($resumo !== null): ?>
            <section class="progress-summary">
                <div><span><i class="bi bi-lightning-charge-fill"></i></span><strong><?= $resumo['nivel'] ?></strong><small><?= $escapar($resumo['titulo_nivel']) ?></small></div>
                <div><span><i class="bi bi-star-fill"></i></span><strong><?= $resumo['estrelas'] ?></strong><small>estrelas totais</small></div>
                <div><span><i class="bi bi-controller"></i></span><strong><?= $resumo['partidas'] ?></strong><small>partidas</small></div>
                <div><span><i class="bi bi-trophy-fill"></i></span><strong><?= $resumo['vitorias'] ?></strong><small>vitórias · 3+ acertos</small></div>
                <div><span><i class="bi bi-check-lg"></i></span><strong><?= $resumo['acertos'] ?></strong><small>acertos totais</small></div>
                <div><span><i class="bi bi-arrow-clockwise"></i></span><strong><?= $resumo['erros'] ?></strong><small>erros que ensinam</small></div>
                <div><span><i class="bi bi-stars"></i></span><strong><?= $resumo['experiencia'] ?></strong><small>XP conquistado</small></div>
                <div><span><i class="bi bi-bullseye"></i></span><strong><?= $resumo['desempenho'] ?>%</strong><small>desempenho geral</small></div>
            </section>
            <section class="level-progress-card">
                <div class="level-progress-top">
                    <div class="level-emblem"><i class="bi bi-lightning-charge-fill"></i></div>
                    <div class="level-progress-title"><span class="eyebrow">SUA PRÓXIMA CONQUISTA</span><h2>Nível <?= $resumo['nivel'] ?> · <?= $escapar($resumo['titulo_nivel']) ?></h2></div>
                    <strong><?= $resumo['progresso_nivel'] ?>%</strong>
                </div>
                <div class="progress-track"><span style="width: <?= $resumo['progresso_nivel'] ?>%"></span></div>
                <p><?= $resumo['proximo_nivel'] === null ? 'Você chegou ao nível máximo: Mestre! Que conquista incrível.' : $resumo['experiencia_no_nivel'] . ' / ' . $resumo['experiencia_para_proximo_nivel'] . ' XP neste nível · faltam ' . max(0, $resumo['experiencia_para_proximo_nivel'] - $resumo['experiencia_no_nivel']) . ' XP para ' . $escapar($resumo['proximo_nivel']) ?></p>
                <div class="level-ladder" aria-label="Níveis FLUX">
                    <?php foreach (Jogo::NIVEIS as $numeroNivel => $dadosNivel): ?>
                        <span class="<?= $resumo['nivel'] === $numeroNivel ? 'level-current' : ($resumo['nivel'] > $numeroNivel ? 'level-reached' : '') ?>">
                            <strong><?= $resumo['nivel'] > $numeroNivel ? '<i class="bi bi-check"></i>' : $numeroNivel ?></strong>
                            <?= $escapar($dadosNivel['nome']) ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </section>
            <section class="subject-performance">
                <?php foreach (Jogo::MATERIAS as $chave => $rotulo): ?>
                    <?php $materiaResumo = $resumo[$chave]; ?>
                    <article class="performance-card <?= $chave === 'matematica' ? 'performance-math' : 'performance-portuguese' ?>">
                        <div class="performance-heading">
                            <span class="performance-icon"><?= $chave === 'matematica' ? '<i class="bi bi-calculator"></i>' : '<i class="bi bi-book-half"></i>' ?></span>
                            <div><h2><?= $escapar($rotulo) ?></h2><span><?= $materiaResumo['acertos'] ?> acertos · <?= $materiaResumo['erros'] ?> erros</span></div>
                            <strong><?= $materiaResumo['desempenho'] ?>%</strong>
                        </div>
                        <div class="progress-track"><span style="width: <?= $materiaResumo['desempenho'] ?>%"></span></div>
                        <p><?= $materiaResumo['questoes'] === 0 ? 'Jogue uma fase para começar a acompanhar seu desempenho.' : $materiaResumo['acertos'] . ' acertos em ' . $materiaResumo['questoes'] . ' perguntas respondidas.' ?></p>
                        <a href="jogos/<?= $chave ?>.php">Continuar aventura <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                    </article>
                <?php endforeach; ?>
            </section>
            <aside class="flor-progress-tip"><span role="img" aria-label="Flor, a raposa">🦊</span><p><strong>A Flor acredita em você!</strong> Cada resposta, certa ou errada, ajuda você a aprender. Continue praticando para conquistar XP e chegar ao próximo nível.</p></aside>
            <?php foreach (Jogo::MATERIAS as $chave => $rotulo): ?>
                <section class="subject-progress">
                    <div class="section-title-row"><h2><?= $escapar($rotulo) ?></h2><span><?= count(array_filter($fases[$chave], static fn (array $fase): bool => $fase['concluida'])) ?>/4 fases</span></div>
                    <div class="phase-progress-list">
                        <?php foreach ($fases[$chave] as $fase): ?>
                            <div class="phase-progress-row">
                                <span class="phase-mini-number"><?= $fase['concluida'] ? '<i class="bi bi-check-lg"></i>' : $fase['numero'] ?></span>
                                <div><strong>Fase <?= $fase['numero'] ?> · <?= $escapar($fase['nome']) ?></strong><small><?= $fase['concluida'] ? $fase['partidas'] . ' partidas · melhor resultado ' . $fase['melhor_pontuacao'] . ' pts' : ($fase['desbloqueada'] ? 'Pronta para jogar' : 'Conclua a fase anterior para desbloquear') ?></small></div>
                                <span class="phase-progress-status"><?= $fase['concluida'] ? '<i class="bi bi-star-fill text-warning"></i> Concluída' : ($fase['desbloqueada'] ? 'Aberta' : '<i class="bi bi-lock-fill"></i> Bloqueada') ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
            <section class="recent-games">
                <div class="section-title-row"><h2>Partidas recentes</h2></div>
                <?php if ($partidas === []): ?><p class="empty-state">Sua primeira aventura está esperando. Escolha uma matéria no painel!</p>
                <?php else: ?>
                    <div class="table-wrap"><table><thead><tr><th>Matéria</th><th>Fase</th><th>Resultado</th><th>Estrelas</th><th>Data</th></tr></thead><tbody>
                    <?php foreach ($partidas as $partida): ?>
                        <tr><td><?= $escapar(Jogo::MATERIAS[$partida['materia']] ?? $partida['materia']) ?></td><td><?= (int) $partida['fase'] ?> · <?= $escapar(Jogo::FASES[(int) $partida['fase']]['nome']) ?></td><td><?= (int) $partida['pontuacao'] ?> pts · <?= (int) $partida['acertos'] ?>/<?= (int) $partida['total_questoes'] ?></td><td><i class="bi bi-star-fill text-warning"></i> <?= (int) $partida['estrelas_ganhas'] ?></td><td><?= date('d/m/Y', strtotime($partida['criada_em'])) ?></td></tr>
                    <?php endforeach; ?>
                    </tbody></table></div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
