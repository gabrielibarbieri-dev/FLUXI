<?php
declare(strict_types=1);

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'use_strict_mode' => true,
]);

function voltarParaPainelProfessor(string $texto, string $tipo = 'error'): never
{
    $_SESSION['mensagem_professor'] = ['tipo' => $tipo, 'texto' => $texto];
    $_SESSION['professor_aba'] = 'alunos';
    header('Location: ../professor.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Método não permitido.');
}

if (!isset($_SESSION['usuario_id'], $_SESSION['usuario_tipo'])
    || $_SESSION['usuario_tipo'] !== 'professor') {
    http_response_code(403);
    exit('Apenas professores autenticados podem gerenciar alunos.');
}

$tokenEnviado = $_POST['csrf_token'] ?? '';
if (!is_string($tokenEnviado) || !hash_equals($_SESSION['csrf_token'] ?? '', $tokenEnviado)) {
    voltarParaPainelProfessor('Sua sessão expirou. Atualize a página e tente novamente.');
}

$acao = $_POST['acao'] ?? null;
if (!is_string($acao) || !in_array($acao, ['vincular', 'desvincular'], true)) {
    voltarParaPainelProfessor('A ação solicitada não é válida.');
}

require_once __DIR__ . '/../classes/Conexao.php';
require_once __DIR__ . '/../classes/Usuario.php';

try {
    $repositorio = new Usuario(Conexao::obter());
    $professorId = (int) $_SESSION['usuario_id'];

    if ($acao === 'vincular') {
        $email = $_POST['email'] ?? null;
        if (!is_string($email)) {
            voltarParaPainelProfessor('Informe o e-mail da conta de aluno.');
        }
        $repositorio->vincularAlunoPorEmail($professorId, $email);
        voltarParaPainelProfessor('Aluno vinculado com sucesso.', 'success');
    }

    $alunoId = filter_var($_POST['aluno_id'] ?? null, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ]);
    if ($alunoId === false || $alunoId === null) {
        voltarParaPainelProfessor('O aluno selecionado não é válido.');
    }

    $repositorio->desvincularAluno($professorId, $alunoId);
    voltarParaPainelProfessor('Aluno desvinculado. A conta e o histórico dele foram preservados.', 'success');
} catch (PDOException $erro) {
    error_log('FLUX: falha ao gerenciar vínculo de aluno: ' . $erro->getMessage());
    voltarParaPainelProfessor('Não foi possível atualizar os alunos agora. Confira a conexão com o banco de dados.');
} catch (InvalidArgumentException | RuntimeException $erro) {
    voltarParaPainelProfessor($erro->getMessage());
}
