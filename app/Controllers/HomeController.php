<?php

declare(strict_types=1);

namespace App\Controllers;

use App\View;

final class HomeController
{
    public function index(): string
    {
        return View::render('home', ['title' => 'Welcome to Vanilla Laravel']);
    }

    public function about(): string
    {
        return View::render('about', ['title' => 'About']);
    }
}
