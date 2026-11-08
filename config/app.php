<?php
declare(strict_types=1);

return [
    'app' => [
        'name' => 'Vite & Gourmand',
        'env' => $_ENV['APP_ENV'] ?? 'development',
        'debug' => filter_var($_ENV['APP_DEBUG'] ?? 'false', FILTER_VALIDATE_BOOLEAN),
        'url' => rtrim((string) ($_ENV['APP_URL'] ?? 'http://localhost:8000'), '/'),
    ],
    // Hébergeur affiché dans les mentions légales (renseigné lors du déploiement).
    'hosting' => [
        'name' => trim((string) ($_ENV['HOSTING_NAME'] ?? '')),
        'address' => trim((string) ($_ENV['HOSTING_ADDRESS'] ?? '')),
        'website' => trim((string) ($_ENV['HOSTING_WEBSITE'] ?? '')),
    ],
    'database' => [
        'mariadb' => [
            'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
            'port' => (int) ($_ENV['DB_PORT'] ?? 3306),
            'database' => $_ENV['DB_DATABASE'] ?? 'viteetgourmand',
            'username' => $_ENV['DB_USERNAME'] ?? '',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
            'charset' => 'utf8mb4',
        ],
        'mongodb' => [
            'uri' => $_ENV['MONGO_CONNECTION_STRING'] ?? '',
            'host' => $_ENV['MONGO_HOST'] ?? '127.0.0.1',
            'port' => (int) ($_ENV['MONGO_PORT'] ?? 27017),
            'database' => $_ENV['MONGO_DATABASE'] ?? 'viteetgourmand',
            'username' => $_ENV['MONGO_USERNAME'] ?? '',
            'password' => $_ENV['MONGO_PASSWORD'] ?? '',
            'auth_source' => $_ENV['MONGO_AUTH_SOURCE'] ?? 'admin',
        ],
    ],
    'session' => [
        'lifetime' => (int) ($_ENV['SESSION_LIFETIME'] ?? 30),
        'secure' => filter_var($_ENV['SESSION_SECURE'] ?? 'false', FILTER_VALIDATE_BOOLEAN),
        'name' => $_ENV['SESSION_NAME'] ?? 'viteetgourmand_session',
    ],
    'security' => [
        'password_hash_cost' => (int) ($_ENV['PASSWORD_HASH_COST'] ?? 12),
    ],
    'mail' => [
        'driver' => $_ENV['MAIL_DRIVER'] ?? 'smtp',
        'host' => $_ENV['MAIL_HOST'] ?? '',
        'port' => (int) ($_ENV['MAIL_PORT'] ?? 2525),
        'username' => $_ENV['MAIL_USERNAME'] ?? '',
        'password' => $_ENV['MAIL_PASSWORD'] ?? '',
        'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls',
        'from_address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@viteetgourmand.com',
        'from_name' => $_ENV['MAIL_FROM_NAME'] ?? 'Vite & Gourmand',
        // Adresse de l'entreprise : contact, nouvelles commandes, demandes de devis.
        'to_address' => $_ENV['MAIL_TO_ADDRESS'] ?? '',
    ],
];
