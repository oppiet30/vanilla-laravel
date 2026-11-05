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
        $match = $this->match($request->method, $path);
        $handler = $match['handler'] ?? null;

        if ($handler === null) {
            $allowed = [];
            foreach ($this->routes as $method => $paths) {
                if ($this->match($method, $path) !== null) {
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

        $result = $match['parameters'] === []
            ? call_user_func($handler, $request)
            : call_user_func($handler, $request, $match['parameters']);

        return $result instanceof Response ? $result : new Response((string) $result);
    }

    /**
     * Literal routes win; dynamic routes are checked in registration order.
     * Placeholders occupy a full segment and match one nonempty segment.
     * @return array{handler: callable|array, parameters: array<string, string>}|null
     */
    private function match(string $method, string $path): ?array
    {
        $routes = $this->routes[$method] ?? [];
        if (isset($routes[$path])) {
            return ['handler' => $routes[$path], 'parameters' => []];
        }

        foreach ($routes as $pattern => $handler) {
            $names = [];
            $segments = explode('/', $pattern);
            foreach ($segments as &$segment) {
                if (preg_match('/^\{([a-zA-Z_][a-zA-Z0-9_]*)\}$/D', $segment, $placeholder)) {
                    $names[] = $placeholder[1];
                    $segment = '([^/]+)';
                } else {
                    $segment = preg_quote($segment, '~');
                }
            }
            unset($segment);
            if ($names === [] || count(array_unique($names)) !== count($names)) {
                continue;
            }
            if (!preg_match('~^' . implode('/', $segments) . '$~D', $path, $captures)) {
                continue;
            }
            array_shift($captures);
            return [
                'handler' => $handler,
                'parameters' => array_combine($names, array_map('rawurldecode', $captures)),
            ];
        }

        return null;
    }
}
