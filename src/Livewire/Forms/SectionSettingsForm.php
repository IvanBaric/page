<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Livewire\Forms;

use IvanBaric\Pages\Models\Section;
use Livewire\Form;

final class SectionSettingsForm extends Form
{
    public string $title = '';

    public ?string $subtitle = null;

    public ?string $description = null;

    public bool $visible = true;

    public bool $showTitle = true;

    public bool $showDescription = true;

    public bool $showInNavigation = false;

    public ?string $navigationLabel = null;

    public int $sortOrder = 0;

    /** @var array<string, mixed> */
    public array $settings = [];

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'visible' => ['boolean'],
            'showTitle' => ['boolean'],
            'showDescription' => ['boolean'],
            'showInNavigation' => ['boolean'],
            'navigationLabel' => ['nullable', 'string', 'max:80'],
            'sortOrder' => ['integer', 'min:0'],
        ];
    }

    /** @return array<string, string> */
    public function validationAttributes(): array
    {
        return [
            'title' => __('naziv'),
            'subtitle' => __('podnaslov'),
            'description' => __('opis'),
            'visible' => __('vidljivost'),
            'showTitle' => __('prikaz naziva sekcije'),
            'showDescription' => __('prikaz opisa sekcije'),
            'showInNavigation' => __('prikaz u izborniku'),
            'navigationLabel' => __('naziv u izborniku'),
            'sortOrder' => __('redoslijed'),
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'required' => __('Obavezno polje'),
        ];
    }

    public function fillFromSection(Section $section): void
    {
        $this->title = $section->localized('title');
        $this->subtitle = $section->localized('subtitle') ?: null;
        $this->description = $section->localized('description') ?: null;
        $this->visible = (bool) $section->getAttribute('is_visible');
        $this->sortOrder = (int) $section->getAttribute('sort_order');
        $this->settings = (array) $section->getAttribute('settings');
        $this->showTitle = (bool) data_get($this->settings, 'show_title', true);
        $this->showDescription = (bool) data_get($this->settings, 'show_description', true);
        $this->showInNavigation = (bool) data_get($this->settings, 'show_in_navigation', false);
        $navigationLabel = trim((string) data_get($this->settings, 'navigation_label', ''));
        $this->navigationLabel = $navigationLabel !== '' ? $navigationLabel : null;
    }

    /** @return array<string, mixed> */
    public function data(): array
    {
        /** @var array{title: string, subtitle?: string|null, description?: string|null, visible: bool, showTitle: bool, showDescription: bool, showInNavigation: bool, navigationLabel?: string|null, sortOrder: int} $validated */
        $validated = $this->validate(messages: $this->messages(), attributes: $this->validationAttributes());
        $locale = corexis_locale_code() ?: (string) config('pages.translatable.default_locale', config('app.locale', 'hr'));
        $settings = $this->settings;
        $settings['show_title'] = (bool) $validated['showTitle'];
        $settings['show_description'] = (bool) $validated['showDescription'];
        $settings['show_in_navigation'] = (bool) $validated['showInNavigation'];
        $navigationLabel = trim((string) ($validated['navigationLabel'] ?? ''));

        if ($navigationLabel !== '') {
            $settings['navigation_label'] = $navigationLabel;
        } else {
            unset($settings['navigation_label']);
        }

        return [
            'title' => [$locale => $validated['title']],
            'subtitle' => filled($validated['subtitle'] ?? null) ? [$locale => $validated['subtitle']] : null,
            'description' => filled($validated['description'] ?? null) ? [$locale => $validated['description']] : null,
            'is_visible' => $validated['visible'],
            'sort_order' => $validated['sortOrder'],
            'settings' => $settings,
        ];
    }
}
