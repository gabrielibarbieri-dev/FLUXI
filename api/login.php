<?php
declare(strict_types=1);

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'use_strict_mode' => true,
]);

function voltarParaLogin(string $texto): never
{
    $_SESSION['mensagem'] = ['tipo' => 'error', 'texto' => $texto];
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Método não permitido.');
}

$tokenEnviado = $_POST['csrf_token'] ?? '';
if (!is_string($tokenEnviado) || !hash_equals($_SESSION['csrf_token'] ?? '', $tokenEnviado)) {
    voltarParaLogin('Sua sessão expirou. Atualize a página e tente novamente.');
}

$email = $_POST['email'] ?? null;
$senha = $_POST['senha'] ?? null;

if (!is_string($email) || !is_string($senha)) {
    voltarParaLogin('Informe valores válidos para entrar.');
}

$email = trim($email);

if ($email === '' || $senha === '') {
    voltarParaLogin('Informe seu e-mail e sua senha para entrar.');
}

if (mb_strlen($email, 'UTF-8') > 190 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
    voltarParaLogin('Confira o e-mail informado e tente novamente.');
}

$tipoConta = $_POST['tipo_conta'] ?? 'aluno';
if (!is_string($tipoConta) || !in_array($tipoConta, ['aluno', 'professor'], true)) {
    voltarParaLogin('Selecione se deseja entrar como aluno ou professor.');
}

require_once __DIR__ . '/../classes/Conexao.php';
require_once __DIR__ . '/../classes/Usuario.php';

try {
    $usuario = (new Usuario(Conexao::obter()))->buscarPorEmail($email);

    if ($usuario === null || !password_verify($senha, $usuario['senha'])) {
        voltarParaLogin('E-mail ou senha incorretos. Confira seus dados e tente novamente.');
    }

    if (($usuario['tipo'] ?? 'aluno') !== $tipoConta) {
        voltarParaLogin('Esse e-mail não está cadastrado para o tipo de acesso escolhido.');
    }

    session_regenerate_id(true);
    $_SESSION['usuario_id'] = (int) $usuario['id'];
    $_SESSION['usuario_nome'] = $usuario['nome'];
    $_SESSION['usuario_tipo'] = $usuario['tipo'];
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    header('Location: ../dashboard.php');
    exit;
} catch (PDOException $erro) {
    error_log('FLUX: falha ao autenticar usuário: ' . $erro->getMessage());
    voltarParaLogin('Não foi possível entrar agora. Confira se o banco de dados está configurado e tente novamente.');
}
