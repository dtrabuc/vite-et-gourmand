<?php
declare(strict_types=1);

namespace App\Core;

use App\Controller\ErrorController;
use App\Core\Exception\NotFoundException;
use ReflectionMethod;
use RuntimeException;

final class Router
{
    private array $routes = [];

    public function __construct(
        private readonly Container $container
    ) {
    }

    public function setRoutes(array $routes): void
    {
        $this->routes = [];

        foreach ($routes as $route) {
            $this->addRoute(
                (string) ($route[0] ?? 'GET'),
                (string) ($route[1] ?? '/'),
                (string) ($route[2] ?? ''),
                array_values($route[3] ?? [])
            );
        }

        usort(
            $this->routes,
            static function (array $left, array $right): int {
                $leftVariables = substr_count($left['uri'], '{');
                $rightVariables = substr_count($right['uri'], '{');

                if ($leftVariables !== $rightVariables) {
                    return $leftVariables <=> $rightVariables;
                }

                return strlen($right['uri']) <=> strlen($left['uri']);
            }
        );
    }

    public function addRoute(
        string $method,
        string $uri,
        string $action,
        array $middlewares = []
    ): void {
        $this->routes[] = [
            'method' => strtoupper($method),
            'uri' => $this->normalizePath($uri),
            'action' => $action,
            'middlewares' => $middlewares,
        ];
    }

    public function dispatch(): void
    {
        $path = $this->normalizePath(
            (string) (
                parse_url(
                    $_SERVER['REQUEST_URI'] ?? '/',
                    PHP_URL_PATH
                ) ?: '/'
            )
        );
        $method = strtoupper(
            (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')
        );

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $parameters = $this->match($route['uri'], $path);

            if ($parameters === null) {
                continue;
            }

            try {
                $this->runMiddlewareStack($route['middlewares']);
                $this->runAction($route['action'], $parameters);
            } catch (NotFoundException) {
                $this->notFound();
            }

            return;
        }

        $this->notFound();
    }

    /**
     * 404 en page HTML, ou en JSON pour les appels d'API.
     */
    private function notFound(): void
    {
        $controller = $this->container->get(ErrorController::class);

        if (!$controller instanceof ErrorController) {
            throw new RuntimeException('Contrôleur d’erreur non configuré.');
        }

        $controller->notFound();
    }

    private function match(string $routeUri, string $requestPath): ?array
    {
        $names = [];

        $pattern = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
            static function (array $matches) use (&$names): string {
                $names[] = $matches[1];
                return '([^/]+)';
            },
            $routeUri
        );

        if ($pattern === null) {
            return null;
        }

        if (!preg_match('#^' . $pattern . '$#u', $requestPath, $matches)) {
            return null;
        }

        array_shift($matches);

        $parameters = [];

        foreach ($names as $index => $name) {
            $parameters[$name] = rawurldecode(
                $matches[$index] ?? ''
            );
        }

        return $parameters;
    }

    private function runMiddlewareStack(array $middlewares): void
    {
        foreach ($middlewares as $middleware) {
            $class = str_starts_with($middleware, 'App\\')
                ? $middleware
                : 'App\\Middleware\\' . $middleware;

            $instance = $this->resolve($class);

            if (!is_callable($instance)) {
                throw new RuntimeException(
                    'Middleware non appelable : ' . $class
                );
            }

            $instance();
        }
    }

    private function runAction(string $action, array $parameters): void
    {
        if (!str_contains($action, '@')) {
            throw new RuntimeException(
                'Action de route invalide : ' . $action
            );
        }

        [$controller, $method] = explode('@', $action, 2);

        $class = str_starts_with($controller, 'App\\')
            ? $controller
            : 'App\\Controller\\' . $controller;

        $instance = $this->resolve($class);

        if (!method_exists($instance, $method)) {
            throw new RuntimeException(
                'Action introuvable : ' . $class . '@' . $method
            );
        }

        $reflection = new ReflectionMethod($instance, $method);
        $arguments = [];

        foreach ($reflection->getParameters() as $parameter) {
            $name = $parameter->getName();

            if (array_key_exists($name, $parameters)) {
                $value = $this->castParameter(
                    $parameters[$name],
                    $parameter->getType()?->getName()
                );

                // Paramètre d'URL non convertible (ex. /menus/abc pour un int) : 404.
                if ($value === null && !$parameter->allowsNull()) {
                    throw new NotFoundException('Paramètre de route invalide : ' . $name);
                }

                $arguments[] = $value;
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
                continue;
            }

            throw new RuntimeException(
                sprintf('Paramètre de route manquant : %s', $name)
            );
        }

        $reflection->invokeArgs($instance, $arguments);
    }

    private function resolve(string $class): object
    {
        if (!class_exists($class)) {
            throw new RuntimeException(
                'Classe introuvable : ' . $class
            );
        }

        if (str_starts_with($class, 'App\\')) {
            try {
                return $this->container->get($class);
            } catch (RuntimeException $exception) {
                throw new RuntimeException(
                    'Dépendance non configurée dans le conteneur : ' . $class,
                    0,
                    $exception
                );
            }
        }

        return new $class();
    }

    private function castParameter(
        string $value,
        ?string $type
    ): mixed {
        return match ($type) {
            'int' => filter_var(
                $value,
                FILTER_VALIDATE_INT,
                FILTER_NULL_ON_FAILURE
            ),
            'float' => filter_var(
                $value,
                FILTER_VALIDATE_FLOAT,
                FILTER_NULL_ON_FAILURE
            ),
            'bool' => filter_var(
                $value,
                FILTER_VALIDATE_BOOL,
                FILTER_NULL_ON_FAILURE
            ),
            default => $value,
        };
    }

    private function normalizePath(string $path): string
    {
        $path = '/' . ltrim($path, '/');
        $path = rtrim($path, '/');

        return $path === '' ? '/' : $path;
    }
}
