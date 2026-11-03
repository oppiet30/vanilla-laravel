<?php

declare(strict_types=1);

require_once __DIR__
    . '/../app/Controllers/Api/V2/UserController.php';

$userController = new \App\Controllers\Api\V2\UserController();

$router->get(
    '/api/v2/users',
    [$userController, 'index']
);

$router->get(
    '/api/v2/users/{id}',
    [$userController, 'show']
);
