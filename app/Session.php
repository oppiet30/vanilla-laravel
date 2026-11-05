<?php

declare(strict_types=1);

namespace App;

use RuntimeException;

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_name('vanilla_session');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        if (!session_start()) {
            throw new RuntimeException('Unable to start session.');
        }
    }

    public static function flash(string $key, mixed $value): void
    {
        self::requireActive();
        $_SESSION['_flash'][$key] = $value;
    }

    public static function pullFlash(string $key, mixed $default = null): mixed
    {
        self::requireActive();
        $flashes = $_SESSION['_flash'] ?? [];
        $value = array_key_exists($key, $flashes) ? $flashes[$key] : $default;
        unset($_SESSION['_flash'][$key]);

        return $value;
    }

    private static function requireActive(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new RuntimeException('Session middleware must run before using flash messages.');
        }
    }
}
