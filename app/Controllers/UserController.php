<?php

declare(strict_types=1);

namespace App\Controllers;

use App\View;

final class UserController
{
    public function index(): string
    {
        return View::render('users.index', ['title' => 'Users']);
    }
}
