<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Actions\Concerns;

use Illuminate\Database\Eloquent\Model;

trait MergesTranslatableAttributes
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $fields
     * @return array<string, mixed>
     */
    protected function mergeTranslatableAttributes(Model $model, array $data, array $fields): array
    {
        foreach ($fields as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            $existing = $model->getAttribute($field);
            $translations = is_array($existing) ? $existing : [];

            if (is_array($data[$field])) {
                $data[$field] = array_replace($translations, $data[$field]);

                continue;
            }

            if ($data[$field] === null && $translations !== []) {
                unset($translations[$this->translationLocale()]);
                $data[$field] = $translations !== [] ? $translations : null;
            }
        }

        return $data;
    }

    private function translationLocale(): string
    {
        return corexis_locale_code()
            ?: (string) config('pages.translatable.default_locale')
            ?: (string) config('app.locale', 'en');
    }
}
