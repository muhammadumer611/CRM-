<?php
namespace Utils;

use Helpers\Response;

class Router {
    private $routes = [];
    
    public function add($method, $path, $handler) {
        $regex = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<\1>[a-zA-Z0-9_-]+)', $path);
        $regex = "#^" . $regex . "$#";
        
        $this->routes[] = [
            'method' => strtoupper($method),
            'regex' => $regex,
            'handler' => $handler
        ];
    }
    
    public function dispatch($method, $uri) {
        $path = parse_url($uri, PHP_URL_PATH);
        
        $config = require __DIR__ . '/../config/app.php';
        $basePath = parse_url($config['base_url'], PHP_URL_PATH);
        if ($basePath && $basePath !== '/') {
            if (strpos($path, $basePath) === 0) {
                $path = substr($path, strlen($basePath));
            }
        }
        
        if (empty($path)) $path = '/';
        
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && preg_match($route['regex'], $path, $matches)) {
                $params = [];
                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        $params[$key] = $value;
                    }
                }
                
                if (is_callable($route['handler'])) {
                    call_user_func($route['handler'], $params);
                    return;
                }
            }
        }
        
        Response::error('Endpoint not found', 404);
    }
}
