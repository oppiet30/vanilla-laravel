<?php

declare(strict_types=1);

use App\Controllers\ContactController;
use App\Controllers\HomeController;
use App\Controllers\UserController;

$router->get('/', [HomeController::class, 'index']);
$router->get('/about', [HomeController::class, 'about']);
$router->get('/users', [UserController::class, 'index']);
$router->get('/contact', [ContactController::class, 'create']);
$router->post('/contact', [ContactController::class, 'store']);
