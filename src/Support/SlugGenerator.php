<?php

namespace IvanBaric\Pages\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

final class SlugGenerator
{
    public function generate(Model $model, string $source): string
    {
        $slug = $this->generateWithSanigen($source) ?? Str::slug($source);
        $slug = $slug !== '' ? $slug : (string) Str::uuid();

        return $this->unique($model, $slug);
    }

    private function generateWithSanigen(string $source): ?string
    {
        $generator = config('pages.slug.sanigen.generator');
        $method = config('pages.slug.sanigen.method', 'generate');

        if (is_string($generator) && class_exists($generator) && method_exists($generator, $method)) {
            return (string) app($generator)->{$method}($source);
        }

        foreach ([
            'IvanBaric\\Sanigen\\Facades\\Sanigen',
            'IvanBaric\\Sanigen\\Support\\Sanigen',
            'IvanBaric\\Sanigen\\Sanigen',
        ] as $class) {
            if (class_exists($class) && method_exists($class, 'slug')) {
                return (string) $class::slug($source);
            }
        }

        return null;
    }

    private function unique(Model $model, string $slug): string
    {
        $base = $slug;
        $counter = 2;

        while ($this->exists($model, $slug)) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    private function exists(Model $model, string $slug): bool
    {
        $query = $model->newQuery()->where('slug', $slug);

        if (in_array(SoftDeletes::class, class_uses_recursive($model), true)) {
            $query->withTrashed();
        }

        if (config('pages.slug.scoped_to_team', true)) {
            $model->team_id === null
                ? $query->whereNull('team_id')
                : $query->where('team_id', $model->team_id);
        }

        if ($model->exists) {
            $query->whereKeyNot($model->getKey());
        }

        return $query->exists();
    }
}
