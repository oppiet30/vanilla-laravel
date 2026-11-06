<?php

declare(strict_types=1);

namespace App\Models;

use App\Database;
use PDO;
use RuntimeException;

/**
 * Access to demo_users. This small model owns SQL, not HTTP responses.
 */
final class DemoUser
{
    /** @return list<array{id: int|string, name: string}> */
    public static function all(): array
    {
        return Database::connection()
            ->query('SELECT id, name FROM demo_users ORDER BY id DESC LIMIT 20')
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

    /** The controller supplies a trimmed, validated name. */
    public static function create(string $name): string
    {
        $connection = Database::connection();
        $statement = $connection->prepare('INSERT INTO demo_users (name) VALUES (:name)');
        $statement->bindValue(':name', $name, PDO::PARAM_STR);
        $statement->execute();
        $id = $connection->lastInsertId();
        if ($id === false || $id === '0') {
            throw new RuntimeException('Unable to determine the created user ID.');
        }

        return $id;
    }

    /** Return false only when the ID no longer exists, including unchanged names. */
    public static function update(string $id, string $name): bool
    {
        $statement = Database::connection()->prepare(
            'UPDATE demo_users SET name = :name WHERE id = :id'
        );
        $statement->bindValue(':name', $name, PDO::PARAM_STR);
        $statement->bindValue(':id', $id, PDO::PARAM_STR);
        $statement->execute();

        // MariaDB can report zero changed rows when the name is unchanged.
        return $statement->rowCount() > 0 || self::find($id) !== null;
    }

    /** Delete exactly the requested record; false means it was already absent. */
    public static function delete(string $id): bool
    {
        $statement = Database::connection()->prepare('DELETE FROM demo_users WHERE id = :id');
        $statement->bindValue(':id', $id, PDO::PARAM_STR);
        $statement->execute();

        return $statement->rowCount() === 1;
    }
}
