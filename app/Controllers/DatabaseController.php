<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\DemoUser;
use App\Http\Response;
use App\Http\Request;
use App\View;
use PDOException;
use RuntimeException;

final class DatabaseController
{
    public function index(): Response
    {
        try {
            $users = DemoUser::all();
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

    /** @param array<string, string> $parameters */
    public function show(Request $request, array $parameters = []): Response
    {
        $rawId = $parameters['id'] ?? $request->query('id');
        // demo_users uses an unsigned INT primary key. Reject malformed input
        // before connecting; bind the validated ID rather than building SQL.
        if ($rawId === null || !preg_match('/^[1-9][0-9]{0,9}$/D', $rawId)
            || (float) $rawId > 4294967295) {
            return new Response(View::render('database-user', [
                'title' => 'Sample user',
                'message' => 'Enter a positive user ID from 1 to 4294967295.',
                'user' => null,
            ]), 422);
        }

        try {
            $user = DemoUser::find($rawId);
        } catch (PDOException | RuntimeException $error) {
            return new Response(View::render('database-user', [
                'title' => 'Sample user',
                'message' => 'Database unavailable. Check your local setup.',
                'user' => null,
            ]), 503);
        }

        return new Response(View::render('database-user', [
            'title' => 'Sample user',
            'message' => $user === null ? 'Sample user not found.' : null,
            'user' => $user,
        ]), $user === null ? 404 : 200);
    }
}
