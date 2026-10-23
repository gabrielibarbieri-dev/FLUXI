<?php
declare(strict_types=1);

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'use_strict_mode' => true,
]);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido. Use GET para consultar o progresso.'], JSON_UNESCAPED_UNICODE);
    exit;
}
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['erro' => 'Sua sessão expirou. Entre novamente.'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/../classes/Conexao.php';
require_once __DIR__ . '/../classes/Jogo.php';

try {
    $jogo = new Jogo(Conexao::obter());
    $resumo = $jogo->obterResumo((int) $_SESSION['usuario_id']);
    $fases = [];
    foreach (array_keys(Jogo::MATERIAS) as $materia) {
        $fases[$materia] = $jogo->listarFases((int) $_SESSION['usuario_id'], $materia);
    }
    echo json_encode([
        'resumo' => $resumo,
        'fases' => $fases,
        'partidas_recentes' => $jogo->listarPartidas((int) $_SESSION['usuario_id']),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (PDOException $erro) {
    error_log('FLUX: falha ao consultar progresso: ' . $erro->getMessage());
    http_response_code(503);
    echo json_encode(['erro' => 'Não foi possível carregar o progresso. Confira a conexão com o banco de dados.'], JSON_UNESCAPED_UNICODE);
} catch (RuntimeException $erro) {
    http_response_code(404);
    echo json_encode(['erro' => $erro->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $erro) {
    error_log('FLUX: erro interno ao consultar progresso: ' . $erro->getMessage());
    http_response_code(500);
    echo json_encode(['erro' => 'Ocorreu um erro interno ao consultar o progresso.'], JSON_UNESCAPED_UNICODE);
}
