<?php

declare(strict_types=1);

namespace App\Http;

use InvalidArgumentException;
use JsonException;

final class Request
{
    private readonly string $method;
    private readonly string $path;
    private readonly array $headers;

    public function __construct(
        string $method,
        string $uri,
        private readonly array $queryParameters = [],
        array $headers = [],
        private readonly string $rawBody = ''
    ) {
        $this->method = strtoupper($method);
        $this->path = parse_url($uri, PHP_URL_PATH) ?: '/';

        $normalized = [];

        foreach ($headers as $name => $value) {
            $normalized[strtolower((string) $name)] = (string) $value;
        }

        $this->headers = $normalized;
    }

    public static function capture(): self
    {
        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', substr($key, 5));
                $headers[$name] = (string) $value;
            }
        }

        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['Content-Type'] = (string) $_SERVER['CONTENT_TYPE'];
        }

        return new self(
            $_SERVER['REQUEST_METHOD'] ?? 'GET',
            $_SERVER['REQUEST_URI'] ?? '/',
            $_GET,
            $headers,
            file_get_contents('php://input') ?: ''
        );
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function query(string $name, mixed $default = null): mixed
    {
        return $this->queryParameters[$name] ?? $default;
    }

    public function header(
        string $name,
        ?string $default = null
    ): ?string {
        return $this->headers[strtolower($name)] ?? $default;
    }

    public function body(): string
    {
        return $this->rawBody;
    }

    public function json(): array
    {
        if (trim($this->rawBody) === '') {
            return [];
        }

        try {
            $decoded = json_decode(
                $this->rawBody,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException $exception) {
            throw new InvalidArgumentException(
                'Malformed JSON request body.',
                0,
                $exception
            );
        }

        if (!is_array($decoded)) {
            throw new InvalidArgumentException(
                'JSON request body must be an object or array.'
            );
        }

        return $decoded;
    }
}
