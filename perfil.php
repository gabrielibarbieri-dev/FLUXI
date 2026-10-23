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
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
require_once __DIR__ . '/classes/Conexao.php';
require_once __DIR__ . '/classes/Jogo.php';
require_once __DIR__ . '/classes/Usuario.php';
require_once __DIR__ . '/classes/Aluno.php';
require_once __DIR__ . '/classes/Professor.php';
$erroBanco = null;
$resumo = null;
$dataCadastro = '';
$aluno = null;
$professoresVinculados = [];
$professoresDisponiveis = [];
$mensagemProfessor = $_SESSION['mensagem_aluno_professor'] ?? null;
unset($_SESSION['mensagem_aluno_professor']);
try {
    $conexao = Conexao::obter();
    $jogo = new Jogo($conexao);
    $resumo = $jogo->obterResumo((int) $_SESSION['usuario_id']);
    $conta = (new Usuario($conexao))->buscarPorId((int) $_SESSION['usuario_id']);
    if ($conta === null) {
        $erroBanco = 'Não foi possível localizar o perfil da sua conta.';
        $resumo = null;
    } else {
        $tipoUsuario = $_SESSION['usuario_tipo'] ?? 'aluno';
        if (!in_array($tipoUsuario, ['aluno', 'professor'], true)) {
            $tipoUsuario = 'aluno';
        }

        $aluno = $tipoUsuario === 'professor'
            ? new Professor($conta['nome'], $conta['email'], $resumo['nivel'], $resumo['estrelas'])
            : new Aluno($conta['nome'], $conta['email'], $resumo['nivel'], $resumo['estrelas']);
        $dataCadastro = date('d/m/Y', strtotime($conta['data_cadastro']));
        if ($tipoUsuario === 'aluno') {
            $repositorio = new Usuario($conexao);
            $professoresVinculados = $repositorio->listarProfessoresDoAluno((int) $_SESSION['usuario_id']);
            if ($professoresVinculados === []) {
                $professoresDisponiveis = $repositorio->listarProfessoresComVagas();
            }
        }
    }
} catch (PDOException $erro) {
    error_log('FLUX: falha ao carregar perfil: ' . $erro->getMessage());
    $erroBanco = 'Não foi possível carregar o perfil. Confira a conexão com o banco de dados.';
} catch (RuntimeException $erro) {
    http_response_code(404);
    $erroBanco = $erro->getMessage();
}
$escapar = static fn (string $valor): string => htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
$progressoGeral = $resumo === null ? 0 : min(100, (int) round($resumo['fases_concluidas'] / $resumo['fases_totais'] * 100));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Meu Perfil — FLUX</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Nunito:wght@500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/style.css">
    <script src="js/acessibilidade.js" defer></script>
    <script src="js/perfil.js" defer></script>
</head>
<body>
    <header class="site-header">
        <a class="brand" href="index.php"><span class="brand-mark">F</span> FLUX<span class="brand-period">.</span></a>
        <nav class="dashboard-nav">
            <a href="dashboard.php"><i class="bi bi-speedometer2" aria-hidden="true"></i> Início</a>
            <a href="progresso.php"><i class="bi bi-bar-chart-line" aria-hidden="true"></i> Meu Progresso</a>
            <form action="logout.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= $escapar($_SESSION['csrf_token'] ?? '') ?>">
                <button class="text-button" type="submit"><i class="bi bi-box-arrow-right" aria-hidden="true"></i> Sair</button>
            </form>
        </nav>
    </header>
    <main class="inner-main">
        <a class="back-link" href="dashboard.php"><i class="bi bi-arrow-left" aria-hidden="true"></i> Voltar ao painel</a>
        <?php if ($erroBanco !== null): ?><div class="alert alert-error" role="alert"><?= $escapar($erroBanco) ?></div><?php endif; ?>
        <?php if ($resumo !== null && $aluno !== null): ?>
            <section class="profile-layout">
                <article class="profile-card">
                    <div class="profile-avatar" role="img" aria-label="Flor, a raposa">🦊</div>
                    <span class="eyebrow"><?= $escapar($aluno->getTipo()) ?></span>
                    <h1 id="profile-name"><?= $escapar($aluno->getNome()) ?></h1>
                    <p class="profile-email"><?= $escapar($aluno->getEmail()) ?></p>
                    <div class="profile-badges">
                        <span><i class="bi bi-lightning-charge-fill" aria-hidden="true"></i> Nível <?= $resumo['nivel'] ?> · <?= $escapar($resumo['titulo_nivel']) ?></span>
                        <span><i class="bi bi-star-fill" aria-hidden="true"></i> <?= $resumo['estrelas'] ?> estrelas</span>
                    </div>
                    <p class="profile-meta">Aventureiro(a) desde <?= $escapar($dataCadastro) ?></p>
                    <div class="flor-profile-message">
                        <span class="tip-fox" role="img" aria-label="Flor">🦊</span>
                        <p><?= $resumo['nivel'] >= 5 ? 'Você chegou ao nível Mestre! Sua dedicação é incrível. Continue explorando!' : ($resumo['vitorias'] > 0 ? 'Olha só o quanto você já conquistou! Continue curioso e vamos buscar a próxima fase.' : 'Que bom ter você na aventura! Cada pergunta é uma chance de descobrir algo novo.') ?></p>
                    </div>
                    <a class="button button-primary" href="progresso.php">Ver meu progresso <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                </article>
                <div class="profile-side">
                    <section class="profile-detail-card">
                        <span class="eyebrow">SUA JORNADA</span>
                        <h2>Resumo do explorador</h2>
                        <div class="profile-stats profile-stats-four">
                            <div><strong><?= $resumo['partidas'] ?></strong><span>partidas</span></div>
                            <div><strong><?= $resumo['vitorias'] ?></strong><span>vitórias</span></div>
                            <div><strong><?= $resumo['melhor_resultado'] ?></strong><span>melhor pontuação</span></div>
                            <div><strong><?= $resumo['fases_concluidas'] ?>/<?= $resumo['fases_totais'] ?></strong><span>fases</span></div>
                        </div>
                        <div class="level-progress-copy"><span>Nível <?= $resumo['nivel'] ?> · <?= $escapar($resumo['titulo_nivel']) ?></span><strong><?= $resumo['progresso_nivel'] ?>%</strong></div>
                        <div class="progress-track"><span style="width: <?= $resumo['progresso_nivel'] ?>%"></span></div>
                        <p class="level-progress-caption"><?= $resumo['proximo_nivel'] === null ? 'Você alcançou o nível máximo!' : $resumo['experiencia_no_nivel'] . ' / ' . $resumo['experiencia_para_proximo_nivel'] . ' XP para ' . $escapar($resumo['proximo_nivel']) ?></p>
                        <div class="progress-track-label"><span>Progresso geral das fases</span><strong><?= $progressoGeral ?>%</strong></div>
                        <div class="progress-track"><span style="width: <?= $progressoGeral ?>%"></span></div>
                    </section>
                    <section class="profile-detail-card profile-edit-card">
                        <span class="eyebrow">SEUS DADOS</span><h2>Editar nome</h2>
                        <form id="profile-edit" data-endpoint="api/usuario.php" data-csrf="<?= $escapar($_SESSION['csrf_token'] ?? '') ?>">
                            <label for="profile-name-input">Nome de exibição</label>
                            <div class="profile-edit-row">
                                <input id="profile-name-input" name="nome" type="text" maxlength="120" minlength="1" value="<?= $escapar($aluno->getNome()) ?>" required>
                                <button class="button button-primary" type="submit">Salvar</button>
                            </div>
                            <p class="profile-edit-message" id="profile-edit-message" role="status" hidden></p>
                        </form>
                    </section>
                    <?php if (($_SESSION['usuario_tipo'] ?? 'aluno') === 'aluno'): ?>
                        <section class="profile-detail-card student-teachers-card" aria-labelledby="student-teachers-title">
                            <span class="eyebrow">SEU ACOMPANHAMENTO</span>
                            <h2 id="student-teachers-title">Meus professores</h2>
                            <?php if (is_array($mensagemProfessor)): ?>
                                <div class="alert alert-<?= $escapar($mensagemProfessor['tipo']) ?>" role="status"><?= $escapar($mensagemProfessor['texto']) ?></div>
                            <?php endif; ?>
                            <?php if ($professoresVinculados !== []): ?>
                                <ul class="student-teacher-list">
                                    <?php foreach ($professoresVinculados as $professor): ?>
                                        <li><i class="bi bi-person-workspace" aria-hidden="true"></i> <?= $escapar($professor['nome']) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <form class="student-teacher-form student-unlink-form" action="api/aluno-professor.php" method="post">
                                    <input type="hidden" name="csrf_token" value="<?= $escapar($_SESSION['csrf_token']) ?>">
                                    <input type="hidden" name="acao" value="desvincular">
                                    <button class="button button-small teacher-remove-button" type="submit">
                                        <i class="bi bi-person-dash" aria-hidden="true"></i> Desvincular professor
                                    </button>
                                    <p class="form-hint">Depois de se desvincular, você poderá escolher outro professor. Seu progresso será mantido.</p>
                                </form>
                            <?php else: ?>
                                <p>Você ainda não está vinculado a um professor.</p>
                            <?php endif; ?>
                            <?php if ($professoresVinculados !== []): ?>
                                <p class="form-hint">Cada aluno pode estar vinculado a apenas um professor por vez.</p>
                            <?php elseif ($professoresDisponiveis !== []): ?>
                                <form class="student-teacher-form" action="api/aluno-professor.php" method="post">
                                    <input type="hidden" name="csrf_token" value="<?= $escapar($_SESSION['csrf_token']) ?>">
                                    <label for="student-professor-id">Escolha um professor</label>
                                    <select id="student-professor-id" name="professor_id" required>
                                        <option value="">Selecione um professor</option>
                                        <?php foreach ($professoresDisponiveis as $professor): ?>
                                            <option value="<?= (int) $professor['id'] ?>"><?= $escapar($professor['nome']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="button button-primary" type="submit"><i class="bi bi-person-plus" aria-hidden="true"></i> Vincular</button>
                                    <p class="form-hint">A escolha é única. Cada professor pode acompanhar até 10 alunos.</p>
                                </form>
                            <?php else: ?>
                                <p class="form-hint">Não há professores com vagas disponíveis no momento.</p>
                            <?php endif; ?>
                        </section>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
