<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Support;

use IvanBaric\Pages\Models\Page;
use IvanBaric\TemplateEngine\Contracts\TemplateEngineContract;
use IvanBaric\TemplateEngine\Data\SectionDefinition;

final readonly class AvailableSectionTypes
{
    public function __construct(
        private TemplateEngineContract $templates,
    ) {}

    /** @return array<string, array<string, mixed>> */
    public function forPage(Page $page): array
    {
        return array_filter(
            $this->supportedForPage($page),
            fn (array $config, string $type): bool => ! in_array($type, $this->excludedTypes(), true),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    /** @return array<string, array<string, mixed>> */
    public function supportedForPage(Page $page): array
    {
        $configured = (array) config('pages.section_types', []);
        $definitions = $this->templateDefinitions($page);

        if ($definitions === null) {
            return $this->filterConfigured($configured);
        }

        $available = [];
        $orderedTypes = array_values(array_unique([
            ...array_keys($configured),
            ...array_keys($definitions),
        ]));

        foreach ($orderedTypes as $type) {
            $definition = $definitions[$type] ?? null;

            if (! $definition instanceof SectionDefinition) {
                continue;
            }

            if (! $this->isSupported($type, $definition)) {
                continue;
            }

            $available[$type] = array_replace([
                'label' => $definition->label !== null ? __($definition->label) : str($type)->headline()->toString(),
            ], $definition->metadata, (array) ($configured[$type] ?? []));
        }

        return $available;
    }

    public function contains(Page $page, string $type): bool
    {
        return array_key_exists($type, $this->supportedForPage($page));
    }

    /** @return array<string, SectionDefinition>|null */
    private function templateDefinitions(Page $page): ?array
    {
        if ($this->templates->templates() === []) {
            return null;
        }

        $templateKey = $this->templates->resolveTemplateKey($page);

        return $this->templates->template($templateKey)->sections;
    }

    private function isSupported(string $type, SectionDefinition $definition): bool
    {
        if (! $definition->enabled || data_get($definition->metadata, 'creatable', true) === false) {
            return false;
        }

        return ! str_starts_with($type, 'template_');
    }

    /**
     * @param  array<string, mixed>  $configured
     * @return array<string, array<string, mixed>>
     */
    private function filterConfigured(array $configured): array
    {
        return array_filter(
            $configured,
            fn (mixed $config, string $type): bool => is_array($config)
                && ! str_starts_with($type, 'template_'),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    /** @return array<int, string> */
    private function excludedTypes(): array
    {
        return array_values(array_filter(
            (array) config('pages.section_creator.exclude_types', ['hero']),
            static fn (mixed $type): bool => is_string($type) && $type !== '',
        ));
    }
}
