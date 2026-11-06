<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static ?PDO $pdo = null;
    private static ?\MongoDB\Client $mongoClient = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $config = config('database.mariadb', []);

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            (string) ($config['host'] ?? '127.0.0.1'),
            (int) ($config['port'] ?? 3306),
            (string) ($config['database'] ?? 'viteetgourmand'),
            (string) ($config['charset'] ?? 'utf8mb4')
        );

        try {
            self::$pdo = new PDO(
                $dsn,
                (string) ($config['username'] ?? ''),
                (string) ($config['password'] ?? ''),
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $exception) {
            throw new RuntimeException(
                'Connexion MariaDB impossible.',
                0,
                $exception
            );
        }

        return self::$pdo;
    }

    public static function mongo(): \MongoDB\Client
    {
        if (self::$mongoClient instanceof \MongoDB\Client) {
            return self::$mongoClient;
        }

        $config = config('database.mongodb', []);
        $uri = trim((string) ($config['uri'] ?? ''));

        if ($uri === '') {
            $host = (string) ($config['host'] ?? '127.0.0.1');
            $port = (int) ($config['port'] ?? 27017);
            $username = (string) ($config['username'] ?? '');
            $password = (string) ($config['password'] ?? '');
            $authSource = (string) ($config['auth_source'] ?? 'admin');
            $database = (string) ($config['database'] ?? 'viteetgourmand');

            if ($username !== '') {
                $uri = sprintf(
                    'mongodb://%s:%s@%s:%d/%s?authSource=%s',
                    rawurlencode($username),
                    rawurlencode($password),
                    $host,
                    $port,
                    $database,
                    rawurlencode($authSource)
                );
            } else {
                $uri = sprintf(
                    'mongodb://%s:%d/%s',
                    $host,
                    $port,
                    $database
                );
            }
        }

        try {
            $mongoClientClass = 'MongoDB\\Client';
            self::$mongoClient = new $mongoClientClass(
                $uri,
                [
                    'serverSelectionTimeoutMS' => (int) config(
                        'database.mongodb.server_selection_timeout_ms',
                        5000
                    ),
                ]
            );
        } catch (\Throwable $exception) {
            throw new RuntimeException(
                'Connexion MongoDB impossible.',
                0,
                $exception
            );
        }

        return self::$mongoClient;
    }

    public static function mongoDatabase(): \MongoDB\Database
    {
        return self::mongo()->getDatabase(
            (string) config(
                'database.mongodb.database',
                'viteetgourmand'
            )
        );
    }
}