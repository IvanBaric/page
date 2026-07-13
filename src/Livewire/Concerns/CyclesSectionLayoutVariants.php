<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Livewire\Concerns;

use Illuminate\Database\Eloquent\Model;
use IvanBaric\Pages\Support\PagesModels;
use IvanBaric\Pages\Support\SectionLayoutVariantResolver;

trait CyclesSectionLayoutVariants
{
    public function canCycleSectionLayoutVariant(): bool
    {
        $section = $this->sectionLayoutVariantModel();

        return $section instanceof Model
            && corexis_can('pages.sections.manage', $section)
            && $this->sectionLayoutVariantResolver()->hasCycleableVariants($section);
    }

    public function cycleSectionLayoutVariant(string $direction = 'next'): void
    {
        abort_unless(in_array($direction, ['next', 'previous'], true), 422);

        $section = $this->sectionLayoutVariantModel();
        abort_unless($section instanceof Model, 404);
        corexis_authorize('pages.sections.manage', $section);

        $resolver = $this->sectionLayoutVariantResolver();
        $nextVariant = $direction === 'previous'
            ? $resolver->previousVariantFor($section)
            : $resolver->nextVariantFor($section);

        abort_unless(is_array($nextVariant), 404);

        $resolver->setVariant($section, (string) $nextVariant['key']);
        $this->sectionLayoutVariantWasCycled($section);
    }

    public function nextSectionLayoutVariantLabel(): string
    {
        return $this->sectionLayoutVariantLabel('next');
    }

    public function previousSectionLayoutVariantLabel(): string
    {
        return $this->sectionLayoutVariantLabel('previous');
    }

    protected function sectionLayoutVariantWasCycled(Model $section): void
    {
        if (property_exists($this, 'section')) {
            $this->section = $section;
        }
    }

    protected function sectionLayoutVariantResolver(): SectionLayoutVariantResolver
    {
        return app(SectionLayoutVariantResolver::class);
    }

    protected function sectionLayoutVariantModel(): ?Model
    {
        $section = $this->section ?? null;
        $uuid = (string) data_get($section, 'uuid', '');
        $model = PagesModels::section();

        if ($uuid !== '') {
            return $model::query()->where('uuid', $uuid)->first();
        }

        return $section instanceof Model && $section->exists
            ? $model::query()->whereKey($section->getKey())->first()
            : null;
    }

    private function sectionLayoutVariantLabel(string $direction): string
    {
        $variant = $direction === 'previous'
            ? $this->sectionLayoutVariantResolver()->previousVariantFor($this->section ?? null)
            : $this->sectionLayoutVariantResolver()->nextVariantFor($this->section ?? null);
        $label = is_array($variant) ? (string) ($variant['label'] ?? '') : '';

        return $label !== '' ? __($label) : ($direction === 'previous' ? __('Prethodni izgled') : __('Sljedeći izgled'));
    }
}
