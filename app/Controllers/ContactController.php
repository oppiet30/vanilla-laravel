<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\View;
use App\Session;

final class ContactController
{
    public function create(): string
    {
        return View::render('contact', ['title' => 'Contact']);
    }

    public function store(Request $request): Response
    {
        $name = trim($request->input('name', '') ?? '');
        $message = trim($request->input('message', '') ?? '');

        if ($name === '' || $message === '') {
            return new Response(
                View::render('contact', [
                    'title' => 'Contact',
                    'error' => 'Name and message are required.',
                    'name' => $name,
                    'message' => $message,
                ]),
                422,
            );
        }

        if (mb_strlen($name) > 100 || mb_strlen($message) > 2000) {
            return new Response(
                View::render('contact', [
                    'title' => 'Contact',
                    'error' => 'Name must be at most 100 characters and message at most 2000.',
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
