CREATE DATABASE IF NOT EXISTS flux
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE flux;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    senha VARCHAR(255) NOT NULL,
    tipo ENUM('aluno', 'professor') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'aluno',
    nivel INT UNSIGNED NOT NULL DEFAULT 1,
    estrelas INT UNSIGNED NOT NULL DEFAULT 0,
    data_cadastro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuarios_email (email)
) ENGINE=InnoDB
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS professor_alunos (
  professor_id INT UNSIGNED NOT NULL,
  aluno_id INT UNSIGNED NOT NULL,
  vinculado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (professor_id, aluno_id),
  UNIQUE KEY uq_professor_alunos_aluno (aluno_id),
  CONSTRAINT fk_professor_alunos_professor
    FOREIGN KEY (professor_id) REFERENCES usuarios (id) ON DELETE CASCADE,
  CONSTRAINT fk_professor_alunos_aluno
    FOREIGN KEY (aluno_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

SET @adicionar_limite_professor_aluno = IF(
  (SELECT COUNT(*) FROM information_schema.statistics
   WHERE table_schema = DATABASE()
     AND table_name = 'professor_alunos'
     AND index_name = 'uq_professor_alunos_aluno') = 0,
  'ALTER TABLE professor_alunos ADD UNIQUE KEY uq_professor_alunos_aluno (aluno_id)',
  'SELECT 1'
);
PREPARE atualizar_limite_professor_aluno FROM @adicionar_limite_professor_aluno;
EXECUTE atualizar_limite_professor_aluno;
DEALLOCATE PREPARE atualizar_limite_professor_aluno;

CREATE TABLE IF NOT EXISTS convites_professor (
  id TINYINT UNSIGNED NOT NULL DEFAULT 1,
  codigo_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

INSERT IGNORE INTO convites_professor (id, codigo_hash)
VALUES (1, '860fdd448dcc6fa3b180b4b11d4268ab20005367e81b34832e3b99f65e864d8a');

CREATE TABLE IF NOT EXISTS perguntas (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  codigo VARCHAR(40) NOT NULL,
  materia ENUM('matematica', 'portugues') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  fase TINYINT UNSIGNED NOT NULL,
  pergunta TEXT NOT NULL,
  alternativa_a VARCHAR(500) NOT NULL,
  alternativa_b VARCHAR(500) NOT NULL,
  alternativa_c VARCHAR(500) NOT NULL,
  alternativa_d VARCHAR(500) NOT NULL,
  resposta_correta CHAR(1) NOT NULL,
  explicacao TEXT NOT NULL,
  pontos SMALLINT UNSIGNED NOT NULL DEFAULT 10,
  ativa TINYINT NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uq_perguntas_codigo (codigo),
  KEY idx_perguntas_materia_fase (materia, fase, ativa)
) ENGINE=InnoDB
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS partidas (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario_id INT UNSIGNED NOT NULL,
  materia ENUM('matematica', 'portugues') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  fase TINYINT UNSIGNED NOT NULL,
  pontuacao INT UNSIGNED NOT NULL DEFAULT 0,
  acertos TINYINT UNSIGNED NOT NULL DEFAULT 0,
  total_questoes TINYINT UNSIGNED NOT NULL DEFAULT 5,
  estrelas_ganhas TINYINT UNSIGNED NOT NULL DEFAULT 1,
  criada_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_partidas_usuario_data (usuario_id, criada_em),
  KEY idx_partidas_usuario_pontuacao (usuario_id, pontuacao),
  CONSTRAINT fk_partidas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS progresso (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario_id INT UNSIGNED NOT NULL,
  materia ENUM('matematica', 'portugues') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  fase TINYINT UNSIGNED NOT NULL,
  partidas INT UNSIGNED NOT NULL DEFAULT 0,
  melhor_pontuacao INT UNSIGNED NOT NULL DEFAULT 0,
  acertos INT UNSIGNED NOT NULL DEFAULT 0,
  total_questoes INT UNSIGNED NOT NULL DEFAULT 0,
  concluida TINYINT NOT NULL DEFAULT 0,
  atualizada_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_progresso_usuario_materia_fase (usuario_id, materia, fase),
  CONSTRAINT fk_progresso_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

ALTER TABLE perguntas
  MODIFY ativa TINYINT NOT NULL DEFAULT 1;

ALTER TABLE progresso
  MODIFY concluida TINYINT NOT NULL DEFAULT 0;

INSERT INTO perguntas
  (codigo, materia, fase, pergunta, alternativa_a, alternativa_b, alternativa_c, alternativa_d, resposta_correta, explicacao, pontos)
VALUES
  ('mat-f1-01', 'matematica', 1, 'Quanto é 27 + 15?', '32', '42', '43', '52', 'B', 'Somando as unidades, 7 + 5 = 12. Com a dezena que vai, 2 + 1 + 1 = 4 dezenas: 42.', 10),
  ('mat-f1-02', 'matematica', 1, 'Qual é o resultado de 8 × 7?', '54', '56', '64', '48', 'B', 'A tabuada do 8 mostra que 8 × 7 = 56.', 10),
  ('mat-f1-03', 'matematica', 1, 'Quanto é 96 ÷ 8?', '10', '11', '12', '14', 'C', 'Como 8 × 12 = 96, então 96 ÷ 8 = 12.', 10),
  ('mat-f1-04', 'matematica', 1, 'Quanto é 70 − 28?', '48', '52', '42', '38', 'C', 'Subtraindo 28 de 70, obtemos 42.', 10),
  ('mat-f1-05', 'matematica', 1, 'Uma caixa tem 6 lápis. Quantos lápis há em 4 caixas iguais?', '20', '24', '26', '30', 'B', 'São 4 grupos de 6 lápis: 4 × 6 = 24.', 10),

  ('mat-f2-01', 'matematica', 2, 'Quanto é 6 + 4 × 3?', '30', '18', '24', '22', 'B', 'A multiplicação vem antes da adição: 4 × 3 = 12; depois, 6 + 12 = 18.', 15),
  ('mat-f2-02', 'matematica', 2, 'Quanto é 3/4 de 20?', '12', '15', '16', '18', 'B', 'Divida 20 por 4 para achar cada parte (5) e multiplique por 3: 15.', 15),
  ('mat-f2-03', 'matematica', 2, 'Uma camiseta custa R$ 80 e recebeu desconto de R$ 20. Qual é o novo preço?', 'R$ 50', 'R$ 55', 'R$ 60', 'R$ 65', 'C', 'O preço com desconto é 80 − 20 = 60 reais.', 15),
  ('mat-f2-04', 'matematica', 2, 'Qual número completa a igualdade 5 × □ = 45?', '7', '8', '9', '10', 'C', 'Dividindo 45 por 5, encontramos 9. Portanto, 5 × 9 = 45.', 15),
  ('mat-f2-05', 'matematica', 2, 'Uma aula começa às 13h40 e dura 50 minutos. A que horas termina?', '14h20', '14h30', '14h40', '15h30', 'B', 'Somando 20 minutos até 14h e mais 30 minutos, a aula termina às 14h30.', 15),

  ('mat-f3-01', 'matematica', 3, 'Quanto é 2³ + 5?', '11', '13', '15', '16', 'B', '2³ significa 2 × 2 × 2 = 8. Então, 8 + 5 = 13.', 20),
  ('mat-f3-02', 'matematica', 3, 'Um retângulo tem lados de 8 cm e 5 cm. Qual é seu perímetro?', '13 cm', '26 cm', '40 cm', '80 cm', 'B', 'O perímetro é a soma dos quatro lados: 8 + 5 + 8 + 5 = 26 cm.', 20),
  ('mat-f3-03', 'matematica', 3, 'Quanto é 25% de 120?', '25', '30', '35', '40', 'B', '25% corresponde a um quarto. Um quarto de 120 é 120 ÷ 4 = 30.', 20),
  ('mat-f3-04', 'matematica', 3, 'Se x + 17 = 42, qual é o valor de x?', '25', '29', '35', '59', 'A', 'Subtraia 17 dos dois lados: x = 42 − 17 = 25.', 20),
  ('mat-f3-05', 'matematica', 3, 'Qual fração é equivalente a 3/5?', '6/15', '9/15', '12/25', '15/30', 'B', 'Multiplicando o numerador e o denominador de 3/5 por 3, obtemos 9/15.', 20),

  ('mat-f4-01', 'matematica', 4, 'Qual é a raiz quadrada de 144?', '10', '11', '12', '14', 'C', '12 × 12 = 144, então a raiz quadrada de 144 é 12.', 30),
  ('mat-f4-02', 'matematica', 4, 'Uma mochila de R$ 150 teve aumento de 20%. Qual é o preço após o aumento?', 'R$ 160', 'R$ 170', 'R$ 180', 'R$ 200', 'C', '20% de 150 é 30. Somando o aumento: 150 + 30 = 180 reais.', 30),
  ('mat-f4-03', 'matematica', 4, 'Resolva: 3 × (8 + 4) ÷ 2.', '12', '18', '24', '36', 'B', 'Primeiro os parênteses: 8 + 4 = 12. Depois, 3 × 12 ÷ 2 = 36 ÷ 2 = 18.', 30),
  ('mat-f4-04', 'matematica', 4, 'Uma receita usa 2/3 de xícara de leite por porção. Quanto é necessário para 3 porções?', '1 xícara', '1⅔ xícara', '2 xícaras', '3 xícaras', 'C', 'Multiplicamos 2/3 por 3: são 6/3, ou seja, 2 xícaras.', 30),
  ('mat-f4-05', 'matematica', 4, 'Um ônibus percorre 180 km em 3 horas, sempre à mesma velocidade. Quantos quilômetros percorre em 5 horas?', '240 km', '270 km', '300 km', '360 km', 'C', 'A velocidade é 180 ÷ 3 = 60 km/h. Em 5 horas, percorre 60 × 5 = 300 km.', 30),

  ('por-f1-01', 'portugues', 1, 'Na palavra “café”, qual é a sílaba tônica?', 'ca', 'fé', 'cá', 'A palavra não tem sílaba tônica', 'B', 'A sílaba pronunciada com mais força é “fé”: ca-FÉ.', 10),
  ('por-f1-02', 'portugues', 1, 'Quantas sílabas tem a palavra “janela”?', '1', '2', '3', '4', 'C', 'A palavra se separa em ja-ne-la: são três sílabas.', 10),
  ('por-f1-03', 'portugues', 1, 'Qual palavra é um substantivo?', 'correr', 'bonito', 'cidade', 'rapidamente', 'C', '“Cidade” nomeia um lugar, por isso é um substantivo.', 10),
  ('por-f1-04', 'portugues', 1, 'Qual substantivo nomeia a estrela que ilumina a Terra: “O ___ está brilhando”?', 'sol', 'sal', 'sul', 'seu', 'A', '“Sol” é o nome da estrela que ilumina a Terra.', 10),
  ('por-f1-05', 'portugues', 1, 'Qual palavra rima com “coração”?', 'cantar', 'balão', 'caderno', 'janela', 'B', '“Coração” e “balão” terminam com sons semelhantes: ão.', 10),

  ('por-f2-01', 'portugues', 2, 'A palavra “também” é classificada como:', 'oxítona', 'paroxítona', 'proparoxítona', 'monossílabo átono', 'A', 'A sílaba tônica é a última (bém), portanto “também” é oxítona.', 15),
  ('por-f2-02', 'portugues', 2, 'Qual palavra é paroxítona?', 'café', 'sofá', 'lápis', 'também', 'C', 'Em “lápis”, a sílaba tônica é a penúltima: LÁ-pis. É uma paroxítona.', 15),
  ('por-f2-03', 'portugues', 2, 'Qual alternativa está escrita corretamente?', 'excessão', 'exceção', 'esceção', 'excesssão', 'B', 'A grafia correta é “exceção”, com xc e ç.', 15),
  ('por-f2-04', 'portugues', 2, 'Na frase “As crianças brincaram”, qual palavra é um verbo?', 'As', 'crianças', 'brincaram', 'crianças brincaram', 'C', '“Brincaram” indica uma ação que aconteceu no passado: é um verbo.', 15),
  ('por-f2-05', 'portugues', 2, 'Qual é o plural de “papel”?', 'papels', 'papéus', 'papéis', 'papeles', 'C', 'O plural de “papel” é “papéis”.', 15),

  ('por-f3-01', 'portugues', 3, 'Qual palavra é proparoxítona?', 'café', 'janela', 'médico', 'também', 'C', '“Médico” tem a sílaba tônica na antepenúltima: MÉ-di-co. É proparoxítona.', 20),
  ('por-f3-02', 'portugues', 3, 'Leia: “Bia levou um guarda-chuva porque o céu estava escuro.” Por que Bia levou o guarda-chuva?', 'Porque estava sol', 'Porque o céu estava escuro', 'Porque ia à escola', 'Porque o guarda-chuva era novo', 'B', 'O texto informa que Bia levou o guarda-chuva porque o céu estava escuro.', 20),
  ('por-f3-03', 'portugues', 3, 'Qual palavra é formada por derivação com o prefixo “re-”?', 'reler', 'livraria', 'pedreiro', 'florista', 'A', '“Re-” é um prefixo que indica repetição; “reler” é ler novamente.', 20),
  ('por-f3-04', 'portugues', 3, 'Na expressão “meninas curiosas”, a palavra “curiosas” é:', 'substantivo', 'verbo', 'adjetivo', 'artigo', 'C', '“Curiosas” caracteriza o substantivo “meninas”, por isso é um adjetivo.', 20),
  ('por-f3-05', 'portugues', 3, 'Qual frase apresenta a pontuação adequada para uma pergunta?', 'Onde você mora.', 'Onde você mora?', 'Onde, você mora!', 'Onde você mora,', 'B', 'Perguntas diretas são encerradas com ponto de interrogação.', 20),

  ('por-f4-01', 'portugues', 4, 'Na frase “Embora estivesse cansada, Lia terminou o trabalho”, o que a palavra “embora” expressa?', 'Uma conclusão', 'Uma oposição/concessão', 'Uma explicação de lugar', 'Uma enumeração', 'B', '“Embora” introduz uma ideia de concessão: estar cansada não impediu Lia de terminar.', 30),
  ('por-f4-02', 'portugues', 4, 'Qual alternativa apresenta uma palavra oxítona acentuada corretamente?', 'lapis', 'arvore', 'parabens', 'avó', 'D', '“Avó” é oxítona terminada em “o” e recebe acento. As demais não estão grafadas corretamente.', 30),
  ('por-f4-03', 'portugues', 4, 'Na frase “O livro que ganhei é divertido”, a palavra “que” retoma:', 'O livro', 'Eu', 'Ganhei', 'Divertido', 'A', '“Que” retoma “o livro” e introduz uma informação sobre ele.', 30),
  ('por-f4-04', 'portugues', 4, 'Qual palavra foi formada pela junção de “passa” e “tempo”?', 'passatempo', 'passatempoo', 'passa-tempos', 'tempo passa', 'A', '“Passatempo” é formada pela junção de “passa” e “tempo”.', 30),
  ('por-f4-05', 'portugues', 4, 'Leia: “A biblioteca, silenciosa e iluminada, convidava todos à leitura.” Qual é a ideia principal?', 'A biblioteca estava fechada', 'A biblioteca era um ambiente convidativo para ler', 'Todos precisavam sair da biblioteca', 'A leitura foi interrompida', 'B', 'As características “silenciosa e iluminada” mostram que o ambiente convidava as pessoas à leitura.', 30)
AS nova_pergunta
ON DUPLICATE KEY UPDATE
  pergunta = nova_pergunta.pergunta,
  alternativa_a = nova_pergunta.alternativa_a,
  alternativa_b = nova_pergunta.alternativa_b,
  alternativa_c = nova_pergunta.alternativa_c,
  alternativa_d = nova_pergunta.alternativa_d,
  resposta_correta = nova_pergunta.resposta_correta,
  explicacao = nova_pergunta.explicacao,
  pontos = nova_pergunta.pontos,
  ativa = 1;
