<?php

class Router {
    protected $routes = [];

    public function add($method, $path, $handler) {
        $this->routes[] = compact('method', 'path', 'handler');
    }

    public function dispatch($method, $uri) {
        // Normalize: trim trailing slash and ensure it starts with /
        $uri = is_string($uri) ? $uri : '';
        $uri = '/' . ltrim(rtrim($uri, '/'), '/');
        $uri = preg_replace('#/+#', '/', $uri);
        if ($uri === '') {
            $uri = '/';
        }
        foreach ($this->routes as $route) {
            $routePath = rtrim($route['path'], '/');
            if ($routePath === '') {
                $routePath = '/';
            }
            if ($route['method'] === $method && $routePath === $uri) {
                return call_user_func($route['handler']);
            }
        }
        // 404 Handler
        http_response_code(404);
        $GLOBALS['_router_404_path'] = $uri;
        
        // Log the 404 attempt for debugging
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) mkdir($logDir, 0755, true);
        file_put_contents($logDir . '/404.log', date('[Y-m-d H:i:s] ') . "404 - Method: $method - URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . " - Matched Path: $uri\n", FILE_APPEND);

        if (file_exists(__DIR__ . '/../views/404.php')) {
            require __DIR__ . '/../views/404.php';
        } else {
            echo "404 Not Found";
            if (isset($GLOBALS['_router_404_path'])) {
                echo "\nRequested path: " . htmlspecialchars($GLOBALS['_router_404_path']);
            }
        }
    }
}
