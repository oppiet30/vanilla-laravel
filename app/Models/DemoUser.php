<?php

declare(strict_types=1);

namespace App\Models;

use App\Database;
use PDO;

/**
 * Read-only access to demo_users. This small model owns SQL, not HTTP responses.
 */
final class DemoUser
{
    /** @return list<array{id: int|string, name: string}> */
    public static function all(): array
    {
        return Database::connection()
            ->query('SELECT id, name FROM demo_users ORDER BY id LIMIT 20')
            ->fetchAll();
    }

    /**
     * The controller supplies a validated unsigned INT ID as a decimal string.
     * @return array{id: int|string, name: string}|null
     */
    public static function find(string $id): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, name FROM demo_users WHERE id = :id'
        );
        $statement->bindValue(':id', $id, PDO::PARAM_STR);
        $statement->execute();
        $user = $statement->fetch();

        // null means not found; database failures still throw to the controller.
        return $user === false ? null : $user;
    }
}
