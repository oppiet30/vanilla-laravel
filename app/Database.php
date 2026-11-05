<?php

declare(strict_types=1);

namespace App;

use PDO;
use RuntimeException;

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection !== null) {
            return self::$connection;
        }

        // Load only when needed: prior routes do not require a database.
        $path = dirname(__DIR__) . '/.env';
        $values = is_file($path) ? @parse_ini_file($path, false, INI_SCANNER_RAW) : [];
        if ($values === false) {
            throw new RuntimeException('Invalid database configuration.');
        }

        // Process environment takes precedence, including intentionally empty values.
        $get = static function (string $key, string $default = '') use ($values): string {
            $value = getenv($key);
            if ($value !== false) {
                return $value;
            }
            $value = $values[$key] ?? $default;
            if (!is_string($value)) {
                throw new RuntimeException('Invalid database configuration.');
            }
            return $value;
        };

        $host = $get('DB_HOST', '127.0.0.1');
        $port = $get('DB_PORT', '3306');
        $database = $get('DB_DATABASE');
        $username = $get('DB_USERNAME');
        if ($host === '' || $database === '' || $username === ''
            || preg_match('/[;\x00-\x20]/', $host . $database)
            || !ctype_digit($port) || (int) $port < 1 || (int) $port > 65535) {
            throw new RuntimeException('Invalid database configuration.');
        }

        // MariaDB uses PDO's mysql driver. utf8mb4 supports full Unicode.
        self::$connection = new PDO(
            "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
            $username,
            $get('DB_PASSWORD'),
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        );

        return self::$connection;
    }
}
