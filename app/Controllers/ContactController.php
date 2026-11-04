<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\View;

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

        return new Response(View::render('contact-success', [
            'title' => 'Message received',
            'name' => $name,
            'message' => $message,
        ]));
    }
}
