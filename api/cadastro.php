<?php
declare(strict_types=1);

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'use_strict_mode' => true,
]);

function voltarParaCadastro(string $texto, string $tipo = 'error'): never
{
    $_SESSION['mensagem'] = ['tipo' => $tipo, 'texto' => $texto];
    header('Location: ../cadastro.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Método não permitido.');
}

$tokenEnviado = $_POST['csrf_token'] ?? '';
if (!is_string($tokenEnviado) || !hash_equals($_SESSION['csrf_token'] ?? '', $tokenEnviado)) {
    voltarParaCadastro('Sua sessão expirou. Atualize a página e tente novamente.');
}

$nome = $_POST['nome'] ?? null;
$email = $_POST['email'] ?? null;
$senha = $_POST['senha'] ?? null;
$confirmarSenha = $_POST['confirmar_senha'] ?? null;

if (!is_string($nome) || !is_string($email) || !is_string($senha) || !is_string($confirmarSenha)) {
    voltarParaCadastro('Preencha os campos com valores válidos.');
}

$nome = trim($nome);
$email = trim($email);

if ($nome === '' || $email === '' || $senha === '' || $confirmarSenha === '') {
    voltarParaCadastro('Preencha todos os campos para criar sua conta.');
}

if (mb_strlen($nome, 'UTF-8') > 120) {
    voltarParaCadastro('O nome deve ter no máximo 120 caracteres.');
}

if (mb_strlen($email, 'UTF-8') > 190 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
    voltarParaCadastro('Informe um e-mail válido.');
}

if (strlen($senha) < 8 || strlen($senha) > 72) {
    voltarParaCadastro('Sua senha deve ter pelo menos 8 caracteres e no máximo 72 bytes.');
}

$tipoConta = $_POST['tipo_conta'] ?? 'aluno';
if (!is_string($tipoConta) || !in_array($tipoConta, ['aluno', 'professor'], true)) {
    voltarParaCadastro('Selecione um tipo de conta válido.');
}

$codigoConvite = $_POST['codigo_convite'] ?? '';
if (!is_string($codigoConvite)) {
    voltarParaCadastro('Informe um código de convite válido.');
}
if ($tipoConta === 'professor' && !preg_match('/^[a-fA-F0-9]{32}$/', trim($codigoConvite))) {
    voltarParaCadastro('Informe o código de convite de professor válido.');
}

$professorId = null;
if ($tipoConta === 'aluno') {
    $professorEnviado = $_POST['professor_id'] ?? '';
    if (!is_string($professorEnviado)) {
        voltarParaCadastro('A seleção de professor não é válida.');
    }
    if ($professorEnviado !== '') {
        $professorId = filter_var($professorEnviado, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        if ($professorId === false) {
            voltarParaCadastro('Selecione um professor válido.');
        }
    }
}

if ($senha !== $confirmarSenha) {
    voltarParaCadastro('A confirmação da senha não corresponde.');
}

require_once __DIR__ . '/../classes/Conexao.php';
require_once __DIR__ . '/../classes/Usuario.php';

try {
    $usuario = new Usuario(Conexao::obter());
    if ($tipoConta === 'professor' && !$usuario->validarConviteProfessor($codigoConvite)) {
        voltarParaCadastro('O código de convite não é válido. Confira e tente novamente.');
    }
    if ($usuario->emailExiste($email)) {
        voltarParaCadastro('Este e-mail já está cadastrado. Tente entrar na sua conta.');
    }

    if ($tipoConta === 'aluno') {
        $usuario->criarAlunoComProfessor(
            $nome,
            $email,
            password_hash($senha, PASSWORD_DEFAULT),
            $professorId
        );
    } else {
        $usuario->criar($nome, $email, password_hash($senha, PASSWORD_DEFAULT), $tipoConta);
    }
    unset($_SESSION['csrf_token']);
    $_SESSION['mensagem'] = [
        'tipo' => 'success',
        'texto' => $tipoConta === 'professor'
            ? 'Conta de professor criada com sucesso! Agora entre para acessar o painel.'
            : ($professorId === null
                ? 'Conta criada com sucesso! Agora entre para começar sua aventura.'
                : 'Conta criada e vinculada ao professor escolhido! Agora entre para começar sua aventura.'),
    ];
    header('Location: ../login.php');
    exit;
} catch (PDOException $erro) {
    if ($erro->getCode() === '23000') {
        voltarParaCadastro('Este e-mail já está cadastrado. Tente entrar na sua conta.');
    }

    error_log('FLUX: falha ao criar usuário: ' . $erro->getMessage());
    voltarParaCadastro('Não foi possível criar sua conta agora. Confira se o banco de dados está configurado e tente novamente.');
} catch (RuntimeException $erro) {
    voltarParaCadastro($erro->getMessage());
}
