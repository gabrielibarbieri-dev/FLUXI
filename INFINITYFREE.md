# Publicar o FLUX no InfinityFree

## 1. Criar o banco

No painel do InfinityFree, crie um banco MySQL e anote o host, o nome do banco,
o usuário e a senha exibidos pelo painel. Não use `localhost` nem os dados do
Laragon: o InfinityFree fornece os valores próprios da conta.

## 2. Importar tabelas e perguntas

Abra o phpMyAdmin pelo painel, selecione o banco criado, escolha **Importar** e
envie `banco/flux-infinityfree.sql`. O arquivo cria as tabelas e carrega as
perguntas iniciais; ele não tenta criar ou selecionar um banco, pois isso é
feito no painel antes da importação.

O arquivo não apaga contas nem progresso existentes. Ao importar novamente,
as perguntas iniciais são atualizadas sem duplicar seus códigos. Faça um backup
antes de qualquer alteração manual no banco.

Se você realmente quiser apagar e reconstruir todas as tabelas do FLUX, importe
`banco/flux-recriar-infinityfree.sql` em vez do arquivo anterior. Esse arquivo
apaga contas, vínculos com professores, partidas, progresso, convites e
perguntas do banco selecionado. Faça um backup antes: os dados apagados não
podem ser recuperados pelo site.

## 3. Configurar a conexão

Antes de enviar o site, copie `config/database.example.php` para
`config/database.php` e preencha os dados anotados no primeiro passo. Mantenha
as aspas e não publique nem compartilhe a senha do banco. O arquivo
`config/database.php` é ignorado pelo Git.

Se a hospedagem permitir configurar variáveis de ambiente, `FLUX_DB_HOST`,
`FLUX_DB_NAME`, `FLUX_DB_USER`, `FLUX_DB_PASSWORD` e `FLUX_DB_PORT` têm
precedência sobre esse arquivo.

## 4. Enviar os arquivos

Extraia o ZIP e envie o conteúdo para a pasta `htdocs` do site pelo Gerenciador
de Arquivos ou FTP. A página inicial é `index.php`. Não envie a pasta `.git`.

## Observações

- O site requer PHP 8.1 ou superior e as extensões PDO e PDO MySQL.
- O banco e a senha precisam ser criados/configurados na conta do InfinityFree;
  o pacote não contém credenciais nem cria recursos no painel da hospedagem.
- A primeira conta criada é de aluno. Para cadastrar uma conta de professor,
  use o código de convite configurado pelo projeto.
