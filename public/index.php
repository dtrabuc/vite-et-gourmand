<?php
/**
 * Front Controller for the Vite & Gourmand application
 *
 * This is the single entry point for all requests.
 */

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables from .env file
$envFile = __DIR__ . '/../.env';
if (is_readable($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && !str_starts_with(trim($line), '#')) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            putenv("$key=$value");
            // Also populate $_ENV and $getenv() for compatibility
            $_ENV[$key] = $value;
        }
    }
}

// Load configuration
$config = require __DIR__ . '/../config/app.php';

// Start session
session_start();

// Initialize router
$router = new \App\Core\Router($config);

// Define routes (could be loaded from a separate file)
$routes = require __DIR__ . '/../config/routes.php';
$router->setRoutes($routes);

// Handle the request
try {
    $router->dispatch();
} catch (\Exception $e) {
    // Log the error (in a real application, use a logger)
    error_log($e->getMessage());

    // Send a generic error response
    http_response_code(500);
    echo 'Internal Server Error';
}