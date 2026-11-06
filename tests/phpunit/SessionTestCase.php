<?php

declare(strict_types=1);

use App\Session;
use PHPUnit\Framework\TestCase;

abstract class SessionTestCase extends TestCase
{
    private string $sessionDirectory;

    protected function setUp(): void
    {
        $this->sessionDirectory = sys_get_temp_dir() . '/vanilla-phpunit-' . bin2hex(random_bytes(8));
        mkdir($this->sessionDirectory, 0700);
        session_save_path($this->sessionDirectory);
        Session::start();
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        foreach (glob($this->sessionDirectory . '/sess_*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->sessionDirectory);
    }

    protected function router(): App\Router
    {
        $router = new App\Router();
        require dirname(__DIR__, 2) . '/routes/web.php';

        return $router;
    }
}
