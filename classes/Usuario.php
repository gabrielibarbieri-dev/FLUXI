<?php
declare(strict_types=1);

final class Usuario
{
    public function __construct(private PDO $conexao)
    {
    }

    public function buscarPorEmail(string $email): ?array
    {
        $consulta = $this->conexao->prepare(
            'SELECT id, nome, email, senha, tipo, nivel, estrelas FROM usuarios WHERE email = :email LIMIT 1'
        );
        $consulta->execute(['email' => mb_strtolower(trim($email), 'UTF-8')]);
        $usuario = $consulta->fetch();

        return $usuario === false ? null : $usuario;
    }

    public function buscarPorId(int $id): ?array
    {
        $consulta = $this->conexao->prepare(
            'SELECT id, nome, email, tipo, nivel, estrelas, data_cadastro FROM usuarios WHERE id = :id LIMIT 1'
        );
        $consulta->execute(['id' => $id]);
        $usuario = $consulta->fetch();

        return $usuario === false ? null : $usuario;
    }

    public function listarAlunosComRendimento(int $professorId): array
    {
        $consulta = $this->conexao->prepare(
            "SELECT u.id, u.nome, u.email,
                    COALESCE(p.partidas, 0) AS partidas,
                    COALESCE(p.acertos, 0) AS acertos,
                    COALESCE(p.questoes, 0) AS questoes,
                    COALESCE(p.matematica_acertos, 0) AS matematica_acertos,
                    COALESCE(p.matematica_questoes, 0) AS matematica_questoes,
                    COALESCE(p.portugues_acertos, 0) AS portugues_acertos,
                    COALESCE(p.portugues_questoes, 0) AS portugues_questoes,
                    COALESCE(f.fases_concluidas, 0) AS fases_concluidas,
                    p.ultima_partida
             FROM usuarios u
             LEFT JOIN (
                SELECT usuario_id,
                       COUNT(*) AS partidas,
                       SUM(acertos) AS acertos,
                       SUM(total_questoes) AS questoes,
                       SUM(CASE WHEN materia = 'matematica' THEN acertos ELSE 0 END) AS matematica_acertos,
                       SUM(CASE WHEN materia = 'matematica' THEN total_questoes ELSE 0 END) AS matematica_questoes,
                       SUM(CASE WHEN materia = 'portugues' THEN acertos ELSE 0 END) AS portugues_acertos,
                       SUM(CASE WHEN materia = 'portugues' THEN total_questoes ELSE 0 END) AS portugues_questoes,
                       MAX(criada_em) AS ultima_partida
                FROM partidas
                GROUP BY usuario_id
             ) p ON p.usuario_id = u.id
             LEFT JOIN (
                SELECT usuario_id, SUM(CASE WHEN concluida = 1 THEN 1 ELSE 0 END) AS fases_concluidas
                FROM progresso
                GROUP BY usuario_id
             ) f ON f.usuario_id = u.id
             INNER JOIN professor_alunos pa ON pa.aluno_id = u.id
             WHERE u.tipo = 'aluno' AND pa.professor_id = :professor_id
             ORDER BY u.nome, u.id"
        );
        $consulta->execute(['professor_id' => $professorId]);

        return $consulta->fetchAll();
    }

    public function contarAlunosVinculados(int $professorId): int
    {
        $consulta = $this->conexao->prepare(
            'SELECT COUNT(*) FROM professor_alunos WHERE professor_id = :professor_id'
        );
        $consulta->execute(['professor_id' => $professorId]);

        return (int) $consulta->fetchColumn();
    }

    public function listarProfessoresComVagas(): array
    {
        $consulta = $this->conexao->query(
            "SELECT u.id, u.nome, COUNT(pa.aluno_id) AS alunos_vinculados
             FROM usuarios u
             LEFT JOIN professor_alunos pa ON pa.professor_id = u.id
             WHERE u.tipo = 'professor'
             GROUP BY u.id, u.nome
             HAVING COUNT(pa.aluno_id) < 10
             ORDER BY u.nome, u.id"
        );

        return $consulta->fetchAll();
    }

    public function listarProfessoresDoAluno(int $alunoId): array
    {
        $consulta = $this->conexao->prepare(
            "SELECT u.id, u.nome, pa.vinculado_em
             FROM professor_alunos pa
             INNER JOIN usuarios u ON u.id = pa.professor_id
             WHERE pa.aluno_id = :aluno_id AND u.tipo = 'professor'
             ORDER BY u.nome, u.id"
        );
        $consulta->execute(['aluno_id' => $alunoId]);

        return $consulta->fetchAll();
    }

    public function vincularAlunoAProfessor(int $alunoId, int $professorId): void
    {
        $this->conexao->beginTransaction();
        try {
            $professor = $this->conexao->prepare(
                "SELECT id FROM usuarios WHERE id = :id AND tipo = 'professor' FOR UPDATE"
            );
            $professor->execute(['id' => $professorId]);
            if ($professor->fetchColumn() === false) {
                throw new RuntimeException('O professor selecionado não está disponível. Escolha outro professor.');
            }

            $aluno = $this->conexao->prepare(
                "SELECT id FROM usuarios WHERE id = :id AND tipo = 'aluno' FOR UPDATE"
            );
            $aluno->execute(['id' => $alunoId]);
            if ($aluno->fetchColumn() === false) {
                throw new RuntimeException('Não foi possível localizar sua conta de aluno.');
            }

            $vinculoExistente = $this->conexao->prepare(
                'SELECT 1 FROM professor_alunos WHERE aluno_id = :aluno_id LIMIT 1'
            );
            $vinculoExistente->execute(['aluno_id' => $alunoId]);
            if ($vinculoExistente->fetchColumn() !== false) {
                throw new RuntimeException('Você já está vinculado a um professor. Cada aluno pode ter apenas um professor.');
            }

            if ($this->contarAlunosVinculados($professorId) >= 10) {
                throw new RuntimeException('Esse professor atingiu o limite de 10 alunos. Escolha outro professor.');
            }

            $inserir = $this->conexao->prepare(
                'INSERT INTO professor_alunos (professor_id, aluno_id) VALUES (:professor_id, :aluno_id)'
            );
            $inserir->execute(['professor_id' => $professorId, 'aluno_id' => $alunoId]);
            $this->conexao->commit();
        } catch (Throwable $erro) {
            if ($this->conexao->inTransaction()) {
                $this->conexao->rollBack();
            }
            throw $erro;
        }
    }

    public function vincularAlunoPorEmail(int $professorId, string $email): void
    {
        $email = mb_strtolower(trim($email), 'UTF-8');
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || mb_strlen($email, 'UTF-8') > 190) {
            throw new InvalidArgumentException('Informe um e-mail válido para localizar o aluno.');
        }

        $this->conexao->beginTransaction();
        try {
            $professor = $this->conexao->prepare(
                "SELECT id FROM usuarios WHERE id = :id AND tipo = 'professor' FOR UPDATE"
            );
            $professor->execute(['id' => $professorId]);
            if ($professor->fetchColumn() === false) {
                throw new RuntimeException('A conta de professor não foi encontrada.');
            }

            if ($this->contarAlunosVinculados($professorId) >= 10) {
                throw new RuntimeException('Você já atingiu o limite de 10 alunos vinculados.');
            }

            $aluno = $this->conexao->prepare(
                "SELECT id FROM usuarios WHERE email = :email AND tipo = 'aluno' LIMIT 1"
            );
            $aluno->execute(['email' => $email]);
            $alunoId = $aluno->fetchColumn();
            if ($alunoId === false) {
                throw new RuntimeException('Não encontramos uma conta de aluno com esse e-mail.');
            }

            $alunoVinculado = $this->conexao->prepare(
                'SELECT u.id
                 FROM usuarios u
                 INNER JOIN professor_alunos pa ON pa.aluno_id = u.id
                 WHERE u.id = :aluno_id
                 LIMIT 1
                 FOR UPDATE'
            );
            $alunoVinculado->execute(['aluno_id' => (int) $alunoId]);
            if ($alunoVinculado->fetchColumn() !== false) {
                throw new RuntimeException('Esse aluno já está vinculado a um professor e só pode ter um.');
            }

            $vinculo = $this->conexao->prepare(
                'SELECT 1 FROM professor_alunos WHERE professor_id = :professor_id AND aluno_id = :aluno_id LIMIT 1'
            );
            $vinculo->execute(['professor_id' => $professorId, 'aluno_id' => (int) $alunoId]);
            if ($vinculo->fetchColumn() !== false) {
                throw new RuntimeException('Esse aluno já está vinculado à sua conta.');
            }

            $inserir = $this->conexao->prepare(
                'INSERT INTO professor_alunos (professor_id, aluno_id) VALUES (:professor_id, :aluno_id)'
            );
            $inserir->execute(['professor_id' => $professorId, 'aluno_id' => (int) $alunoId]);
            $this->conexao->commit();
        } catch (Throwable $erro) {
            if ($this->conexao->inTransaction()) {
                $this->conexao->rollBack();
            }
            throw $erro;
        }
    }

    public function criarAlunoComProfessor(
        string $nome,
        string $email,
        string $senhaHash,
        ?int $professorId
    ): void {
        if ($professorId === null) {
            $this->criar($nome, $email, $senhaHash, 'aluno');
            return;
        }

        $this->conexao->beginTransaction();
        try {
            $professor = $this->conexao->prepare(
                "SELECT id FROM usuarios WHERE id = :id AND tipo = 'professor' FOR UPDATE"
            );
            $professor->execute(['id' => $professorId]);
            if ($professor->fetchColumn() === false) {
                throw new RuntimeException('O professor selecionado não está disponível. Escolha outro professor.');
            }

            if ($this->contarAlunosVinculados($professorId) >= 10) {
                throw new RuntimeException('Esse professor atingiu o limite de 10 alunos. Escolha outro professor.');
            }

            $aluno = $this->conexao->prepare(
                'INSERT INTO usuarios (nome, email, senha, tipo) VALUES (:nome, :email, :senha, :tipo)'
            );
            $aluno->execute([
                'nome' => trim($nome),
                'email' => mb_strtolower(trim($email), 'UTF-8'),
                'senha' => $senhaHash,
                'tipo' => 'aluno',
            ]);
            $alunoId = (int) $this->conexao->lastInsertId();

            $vinculo = $this->conexao->prepare(
                'INSERT INTO professor_alunos (professor_id, aluno_id) VALUES (:professor_id, :aluno_id)'
            );
            $vinculo->execute(['professor_id' => $professorId, 'aluno_id' => $alunoId]);
            $this->conexao->commit();
        } catch (Throwable $erro) {
            if ($this->conexao->inTransaction()) {
                $this->conexao->rollBack();
            }
            throw $erro;
        }
    }

    public function desvincularAluno(int $professorId, int $alunoId): void
    {
        $this->conexao->beginTransaction();
        try {
            $professor = $this->conexao->prepare(
                "SELECT id FROM usuarios WHERE id = :id AND tipo = 'professor' FOR UPDATE"
            );
            $professor->execute(['id' => $professorId]);
            if ($professor->fetchColumn() === false) {
                throw new RuntimeException('A conta de professor não foi encontrada.');
            }

            $remover = $this->conexao->prepare(
                'DELETE FROM professor_alunos WHERE professor_id = :professor_id AND aluno_id = :aluno_id'
            );
            $remover->execute(['professor_id' => $professorId, 'aluno_id' => $alunoId]);
            if ($remover->rowCount() !== 1) {
                throw new RuntimeException('Esse aluno não está vinculado à sua conta.');
            }

            $this->conexao->commit();
        } catch (Throwable $erro) {
            if ($this->conexao->inTransaction()) {
                $this->conexao->rollBack();
            }
            throw $erro;
        }
    }

    public function desvincularAlunoDoProfessor(int $alunoId): void
    {
        $this->conexao->beginTransaction();
        try {
            $aluno = $this->conexao->prepare(
                "SELECT id FROM usuarios WHERE id = :id AND tipo = 'aluno' FOR UPDATE"
            );
            $aluno->execute(['id' => $alunoId]);
            if ($aluno->fetchColumn() === false) {
                throw new RuntimeException('Não foi possível localizar sua conta de aluno.');
            }

            $remover = $this->conexao->prepare(
                'DELETE FROM professor_alunos WHERE aluno_id = :aluno_id'
            );
            $remover->execute(['aluno_id' => $alunoId]);
            if ($remover->rowCount() !== 1) {
                throw new RuntimeException('Você não está vinculado a um professor.');
            }

            $this->conexao->commit();
        } catch (Throwable $erro) {
            if ($this->conexao->inTransaction()) {
                $this->conexao->rollBack();
            }
            throw $erro;
        }
    }

    public function emailExiste(string $email): bool
    {
        $consulta = $this->conexao->prepare(
            'SELECT 1 FROM usuarios WHERE email = :email LIMIT 1'
        );
        $consulta->execute(['email' => mb_strtolower(trim($email), 'UTF-8')]);

        return $consulta->fetchColumn() !== false;
    }

    public function validarConviteProfessor(string $codigo): bool
    {
        $consulta = $this->conexao->prepare(
            'SELECT codigo_hash FROM convites_professor WHERE id = 1 LIMIT 1'
        );
        $consulta->execute();
        $codigoHash = $consulta->fetchColumn();
        if (!is_string($codigoHash)) {
            return false;
        }

        return hash_equals($codigoHash, hash('sha256', strtoupper(trim($codigo))));
    }

    public function criar(string $nome, string $email, string $senhaHash, string $tipo = 'aluno'): void
    {
        $tipo = in_array($tipo, ['aluno', 'professor'], true) ? $tipo : 'aluno';
        $consulta = $this->conexao->prepare(
            'INSERT INTO usuarios (nome, email, senha, tipo) VALUES (:nome, :email, :senha, :tipo)'
        );
        $consulta->execute([
            'nome' => trim($nome),
            'email' => mb_strtolower(trim($email), 'UTF-8'),
            'senha' => $senhaHash,
            'tipo' => $tipo,
        ]);
    }

    public function atualizarNome(int $id, string $nome): void
    {
        $consulta = $this->conexao->prepare(
            'UPDATE usuarios SET nome = :nome WHERE id = :id'
        );
        $consulta->execute(['nome' => trim($nome), 'id' => $id]);
    }
}
