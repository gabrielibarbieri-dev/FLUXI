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

if (($_SESSION['usuario_tipo'] ?? 'aluno') !== 'professor') {
    header('Location: dashboard.php');
    exit;
}
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/classes/Conexao.php';
require_once __DIR__ . '/classes/Usuario.php';

$alunos = [];
$totalVinculados = 0;
$conta = null;
$erroBanco = null;
$abaInicial = $_SESSION['professor_aba'] ?? 'visao-geral';
unset($_SESSION['professor_aba']);
if (!in_array($abaInicial, ['visao-geral', 'alunos', 'perfil'], true)) {
    $abaInicial = 'visao-geral';
}
$mensagem = $_SESSION['mensagem_professor'] ?? null;
unset($_SESSION['mensagem_professor']);
try {
    $repositorio = new Usuario(Conexao::obter());
    $conta = $repositorio->buscarPorId((int) $_SESSION['usuario_id']);
    if ($conta === null) {
        throw new RuntimeException('Não foi possível localizar o perfil da sua conta.');
    }
    $alunos = $repositorio->listarAlunosComRendimento((int) $_SESSION['usuario_id']);
    $totalVinculados = $repositorio->contarAlunosVinculados((int) $_SESSION['usuario_id']);
} catch (PDOException $erro) {
    error_log('FLUX: falha ao carregar rendimento dos alunos: ' . $erro->getMessage());
    $erroBanco = 'Não foi possível carregar o rendimento dos alunos. Confira a conexão com o banco de dados.';
} catch (RuntimeException $erro) {
    http_response_code(404);
    $erroBanco = $erro->getMessage();
}

$totalAlunos = count($alunos);
$totalPartidas = 0;
$totalAcertos = 0;
$totalQuestoes = 0;
foreach ($alunos as $aluno) {
    $totalPartidas += (int) $aluno['partidas'];
    $totalAcertos += (int) $aluno['acertos'];
    $totalQuestoes += (int) $aluno['questoes'];
}
$desempenhoGeral = $totalQuestoes === 0 ? 0 : (int) round($totalAcertos / $totalQuestoes * 100);
$escapar = static fn (string $valor): string => htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rendimento dos alunos — FLUX</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Nunito:wght@500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/style.css">
    <script src="js/acessibilidade.js" defer></script>
</head>
<body>
    <header class="site-header teacher-header">
        <a class="brand" href="index.php"><span class="brand-mark">F</span> FLUX<span class="brand-period">.</span></a>
        <?php if ($erroBanco === null): ?>
            <nav class="teacher-tabs teacher-tabs-header" role="tablist" aria-label="Navegação do professor">
                <button id="tab-visao-geral" type="button" role="tab" aria-controls="painel-visao-geral" aria-selected="<?= $abaInicial === 'visao-geral' ? 'true' : 'false' ?>" tabindex="<?= $abaInicial === 'visao-geral' ? '0' : '-1' ?>">
                    <i class="bi bi-grid-1x2" aria-hidden="true"></i> Visão geral
                </button>
                <button id="tab-alunos" type="button" role="tab" aria-controls="painel-alunos" aria-selected="<?= $abaInicial === 'alunos' ? 'true' : 'false' ?>" tabindex="<?= $abaInicial === 'alunos' ? '0' : '-1' ?>">
                    <i class="bi bi-people" aria-hidden="true"></i> Rendimento dos alunos
                </button>
                <button id="tab-perfil" type="button" role="tab" aria-controls="painel-perfil" aria-selected="<?= $abaInicial === 'perfil' ? 'true' : 'false' ?>" tabindex="<?= $abaInicial === 'perfil' ? '0' : '-1' ?>">
                    <i class="bi bi-person" aria-hidden="true"></i> Meu Perfil
                </button>
            </nav>
        <?php endif; ?>
        <form class="teacher-logout" action="logout.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= $escapar((string) ($_SESSION['csrf_token'] ?? '')) ?>">
            <button class="text-button" type="submit"><i class="bi bi-box-arrow-right" aria-hidden="true"></i> Sair</button>
        </form>
    </header>
    <main class="inner-main teacher-page">
        <?php if ($erroBanco !== null): ?>
            <div class="alert alert-error" role="alert"><?= $escapar($erroBanco) ?></div>
        <?php else: ?>
            <section id="painel-visao-geral" class="teacher-tab-panel<?= $abaInicial === 'visao-geral' ? ' is-active' : '' ?>" role="tabpanel" aria-labelledby="tab-visao-geral" <?= $abaInicial !== 'visao-geral' ? 'hidden' : '' ?>>
                <div class="page-heading">
                    <span class="eyebrow"><span class="eyebrow-dot"></span> PAINEL DO PROFESSOR</span>
                    <h1>Visão geral</h1>
                    <p>Acompanhe um resumo do rendimento dos alunos cadastrados.</p>
                </div>
                <section class="stats-grid teacher-summary" aria-label="Resumo geral dos alunos">
                    <article class="stat-card">
                        <span class="stat-icon stat-purple"><i class="bi bi-people-fill" aria-hidden="true"></i></span>
                        <span class="stat-label">Alunos</span>
                        <strong><?= $totalAlunos ?></strong>
                        <small>contas cadastradas</small>
                    </article>
                    <article class="stat-card">
                        <span class="stat-icon stat-aqua"><i class="bi bi-controller" aria-hidden="true"></i></span>
                        <span class="stat-label">Partidas</span>
                        <strong><?= $totalPartidas ?></strong>
                        <small>jogadas pelos alunos</small>
                    </article>
                    <article class="stat-card">
                        <span class="stat-icon stat-yellow"><i class="bi bi-check2-circle" aria-hidden="true"></i></span>
                        <span class="stat-label">Acertos</span>
                        <strong><?= $totalAcertos ?></strong>
                        <small>de <?= $totalQuestoes ?> respostas</small>
                    </article>
                    <article class="stat-card">
                        <span class="stat-icon stat-peach"><i class="bi bi-bullseye" aria-hidden="true"></i></span>
                        <span class="stat-label">Desempenho geral</span>
                        <strong><?= $desempenhoGeral ?>%</strong>
                        <small>acertos sobre respostas</small>
                    </article>
                </section>
            </section>

            <section id="painel-alunos" class="teacher-tab-panel<?= $abaInicial === 'alunos' ? ' is-active' : '' ?>" role="tabpanel" aria-labelledby="tab-alunos" <?= $abaInicial !== 'alunos' ? 'hidden' : '' ?>>
                <div class="page-heading">
                    <span class="eyebrow"><span class="eyebrow-dot"></span> PAINEL DO PROFESSOR</span>
                    <h1>Rendimento dos alunos</h1>
                    <p>Compare partidas, acertos e desempenho de cada aluno por matéria.</p>
                </div>
                <section class="teacher-roster" aria-labelledby="teacher-roster-title">
                    <?php if (is_array($mensagem)): ?>
                        <div class="alert alert-<?= $escapar($mensagem['tipo']) ?>" role="status"><?= $escapar($mensagem['texto']) ?></div>
                    <?php endif; ?>
                    <div class="section-title-row">
                        <div>
                            <span class="eyebrow">VISÃO DA TURMA</span>
                            <h2 id="teacher-roster-title">Gerenciar alunos</h2>
                        </div>
                        <span><?= $totalVinculados ?>/10 alunos</span>
                    </div>
                    <form class="teacher-add-student" action="api/alunos-professor.php" method="post">
                        <input type="hidden" name="csrf_token" value="<?= $escapar((string) ($_SESSION['csrf_token'] ?? '')) ?>">
                        <input type="hidden" name="acao" value="vincular">
                        <div class="form-field">
                            <label for="email-aluno">Vincular aluno pelo e-mail</label>
                            <input id="email-aluno" name="email" type="email" maxlength="190" autocomplete="email" placeholder="aluno@exemplo.com" required <?= $totalVinculados >= 10 ? 'disabled' : '' ?>>
                        </div>
                        <button class="button button-primary" type="submit" <?= $totalVinculados >= 10 ? 'disabled' : '' ?>>
                            <i class="bi bi-person-plus" aria-hidden="true"></i> Vincular aluno
                        </button>
                        <?php if ($totalVinculados >= 10): ?>
                            <p class="form-hint teacher-limit-message">Limite de 10 alunos atingido. Desvincule um aluno para liberar uma vaga.</p>
                        <?php else: ?>
                            <p class="form-hint">Vagas disponíveis: <?= 10 - $totalVinculados ?>. O aluno precisa ter uma conta FLUX.</p>
                        <?php endif; ?>
                    </form>
                    <?php if ($alunos === []): ?>
                        <div class="empty-state">Ainda não há alunos cadastrados para acompanhar.</div>
                    <?php else: ?>
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th scope="col">Aluno</th>
                                        <th scope="col">Partidas</th>
                                        <th scope="col">Matemática</th>
                                        <th scope="col">Português</th>
                                        <th scope="col">Geral</th>
                                        <th scope="col">Fases concluídas</th>
                                        <th scope="col">Última partida</th>
                                        <th scope="col">Ação</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($alunos as $aluno): ?>
                                        <?php
                                        $questoes = (int) $aluno['questoes'];
                                        $matematicaQuestoes = (int) $aluno['matematica_questoes'];
                                        $portuguesQuestoes = (int) $aluno['portugues_questoes'];
                                        $desempenho = $questoes === 0 ? 0 : (int) round((int) $aluno['acertos'] / $questoes * 100);
                                        $desempenhoMatematica = $matematicaQuestoes === 0 ? 0 : (int) round((int) $aluno['matematica_acertos'] / $matematicaQuestoes * 100);
                                        $desempenhoPortugues = $portuguesQuestoes === 0 ? 0 : (int) round((int) $aluno['portugues_acertos'] / $portuguesQuestoes * 100);
                                        ?>
                                        <tr>
                                            <td>
                                                <strong><?= $escapar($aluno['nome']) ?></strong>
                                                <small class="teacher-student-email"><?= $escapar($aluno['email']) ?></small>
                                            </td>
                                            <td><?= (int) $aluno['partidas'] ?></td>
                                            <td><?= $desempenhoMatematica ?>% <small>(<?= (int) $aluno['matematica_acertos'] ?>/<?= $matematicaQuestoes ?>)</small></td>
                                            <td><?= $desempenhoPortugues ?>% <small>(<?= (int) $aluno['portugues_acertos'] ?>/<?= $portuguesQuestoes ?>)</small></td>
                                            <td><strong><?= $desempenho ?>%</strong> <small>(<?= (int) $aluno['acertos'] ?>/<?= $questoes ?>)</small></td>
                                            <td><?= (int) $aluno['fases_concluidas'] ?>/8</td>
                                            <td><?= $aluno['ultima_partida'] === null ? 'Ainda não jogou' : $escapar(date('d/m/Y H:i', strtotime($aluno['ultima_partida']))) ?></td>
                                            <td>
                                                <form class="teacher-remove-student" data-student-name="<?= $escapar($aluno['nome']) ?>" action="api/alunos-professor.php" method="post">
                                                    <input type="hidden" name="csrf_token" value="<?= $escapar((string) ($_SESSION['csrf_token'] ?? '')) ?>">
                                                    <input type="hidden" name="acao" value="desvincular">
                                                    <input type="hidden" name="aluno_id" value="<?= (int) $aluno['id'] ?>">
                                                    <button class="text-button teacher-remove-button" type="submit" aria-label="Desvincular <?= $escapar($aluno['nome']) ?>">
                                                        <i class="bi bi-person-dash" aria-hidden="true"></i> Desvincular
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </section>
            </section>

            <section id="painel-perfil" class="teacher-tab-panel<?= $abaInicial === 'perfil' ? ' is-active' : '' ?>" role="tabpanel" aria-labelledby="tab-perfil" <?= $abaInicial !== 'perfil' ? 'hidden' : '' ?>>
                <div class="page-heading">
                    <span class="eyebrow"><span class="eyebrow-dot"></span> SUA CONTA</span>
                    <h1>Meu Perfil</h1>
                    <p>Consulte seus dados de acesso e atualize seu nome de exibição.</p>
                </div>
                <section class="profile-layout teacher-profile-layout">
                    <article class="profile-card">
                        <div class="profile-avatar" role="img" aria-label="Ícone de perfil">👩‍🏫</div>
                        <span class="eyebrow">Professor FLUX</span>
                        <h2 id="profile-name"><?= $escapar($conta['nome']) ?></h2>
                        <p class="profile-email"><?= $escapar($conta['email']) ?></p>
                    </article>
                    <section class="profile-detail-card profile-edit-card">
                        <span class="eyebrow">SEUS DADOS</span>
                        <h2>Editar nome</h2>
                        <form id="profile-edit" data-endpoint="api/usuario.php" data-csrf="<?= $escapar((string) ($_SESSION['csrf_token'] ?? '')) ?>">
                            <label for="profile-name-input">Nome de exibição</label>
                            <div class="profile-edit-row">
                                <input id="profile-name-input" name="nome" type="text" maxlength="120" minlength="1" value="<?= $escapar($conta['nome']) ?>" required>
                                <button class="button button-primary" type="submit">Salvar</button>
                            </div>
                            <p class="profile-edit-message" id="profile-edit-message" role="status" hidden></p>
                        </form>
                    </section>
                </section>
            </section>
        <?php endif; ?>
    </main>
    <script src="js/professor.js" defer></script>
    <script src="js/perfil.js" defer></script>
</body>
</html>
