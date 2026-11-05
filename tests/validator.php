<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Validator;

function check(bool $condition, string $label): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $label);
    }
    echo 'PASS: ' . $label . PHP_EOL;
}

$rules = ['name' => ['required' => true, 'max' => 100], 'message' => ['required' => true, 'max' => 2000]];
check(Validator::validate(['name' => 'Oppie', 'message' => 'Hello'], $rules) === [], 'valid text');
foreach ([[], ['name' => '', 'message' => ''], ['name' => " \t\n", 'message' => null]] as $data) {
    $errors = Validator::validate($data, $rules);
    check(isset($errors['name']['required'], $errors['message']['required']), 'required fields');
}
check(Validator::validate(['name' => '0', 'message' => '0'], $rules) === [], 'zero is valid text');
check(Validator::validate(['name' => str_repeat('a', 100), 'message' => str_repeat('b', 2000)], $rules) === [], 'exact length limits');
$errors = Validator::validate(['name' => str_repeat('a', 101), 'message' => str_repeat('b', 2001)], $rules);
check(isset($errors['name']['max'], $errors['message']['max']), 'over-limit fields');
check(Validator::validate(['name' => str_repeat('é', 100), 'message' => str_repeat('🙂', 2000)], $rules) === [], 'Unicode characters counted rather than bytes');
$errors = Validator::validate(['name' => str_repeat('é', 101), 'message' => 'Hello'], $rules);
check(isset($errors['name']['max']), 'Unicode over-limit');
$errors = Validator::validate(['name' => ['unexpected'], 'message' => 123], $rules);
check(isset($errors['name']['string'], $errors['message']['string']), 'non-text rejected');
check(Validator::validate([], ['note' => ['max' => 10]]) === [], 'optional field absent');
check(Validator::validate(['note' => ''], ['note' => ['max' => 0]]) === [], 'empty optional field at zero limit');
check(isset(Validator::validate(['note' => 'x'], ['note' => ['max' => 0]])['note']['max']), 'zero limit enforced');
$data = ['name' => '  Oppie  ', 'message' => 'Hello', 'extra' => 'ignored'];
$copy = $data;
Validator::validate($data, $rules);
check($data === $copy, 'validation does not modify data');
check(Validator::validate(['title' => 'Book'], ['title' => ['required' => true, 'max' => 20]]) === [], 'rules reusable for another form');
