<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Csrf;
use App\Http\Request;
use App\Router;
use App\Session;
use App\View;

$directory = sys_get_temp_dir() . '/vanilla-user-edit-' . bin2hex(random_bytes(8));
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
putenv('DB_PORT=1'); // This test cannot connect or modify records.
Session::start();
$token = Csrf::token();
$router = new Router();
require dirname(__DIR__) . '/routes/web.php';
foreach (['0', 'bad', '01', '4294967296'] as $id) {
    $check($router->dispatch(new Request('GET', '/database/users/' . $id . '/edit'))->status === 422, 'invalid edit ID');
    $check($router->dispatch(new Request('POST', '/database/users/' . $id, ['_token' => $token, 'name' => 'Test']))->status === 422, 'invalid update ID');
}
foreach ([[], ['_token' => 'wrong'], ['_token' => [$token]]] as $data) {
    $response = $router->dispatch(new Request('POST', '/database/users/1', $data + ['name' => 'Test']));
    $check($response->status === 403 && str_contains($response->body, 'href="/database/users/1/edit"'), 'CSRF rejected with edit retry link');
}
$response = $router->dispatch(new Request('GET', '/database/users/1/edit'));
$check($response->status === 503 && !str_contains($response->body, 'SQLSTATE'), 'generic edit failure');
$response = $router->dispatch(new Request('POST', '/database/users/1', ['_token' => $token, 'name' => '<b>Test</b>']));
$check($response->status === 503 && str_contains($response->body, '&lt;b&gt;Test&lt;/b&gt;') && str_contains($response->body, $token) && !str_contains($response->body, 'SQLSTATE'), 'update failure retains escaped retry input');
$check($router->dispatch(new Request('PUT', '/database/users/1'))->status === 405, 'unsupported update method');
$html = View::render('database-user-edit', ['id' => '7', 'name' => '"><script>bad</script>', 'csrfToken' => 'token', 'errors' => ['name' => ['required' => 'This field is required.']]]);
$check(!str_contains($html, '<script>') && str_contains($html, '&lt;script&gt;bad&lt;/script&gt;'), 'edit value escaped');
$check(str_contains($html, 'action="/database/users/7"') && str_contains($html, 'href="/database/users/7"') && str_contains($html, 'aria-describedby="name-error"'), 'edit action, cancel link, and accessible error');
session_write_close();
echo implode(PHP_EOL, $checks) . PHP_EOL;
