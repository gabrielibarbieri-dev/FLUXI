<?php
declare(strict_types=1);

final class Jogo
{
    public const MATERIAS = [
        'matematica' => 'Matemática',
        'portugues' => 'Português',
    ];

    public const FASES = [
        1 => ['nome' => 'Fácil', 'descricao' => 'Comece sua jornada com desafios para aquecer.'],
        2 => ['nome' => 'Médio', 'descricao' => 'Combine conhecimentos e avance um pouco mais.'],
        3 => ['nome' => 'Difícil', 'descricao' => 'Mostre tudo o que aprendeu em desafios avançados.'],
        4 => ['nome' => 'Desafio', 'descricao' => 'A fase final! Confio em você.'],
    ];

    private const QUESTOES_POR_PARTIDA = 5;
    private const ACERTOS_PARA_VITORIA = 3;
    public const NIVEIS = [
        1 => ['nome' => 'Iniciante', 'xp' => 0],
        2 => ['nome' => 'Explorador', 'xp' => 100],
        3 => ['nome' => 'Aprendiz', 'xp' => 300],
        4 => ['nome' => 'Especialista', 'xp' => 600],
        5 => ['nome' => 'Mestre', 'xp' => 1000],
    ];

    public function __construct(private PDO $conexao)
    {
    }

    public function obterResumo(int $usuarioId): array
    {
        $usuario = $this->conexao->prepare(
            'SELECT nivel, estrelas FROM usuarios WHERE id = :id'
        );
        $usuario->execute(['id' => $usuarioId]);
        $dadosUsuario = $usuario->fetch();

        if ($dadosUsuario === false) {
            throw new RuntimeException('Não foi possível localizar a conta do jogador.');
        }

        $partidas = $this->conexao->prepare(
            'SELECT COUNT(*) AS total,
                    COALESCE(MAX(pontuacao), 0) AS melhor_resultado,
                    COALESCE(SUM(acertos), 0) AS acertos,
                    COALESCE(SUM(total_questoes), 0) AS questoes,
                    COALESCE(SUM(pontuacao), 0) AS experiencia,
                    COALESCE(SUM(CASE WHEN acertos >= :acertos_vitoria THEN 1 ELSE 0 END), 0) AS vitorias,
                    COALESCE(SUM(CASE WHEN materia = :matematica THEN acertos ELSE 0 END), 0) AS matematica_acertos,
                    COALESCE(SUM(CASE WHEN materia = :matematica_questoes THEN total_questoes ELSE 0 END), 0) AS matematica_questoes,
                    COALESCE(SUM(CASE WHEN materia = :portugues THEN acertos ELSE 0 END), 0) AS portugues_acertos,
                    COALESCE(SUM(CASE WHEN materia = :portugues_questoes THEN total_questoes ELSE 0 END), 0) AS portugues_questoes
             FROM partidas WHERE usuario_id = :id'
        );
        $partidas->execute([
            'acertos_vitoria' => self::ACERTOS_PARA_VITORIA,
            'matematica' => 'matematica',
            'matematica_questoes' => 'matematica',
            'portugues' => 'portugues',
            'portugues_questoes' => 'portugues',
            'id' => $usuarioId,
        ]);
        $resumoPartidas = $partidas->fetch();

        $fases = $this->conexao->prepare(
            'SELECT COUNT(*) FROM progresso WHERE usuario_id = :id AND concluida = 1'
        );
        $fases->execute(['id' => $usuarioId]);

        $experiencia = (int) $resumoPartidas['experiencia'];
        $dadosNivel = self::obterDadosNivel($experiencia);
        $matematicaQuestoes = (int) $resumoPartidas['matematica_questoes'];
        $portuguesQuestoes = (int) $resumoPartidas['portugues_questoes'];
        $acertos = (int) $resumoPartidas['acertos'];
        $questoes = (int) $resumoPartidas['questoes'];

        return [
            'nivel' => $dadosNivel['nivel'],
            'titulo_nivel' => $dadosNivel['titulo'],
            'estrelas' => (int) $dadosUsuario['estrelas'],
            'partidas' => (int) $resumoPartidas['total'],
            'melhor_resultado' => (int) $resumoPartidas['melhor_resultado'],
            'vitorias' => (int) $resumoPartidas['vitorias'],
            'acertos' => $acertos,
            'erros' => max(0, $questoes - $acertos),
            'questoes' => $questoes,
            'desempenho' => $questoes === 0 ? 0 : (int) round($acertos / $questoes * 100),
            'matematica' => [
                'acertos' => (int) $resumoPartidas['matematica_acertos'],
                'questoes' => $matematicaQuestoes,
                'erros' => max(0, $matematicaQuestoes - (int) $resumoPartidas['matematica_acertos']),
                'desempenho' => $matematicaQuestoes === 0 ? 0 : (int) round((int) $resumoPartidas['matematica_acertos'] / $matematicaQuestoes * 100),
            ],
            'portugues' => [
                'acertos' => (int) $resumoPartidas['portugues_acertos'],
                'questoes' => $portuguesQuestoes,
                'erros' => max(0, $portuguesQuestoes - (int) $resumoPartidas['portugues_acertos']),
                'desempenho' => $portuguesQuestoes === 0 ? 0 : (int) round((int) $resumoPartidas['portugues_acertos'] / $portuguesQuestoes * 100),
            ],
            'experiencia' => $experiencia,
            'experiencia_no_nivel' => $dadosNivel['experiencia_no_nivel'],
            'experiencia_para_proximo_nivel' => $dadosNivel['experiencia_para_proximo_nivel'],
            'progresso_nivel' => $dadosNivel['progresso'],
            'proximo_nivel' => $dadosNivel['proximo_nivel'],
            'fases_concluidas' => (int) $fases->fetchColumn(),
            'fases_totais' => count(self::MATERIAS) * count(self::FASES),
        ];
    }

    public static function obterDadosNivel(int $experiencia): array
    {
        $experiencia = max(0, $experiencia);
        $nivel = 1;
        foreach (self::NIVEIS as $numero => $dados) {
            if ($experiencia >= $dados['xp']) {
                $nivel = $numero;
            }
        }

        $atual = self::NIVEIS[$nivel];
        $seguinte = self::NIVEIS[$nivel + 1] ?? null;
        if ($seguinte === null) {
            return [
                'nivel' => $nivel,
                'titulo' => $atual['nome'],
                'experiencia_no_nivel' => $experiencia - $atual['xp'],
                'experiencia_para_proximo_nivel' => null,
                'progresso' => 100,
                'proximo_nivel' => null,
            ];
        }

        $tamanhoFaixa = $seguinte['xp'] - $atual['xp'];
        $experienciaNoNivel = $experiencia - $atual['xp'];

        return [
            'nivel' => $nivel,
            'titulo' => $atual['nome'],
            'experiencia_no_nivel' => $experienciaNoNivel,
            'experiencia_para_proximo_nivel' => $tamanhoFaixa,
            'progresso' => (int) round($experienciaNoNivel / $tamanhoFaixa * 100),
            'proximo_nivel' => $seguinte['nome'],
        ];
    }

    public function listarFases(int $usuarioId, string $materia): array
    {
        $this->validarMateria($materia);
        $consulta = $this->conexao->prepare(
            'SELECT fase, partidas, melhor_pontuacao, acertos, total_questoes, concluida
             FROM progresso WHERE usuario_id = :usuario_id AND materia = :materia'
        );
        $consulta->execute(['usuario_id' => $usuarioId, 'materia' => $materia]);
        $progresso = [];
        foreach ($consulta->fetchAll() as $registro) {
            $progresso[(int) $registro['fase']] = $registro;
        }

        $fases = [];
        foreach (self::FASES as $numero => $fase) {
            $dados = $progresso[$numero] ?? null;
            $desbloqueada = $numero === 1
                || !empty($progresso[$numero - 1]['concluida']);
            $fases[] = [
                'numero' => $numero,
                'nome' => $fase['nome'],
                'descricao' => $fase['descricao'],
                'desbloqueada' => $desbloqueada,
                'concluida' => $dados !== null && (bool) $dados['concluida'],
                'melhor_pontuacao' => $dados === null ? 0 : (int) $dados['melhor_pontuacao'],
                'partidas' => $dados === null ? 0 : (int) $dados['partidas'],
            ];
        }

        return $fases;
    }

    public function obterPerguntas(string $materia, int $fase): array
    {
        $this->validarFase($fase);
        $this->validarMateria($materia);

        $consulta = $this->conexao->prepare(
            'SELECT id, pergunta, alternativa_a, alternativa_b, alternativa_c, alternativa_d,
                    resposta_correta, explicacao, pontos
             FROM perguntas
             WHERE materia = :materia AND fase = :fase AND ativa = 1
             ORDER BY RAND()
             LIMIT ' . self::QUESTOES_POR_PARTIDA
        );
        $consulta->execute(['materia' => $materia, 'fase' => $fase]);
        $perguntas = $consulta->fetchAll();

        if (count($perguntas) < self::QUESTOES_POR_PARTIDA) {
            throw new RuntimeException('Esta fase ainda não tem perguntas suficientes. Importe o banco atualizado.');
        }

        return $perguntas;
    }

    public function registrarPartida(
        int $usuarioId,
        string $materia,
        int $fase,
        int $pontuacao,
        int $acertos,
        int $totalQuestoes,
        int $estrelas
    ): int {
        $this->validarMateria($materia);
        $this->validarFase($fase);
        if ($totalQuestoes !== self::QUESTOES_POR_PARTIDA
            || $acertos < 0 || $acertos > $totalQuestoes
            || $pontuacao < 0 || $estrelas < 1 || $estrelas > 3) {
            throw new InvalidArgumentException('Os dados da partida são inválidos.');
        }

        $this->conexao->beginTransaction();
        try {
            $bloqueio = $this->conexao->prepare(
                'SELECT estrelas FROM usuarios WHERE id = :id FOR UPDATE'
            );
            $bloqueio->execute(['id' => $usuarioId]);
            $estrelasAtuais = $bloqueio->fetchColumn();
            if ($estrelasAtuais === false) {
                throw new RuntimeException('Não foi possível localizar a conta do jogador.');
            }

            $partida = $this->conexao->prepare(
                'INSERT INTO partidas (usuario_id, materia, fase, pontuacao, acertos, total_questoes, estrelas_ganhas)
                 VALUES (:usuario_id, :materia, :fase, :pontuacao, :acertos, :total_questoes, :estrelas)'
            );
            $partida->execute([
                'usuario_id' => $usuarioId,
                'materia' => $materia,
                'fase' => $fase,
                'pontuacao' => $pontuacao,
                'acertos' => $acertos,
                'total_questoes' => $totalQuestoes,
                'estrelas' => $estrelas,
            ]);
            $idPartida = (int) $this->conexao->lastInsertId();

            $progresso = $this->conexao->prepare(
                'INSERT INTO progresso (usuario_id, materia, fase, partidas, melhor_pontuacao, acertos, total_questoes, concluida)
                 VALUES (:usuario_id, :materia, :fase, 1, :pontuacao, :acertos, :total_questoes, 1)
                 ON DUPLICATE KEY UPDATE
                    partidas = progresso.partidas + 1,
                    melhor_pontuacao = GREATEST(progresso.melhor_pontuacao, VALUES(melhor_pontuacao)),
                    acertos = progresso.acertos + VALUES(acertos),
                    total_questoes = progresso.total_questoes + VALUES(total_questoes),
                    concluida = 1'
            );
            $progresso->execute([
                'usuario_id' => $usuarioId,
                'materia' => $materia,
                'fase' => $fase,
                'pontuacao' => $pontuacao,
                'acertos' => $acertos,
                'total_questoes' => $totalQuestoes,
            ]);

            $experienciaAtual = $this->conexao->prepare(
                'SELECT COALESCE(SUM(pontuacao), 0) FROM partidas WHERE usuario_id = :id'
            );
            $experienciaAtual->execute(['id' => $usuarioId]);
            $nivel = self::obterDadosNivel((int) $experienciaAtual->fetchColumn() + $pontuacao)['nivel'];
            $atualizarUsuario = $this->conexao->prepare(
                'UPDATE usuarios SET estrelas = estrelas + :estrelas, nivel = GREATEST(nivel, :nivel) WHERE id = :id'
            );
            $atualizarUsuario->execute([
                'estrelas' => $estrelas,
                'nivel' => $nivel,
                'id' => $usuarioId,
            ]);

            $this->conexao->commit();

            return $idPartida;
        } catch (Throwable $erro) {
            if ($this->conexao->inTransaction()) {
                $this->conexao->rollBack();
            }
            throw $erro;
        }
    }

    public function listarPartidas(int $usuarioId, int $limite = 10): array
    {
        $limite = max(1, min($limite, 50));
        $consulta = $this->conexao->prepare(
            'SELECT materia, fase, pontuacao, acertos, total_questoes, estrelas_ganhas, criada_em
             FROM partidas WHERE usuario_id = :usuario_id
             ORDER BY criada_em DESC, id DESC LIMIT ' . $limite
        );
        $consulta->execute(['usuario_id' => $usuarioId]);

        return $consulta->fetchAll();
    }

    private function validarMateria(string $materia): void
    {
        if (!array_key_exists($materia, self::MATERIAS)) {
            throw new InvalidArgumentException('Matéria inválida.');
        }
    }

    private function validarFase(int $fase): void
    {
        if (!array_key_exists($fase, self::FASES)) {
            throw new InvalidArgumentException('Fase inválida.');
        }
    }
}
