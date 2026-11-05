<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Http\Middleware\ContentTypeOptions;
use App\Http\Middleware\StartSession;
use App\Http\Request;
use App\Router;

$router = new Router();
$router->middleware(new ContentTypeOptions());
$router->middleware(new StartSession());

require __DIR__ . '/../routes/web.php';

$request = Request::capture();
$response = $router->dispatch($request);
$response->send();
