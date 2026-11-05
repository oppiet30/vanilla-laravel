<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\View;
use App\Csrf;
use App\Session;
use App\Validator;

final class ContactController
{
    public function create(): string
    {
        return View::render('contact', ['title' => 'Contact', 'csrfToken' => Csrf::token()]);
    }

    public function store(Request $request): Response
    {
        if (!Csrf::validate($request->input('_token'))) {
            return new Response(View::render('csrf-error', [
                'title' => 'Form verification failed',
            ]), 403);
        }

        $name = trim($request->input('name', '') ?? '');
        $message = trim($request->input('message', '') ?? '');

        $errors = Validator::validate(
            ['name' => $name, 'message' => $message],
            [
                'name' => ['required' => true, 'max' => 100],
                'message' => ['required' => true, 'max' => 2000],
            ],
        );

        if ($errors !== []) {
            // Preserve the form's existing summary and required-first behavior.
            $required = isset($errors['name']['required']) || isset($errors['message']['required']);

            return new Response(
                View::render('contact', [
                    'title' => 'Contact',
                    'csrfToken' => Csrf::token(),
                    'error' => $required
                        ? 'Name and message are required.'
                        : 'Name must be at most 100 characters and message at most 2000.',
                    'name' => $name,
                    'message' => $message,
                ]),
                422,
            );
        }

        Session::flash('contact', ['name' => $name, 'message' => $message]);

        return Response::redirect('/contact/success');
    }

    public function success(): Response
    {
        $contact = Session::pullFlash('contact');
        if (!is_array($contact)) {
            return Response::redirect('/contact');
        }

        return new Response(View::render('contact-success', [
            'title' => 'Message received',
            'name' => $contact['name'],
            'message' => $contact['message'],
        ]), 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'no-store',
        ]);
    }
}
