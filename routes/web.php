<?php

use App\Controllers\HomeController;

$router->get('/', [HomeController::class, 'index']);

$router->get('/hello', function () {
    return '<h1>Hello from the router!</h1>';
});

$router->get('/hello/{name}', function ($name) {
    return '<h1>Hello, ' . htmlspecialchars($name) . '!</h1>';
});
