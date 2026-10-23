<?php
declare(strict_types=1);

interface PerfilEducacional
{
    public function getEmail(): string;

    public function getNivel(): int;

    public function getEstrelas(): int;
}
