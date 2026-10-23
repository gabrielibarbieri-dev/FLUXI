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
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Entrar — FLUX</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Nunito:wght@500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-shell">
    <header class="auth-header"><a class="brand" href="index.php"><span class="brand-mark">F</span> FLUX<span class="brand-period">.</span></a></header>
    <main class="auth-main">
        <section class="auth-welcome">
            <span class="eyebrow"><span class="eyebrow-dot"></span> SUA AVENTURA CONTINUA</span>
            <h1>Que bom ter você <span class="text-lilac">aqui!</span></h1>
            <p>Entre na sua conta e vamos começar. A Flor já está pronta para acompanhar você!</p>
            <div class="auth-mascot" role="img" aria-label="Flor, a raposa mascote">🦊</div>
        </section>
        <section class="auth-card" aria-labelledby="login-title">
            <h2 id="login-title">Boas-vindas de volta</h2>
            <p class="auth-card-intro">Acesse sua conta para continuar de onde parou.</p>
            <?php if (is_array($mensagem)): ?>
                <div class="alert alert-<?= htmlspecialchars($mensagem['tipo'], ENT_QUOTES, 'UTF-8') ?>" role="alert"><?= htmlspecialchars($mensagem['texto'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <form action="api/login.php" method="post">
                <div class="role-selector" role="radiogroup" aria-label="Escolha o tipo de acesso">
                    <label class="role-option">
                        <input type="radio" name="tipo_conta" value="aluno" checked>
                        <span>Aluno</span>
                    </label>
                    <label class="role-option">
                        <input type="radio" name="tipo_conta" value="professor">
                        <span>Professor</span>
                    </label>
                </div>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                <div class="form-field">
                    <label for="email"><i class="bi bi-envelope" aria-hidden="true"></i> E-mail</label>
                    <input id="email" name="email" type="email" autocomplete="email" maxlength="190" placeholder="voce@exemplo.com" required>
                </div>
                <div class="form-field">
                    <label for="senha"><i class="bi bi-lock" aria-hidden="true"></i> Senha</label>
                    <input id="senha" name="senha" type="password" autocomplete="current-password" required>
                </div>
                <button class="button button-primary" type="submit">Entrar <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
            </form>
            <p class="auth-switch">Ainda não possui uma conta? <a href="cadastro.php">Cadastre-se</a></p>
        </section>
    </main>
</body>
</html>
