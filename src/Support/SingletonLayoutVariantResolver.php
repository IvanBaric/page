<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Support;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use IvanBaric\Pages\Admin\AdminSection;
use IvanBaric\Pages\Admin\AdminSectionRegistry;
use IvanBaric\Pages\Admin\LayoutVariant;
use IvanBaric\Pages\Admin\Tab;

final readonly class SingletonLayoutVariantResolver
{
    public function __construct(
        private AdminSectionRegistry $sections,
    ) {}

    public function definitionFor(string $definitionKey): ?AdminSection
    {
        return $this->sections->get($definitionKey);
    }

    public function layoutTabFor(string $definitionKey): ?Tab
    {
        return $this->definitionFor($definitionKey)?->layoutTab();
    }

    public function storagePathFor(string $definitionKey): ?string
    {
        $definition = $this->definitionFor($definitionKey);
        $tab = $definition?->layoutTab();

        if (! $definition instanceof AdminSection || ! $tab instanceof Tab) {
            return null;
        }

        $rootStorage = trim((string) $definition->optionValue('storage', ''), '.');
        $layoutStorage = trim((string) $tab->optionValue('storage', 'layout_variant'), '.');

        if ($layoutStorage === '') {
            return null;
        }

        if ($rootStorage === '' || $layoutStorage === $rootStorage || str_starts_with($layoutStorage, $rootStorage.'.')) {
            return $layoutStorage;
        }

        return $rootStorage.'.'.$layoutStorage;
    }

    /** @return array<int, array{key: string, label: string, description: string}> */
    public function variantsFor(string $definitionKey): array
    {
        $tab = $this->layoutTabFor($definitionKey);

        if (! $tab instanceof Tab) {
            return [];
        }

        return collect($tab->variantsValue())
            ->filter(fn (LayoutVariant $variant): bool => $variant->key() !== '')
            ->map(fn (LayoutVariant $variant): array => [
                'key' => $variant->key(),
                'label' => $variant->labelValue() !== '' ? $variant->labelValue() : $variant->key(),
                'description' => $variant->descriptionValue(),
            ])
            ->values()
            ->all();
    }

    public function hasCycleableVariants(string $definitionKey): bool
    {
        return $this->storagePathFor($definitionKey) !== null && count($this->variantsFor($definitionKey)) > 1;
    }

    /** @return array{key: string, label: string, description: string}|null */
    public function nextVariantFor(Model $model, string $definitionKey): ?array
    {
        return $this->adjacentVariantFor($model, $definitionKey, 1);
    }

    /** @return array{key: string, label: string, description: string}|null */
    public function previousVariantFor(Model $model, string $definitionKey): ?array
    {
        return $this->adjacentVariantFor($model, $definitionKey, -1);
    }

    /** @return array{key: string, label: string, description: string}|null */
    private function adjacentVariantFor(Model $model, string $definitionKey, int $offset): ?array
    {
        $variants = $this->variantsFor($definitionKey);

        if (count($variants) < 2) {
            return null;
        }

        $keys = array_column($variants, 'key');
        $current = $this->currentVariantKeyFor($model, $definitionKey);
        $currentIndex = array_search($current, $keys, true);

        if ($currentIndex === false) {
            return $variants[0];
        }

        return $variants[($currentIndex + $offset + count($variants)) % count($variants)];
    }

    public function setVariant(Model $model, string $definitionKey, string $variant): void
    {
        $variants = array_column($this->variantsFor($definitionKey), 'key');

        if (! in_array($variant, $variants, true)) {
            throw new InvalidArgumentException('Unknown singleton layout variant ['.$variant.'] for ['.$definitionKey.'].');
        }

        $storage = $this->storagePathFor($definitionKey);

        if ($storage === null) {
            throw new InvalidArgumentException('Singleton layout ['.$definitionKey.'] does not define layout variant storage.');
        }

        $this->setModelPath($model, $storage, $variant);

        $model->save();
    }

    private function currentVariantKeyFor(Model $model, string $definitionKey): ?string
    {
        $variants = $this->variantsFor($definitionKey);

        if ($variants === []) {
            return null;
        }

        $keys = array_column($variants, 'key');
        $storage = $this->storagePathFor($definitionKey);
        $current = $storage !== null ? data_get($model, $storage) : null;
        $current = is_string($current) && $current !== '' ? $current : $this->defaultVariantKeyFor($definitionKey);

        return in_array($current, $keys, true) ? $current : ($this->defaultVariantKeyFor($definitionKey) ?? $keys[0]);
    }

    private function defaultVariantKeyFor(string $definitionKey): ?string
    {
        $default = $this->layoutTabFor($definitionKey)?->optionValue('default');

        if (is_string($default) && $default !== '') {
            return $default;
        }

        return $this->variantsFor($definitionKey)[0]['key'] ?? null;
    }

    private function setModelPath(Model $model, string $path, mixed $value): void
    {
        if (! str_contains($path, '.')) {
            $model->forceFill([$path => $value]);

            return;
        }

        [$attribute, $nestedPath] = explode('.', $path, 2);
        $payload = $model->getAttribute($attribute);
        $payload = is_array($payload) ? $payload : [];

        data_set($payload, $nestedPath, $value);

        $model->forceFill([$attribute => $payload]);
    }
}
