<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Csrf;
use App\Http\Request;
use App\Router;
use App\Session;
use App\View;

$directory = sys_get_temp_dir() . '/vanilla-user-form-' . bin2hex(random_bytes(8));
mkdir($directory, 0700);
session_save_path($directory);
$previousPort = getenv('DB_PORT');
register_shutdown_function(static function () use ($directory, $previousPort): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    putenv($previousPort === false ? 'DB_PORT' : 'DB_PORT=' . $previousPort);
    foreach (glob($directory . '/sess_*') ?: [] as $file) {
        unlink($file);
    }
    rmdir($directory);
});

$checks = [];
$check = static function (bool $ok, string $label) use (&$checks): void {
    if (!$ok) {
        throw new RuntimeException('FAIL: ' . $label);
    }
    $checks[] = 'PASS: ' . $label;
};

// This test never writes to the database: valid submissions use a closed port.
putenv('DB_PORT=1');
Session::start();
$router = new Router();
require dirname(__DIR__) . '/routes/web.php';
$form = $router->dispatch(new Request('GET', '/database/users/create'));
$token = Csrf::token();
$check($form->status === 200 && str_contains($form->body, $token), 'literal create route wins over dynamic ID route');
foreach ([[], ['_token' => 'wrong'], ['_token' => [$token]]] as $data) {
    $response = $router->dispatch(new Request('POST', '/database/users', $data + ['name' => 'Test']));
    $check($response->status === 403 && str_contains($response->body, 'href="/database/users/create"'), 'CSRF rejection links to correct form');
}
foreach (['', '   ', str_repeat('a', 101)] as $name) {
    $response = $router->dispatch(new Request('POST', '/database/users', ['_token' => $token, 'name' => $name]));
    $check($response->status === 422 && str_contains($response->body, 'aria-describedby="name-error"'), 'invalid name shows field error');
}
$response = $router->dispatch(new Request('POST', '/database/users', ['_token' => $token, 'name' => ['unexpected']]));
$check($response->status === 422, 'array-shaped name rejected');
$response = $router->dispatch(new Request('POST', '/database/users', ['_token' => $token, 'name' => '<b>Test</b>']));
$check($response->status === 503 && str_contains($response->body, '&lt;b&gt;Test&lt;/b&gt;') && !str_contains($response->body, 'SQLSTATE'), 'database failure retains escaped input with generic error');
$check($router->dispatch(new Request('GET', '/database/users'))->status === 405, 'create endpoint is POST only');
$html = View::render('database-user', ['message' => null, 'user' => ['id' => 1, 'name' => '<script>test</script>'], 'notice' => '<b>Created</b>']);
$check(str_contains($html, '&lt;script&gt;test&lt;/script&gt;') && str_contains($html, '&lt;b&gt;Created&lt;/b&gt;'), 'detail and notice escaped');
session_write_close();
echo implode(PHP_EOL, $checks) . PHP_EOL;
