<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Middleware;
use App\Http\Request;
use App\Http\Response;
use App\Session;

final class StartSession implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        Session::start();

        try {
            return $next($request);
        } finally {
            // Persist flash data and release PHP's session lock before sending.
            session_write_close();
        }
    }
}
