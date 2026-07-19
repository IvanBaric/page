<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class NavigationUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value)) {
            $fail(__('URL poveznice nije ispravan.'));

            return;
        }

        $url = trim($value);

        if (
            preg_match('/^\/(?!\/)[^\s]*$/', $url) === 1
            || preg_match('/^#[^\s]+$/', $url) === 1
            || preg_match('/^mailto:[^\s@]+@[^\s@]+\.[^\s@]+$/i', $url) === 1
            || preg_match('/^tel:\+?[0-9][0-9\s().-]*$/i', $url) === 1
        ) {
            return;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        if (in_array($scheme, ['http', 'https'], true) && filter_var($url, FILTER_VALIDATE_URL) !== false) {
            return;
        }

        $fail(__('URL mora biti HTTP/HTTPS adresa, relativna putanja, e-pošta ili telefonska poveznica.'));
    }
}
