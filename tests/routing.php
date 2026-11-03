<?php

declare(strict_types=1);

require __DIR__ . '/../app/Router.php';
require __DIR__ . '/../app/Controllers/HomeController.php';

use App\Router;

$router = new Router();
require __DIR__ . '/../routes/web.php';

$router->get('/teams/{team}/users/{id}', fn (string $team, string $id): string => "$team:$id");
$router->get('/file.v1+~', fn (): string => 'literal');
$router->get('/ends', fn (): string => 'end');

$cases = [
    ['GET', '/', '<h1>Hello from Vanilla PHP</h1>', 200],
    ['GET', '/hello', '<h1>Hello from the router!</h1>', 200],
    ['GET', '/hello/Ada?sort=desc', '<h1>Hello, Ada!</h1>', 200],
    ['GET', '/hello/Ada%20Lovelace', '<h1>Hello, Ada Lovelace!</h1>', 200],
    ['GET', '/hello/A+B', '<h1>Hello, A+B!</h1>', 200],
    ['GET', '/hello/%3Cscript%3E', '<h1>Hello, &lt;script&gt;!</h1>', 200],
    ['GET', '/hello/a%2Fb', '<h1>Hello, a/b!</h1>', 200],
    ['POST', '/hello/Ada', '<h1>POST: Hello, Ada!</h1>', 200],
    ['GET', '/teams/red/users/42', 'red:42', 200],
    ['GET', '/file.v1+~', 'literal', 200],
    ['GET', '/fileXv11~', '404 - Page Not Found', 404],
    ['GET', '/hello/', '404 - Page Not Found', 404],
    ['GET', '/hello/a/b', '404 - Page Not Found', 404],
    ['GET', '/missing', '404 - Page Not Found', 404],
    ['POST', '/', '404 - Page Not Found', 404],
    ['DELETE', '/hello/Ada', '404 - Page Not Found', 404],
    ['GET', "/ends\n", '404 - Page Not Found', 404],
    ['GET', '/', '<h1>Hello from Vanilla PHP</h1>', 200],
];

foreach ($cases as [$method, $uri, $expected, $status]) {
    $actual = $router->dispatch($method, $uri);
    if ($actual !== $expected || http_response_code() !== $status) {
        fwrite(STDERR, "FAIL: $method $uri\n");
        exit(1);
    }
}

echo count($cases) . " routing tests passed on PHP " . PHP_VERSION . ".\n";
