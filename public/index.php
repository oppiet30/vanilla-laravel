<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/Router.php';

use App\Router;

$router = new Router();

require __DIR__ . '/../routes/web.php';

$router->dispatch(
    $_SERVER['REQUEST_METHOD'] ?? 'GET',
    $_SERVER['REQUEST_URI'] ?? '/'
);
