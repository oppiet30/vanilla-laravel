<?php

declare(strict_types=1);

use App\Controllers\HomeController;

$router->get('/', [HomeController::class, 'index']);

$router->get('/hello', function () {
    return '<h1>Hello from the router!</h1>';
});

$router->get('/hello/{name}', function (string $name): string {
    return '<h1>Hello, ' . htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '!</h1>';
});

// A read-only POST demonstration; persistence and CSRF protection come later.
$router->post('/hello/{name}', function (string $name): string {
    return '<h1>POST: Hello, ' . htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '!</h1>';
});
