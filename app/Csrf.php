<?php

declare(strict_types=1);

namespace App;

final class Csrf
{
    public static function token(): string
    {
        $token = Session::get('_csrf_token');
        if (!is_string($token) || strlen($token) !== 64) {
            $token = bin2hex(random_bytes(32));
            Session::put('_csrf_token', $token);
        }

        return $token;
    }

    public static function validate(?string $submitted): bool
    {
        // Do not generate a token during validation: a form must be loaded first.
        $known = Session::get('_csrf_token');

        return is_string($known) && strlen($known) === 64
            && $submitted !== null && strlen($submitted) === 64
            && hash_equals($known, $submitted);
    }
}
