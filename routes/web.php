<?php

declare(strict_types=1);

use App\Controllers\ContactController;
use App\Controllers\DatabaseController;
use App\Controllers\HomeController;
use App\Controllers\UserController;

$router->get('/', [HomeController::class, 'index']);
$router->get('/about', [HomeController::class, 'about']);
$router->get('/users', [UserController::class, 'index']);
$router->get('/contact', [ContactController::class, 'create']);
$router->post('/contact', [ContactController::class, 'store']);
$router->get('/database', [DatabaseController::class, 'index']);
$router->get('/database/user', [DatabaseController::class, 'show']);
$router->get('/database/users/{id}', [DatabaseController::class, 'show']);
$router->get('/contact/success', [ContactController::class, 'success']);

$router->get('/database/users/create', [DatabaseController::class, 'create']);
$router->post('/database/users', [DatabaseController::class, 'store']);

$router->get('/database/users/{id}/edit', [DatabaseController::class, 'edit']);
$router->post('/database/users/{id}', [DatabaseController::class, 'update']);

$router->get('/database/users/{id}/delete', [DatabaseController::class, 'confirmDelete']);
$router->post('/database/users/{id}/delete', [DatabaseController::class, 'destroy']);
