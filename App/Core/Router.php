<?php
namespace App\Core;

class Router
{
    private $routes;
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->routes = [];
    }

    public function addRoute(string $method, string $uri, string $controllerAction, array $middlewares = []): void
    {
        $this->routes[] = [
            'method' => $method,
            'uri' => $uri,
            'controller_action' => $controllerAction,
            'middlewares' => $middlewares,
        ];
    }

    public function setRoutes(array $routes): void
    {
        $this->routes = $routes;
    }

    public function dispatch(): void
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];

        foreach ($this->routes as $route) {
            // Route format: [HTTP_METHOD, URI, CONTROLLER@ACTION, MIDDLEWARES]
            $routeMethod = $route[0];
            $routeUri = $route[1];
            $controllerAction = $route[2];
            $middlewares = $route[3] ?? [];

            // Convert route uri pattern to regex
            // Example: /users/{id} -> #^/users/([^/]+)$#
            $pattern = '#^' . preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $routeUri) . '$#';

            if (preg_match($pattern, $uri, $matches) && strtoupper($routeMethod) === $method) {
                // Remove the first match (the entire string)
                array_shift($matches);

                // Split controller@action
                [$controllerName, $actionName] = explode('@', $controllerAction);

                // Apply middlewares
                foreach ($middlewares as $middleware) {
                    $this->applyMiddleware($middleware);
                }

                // Execute controller action
                $this->executeControllerAction($controllerAction, $matches);

                return;
            }
        }

        // If no route matched, throw 404
        http_response_code(404);
        echo 'Not Found';
    }

    private function applyMiddleware(string $middleware): void
    {
        $middlewarePath = __DIR__ . '/../../App/Middleware/' . $middleware . '.php';
        if (file_exists($middlewarePath)) {
            require_once $middlewarePath;
            // First, try to instantiate a class with the given name in the App\Middleware namespace
            $className = '\\App\\Middleware\\' . $middleware;
            if (class_exists($className)) {
                $instance = new $className();
                $instance();
            } elseif (function_exists($middleware)) {
                $middleware();
            }
        }
    }

    private function executeControllerAction(string $controllerAction, array $params): void
    {
        // Split controller@action
        [$controllerName, $actionName] = explode('@', $controllerAction);

        // Build the controller class name
        $controllerClass = '\\App\\Controller\\' . $controllerName;

        // Check if the controller class exists
        if (!class_exists($controllerClass)) {
            http_response_code(500);
            echo 'Controller not found: ' . $controllerClass;
            return;
        }

        // Instantiate the controller
        $controller = new $controllerClass();

        // Check if the action method exists
        if (!method_exists($controller, $actionName)) {
            http_response_code(500);
            echo 'Method not found: ' . $actionName . ' in ' . $controllerClass;
            return;
        }

        // Call the action with parameters
        call_user_func_array([$controller, $actionName], $params);
    }
}
