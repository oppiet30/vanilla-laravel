<?php

declare(strict_types=1);

use App\Controllers\HomeController;
use App\Controllers\UserController;

$router->get('/', [HomeController::class, 'index']);
$router->get('/about', [HomeController::class, 'about']);
$router->get('/users', [UserController::class, 'index']);
