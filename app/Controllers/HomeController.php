<?php

declare(strict_types=1);

namespace App\Controllers;

final class HomeController
{
    public function index(): string
    {
        return '<h1>Welcome to Vanilla Laravel</h1><p>Our router is working!</p>';
    }

    public function about(): string
    {
        return '<h1>About</h1><p>This application uses vanilla PHP routing.</p>';
    }
}
