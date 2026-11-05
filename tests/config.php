<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Config;

function check(bool $condition, string $label): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $label);
    }
    echo 'PASS: ' . $label . PHP_EOL;
}

$path = tempnam(sys_get_temp_dir(), 'vanilla-config-');
$key = 'VANILLA_CONFIG_TEST_' . bin2hex(random_bytes(8));
$previous = getenv($key);
try {
    file_put_contents($path, $key . "=\"from file\"\nEMPTY=\"\"\nZERO=0\nQUOTED=\"Hello # world\"\nBAD[]=array\n");
    $config = new Config($path);
    check($config->get($key) === 'from file', 'local file value');
    check($config->get('MISSING', 'fallback') === 'fallback', 'default value');
    check($config->get('EMPTY', 'fallback') === '', 'empty file value preserved');
    check($config->get('ZERO') === '0', 'zero remains text');
    check($config->get('QUOTED') === 'Hello # world', 'quoted value parsed');

    putenv($key . '=process');
    check($config->get($key) === 'process', 'process value wins');
    putenv($key . '=');
    check($config->get($key, 'fallback') === '', 'empty process value wins');
    putenv($key);

    $threw = false;
    try { $config->get('BAD'); } catch (RuntimeException) { $threw = true; }
    check($threw, 'array values rejected');
    file_put_contents($path, $key . "=changed\n");
    check($config->get($key) === 'from file', 'file snapshot reused per instance');
    check((new Config($path))->get($key) === 'changed', 'new instance reads changes');

    file_put_contents($path, "[broken\n");
    $threw = false;
    try { (new Config($path))->get($key); } catch (RuntimeException) { $threw = true; }
    check($threw, 'malformed file rejected');
    putenv($key . '=process');
    check((new Config($path))->get($key) === 'process', 'process value does not require file parsing');
    putenv($key);
    unlink($path);
    check((new Config($path))->get($key, 'fallback') === 'fallback', 'missing file allowed');
} finally {
    if ($previous === false) {
        putenv($key);
    } else {
        putenv($key . '=' . $previous);
    }
    if (is_file($path)) {
        unlink($path);
    }
}
