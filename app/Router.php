<?php

declare(strict_types=1);

namespace App;

final class Router
{
    /** @var array<string, array<string, callable|array>> */
    private array $routes = [];

    public function get(string $path, callable|array $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH);
        $path = is_string($path) ? $path : '/';

        $handler = $this->routes[$method][$path] ?? null;

        if ($handler === null) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=UTF-8');
            echo '404 - Page Not Found';
            return;
        }

        if (is_array($handler) && isset($handler[0], $handler[1]) && is_string($handler[0])) {
            $handler = [new $handler[0](), $handler[1]];
        }

        header('Content-Type: text/html; charset=UTF-8');
        echo call_user_func($handler);
    }
}
