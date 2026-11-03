<?php

declare(strict_types=1);

namespace App\Http;

final class JsonResponse
{
    public function __construct(
        private readonly array $data,
        private readonly int $status = 200
    ) {
        if ($status < 100 || $status > 599) {
            throw new \InvalidArgumentException(
                'Invalid HTTP status code.'
            );
        }
    }

    public function render(): string
    {
        http_response_code($this->status);

        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');

        return json_encode(
            $this->data,
            JSON_PRETTY_PRINT
                | JSON_UNESCAPED_SLASHES
                | JSON_THROW_ON_ERROR
        );
    }
}
