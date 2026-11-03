<?php

declare(strict_types=1);

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

            // Quote literal text so dots, plus signs and delimiters stay literal.
            $parts = preg_split('/(\{[A-Za-z_][A-Za-z0-9_]*\})/', $route['path'], -1, PREG_SPLIT_DELIM_CAPTURE);
            $pattern = '';
            foreach ($parts as $part) {
                $pattern .= preg_match('/^\{[A-Za-z_][A-Za-z0-9_]*\}$/', $part)
                    ? '([^/]+)'
                    : preg_quote($part, '~');
            }

            // Split the query before matching; decode parameters only afterward.
            $path = explode('?', $uri, 2)[0];
            if (preg_match('~^' . $pattern . '$~D', $path, $matches)) {

                array_shift($matches);

                $handler = $route['handler'];
                if (is_array($handler) && is_string($handler[0])) {
                    $handler = [new $handler[0](), $handler[1]];
                }

                http_response_code(200);
                return call_user_func_array($handler, array_map('rawurldecode', $matches));
            }
        }

        http_response_code(404);

        return '404 - Page Not Found';
    }
}
