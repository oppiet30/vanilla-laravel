<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use App\Http\Response;
use App\View;
use PDOException;
use RuntimeException;

final class DatabaseController
{
    public function index(): Response
    {
        try {
            $users = Database::connection()
                ->query('SELECT id, name FROM demo_users ORDER BY id LIMIT 20')
                ->fetchAll();
        } catch (PDOException | RuntimeException $error) {
            // Never expose credentials, SQL details, or connection messages.
            return new Response(View::render('database', [
                'title' => 'Database demo',
                'users' => [],
                'unavailable' => true,
            ]), 503);
        }

        return new Response(View::render('database', [
            'title' => 'Database demo',
            'users' => $users,
            'unavailable' => false,
        ]));
    }
}
