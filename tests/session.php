<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Http\Middleware\StartSession;
use App\Http\Request;
use App\Http\Response;
use App\Session;

$directory = sys_get_temp_dir() . '/vanilla-session-test-' . bin2hex(random_bytes(8));
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

// Avoid output until session operations finish: cookies use response headers.
$threw = false;
try {
    Session::flash('inactive', 'value');
} catch (RuntimeException) {
    $threw = true;
}
$check($threw, 'flash requires an active session');

Session::start();
$id = session_id();
Session::flash('greeting', 'Hello');
Session::flash('other', 'Keep');
Session::flash('null', null);
Session::flash('false', false);
Session::flash('replace', 'old');
Session::flash('replace', 'new');
$cookie = session_get_cookie_params();
$check($cookie['httponly'] && $cookie['samesite'] === 'Lax', 'cookie options');
session_write_close();

Session::start();
$check(session_id() === $id, 'same session reopened');
$check(Session::pullFlash('greeting') === 'Hello', 'flash survives close and reopen');
$check(Session::pullFlash('greeting', 'missing') === 'missing', 'flash consumed once');
$check(Session::pullFlash('other') === 'Keep', 'unrelated flash retained');
$check(Session::pullFlash('null', 'fallback') === null, 'stored null preserved');
$check(Session::pullFlash('false', true) === false, 'stored false preserved');
$check(Session::pullFlash('replace') === 'new', 'latest flash replaces old value');
session_write_close();

$middleware = new StartSession();
$response = $middleware->handle(new Request('GET', '/'), static function (Request $request): Response {
    Session::flash('pipeline', 'Saved');
    return Response::redirect('/contact/success');
});
$check(session_status() !== PHP_SESSION_ACTIVE, 'middleware closes session');
$check($response->status === 303 && $response->headers['Location'] === '/contact/success', 'redirect response');
Session::start();
$check(Session::pullFlash('pipeline') === 'Saved', 'middleware persisted flash');
session_write_close();

try {
    $middleware->handle(new Request('GET', '/'), static function (): Response {
        throw new RuntimeException('handler failed');
    });
} catch (RuntimeException $error) {
    $check($error->getMessage() === 'handler failed' && session_status() !== PHP_SESSION_ACTIVE, 'middleware closes session after exception');
}

echo implode(PHP_EOL, $checks) . PHP_EOL;
