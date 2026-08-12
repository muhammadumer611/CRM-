<?php
namespace App\Core;

class Router {
    private $routes = [];

    public function add($method, $path, $controller, $action) {
        $path = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[a-zA-Z0-9_]+)', $path);
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => '#^' . $path . '$#',
            'controller' => $controller,
            'action' => $action
        ];
    }

    public function dispatch($url) {
        $url = rtrim($url, '/');
        if (empty($url)) {
            $url = '/';
        } else {
            $url = '/' . $url;
        }

        $method = $_SERVER['REQUEST_METHOD'];
        // Handle method spoofing if needed (e.g., _method in POST)
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && preg_match($route['path'], $url, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                
                $controllerName = "\\App\\Controllers\\" . $route['controller'];
                if (class_exists($controllerName)) {
                    $controller = new $controllerName();
                    $action = $route['action'];
                    if (method_exists($controller, $action)) {
                        call_user_func_array([$controller, $action], $params);
                        return;
                    }
                }
            }
        }

        // 404
        http_response_code(404);
        echo "404 Not Found";
    }
}
