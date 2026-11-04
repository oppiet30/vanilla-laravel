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

    public function dispatch(Request $request): Response
    {
        $handler = $this->routes[$request->method][$request->path()] ?? null;

        if ($handler === null) {
            return new Response(
                '404 - Page Not Found',
                404,
                ['Content-Type' => 'text/plain; charset=UTF-8'],
            );
        }

        if (is_array($handler) && isset($handler[0], $handler[1]) && is_string($handler[0])) {
            $handler = [new $handler[0](), $handler[1]];
        }

        return new Response((string) call_user_func($handler));
    }
}
