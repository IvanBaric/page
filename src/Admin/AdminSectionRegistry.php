<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Admin;

final class AdminSectionRegistry
{
    /** @var array<string, AdminSection>|null */
    private ?array $definitions = null;

    /** @return array<string, AdminSection> */
    public function all(): array
    {
        if ($this->definitions !== null) {
            return $this->definitions;
        }

        $definitions = [];

        foreach ((array) config('pages.admin_section_definitions', []) as $source) {
            $definitions = array_replace($definitions, $this->collect($source));
        }

        foreach ((array) config('pages.admin_sections', []) as $source) {
            $definitions = array_replace($definitions, $this->collect($source));
        }

        return $this->definitions = $definitions;
    }

    public function get(string $key): ?AdminSection
    {
        $definitions = $this->all();

        if (isset($definitions[$key])) {
            return $definitions[$key];
        }

        $alias = data_get(config('pages.section_editor_aliases', []), $key);

        return is_string($alias) ? ($definitions[$alias] ?? null) : null;
    }

    public function for(mixed $section): ?AdminSection
    {
        $key = is_string($section) ? $section : null;

        if ($key === null && is_object($section) && method_exists($section, 'getAttribute')) {
            $key = (string) $section->getAttribute('type');
        }

        if ($key === null || $key === '') {
            return null;
        }

        return $this->get($key);
    }

    public function flush(): void
    {
        $this->definitions = null;
    }

    /** @return array<string, AdminSection> */
    private function collect(mixed $source): array
    {
        if ($source instanceof AdminSection) {
            return [$source->key() => $source];
        }

        if (is_array($source)) {
            if (array_key_exists('key', $source)) {
                $section = AdminSection::fromArray($source);

                return [$section->key() => $section];
            }

            $definitions = [];

            foreach ($source as $key => $definition) {
                if (is_array($definition) && is_string($key) && ! array_key_exists('key', $definition)) {
                    $definition = ['key' => $key] + $definition;
                }

                $definitions = array_replace($definitions, $this->collect($definition));
            }

            return $definitions;
        }

        if (is_string($source) && class_exists($source)) {
            $provider = app($source);

            if ($provider instanceof AdminSection) {
                return [$provider->key() => $provider];
            }

            if (! is_object($provider)) {
                return [];
            }

            if (method_exists($provider, 'definitions')) {
                return $this->collect($provider->definitions());
            }

            if (method_exists($provider, 'make')) {
                return $this->collect($provider->make());
            }

            if (is_callable($provider)) {
                return $this->collect($provider());
            }
        }

        if (is_callable($source)) {
            return $this->collect(app()->call($source));
        }

        return [];
    }
}
