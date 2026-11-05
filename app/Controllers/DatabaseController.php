<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\DemoUser;
use App\Http\Response;
use App\Http\Request;
use App\View;
use App\Csrf;
use App\Session;
use App\Validator;
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
            'notice' => $user === null ? null : Session::pullFlash('user_created:' . $rawId),
        ]), $user === null ? 404 : 200);
    }

    public function create(): string
    {
        return View::render('database-user-create', [
            'title' => 'New sample user',
            'csrfToken' => Csrf::token(),
        ]);
    }

    public function store(Request $request): Response
    {
        if (!Csrf::validate($request->input('_token'))) {
            return new Response(View::render('csrf-error', [
                'title' => 'Form verification failed',
                'retryUrl' => '/database/users/create',
            ]), 403);
        }

        $name = trim($request->input('name', '') ?? '');
        $errors = Validator::validate(
            ['name' => $name],
            ['name' => ['required' => true, 'max' => 100]],
        );
        if ($errors !== []) {
            return new Response(View::render('database-user-create', [
                'title' => 'New sample user',
                'csrfToken' => Csrf::token(),
                'name' => $name,
                'errors' => $errors,
            ]), 422);
        }

        try {
            $id = DemoUser::create($name);
        } catch (PDOException | RuntimeException $error) {
            return new Response(View::render('database-user-create', [
                'title' => 'New sample user',
                'csrfToken' => Csrf::token(),
                'name' => $name,
                'error' => 'Unable to save the user. Check your database setup and try again.',
            ]), 503);
        }

        Session::flash('user_created:' . $id, 'User created successfully.');

        return Response::redirect('/database/users/' . $id);
    }
}
