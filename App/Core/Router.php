<?php
namespace App\Core;

class Router
{
  private $routes;
  public function __construct()
  {
    $this->routes = [];

  }

  public static function redirect($path = '')
  {
    header("Location: " . ROOT . "/" . $path);
    die;
  }


  public function goRoute($router)
  {
    $method = $_SERVER['REQUEST_METHOD'];
    $uri = BASE_URL . $_SERVER['REQUEST_URI'];

    $getRoute = $router->getRoute($method, $uri);
    if ($getRoute == null) {
      $this->redirect('error');
    }
    $controller = new $getRoute['controller']();
    $action = $getRoute['action'];
    $controller->$action($getRoute['params']);

  }
  public function addRoute(string $method, string $path, string $controller, string $action)
  {
    $this->routes[] = [
      'method' => $method,
      'path' => $path,
      'controller' => 'App\Controller\\' . $controller,
      'action' => $action,
    ];

  }
  private function getRoute(string $method, string $uri): ?array
  {
    foreach ($this->routes as $route) {
      $routeParts = explode('/', $route['path']);
      $uriParts = explode('/', $uri);

      if (
        $route['method'] === $method && count($routeParts) === count($uriParts)
      ) {
        $params = [];
        $paramName = null;
        $match = true;

        foreach ($routeParts as $index => $part) {

          if (isset($part[0]) && $part[0] === '{' && $part[strlen($part) - 1] === '}') {
            $paramName = trim($part, '{}');
            $params[$paramName] = $uriParts[$index];

            if (str_contains($params[$paramName], '?')) {
              $params[$paramName] = strstr($params[$paramName], '?', true);
            }

          } elseif ($part !== $uriParts[$index]) {
            $match = false;
            break;
          }
        }

        if ($match) {
          return [
            'method' => $route['method'],
            'controller' => $route['controller'],
            'action' => $route['action'],
            'params' => $params ?? null,
          ];
        }
      }
    }

    return null;
  }
}
