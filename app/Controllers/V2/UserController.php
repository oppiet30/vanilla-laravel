<?php

declare(strict_types=1);

namespace App\Controllers\Api\V2;

final class UserController
{
    // Temporary data until MariaDB integration.
    private const USERS = [
        1 => [
            'id' => 1,
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.test',
        ],
        2 => [
            'id' => 2,
            'name' => 'Alan Turing',
            'email' => 'alan@example.test',
        ],
    ];

    public function index(): string
    {
        return $this->json(200, [
            'data' => array_values(self::USERS),
            'meta' => [
                'api_version' => 'v2',
                'count' => count(self::USERS),
            ],
        ]);
    }

    public function show(string $id): string
    {
        if (!ctype_digit($id) || (int) $id < 1) {
            return $this->json(400, [
                'error' => [
                    'code' => 'INVALID_USER_ID',
                    'message' => 'The user ID must be a positive integer.',
                ],
            ]);
        }

        $user = self::USERS[(int) $id] ?? null;

        if ($user === null) {
            return $this->json(404, [
                'error' => [
                    'code' => 'USER_NOT_FOUND',
                    'message' => 'User not found.',
                ],
            ]);
        }

        return $this->json(200, [
            'data' => $user,
        ]);
    }

    private function json(int $status, array $payload): string
    {
        http_response_code($status);

        header('Content-Type: application/json; charset=utf-8');

        return json_encode(
            $payload,
            JSON_PRETTY_PRINT
                | JSON_UNESCAPED_SLASHES
                | JSON_THROW_ON_ERROR
        );
    }
}
