<?php

declare(strict_types=1);

namespace App;

use RuntimeException;

final class View
{
    public static function render(string $name, array $data = []): string
    {
        // Keep view names within resources/views.
        if (!preg_match('/^[a-zA-Z0-9_-]+(?:\.[a-zA-Z0-9_-]+)*$/D', $name)) {
            throw new RuntimeException('Invalid view name.');
        }

        $path = dirname(__DIR__) . '/resources/views/' . str_replace('.', '/', $name) . '.php';

        if (!is_file($path)) {
            throw new RuntimeException("View not found: {$name}");
        }

        $render = static function (string $file, array $variables): string {
            extract($variables, EXTR_SKIP);
            ob_start();
            try {
                require $file;
                return (string) ob_get_clean();
            } catch (\Throwable $error) {
                ob_end_clean();
                throw $error;
            }
        };

        $content = $render($path, $data);
        return $render(dirname(__DIR__) . '/resources/views/layouts/app.php', [
            'title' => $data['title'] ?? 'Vanilla Laravel',
            'content' => $content,
        ]);
    }
}
