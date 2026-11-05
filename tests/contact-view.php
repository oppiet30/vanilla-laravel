<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\View;

function check(bool $condition, string $label): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $label);
    }
    echo 'PASS: ' . $label . PHP_EOL;
}

$base = ['title' => 'Contact', 'csrfToken' => 'test-token'];
$html = View::render('contact', $base);
check(!str_contains($html, 'aria-invalid') && !str_contains($html, 'id="name-error"') && !str_contains($html, 'id="message-error"'), 'fresh form has no error markers');
check(str_contains($html, 'name="_token" value="test-token"'), 'CSRF field retained');

foreach ([
    ['name' => ['required' => 'This field is required.']],
    ['message' => ['max' => 'This field must be at most 2000 characters.']],
    ['name' => ['max' => 'This field must be at most 100 characters.'], 'message' => ['required' => 'This field is required.']],
] as $errors) {
    $html = View::render('contact', $base + ['errors' => $errors, 'error' => 'Existing summary']);
    foreach (['name', 'message'] as $field) {
        $hasError = isset($errors[$field]);
        check(str_contains($html, 'aria-describedby="' . $field . '-error"') === $hasError, $field . ' linked only when invalid');
        check(str_contains($html, 'id="' . $field . '-error"') === $hasError, $field . ' error appears only when invalid');
        if ($hasError) {
            check(str_contains($html, implode(' ', $errors[$field])), $field . ' error text shown');
        }
    }
    check(substr_count($html, 'aria-invalid="true"') === count($errors), 'only affected fields marked invalid');
    check(str_contains($html, 'role="alert">Existing summary'), 'existing summary retained');
}

$html = View::render('contact', [
    'csrfToken' => 'token" onclick="bad',
    'name' => '"><script>bad</script>',
    'message' => '</textarea><script>bad</script>',
    'errors' => ['name' => ['custom' => '<b>bad</b>']],
    'error' => '<script>summary</script>',
]);
check(!str_contains($html, '<script>') && !str_contains($html, '<b>bad</b>'), 'values and errors escaped');
check(str_contains($html, '&quot;&gt;&lt;script&gt;bad&lt;/script&gt;'), 'name retained safely');
check(str_contains($html, '&lt;/textarea&gt;&lt;script&gt;bad&lt;/script&gt;'), 'message retained safely');
check(str_contains($html, 'token&quot; onclick=&quot;bad'), 'CSRF value escaped');
