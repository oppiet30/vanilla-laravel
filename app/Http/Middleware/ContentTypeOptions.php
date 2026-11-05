<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Middleware;
use App\Http\Request;
use App\Http\Response;

final class ContentTypeOptions implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        // Run the remaining pipeline before adding a response header.
        $response = $next($request);

        return new Response(
            $response->body,
            $response->status,
            array_merge($response->headers, ['X-Content-Type-Options' => 'nosniff']),
        );
    }
}
