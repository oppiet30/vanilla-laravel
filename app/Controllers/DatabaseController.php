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
            'notice' => Session::pullFlash('user_deleted'),
        ]));
    }

    /** @param array<string, string> $parameters */
    public function show(Request $request, array $parameters = []): Response
    {
        $rawId = $parameters['id'] ?? $request->query('id');
        // demo_users uses an unsigned INT primary key. Reject malformed input
        // before connecting; bind the validated ID rather than building SQL.
        if (!$this->validId($rawId)) {
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

        $created = $user === null ? null : Session::pullFlash('user_created:' . $rawId);
        $updated = $user === null ? null : Session::pullFlash('user_updated:' . $rawId);

        return new Response(View::render('database-user', [
            'title' => 'Sample user',
            'message' => $user === null ? 'Sample user not found.' : null,
            'user' => $user,
            'notice' => $updated ?? $created,
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

    /** @param array<string, string> $parameters */
    public function edit(Request $request, array $parameters): Response
    {
        $id = $parameters['id'] ?? null;
        if (!$this->validId($id)) {
            return $this->userError('Enter a positive user ID from 1 to 4294967295.', 422);
        }

        try {
            $user = DemoUser::find($id);
        } catch (PDOException | RuntimeException $error) {
            return $this->userError('Database unavailable. Check your local setup.', 503);
        }
        if ($user === null) {
            return $this->userError('Sample user not found.', 404);
        }

        return $this->editForm($id, $user['name']);
    }

    /** @param array<string, string> $parameters */
    public function update(Request $request, array $parameters): Response
    {
        $id = $parameters['id'] ?? null;
        if (!Csrf::validate($request->input('_token'))) {
            return new Response(View::render('csrf-error', [
                'title' => 'Form verification failed',
                'retryUrl' => $this->validId($id) ? '/database/users/' . $id . '/edit' : '/database',
            ]), 403);
        }
        if (!$this->validId($id)) {
            return $this->userError('Enter a positive user ID from 1 to 4294967295.', 422);
        }

        $name = trim($request->input('name', '') ?? '');
        try {
            $user = DemoUser::find($id);
        } catch (PDOException | RuntimeException $error) {
            return $this->editForm($id, $name, [], 'Unable to save the user. Check your database setup and try again.', 503);
        }
        if ($user === null) {
            return $this->userError('Sample user not found.', 404);
        }

        $errors = Validator::validate(
            ['name' => $name],
            ['name' => ['required' => true, 'max' => 100]],
        );
        if ($errors !== []) {
            return $this->editForm($id, $name, $errors, null, 422);
        }

        try {
            if (!DemoUser::update($id, $name)) {
                return $this->userError('Sample user not found.', 404);
            }
        } catch (PDOException | RuntimeException $error) {
            return $this->editForm($id, $name, [], 'Unable to save the user. Check your database setup and try again.', 503);
        }

        Session::flash('user_updated:' . $id, 'User updated successfully.');

        return Response::redirect('/database/users/' . $id);
    }


    /** @param array<string, string> $parameters */
    public function confirmDelete(Request $request, array $parameters): Response
    {
        $id = $parameters['id'] ?? null;
        if (!$this->validId($id)) {
            return $this->userError('Enter a positive user ID from 1 to 4294967295.', 422);
        }

        try {
            $user = DemoUser::find($id);
        } catch (PDOException | RuntimeException $error) {
            return $this->userError('Database unavailable. Check your local setup.', 503);
        }
        if ($user === null) {
            return $this->userError('Sample user not found.', 404);
        }

        return new Response(View::render('database-user-delete', [
            'title' => 'Delete sample user',
            'user' => $user,
            'csrfToken' => Csrf::token(),
        ]));
    }

    /** @param array<string, string> $parameters */
    public function destroy(Request $request, array $parameters): Response
    {
        $id = $parameters['id'] ?? null;
        if (!Csrf::validate($request->input('_token'))) {
            return new Response(View::render('csrf-error', [
                'title' => 'Form verification failed',
                'retryUrl' => $this->validId($id) ? '/database/users/' . $id . '/delete' : '/database',
            ]), 403);
        }
        if (!$this->validId($id)) {
            return $this->userError('Enter a positive user ID from 1 to 4294967295.', 422);
        }

        try {
            if (!DemoUser::delete($id)) {
                return $this->userError('Sample user not found.', 404);
            }
        } catch (PDOException | RuntimeException $error) {
            return $this->userError('Unable to delete the user. Check your database setup and try again.', 503);
        }

        Session::pullFlash('user_created:' . $id);
        Session::pullFlash('user_updated:' . $id);
        Session::flash('user_deleted', 'User deleted successfully.');

        return Response::redirect('/database');
    }

    private function validId(?string $id): bool
    {
        return $id !== null && preg_match('/^[1-9][0-9]{0,9}$/D', $id) === 1
            && (float) $id <= 4294967295;
    }

    private function userError(string $message, int $status): Response
    {
        return new Response(View::render('database-user', [
            'title' => 'Sample user',
            'message' => $message,
            'user' => null,
        ]), $status);
    }

    /** @param array<string, array<string, string>> $errors */
    private function editForm(string $id, string $name, array $errors = [], ?string $error = null, int $status = 200): Response
    {
        return new Response(View::render('database-user-edit', [
            'title' => 'Edit sample user',
            'id' => $id,
            'name' => $name,
            'errors' => $errors,
            'error' => $error,
            'csrfToken' => Csrf::token(),
        ]), $status);
    }
}
