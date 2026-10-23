<?php
declare(strict_types=1);

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'use_strict_mode' => true,
]);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function responderPartida(int $status, array $dados): never
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function registrarRespostaPartida(array $pergunta, ?string $resposta, bool $expirada): array
{
    $acertou = !$expirada && is_string($resposta) && hash_equals($pergunta['resposta'], $resposta);
    $pontosGanhos = $acertou ? $pergunta['pontos'] : 0;
    $_SESSION['jogo_ativo']['indice']++;
    $_SESSION['jogo_ativo']['pontuacao'] += $pontosGanhos;
    $_SESSION['jogo_ativo']['pontuacao_questao_atual'] = $pontosGanhos;
    if ($acertou) {
        $_SESSION['jogo_ativo']['acertos']++;
    }

    $total = count($_SESSION['jogo_ativo']['perguntas']);
    $_SESSION['jogo_ativo']['prazo_pergunta'] = null;
    $_SESSION['jogo_ativo']['pergunta_iniciada_id'] = null;

    return [
        'acertou' => $acertou,
        'expirada' => $expirada,
        'resposta_correta' => $pergunta['resposta'],
        'explicacao' => $pergunta['explicacao'],
        'pontos_ganhos' => $pontosGanhos,
        'pontuacao' => $_SESSION['jogo_ativo']['pontuacao'],
        'questoes_respondidas' => $_SESSION['jogo_ativo']['indice'],
        'total' => $total,
        'mensagem_flor' => $expirada
            ? 'O tempo acabou nesta questão. Respire fundo e siga para a próxima! 🦊'
            : ($acertou
                ? ['Mandou muito bem! ⭐', 'Resposta brilhante! Você está voando! ✨', 'Acertou em cheio! A Flor sabia que você conseguia! 🦊']
                    [random_int(0, 2)]
                : ['Quase! Não desista. Aprenda com essa e tente novamente!', 'Errar também faz parte da aventura. Você consegue!', 'Boa descoberta! Agora você já sabe mais uma. 🦊']
                    [random_int(0, 2)]),
    ];
}

if (!isset($_SESSION['usuario_id'])) {
    responderPartida(401, ['erro' => 'Sua sessão expirou. Entre novamente.']);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    responderPartida(405, ['erro' => 'Método não permitido.']);
}
$csrfEnviado = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!is_string($csrfEnviado) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrfEnviado)) {
    responderPartida(403, ['erro' => 'Sua sessão expirou. Atualize a página e tente novamente.']);
}

$entrada = json_decode(file_get_contents('php://input'), true);
if (!is_array($entrada) || !is_string($entrada['acao'] ?? null)) {
    responderPartida(400, ['erro' => 'Envie um JSON válido com a ação desejada.']);
}
$jogoAtivo = $_SESSION['jogo_ativo'] ?? null;
if (!is_array($jogoAtivo)) {
    if ($entrada['acao'] === 'finalizar' && is_array($_SESSION['jogo_finalizado'] ?? null)) {
        responderPartida(200, $_SESSION['jogo_finalizado']);
    }
    responderPartida(409, ['erro' => 'Nenhuma partida ativa. Volte às fases e comece uma nova partida.']);
}

if (is_int($jogoAtivo['prazo_pergunta'] ?? null) || is_float($jogoAtivo['prazo_pergunta'] ?? null)) {
    $prazoAnterior = (float) $jogoAtivo['prazo_pergunta'];
    if ($prazoAnterior > microtime(true) + 30) {
        $_SESSION['jogo_ativo']['prazo_pergunta'] = microtime(true) + 30;
        $jogoAtivo['prazo_pergunta'] = $_SESSION['jogo_ativo']['prazo_pergunta'];
    }
}

if ($entrada['acao'] === 'iniciar_questao') {
    $perguntaAtual = $jogoAtivo['perguntas'][$jogoAtivo['indice']] ?? null;
    $idPergunta = filter_var($entrada['pergunta_id'] ?? null, FILTER_VALIDATE_INT);
    if ($perguntaAtual === null || !is_int($idPergunta) || $idPergunta !== $perguntaAtual['id']) {
        responderPartida(400, ['erro' => 'Questão inválida ou fora de ordem.']);
    }

    if (($jogoAtivo['pergunta_iniciada_id'] ?? null) !== $idPergunta) {
        $_SESSION['jogo_ativo']['pergunta_iniciada_id'] = $idPergunta;
        $_SESSION['jogo_ativo']['prazo_pergunta'] = microtime(true) + 30;
    }
    responderPartida(200, [
        'tempo_restante_ms' => max(0, (int) ceil(($_SESSION['jogo_ativo']['prazo_pergunta'] - microtime(true)) * 1000)),
    ]);
}

if (in_array($entrada['acao'], ['responder', 'tempo_esgotado'], true)) {
    $indice = $jogoAtivo['indice'];
    $pergunta = $jogoAtivo['perguntas'][$indice] ?? null;
    $idPergunta = filter_var($entrada['pergunta_id'] ?? null, FILTER_VALIDATE_INT);
    $expiracaoSolicitada = $entrada['acao'] === 'tempo_esgotado';
    $resposta = $expiracaoSolicitada ? null : ($entrada['resposta'] ?? null);
    $ultimaResposta = $jogoAtivo['ultima_resposta'] ?? null;
    if (is_array($ultimaResposta)
        && $ultimaResposta['pergunta_id'] === $idPergunta
        && $ultimaResposta['resposta'] === $resposta) {
        responderPartida(200, $ultimaResposta['resultado']);
    }
    if ($pergunta === null || !is_int($idPergunta) || $idPergunta !== $pergunta['id']) {
        responderPartida(400, ['erro' => 'Resposta inválida ou fora de ordem.']);
    }

    if (!$expiracaoSolicitada
        && (!is_string($resposta) || !array_key_exists($resposta, $pergunta['alternativas']))) {
        responderPartida(400, ['erro' => 'Resposta inválida ou fora de ordem.']);
    }

    $prazo = $_SESSION['jogo_ativo']['prazo_pergunta'] ?? null;
    if (!is_float($prazo) && !is_int($prazo)) {
        $prazo = microtime(true) + 30;
        $_SESSION['jogo_ativo']['prazo_pergunta'] = $prazo;
    }
    $expirada = microtime(true) >= $prazo;
    if ($expiracaoSolicitada && microtime(true) < $prazo - 2) {
        responderPartida(409, [
            'erro' => 'Ainda há tempo para responder.',
            'tempo_restante_ms' => (int) ceil(($prazo - microtime(true)) * 1000),
        ]);
    }
    $resultadoResposta = registrarRespostaPartida($pergunta, $resposta, $expirada || $expiracaoSolicitada);
    $_SESSION['jogo_ativo']['ultima_resposta'] = [
        'pergunta_id' => $idPergunta,
        'resposta' => $resposta,
        'resultado' => $resultadoResposta,
    ];
    responderPartida(200, $resultadoResposta);
}

if ($entrada['acao'] === 'finalizar') {
    $total = count($jogoAtivo['perguntas']);
    if ($jogoAtivo['indice'] !== $total) {
        responderPartida(409, ['erro' => 'Responda todas as perguntas antes de finalizar a partida.']);
    }

    $estrelas = 1;
    if ($jogoAtivo['acertos'] / $total >= 0.8) {
        $estrelas++;
    }
    if ($jogoAtivo['fase'] >= 3) {
        $estrelas++;
    }

    require_once __DIR__ . '/../classes/Conexao.php';
    require_once __DIR__ . '/../classes/Jogo.php';
    try {
        $jogo = new Jogo(Conexao::obter());
        $idPartida = $jogo->registrarPartida(
            (int) $_SESSION['usuario_id'],
            $jogoAtivo['materia'],
            $jogoAtivo['fase'],
            $jogoAtivo['pontuacao'],
            $jogoAtivo['acertos'],
            $total,
            $estrelas
        );
        $resultadoFinal = [
            'id_partida' => $idPartida,
            'pontuacao' => $jogoAtivo['pontuacao'],
            'acertos' => $jogoAtivo['acertos'],
            'total' => $total,
            'estrelas_ganhas' => $estrelas,
            'partida_perfeita' => $jogoAtivo['acertos'] === $total,
            'mensagem_flor' => $jogoAtivo['acertos'] === $total
                ? 'Perfeito! Você dominou a fase. A Flor está muito orgulhosa! 🦊'
                : 'Aventura concluída! Cada pergunta ensinou algo novo. Vamos para a próxima! 🦊',
        ];
        $_SESSION['jogo_finalizado'] = $resultadoFinal;
        unset($_SESSION['jogo_ativo']);
        responderPartida(201, $resultadoFinal);
    } catch (PDOException $erro) {
        error_log('FLUX: falha ao salvar partida: ' . $erro->getMessage());
        responderPartida(503, ['erro' => 'Não foi possível salvar o resultado. Sua partida continua ativa; tente finalizar novamente.']);
    } catch (Throwable $erro) {
        error_log('FLUX: falha ao finalizar partida: ' . $erro->getMessage());
        responderPartida(500, ['erro' => 'Ocorreu um erro ao salvar o resultado. Tente novamente.']);
    }
}

responderPartida(400, ['erro' => 'Ação desconhecida.']);
