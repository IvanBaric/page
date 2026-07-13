<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Support;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use IvanBaric\Pages\Admin\AdminSectionRegistry;
use IvanBaric\Pages\Admin\LayoutVariant;
use IvanBaric\Pages\Admin\Tab;

final readonly class SectionLayoutVariantResolver
{
    public function __construct(
        private AdminSectionRegistry $sections,
    ) {}

    public function layoutTabFor(mixed $section): ?Tab
    {
        return $this->sections->for($section)?->layoutTab();
    }

    public function storagePathFor(mixed $section): ?string
    {
        $storage = (string) ($this->layoutTabFor($section)?->optionValue('storage', 'settings.layout_variant') ?? '');

        return $storage !== '' ? $storage : null;
    }

    /** @return array<int, array{key: string, label: string, description: string}> */
    public function variantsFor(mixed $section): array
    {
        $tab = $this->layoutTabFor($section);

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

    public function hasCycleableVariants(mixed $section): bool
    {
        return $this->storagePathFor($section) !== null && count($this->variantsFor($section)) > 1;
    }

    /** @return array{key: string, label: string, description: string}|null */
    public function nextVariantFor(mixed $section): ?array
    {
        return $this->adjacentVariantFor($section, 1);
    }

    /** @return array{key: string, label: string, description: string}|null */
    public function previousVariantFor(mixed $section): ?array
    {
        return $this->adjacentVariantFor($section, -1);
    }

    /** @return array{key: string, label: string, description: string}|null */
    private function adjacentVariantFor(mixed $section, int $offset): ?array
    {
        $variants = $this->variantsFor($section);

        if (count($variants) < 2) {
            return null;
        }

        $keys = array_column($variants, 'key');
        $current = $this->currentVariantKeyFor($section);
        $currentIndex = array_search($current, $keys, true);

        if ($currentIndex === false) {
            return $variants[0];
        }

        return $variants[($currentIndex + $offset + count($variants)) % count($variants)];
    }

    public function setVariant(Model $section, string $variant): void
    {
        $variants = array_column($this->variantsFor($section), 'key');

        if (! in_array($variant, $variants, true)) {
            throw new InvalidArgumentException('Unknown section layout variant ['.$variant.'].');
        }

        $storage = $this->storagePathFor($section);

        if ($storage === null) {
            throw new InvalidArgumentException('Section does not define layout variant storage.');
        }

        $this->setModelPath($section, $storage, $variant);

        $section->save();
    }

    private function currentVariantKeyFor(mixed $section): ?string
    {
        $variants = $this->variantsFor($section);

        if ($variants === []) {
            return null;
        }

        $keys = array_column($variants, 'key');
        $storage = $this->storagePathFor($section);
        $current = $storage !== null ? data_get($section, $storage) : null;
        $current = is_string($current) && $current !== '' ? $current : $this->defaultVariantKeyFor($section);

        return in_array($current, $keys, true) ? $current : ($this->defaultVariantKeyFor($section) ?? $keys[0]);
    }

    private function defaultVariantKeyFor(mixed $section): ?string
    {
        $default = $this->layoutTabFor($section)?->optionValue('default');

        if (is_string($default) && $default !== '') {
            return $default;
        }

        return $this->variantsFor($section)[0]['key'] ?? null;
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
