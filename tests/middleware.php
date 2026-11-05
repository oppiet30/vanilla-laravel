<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Http\Middleware;
use App\Http\Middleware\ContentTypeOptions;
use App\Http\Request;
use App\Http\Response;
use App\Router;

function check(bool $condition, string $label): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $label);
    }
    echo 'PASS: ' . $label . PHP_EOL;
}

function middleware(callable $callback): Middleware
{
    return new class($callback) implements Middleware {
        public function __construct(private readonly mixed $callback) {}
        public function handle(Request $request, callable $next): Response
        {
            return ($this->callback)($request, $next);
        }
    };
}

$router = new Router();
$events = [];
foreach (['first', 'second'] as $name) {
    $router->middleware(middleware(function (Request $request, callable $next) use (&$events, $name): Response {
        $events[] = $name . ' before';
        $response = $next($request);
        $events[] = $name . ' after';
        return $response;
    }));
}
$router->get('/example/{id}', function (Request $request, array $parameters) use (&$events): Response {
    $events[] = 'handler';
    return new Response($parameters['id'], 202, ['X-Example' => 'kept']);
});
foreach ([1, 2] as $dispatch) {
    $events = [];
    $response = $router->dispatch(new Request('GET', '/example/7'));
    check($events === ['first before', 'second before', 'handler', 'second after', 'first after'], 'order on dispatch ' . $dispatch);
    check($response->body === '7' && $response->status === 202 && $response->headers['X-Example'] === 'kept', 'response preserved');
}

$blocked = new Router();
$called = false;
$blocked->middleware(middleware(fn (Request $request, callable $next): Response => new Response('Stopped', 403)));
$blocked->get('/', function () use (&$called): string { $called = true; return 'Unexpected'; });
check($blocked->dispatch(new Request('GET', '/'))->status === 403 && !$called, 'middleware can stop dispatch');

$forwarded = new Router();
$forwarded->middleware(middleware(fn (Request $request, callable $next): Response => $next(new Request('GET', '/target'))));
$forwarded->get('/target', fn (): string => 'forwarded');
check($forwarded->dispatch(new Request('GET', '/original'))->body === 'forwarded', 'next receives supplied request');

$headers = new Router();
$headers->middleware(new ContentTypeOptions());
$headers->get('/', fn (): Response => new Response('Hello', 201, ['Content-Type' => 'text/plain']));
foreach ([['GET', '/', 201], ['GET', '/missing', 404], ['POST', '/', 405]] as [$method, $uri, $status]) {
    $response = $headers->dispatch(new Request($method, $uri));
    check($response->status === $status && $response->headers['X-Content-Type-Options'] === 'nosniff', 'header on status ' . $status);
    if ($status === 405) {
        check($response->headers['Allow'] === 'GET', 'Allow header preserved');
    }
    if ($status === 201) {
        check($response->body === 'Hello' && $response->headers['Content-Type'] === 'text/plain', 'body and content type preserved');
    }
}
