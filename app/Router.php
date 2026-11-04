<?php

declare(strict_types=1);

namespace App;

use App\Http\Request;
use App\Http\Response;

final class Router
{
    /** @var array<string, array<string, callable|array>> */
    private array $routes = [];

    public function get(string $path, callable|array $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function dispatch(Request $request): Response
    {
        $path = $request->path();
        $handler = $this->routes[$request->method][$path] ?? null;

        if ($handler === null) {
            $allowed = [];
            foreach ($this->routes as $method => $paths) {
                if (isset($paths[$path])) {
                    $allowed[] = $method;
                }
            }

            if ($allowed !== []) {
                return new Response(
                    '405 - Method Not Allowed',
                    405,
                    [
                        'Content-Type' => 'text/plain; charset=UTF-8',
                        'Allow' => implode(', ', $allowed),
                    ],
                );
            }

            return new Response(
                '404 - Page Not Found',
                404,
                ['Content-Type' => 'text/plain; charset=UTF-8'],
            );
        }

        if (is_array($handler) && isset($handler[0], $handler[1]) && is_string($handler[0])) {
            $handler = [new $handler[0](), $handler[1]];
        }

        $result = call_user_func($handler, $request);

        return $result instanceof Response ? $result : new Response((string) $result);
    }
}
