<?php
declare(strict_types=1);

abstract class Pessoa
{
    private string $nome;

    public function __construct(string $nome)
    {
        $this->setNome($nome);
    }

    public function getNome(): string
    {
        return $this->nome;
    }

    public function setNome(string $nome): void
    {
        $nome = trim($nome);
        if ($nome === '' || mb_strlen($nome, 'UTF-8') > 120) {
            throw new InvalidArgumentException('O nome deve ter entre 1 e 120 caracteres.');
        }

        $this->nome = $nome;
    }

    protected function normalizarEmail(string $email): string
    {
        $email = mb_strtolower(trim($email), 'UTF-8');
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || mb_strlen($email, 'UTF-8') > 190) {
            throw new InvalidArgumentException('Informe um e-mail válido.');
        }

        return $email;
    }

    abstract public function getTipo(): string;
}
