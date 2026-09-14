<?php
return [
    'app' => [
        'name' => 'Vite & Gourmand',
        'env' => $_ENV['APP_ENV'] ?? 'development',
        'debug' => filter_var($_ENV['APP_DEBUG'] ?? 'true', FILTER_VALIDATE_BOOLEAN),
        'url' => $_ENV['APP_URL'] ?? 'http://localhost:8000',
    ],
    'database' => [
        'mariadb' => [
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'port' => (int)($_ENV['DB_PORT'] ?? 3306),
            'database' => $_ENV['DB_DATABASE'] ?? 'vitegourmand',
            'username' => $_ENV['DB_USERNAME'] ?? '',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
            'charset' => 'utf8mb4',
        ],
        'mongodb' => [
            'host' => $_ENV['MONGO_HOST'] ?? 'localhost',
            'port' => (int)($_ENV['MONGO_PORT'] ?? 27017),
            'database' => $_ENV['MONGO_DATABASE'] ?? 'vitegourmand',
            'username' => $_ENV['MONGO_USERNAME'] ?? '',
            'password' => $_ENV['MONGO_PASSWORD'] ?? '',
        ],
    ],
    'session' => [
        'lifetime' => (int)($_ENV['SESSION_LIFETIME'] ?? 120),
        'secure' => filter_var($_ENV['SESSION_SECURE'] ?? 'false', FILTER_VALIDATE_BOOLEAN),
        'name' => $_ENV['SESSION_NAME'] ?? 'viteetgourmand_session',
    ],
    'security' => [
        'password_hash_cost' => (int)($_ENV['PASSWORD_HASH_COST'] ?? 12),
    ],
    'mail' => [
        'driver' => $_ENV['MAIL_DRIVER'] ?? 'smtp',
        'host' => $_ENV['MAIL_HOST'] ?? '',
        'port' => (int)($_ENV['MAIL_PORT'] ?? 2525),
        'username' => $_ENV['MAIL_USERNAME'] ?? '',
        'password' => $_ENV['MAIL_PASSWORD'] ?? '',
        'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls',
        'from_address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@viteetgourmand.com',
        'from_name' => $_ENV['MAIL_FROM_NAME'] ?? 'Vite & Gourmand',
    ],
    'logging' => [
        'channel' => $_ENV['LOG_CHANNEL'] ?? 'file',
        'level' => $_ENV['LOG_LEVEL'] ?? 'debug',
    ],
];