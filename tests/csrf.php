<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Csrf;
use App\Http\Request;
use App\Session;
use App\Router;

$directory = sys_get_temp_dir() . '/vanilla-csrf-test-' . bin2hex(random_bytes(8));
mkdir($directory, 0700);
session_save_path($directory);
register_shutdown_function(static function () use ($directory): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
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
Session::start();
$check(!Csrf::validate(null) && Session::get('_csrf_token') === null, 'validation does not generate token');
$token = Csrf::token();
$check(strlen($token) === 64 && ctype_xdigit($token), 'random token format');
$check(Csrf::token() === $token && Csrf::validate($token), 'stable valid session token');
$check(!Csrf::validate('') && !Csrf::validate('wrong') && !Csrf::validate(str_repeat('z', 64)), 'invalid tokens rejected');
session_write_close();
Session::start();
$check(Csrf::validate($token), 'token persists across requests');

$router = new Router();
require dirname(__DIR__) . '/routes/web.php';
foreach ([[], ['_token' => 'wrong'], ['_token' => [$token]]] as $data) {
    $response = $router->dispatch(new Request('POST', '/contact', $data + ['name' => 'Oppie', 'message' => 'Hello']));
    $check($response->status === 403 && Session::pullFlash('contact') === null, 'reject before writing flash');
}
$response = $router->dispatch(new Request('POST', '/contact', ['_token' => $token, 'name' => '', 'message' => '<b>test</b>']));
$check($response->status === 422 && str_contains($response->body, $token) && str_contains($response->body, '&lt;b&gt;test&lt;/b&gt;'), 'valid token retains validation and escaped retry form');
$response = $router->dispatch(new Request('POST', '/contact', ['_token' => $token, 'name' => 'Oppie', 'message' => 'Hello']));
$check($response->status === 303 && $response->headers['Location'] === '/contact/success', 'valid submission redirects');
$response = $router->dispatch(new Request('GET', '/contact/success'));
$check($response->status === 200 && str_contains($response->body, 'Oppie'), 'confirmation works');
$check(Csrf::validate($token), 'flash does not consume CSRF token');
$_SESSION = [];
$otherToken = Csrf::token();
$check($otherToken !== $token && !Csrf::validate($token), 'other session token rejected');
session_write_close();

echo implode(PHP_EOL, $checks) . PHP_EOL;
