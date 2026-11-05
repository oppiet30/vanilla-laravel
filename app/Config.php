<?php

declare(strict_types=1);

namespace App;

use RuntimeException;

final class Config
{
    /** @var array<string, mixed>|null */
    private ?array $values = null;

    public function __construct(private readonly string $path)
    {
    }

    public static function load(): self
    {
        return new self(dirname(__DIR__) . '/.env');
    }

    public function get(string $key, string $default = ''): string
    {
        // Explicit empty process values also override the local file.
        $environment = getenv($key);
        if ($environment !== false) {
            return $environment;
        }

        // Load once per instance, only if a file-backed value is needed.
        if ($this->values === null) {
            $values = is_file($this->path)
                ? @parse_ini_file($this->path, false, INI_SCANNER_RAW)
                : [];
            if ($values === false) {
                throw new RuntimeException('Invalid configuration file.');
            }
            $this->values = $values;
        }

        $value = $this->values[$key] ?? $default;
        if (!is_string($value)) {
            throw new RuntimeException('Configuration values must be text.');
        }

        return $value;
    }
}
