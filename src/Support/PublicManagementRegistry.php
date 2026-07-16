<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Support;

use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use IvanBaric\Pages\Contracts\PublicManagementPanelDataProvider;
use IvanBaric\Pages\Data\PublicManagementPanel;

final readonly class PublicManagementRegistry
{
    public function __construct(private Container $container) {}

    public function get(string $key): ?PublicManagementPanel
    {
        if (preg_match('/\A[a-z0-9][a-z0-9_-]*\z/iD', $key) !== 1) {
            return null;
        }

        $definition = config('pages.public_management.panels.'.$key);

        if ($definition === null) {
            return null;
        }

        if (! is_array($definition)) {
            throw new InvalidArgumentException("Public management panel [{$key}] must be an array.");
        }

        $panel = new PublicManagementPanel(
            key: $key,
            title: $this->requiredString($definition, 'title', $key),
            icon: $this->requiredString($definition, 'icon', $key),
            permission: $this->nullableString($definition['permission'] ?? null),
            component: $this->nullableString($definition['component'] ?? null),
            view: $this->nullableString($definition['view'] ?? null),
            event: $this->nullableString($definition['event'] ?? null),
            dataProvider: $this->nullableString($definition['data_provider'] ?? null),
            parameters: is_array($definition['parameters'] ?? null) ? $definition['parameters'] : [],
        );

        $presentationCount = count(array_filter(
            [$panel->component, $panel->view, $panel->event],
            static fn (?string $value): bool => $value !== null,
        ));

        if ($presentationCount !== 1) {
            throw new InvalidArgumentException(
                "Public management panel [{$key}] must define exactly one component, view or event.",
            );
        }

        if ($panel->dataProvider !== null && $panel->view === null) {
            throw new InvalidArgumentException(
                "Public management panel [{$key}] may define a data provider only for a view panel.",
            );
        }

        return $panel;
    }

    /** @return array<string, mixed> */
    public function dataFor(PublicManagementPanel $panel): array
    {
        if ($panel->dataProvider === null) {
            return [];
        }

        $provider = $this->container->make($panel->dataProvider);

        if (! $provider instanceof PublicManagementPanelDataProvider) {
            throw new InvalidArgumentException(
                "Public management data provider [{$panel->dataProvider}] must implement ".PublicManagementPanelDataProvider::class.'.',
            );
        }

        return $provider->data($panel);
    }

    /** @param array<string, mixed> $definition */
    private function requiredString(array $definition, string $field, string $key): string
    {
        $value = $this->nullableString($definition[$field] ?? null);

        if ($value === null) {
            throw new InvalidArgumentException("Public management panel [{$key}] must define [{$field}].");
        }

        return $value;
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
