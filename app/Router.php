<?php

namespace App;

class Router
{
    private array $routes = [];

    public function get(string $path, callable|array $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    private function addRoute(
        string $method,
        string $path,
        callable|array $handler
    ): void {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
        ];
    }

    public function dispatch(
        string $method,
        string $uri
    ): mixed {
        foreach ($this->routes as $route) {

            if ($route['method'] !== $method) {
                continue;
            }

            $pattern = preg_replace(
                '#\{([^}]+)\}#',
                '([^/]+)',
                $route['path']
            );

            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $uri, $matches)) {

                array_shift($matches);

                return call_user_func_array(
                    $route['handler'],
                    $matches
                );
            }
        }

        http_response_code(404);

        return '404 - Page Not Found';
    }
}