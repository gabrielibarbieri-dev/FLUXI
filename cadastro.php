<?php
declare(strict_types=1);

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'use_strict_mode' => true,
]);

if (isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit;
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$mensagem = $_SESSION['mensagem'] ?? null;
unset($_SESSION['mensagem']);

require_once __DIR__ . '/classes/Conexao.php';
require_once __DIR__ . '/classes/Usuario.php';
$professores = [];
$erroListaProfessores = null;
try {
    $professores = (new Usuario(Conexao::obter()))->listarProfessoresComVagas();
} catch (PDOException $erro) {
    error_log('FLUX: falha ao carregar professores disponíveis no cadastro: ' . $erro->getMessage());
    $erroListaProfessores = 'Não foi possível carregar os professores disponíveis agora.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Criar conta — FLUX</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Nunito:wght@500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/style.css">
    <script src="js/acessibilidade.js" defer></script>
</head>
<body class="auth-shell">
    <header class="auth-header"><a class="brand" href="index.php"><span class="brand-mark">F</span> FLUX<span class="brand-period">.</span></a></header>
    <main class="auth-main">
        <section class="auth-welcome">
            <span class="eyebrow"><span class="eyebrow-dot"></span> UM MUNDO DE DESCOBERTAS</span>
            <h1>Vamos nessa, <span class="text-lilac">explorador?</span></h1>
            <p>Crie sua conta e venha aprender, jogar e conquistar novas estrelas com a Flor.</p>
            <div class="auth-mascot" role="img" aria-label="Flor, a raposa mascote">🦊</div>
        </section>
        <section class="auth-card" aria-labelledby="cadastro-title">
            <h2 id="cadastro-title">Crie sua conta</h2>
            <p class="auth-card-intro">É rapidinho. Sua aventura começa logo depois.</p>
            <?php if (is_array($mensagem)): ?>
                <div class="alert alert-<?= htmlspecialchars($mensagem['tipo'], ENT_QUOTES, 'UTF-8') ?>" role="alert"><?= htmlspecialchars($mensagem['texto'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <form action="api/cadastro.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                <fieldset class="form-field">
                    <legend>Tipo de conta</legend>
                    <div class="role-selector" role="radiogroup" aria-label="Escolha o tipo de conta">
                        <label class="role-option">
                            <input type="radio" name="tipo_conta" value="aluno" checked>
                            <span>Aluno</span>
                        </label>
                        <label class="role-option">
                            <input type="radio" name="tipo_conta" value="professor">
                            <span>Professor</span>
                        </label>
                    </div>
                </fieldset>
                <div id="convite-professor-field" class="form-field">
                    <label for="codigo_convite"><i class="bi bi-ticket-perforated" aria-hidden="true"></i> Código de convite para professor</label>
                    <input id="codigo_convite" name="codigo_convite" type="text" inputmode="text" autocomplete="off" autocapitalize="characters" maxlength="32" pattern="[A-Fa-f0-9]{32}" aria-describedby="convite-professor-hint" placeholder="Obrigatório para conta de professor">
                    <span class="form-hint" id="convite-professor-hint">Deixe vazio se estiver criando uma conta de aluno.</span>
                </div>
                <div id="professor-aluno-field" class="form-field">
                    <label for="professor_id"><i class="bi bi-person-workspace" aria-hidden="true"></i> Escolha seu professor (opcional)</label>
                    <select id="professor_id" name="professor_id" <?= $professores === [] ? 'disabled' : '' ?>>
                        <option value="">Quero escolher depois</option>
                        <?php foreach ($professores as $professor): ?>
                            <option value="<?= (int) $professor['id'] ?>"><?= htmlspecialchars($professor['nome'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($erroListaProfessores !== null): ?>
                        <span class="form-hint"><?= htmlspecialchars($erroListaProfessores, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php elseif ($professores === []): ?>
                        <span class="form-hint">No momento, não há professores com vagas disponíveis. Você pode escolher depois.</span>
                    <?php else: ?>
                        <span class="form-hint">Sua conta será vinculada ao professor selecionado. Você também pode escolher depois.</span>
                    <?php endif; ?>
                </div>
                <div class="form-field">
                    <label for="nome"><i class="bi bi-person" aria-hidden="true"></i> Nome</label>
                    <input id="nome" name="nome" type="text" autocomplete="name" maxlength="120" placeholder="Como podemos chamar você?" required>
                </div>
                <div class="form-field">
                    <label for="email"><i class="bi bi-envelope" aria-hidden="true"></i> E-mail</label>
                    <input id="email" name="email" type="email" autocomplete="email" maxlength="190" placeholder="voce@exemplo.com" required>
                </div>
                <div class="form-field">
                    <label for="senha"><i class="bi bi-lock" aria-hidden="true"></i> Senha</label>
                    <input id="senha" name="senha" type="password" autocomplete="new-password" minlength="8" maxlength="72" required>
                </div>
                <div class="form-field">
                    <label for="confirmar_senha"><i class="bi bi-shield-check" aria-hidden="true"></i> Confirme sua senha</label>
                    <input id="confirmar_senha" name="confirmar_senha" type="password" autocomplete="new-password" minlength="8" maxlength="72" required>
                </div>
                <span class="form-hint">Use pelo menos 8 caracteres; senhas com mais de 72 bytes não são aceitas.</span>
                <button class="button button-primary" type="submit">Criar minha conta <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
            </form>
            <p class="auth-switch">Já possui uma conta? <a href="login.php">Entre por aqui</a></p>
        </section>
    </main>
    <script src="js/cadastro.js" defer></script>
</body>
</html>
