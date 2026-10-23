<?php
declare(strict_types=1);

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'use_strict_mode' => true,
]);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function responderUsuario(int $status, array $dados): never
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!isset($_SESSION['usuario_id'])) {
    responderUsuario(401, ['erro' => 'Sua sessão expirou. Entre novamente.']);
}
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'PUT'], true)) {
    header('Allow: GET, PUT');
    responderUsuario(405, ['erro' => 'Método não permitido.']);
}

require_once __DIR__ . '/../classes/Conexao.php';
require_once __DIR__ . '/../classes/Usuario.php';
require_once __DIR__ . '/../classes/Aluno.php';
require_once __DIR__ . '/../classes/Professor.php';
require_once __DIR__ . '/../classes/Jogo.php';

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $csrfEnviado = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($csrfEnviado) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrfEnviado)) {
        responderUsuario(403, ['erro' => 'Sua sessão expirou. Atualize a página e tente novamente.']);
    }

    $entrada = json_decode(file_get_contents('php://input'), true);
    if (!is_array($entrada)) {
        responderUsuario(400, ['erro' => 'Envie um JSON válido com o novo nome.']);
    }
    if (!is_string($entrada['nome'] ?? null)) {
        responderUsuario(400, ['erro' => 'O nome deve ser enviado como texto.']);
    }
    $novoNome = trim($entrada['nome']);
    if ($novoNome === '' || mb_strlen($novoNome, 'UTF-8') > 120) {
        responderUsuario(400, ['erro' => 'O nome deve ter entre 1 e 120 caracteres.']);
    }
} else {
    $novoNome = null;
}

try {
    $conexao = Conexao::obter();
    $repositorio = new Usuario($conexao);
    $usuario = $repositorio->buscarPorId((int) $_SESSION['usuario_id']);
    if ($usuario === null) {
        responderUsuario(404, ['erro' => 'Usuário não encontrado.']);
    }

    $jogo = new Jogo($conexao);
    $resumo = $jogo->obterResumo((int) $_SESSION['usuario_id']);
    $tipoUsuario = $_SESSION['usuario_tipo'] ?? 'aluno';
    if (!in_array($tipoUsuario, ['aluno', 'professor'], true)) {
        $tipoUsuario = 'aluno';
    }

    $perfilEducacional = $tipoUsuario === 'professor'
        ? new Professor($usuario['nome'], $usuario['email'], $resumo['nivel'], $resumo['estrelas'])
        : new Aluno($usuario['nome'], $usuario['email'], $resumo['nivel'], $resumo['estrelas']);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        responderUsuario(200, [
            'perfil' => [
                'id' => (int) $usuario['id'],
                'nome' => $perfilEducacional->getNome(),
                'email' => $perfilEducacional->getEmail(),
                'tipo' => $perfilEducacional->getTipo(),
                'nivel' => $perfilEducacional->getNivel(),
                'titulo_nivel' => $resumo['titulo_nivel'],
                'estrelas' => $perfilEducacional->getEstrelas(),
                'data_cadastro' => $usuario['data_cadastro'],
            ],
            'estatisticas' => $resumo,
        ]);
    }

    $perfilEducacional->setNome($novoNome ?? '');
    $repositorio->atualizarNome((int) $usuario['id'], $perfilEducacional->getNome());
    $_SESSION['usuario_nome'] = $perfilEducacional->getNome();

    responderUsuario(200, [
        'mensagem' => 'Seu nome foi atualizado.',
        'perfil' => [
            'nome' => $perfilEducacional->getNome(),
            'email' => $perfilEducacional->getEmail(),
            'nivel' => $perfilEducacional->getNivel(),
            'titulo_nivel' => $resumo['titulo_nivel'],
            'estrelas' => $perfilEducacional->getEstrelas(),
        ],
    ]);
} catch (InvalidArgumentException $erro) {
    responderUsuario(400, ['erro' => $erro->getMessage()]);
} catch (PDOException $erro) {
    error_log('FLUX: falha ao consultar ou atualizar perfil via API: ' . $erro->getMessage());
    responderUsuario(503, ['erro' => 'Não foi possível acessar os dados do usuário. Confira a conexão com o banco de dados.']);
} catch (Throwable $erro) {
    error_log('FLUX: erro interno na API de usuário: ' . $erro->getMessage());
    responderUsuario(500, ['erro' => 'Ocorreu um erro interno ao consultar ou atualizar o perfil.']);
}
