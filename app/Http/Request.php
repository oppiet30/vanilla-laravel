<?php

declare(strict_types=1);

namespace App\Http;

final class Request
{
    /** @param array<string, mixed> $post */
    public function __construct(
        public readonly string $method,
        public readonly string $uri,
        private readonly array $post = [],
    ) {
    }

    public static function capture(): self
    {
        return new self(
            $_SERVER['REQUEST_METHOD'] ?? 'GET',
            $_SERVER['REQUEST_URI'] ?? '/',
            $_POST,
        );
    }

    public function path(): string
    {
        $path = parse_url($this->uri, PHP_URL_PATH);

        return is_string($path) ? $path : '/';
    }

    /** Read a query-string value separately from POST form input. */
    public function query(string $key, ?string $default = null): ?string
    {
        $value = $this->queryValues()[$key] ?? null;

        return is_string($value) ? $value : $default;
    }

    public function hasQuery(string $key): bool
    {
        return array_key_exists($key, $this->queryValues());
    }

    /** @return array<string, mixed> */
    private function queryValues(): array
    {
        $query = parse_url($this->uri, PHP_URL_QUERY);
        $values = [];
        if (is_string($query)) {
            parse_str($query, $values);
        }

        return $values;
    }

    public function input(string $key, ?string $default = null): ?string
    {
        $value = $this->post[$key] ?? null;

        return is_string($value) ? $value : $default;
    }
}
