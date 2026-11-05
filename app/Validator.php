<?php

declare(strict_types=1);

namespace App;

final class Validator
{
    /**
     * Validate text fields; callers handle normalization such as trimming.
     * Errors are keyed by field and rule, so forms can choose their messages.
     *
     * @param array<string, mixed> $data
     * @param array<string, array{required?: bool, max?: non-negative-int}> $rules
     * @return array<string, array<string, string>>
     */
    public static function validate(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $constraints) {
            $value = $data[$field] ?? null;
            if ($value !== null && !is_string($value)) {
                $errors[$field]['string'] = 'This field must be text.';
                continue;
            }

            if (($constraints['required'] ?? false) && ($value === null || trim($value) === '')) {
                $errors[$field]['required'] = 'This field is required.';
                continue;
            }

            if ($value !== null && isset($constraints['max'])
                && mb_strlen($value) > $constraints['max']) {
                $errors[$field]['max'] = 'This field must be at most ' . $constraints['max'] . ' characters.';
            }
        }

        return $errors;
    }
}
