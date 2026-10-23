<?php
declare(strict_types=1);

final class Conexao
{
    private static ?PDO $instancia = null;

    public static function obter(): PDO
    {
        if (self::$instancia === null) {
            $host = getenv('FLUX_DB_HOST') ?: '127.0.0.1';
            $database = getenv('FLUX_DB_NAME') ?: 'flux';
            $username = getenv('FLUX_DB_USER') ?: 'root';
            $password = getenv('FLUX_DB_PASSWORD') ?: '';
            
            // Portas para testar: se definida no env, usa ela; caso contrário, testa 3308 (porta do Laragon) e 3306
            $portas = getenv('FLUX_DB_PORT') ? [(int) getenv('FLUX_DB_PORT')] : [3308, 3306];
            $ultimoErro = null;

            foreach ($portas as $porta) {
                try {
                    $dsn = sprintf(
                        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                        $host,
                        $porta,
                        $database
                    );

                    self::$instancia = new PDO($dsn, $username, $password, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]);

                    return self::$instancia;
                } catch (PDOException $erro) {
                    $ultimoErro = $erro;
                }
            }

            if ($ultimoErro !== null) {
                throw $ultimoErro;
            }
        }

        return self::$instancia;
    }
}
