<?php
declare(strict_types=1);

final class Conexao
{
    private static ?PDO $instancia = null;

    public static function obter(): PDO
    {
        if (self::$instancia === null) {
            $configuracao = [];
            $arquivoConfiguracao = __DIR__ . '/../config/database.php';
            if (is_file($arquivoConfiguracao)) {
                $configuracaoCarregada = require $arquivoConfiguracao;
                if (!is_array($configuracaoCarregada)) {
                    throw new RuntimeException('A configuração do banco deve retornar um array.');
                }
                $configuracao = $configuracaoCarregada;
            }

            $host = getenv('FLUX_DB_HOST') ?: ($configuracao['host'] ?? '127.0.0.1');
            $database = getenv('FLUX_DB_NAME') ?: ($configuracao['database'] ?? 'flux');
            $username = getenv('FLUX_DB_USER') ?: ($configuracao['username'] ?? 'root');
            $password = getenv('FLUX_DB_PASSWORD') ?: ($configuracao['password'] ?? '');

            $portaConfigurada = getenv('FLUX_DB_PORT') ?: ($configuracao['port'] ?? null);
            $portas = $portaConfigurada !== null
                ? [(int) $portaConfigurada]
                : [3308, 3306];
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
