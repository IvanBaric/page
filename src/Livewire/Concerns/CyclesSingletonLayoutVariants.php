<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Livewire\Concerns;

use Illuminate\Database\Eloquent\Model;
use IvanBaric\Pages\Support\SingletonLayoutVariantResolver;

trait CyclesSingletonLayoutVariants
{
    protected function canCycleSingletonLayoutVariant(string $definitionKey): bool
    {
        $model = $this->singletonLayoutVariantModel($definitionKey);

        return $model instanceof Model
            && $this->singletonBelongsToCurrentTenant($model)
            && corexis_can('pages.update', $model)
            && $this->singletonLayoutVariantResolver()->hasCycleableVariants($definitionKey);
    }

    protected function cycleSingletonLayoutVariant(string $definitionKey, string $direction = 'next'): void
    {
        abort_unless(in_array($direction, ['next', 'previous'], true), 422);

        $model = $this->singletonLayoutVariantModel($definitionKey);
        abort_unless($model instanceof Model && $this->singletonBelongsToCurrentTenant($model), 404);
        corexis_authorize('pages.update', $model);

        $resolver = $this->singletonLayoutVariantResolver();
        $nextVariant = $direction === 'previous'
            ? $resolver->previousVariantFor($model, $definitionKey)
            : $resolver->nextVariantFor($model, $definitionKey);

        abort_unless(is_array($nextVariant), 404);

        $resolver->setVariant($model, $definitionKey, (string) $nextVariant['key']);
        $this->singletonLayoutVariantWasCycled($model, $definitionKey);
    }

    protected function nextSingletonLayoutVariantLabel(string $definitionKey): string
    {
        return $this->singletonLayoutVariantLabel($definitionKey, 'next');
    }

    protected function previousSingletonLayoutVariantLabel(string $definitionKey): string
    {
        return $this->singletonLayoutVariantLabel($definitionKey, 'previous');
    }

    protected function singletonLayoutVariantWasCycled(Model $model, string $definitionKey): void
    {
        if (property_exists($this, 'organization')) {
            $this->organization = $model;
        }
    }

    protected function singletonLayoutVariantResolver(): SingletonLayoutVariantResolver
    {
        return app(SingletonLayoutVariantResolver::class);
    }

    protected function singletonLayoutVariantModel(string $definitionKey): ?Model
    {
        $model = $this->organization ?? null;

        if (! $model instanceof Model || ! $model->exists || $model->getKey() === null) {
            return $model instanceof Model ? $model : null;
        }

        return $model->newQuery()->whereKey($model->getKey())->first();
    }

    private function singletonBelongsToCurrentTenant(Model $model): bool
    {
        $tenantId = corexis_tenant_id();

        return $tenantId === null || (string) $model->getAttribute('team_id') === (string) $tenantId;
    }

    private function singletonLayoutVariantLabel(string $definitionKey, string $direction): string
    {
        $model = $this->singletonLayoutVariantModel($definitionKey);
        $variant = ! $model instanceof Model
            ? null
            : ($direction === 'previous'
                ? $this->singletonLayoutVariantResolver()->previousVariantFor($model, $definitionKey)
                : $this->singletonLayoutVariantResolver()->nextVariantFor($model, $definitionKey));
        $label = is_array($variant) ? (string) ($variant['label'] ?? '') : '';

        return $label !== '' ? __($label) : ($direction === 'previous' ? __('Prethodni izgled') : __('Sljedeći izgled'));
    }
}
