<?php
declare(strict_types=1);

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'use_strict_mode' => true,
]);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function responderJson(int $status, array $dados): never
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!isset($_SESSION['usuario_id'])) {
    responderJson(401, ['erro' => 'Sua sessão expirou. Entre novamente.']);
}
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    responderJson(405, ['erro' => 'Método não permitido.']);
}

require_once __DIR__ . '/../classes/Conexao.php';
require_once __DIR__ . '/../classes/Jogo.php';
$entrada = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfEnviado = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($csrfEnviado) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrfEnviado)) {
        responderJson(403, ['erro' => 'Sua sessão expirou. Atualize a página e tente novamente.']);
    }
    $entrada = json_decode(file_get_contents('php://input'), true);
    if (!is_array($entrada)) {
        responderJson(400, ['erro' => 'Envie um JSON válido com a matéria e a fase.']);
    }
    $materia = $entrada['materia'] ?? null;
    $fase = filter_var($entrada['fase'] ?? null, FILTER_VALIDATE_INT);
} else {
    $materia = $_GET['materia'] ?? null;
    $fase = filter_var($_GET['fase'] ?? null, FILTER_VALIDATE_INT);
}
if (!is_string($materia) || !is_int($fase) || !isset(Jogo::MATERIAS[$materia], Jogo::FASES[$fase])) {
    responderJson(400, ['erro' => 'Matéria ou fase inválida.']);
}

try {
    $jogo = new Jogo(Conexao::obter());
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $fasesDisponiveis = $jogo->listarFases((int) $_SESSION['usuario_id'], $materia);
        if (empty($fasesDisponiveis[$fase - 1]['desbloqueada'])) {
            responderJson(403, ['erro' => 'Conclua a fase anterior para desbloquear esta aventura.']);
        }
        $perguntas = $jogo->obterPerguntas($materia, $fase);
        $publicas = array_map(
            static fn (array $pergunta): array => [
                'id' => (int) $pergunta['id'],
                'pergunta' => $pergunta['pergunta'],
                'alternativas' => [
                    'A' => $pergunta['alternativa_a'],
                    'B' => $pergunta['alternativa_b'],
                    'C' => $pergunta['alternativa_c'],
                    'D' => $pergunta['alternativa_d'],
                ],
                'pontos' => (int) $pergunta['pontos'],
            ],
            $perguntas
        );
        responderJson(200, [
            'materia' => Jogo::MATERIAS[$materia],
            'fase' => $fase,
            'total' => count($publicas),
            'perguntas' => $publicas,
        ]);
    }

    $fases = $jogo->listarFases((int) $_SESSION['usuario_id'], $materia);
    if (empty($fases[$fase - 1]['desbloqueada'])) {
        responderJson(403, ['erro' => 'Conclua a fase anterior para desbloquear esta aventura.']);
    }

    $perguntas = $jogo->obterPerguntas($materia, $fase);
    $estado = [];
    $publicas = [];
    foreach ($perguntas as $pergunta) {
        $alternativas = [
            'A' => $pergunta['alternativa_a'],
            'B' => $pergunta['alternativa_b'],
            'C' => $pergunta['alternativa_c'],
            'D' => $pergunta['alternativa_d'],
        ];
        $estado[] = [
            'id' => (int) $pergunta['id'],
            'resposta' => $pergunta['resposta_correta'],
            'explicacao' => $pergunta['explicacao'],
            'pontos' => (int) $pergunta['pontos'],
            'alternativas' => $alternativas,
        ];
        $publicas[] = [
            'id' => (int) $pergunta['id'],
            'pergunta' => $pergunta['pergunta'],
            'alternativas' => $alternativas,
        ];
    }

    $_SESSION['jogo_ativo'] = [
        'materia' => $materia,
        'fase' => $fase,
        'perguntas' => $estado,
        'indice' => 0,
        'pontuacao' => 0,
        'acertos' => 0,
        'pontuacao_questao_atual' => 0,
        'prazo_pergunta' => microtime(true) + 30,
        'pergunta_iniciada_id' => $estado[0]['id'] ?? null,
    ];
    unset($_SESSION['jogo_finalizado']);

    responderJson(200, [
        'materia' => Jogo::MATERIAS[$materia],
        'fase' => $fase,
        'total' => count($publicas),
        'perguntas' => $publicas,
        'tempo_restante_ms' => max(0, (int) ceil(($_SESSION['jogo_ativo']['prazo_pergunta'] - microtime(true)) * 1000)),
        'mensagem_flor' => $fase === 4
            ? 'Essa é um desafio e tanto. Confio em você!'
            : 'Preparado? Vamos ver do que você é capaz!',
    ]);
} catch (PDOException $erro) {
    error_log('FLUX: falha ao iniciar partida: ' . $erro->getMessage());
    responderJson(503, ['erro' => 'Não foi possível carregar o jogo. Confira a conexão com o banco de dados.']);
} catch (InvalidArgumentException $erro) {
    responderJson(400, ['erro' => $erro->getMessage()]);
} catch (RuntimeException $erro) {
    error_log('FLUX: banco de perguntas incompleto: ' . $erro->getMessage());
    responderJson(503, ['erro' => $erro->getMessage()]);
} catch (Throwable $erro) {
    error_log('FLUX: erro interno ao consultar perguntas: ' . $erro->getMessage());
    responderJson(500, ['erro' => 'Ocorreu um erro interno ao consultar as perguntas.']);
}
