<?php
declare(strict_types=1);

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'use_strict_mode' => true,
]);

function voltarParaPerfilAluno(string $texto, string $tipo = 'error'): never
{
    $_SESSION['mensagem_aluno_professor'] = ['tipo' => $tipo, 'texto' => $texto];
    header('Location: ../perfil.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Método não permitido.');
}

if (!isset($_SESSION['usuario_id'], $_SESSION['usuario_tipo'])
    || $_SESSION['usuario_tipo'] !== 'aluno') {
    http_response_code(403);
    exit('Apenas alunos autenticados podem escolher um professor.');
}

$tokenEnviado = $_POST['csrf_token'] ?? '';
if (!is_string($tokenEnviado) || !hash_equals($_SESSION['csrf_token'] ?? '', $tokenEnviado)) {
    voltarParaPerfilAluno('Sua sessão expirou. Atualize a página e tente novamente.');
}

$professorId = filter_var($_POST['professor_id'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);
if ($professorId === false || $professorId === null) {
    voltarParaPerfilAluno('Selecione um professor válido.');
}

require_once __DIR__ . '/../classes/Conexao.php';
require_once __DIR__ . '/../classes/Usuario.php';

try {
    $repositorio = new Usuario(Conexao::obter());
    $repositorio->vincularAlunoAProfessor((int) $_SESSION['usuario_id'], $professorId);
    voltarParaPerfilAluno('Você foi vinculado ao professor escolhido.', 'success');
} catch (PDOException $erro) {
    error_log('FLUX: falha ao vincular aluno ao professor: ' . $erro->getMessage());
    voltarParaPerfilAluno('Não foi possível atualizar seu professor agora. Confira a conexão com o banco de dados.');
} catch (RuntimeException $erro) {
    voltarParaPerfilAluno($erro->getMessage());
}
