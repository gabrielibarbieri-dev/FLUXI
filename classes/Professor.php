<?php
declare(strict_types=1);

require_once __DIR__ . '/Pessoa.php';
require_once __DIR__ . '/PerfilEducacional.php';

final class Professor extends Pessoa implements PerfilEducacional
{
    private string $email;
    private int $nivel;
    private int $estrelas;

    public function __construct(string $nome, string $email, int $nivel = 1, int $estrelas = 0)
    {
        parent::__construct($nome);
        $this->setEmail($email);
        $this->setNivel($nivel);
        $this->setEstrelas($estrelas);
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $this->normalizarEmail($email);
    }

    public function getNivel(): int
    {
        return $this->nivel;
    }

    public function setNivel(int $nivel): void
    {
        if ($nivel < 1 || $nivel > 5) {
            throw new InvalidArgumentException('O nível deve estar entre 1 e 5.');
        }

        $this->nivel = $nivel;
    }

    public function getEstrelas(): int
    {
        return $this->estrelas;
    }

    public function setEstrelas(int $estrelas): void
    {
        if ($estrelas < 0) {
            throw new InvalidArgumentException('A quantidade de estrelas não pode ser negativa.');
        }

        $this->estrelas = $estrelas;
    }

    public function getTipo(): string
    {
        return 'Professor FLUX';
    }
}
