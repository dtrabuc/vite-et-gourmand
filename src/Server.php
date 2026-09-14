<?php
/**
 * Application class responsible for bootstrapping and running the application.
 */

namespace App;

use App\Router\Router;
use App\Middleware\MiddlewareInterface;

/**
 * Class Application
 */
class Application
{
    /**
     * @var array Configuration array
     */
    protected $config;

    /**
     * @var array Database configuration array
     */
    protected $databaseConfig;

    /**
     * @var Router Router instance
     */
    protected $router;

    /**
     * Application constructor.
     *
     * @param array $config Application configuration
     * @param array $databaseConfig Database configuration
     */
    public function __construct(array $config, array $databaseConfig)
    {
        $this->config = $config;
        $this->databaseConfig = $databaseConfig;

        // Initialize the router
        $this->router = new Router();

        // Load routes
        $this->loadRoutes();
    }

    /**
     * Load routes from the routes configuration file.
     */
    protected function loadRoutes()
    {
        $routesFile = __DIR__ . '/../config/routes.php';
        if (file_exists($routesFile)) {
            $routes = require $routesFile;
            // Assuming $routes is an array of route definitions
            foreach ($routes as $route) {
                // Each route is expected to be an array with keys: method, path, controller, middleware (optional)
                $method = $route['method'] ?? 'GET';
                $path = $route['path'];
                $controller = $route['controller'];
                $middleware = $route['middleware'] ?? [];

                // Add route to router
                $this->router->addRoute($method, $path, $controller, $middleware);
            }
        }
    }

    /**
     * Run the application.
     */
    public function run()
    {
        // Get the current URI and method
        $uri = $_SERVER['REQUEST_URI'];
        $method = $_SERVER['REQUEST_METHOD'];

        // Remove query string from URI if present
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }

        // Dispatch the request
        $this->router->dispatch($uri, $method);
    }
}