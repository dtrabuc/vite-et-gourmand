<?php
namespace App\Core;

use PDO;
use MongoDB\Client as MongoClient;

class Database
{
    private static ?PDO $pdo = null;
    private static ?MongoClient $mongo = null;

    public static function getPDO(): PDO
    {
        if (self::$pdo === null) {
            $config = require __DIR__ . '/../../config/app.php';
            $dbConfig = $config['database']['mariadb'];

            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $dbConfig['host'],
                $dbConfig['port'],
                $dbConfig['database'],
                $dbConfig['charset']
            );

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            self::$pdo = new PDO(
                $dsn,
                $dbConfig['username'],
                $dbConfig['password'],
                $options
            );
        }

        return self::$pdo;
    }

    public static function getMongo(): MongoClient
    {
        if (self::$mongo === null) {
            // Check if we have a full connection string
            $connectionString = getenv('MONGO_CONNECTION_STRING');
            if (!empty($connectionString)) {
                self::$mongo = new MongoClient($connectionString);
            } else {
                // Fall back to individual components approach
                $config = require __DIR__ . '/../../config/app.php';
                $mongoConfig = $config['database']['mongodb'];

                $uri = sprintf(
                    'mongodb://%s:%s@%s:%d/%s',
                    $mongoConfig['username'],
                    $mongoConfig['password'],
                    $mongoConfig['host'],
                    $mongoConfig['port'],
                    $mongoConfig['database']
                );

                // If username and password are empty, we don't want to include them in the URI
                if (empty($mongoConfig['username']) && empty($mongoConfig['password'])) {
                    $uri = sprintf(
                        'mongodb://%s:%d/%s',
                        $mongoConfig['host'],
                        $mongoConfig['port'],
                        $mongoConfig['database']
                    );
                }

                self::$mongo = new MongoClient($uri);
            }
        }

        return self::$mongo;
    }

    public static function getMongoDatabase()
    {
        $mongo = self::getMongo();
        $config = require __DIR__ . '/../../config/app.php';
        $dbName = $config['database']['mongodb']['database'];
        return $mongo->selectDatabase($dbName);
    }
}
