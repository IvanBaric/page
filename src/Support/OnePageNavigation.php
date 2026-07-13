<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class OnePageNavigation
{
    /**
     * @return array<int, array{label: string, href: string, active: bool}>
     */
    public function navItems(mixed $publicPages, string $homeUrl): array
    {
        if (! $this->isAvailable($publicPages)) {
            return [];
        }

        $homePage = $this->pages($publicPages)->first();

        return $this->sectionsFor($homePage)
            ->filter(fn (mixed $section): bool => $this->isVisibleInNavigation($section))
            ->map(fn (mixed $section): array => [
                'label' => $this->sectionLabel($section),
                'href' => $this->normalizeBaseUrl($homeUrl).'#'.$this->anchorId($section),
                'active' => false,
            ])
            ->filter(fn (array $item): bool => filled($item['label']) && filled($item['href']))
            ->values()
            ->all();
    }

    public function isAvailable(mixed $publicPages): bool
    {
        $pages = $this->pages($publicPages);
        $homePage = $pages->first();

        return $pages->count() === 1 && (bool) data_get($homePage, 'is_home');
    }

    public function canShowSection(mixed $section): bool
    {
        $type = (string) data_get($section, 'type', '');

        return $type !== ''
            && $type !== 'hero'
            && ! str_starts_with($type, 'template_');
    }

    public function anchorId(mixed $section): string
    {
        $label = $this->sectionLabel($section);
        $slug = Str::slug($label !== '' ? $label : (string) data_get($section, 'type', 'sekcija')) ?: 'sekcija';
        $uuid = preg_replace('/[^A-Za-z0-9]/', '', (string) data_get($section, 'uuid', ''));
        $suffix = is_string($uuid) && $uuid !== '' ? '-'.Str::lower(substr($uuid, 0, 8)) : '';

        return $slug.$suffix;
    }

    /** @return array<string, mixed> */
    public function defaultSectionSettings(mixed $page, string $sectionKey): array
    {
        if (! (bool) data_get($page, 'is_home')) {
            return [];
        }

        $settings = data_get(config('pages.one_page_navigation.home_section_defaults', []), $sectionKey, []);

        if (! is_array($settings) || $settings === []) {
            return [];
        }

        if (array_key_exists('label', $settings)) {
            $settings['navigation_label'] = $settings['label'];
            unset($settings['label']);
        }

        if (! array_key_exists('show_in_navigation', $settings)) {
            $settings['show_in_navigation'] = true;
        }

        return $settings;
    }

    private function isVisibleInNavigation(mixed $section): bool
    {
        return $this->canShowSection($section)
            && (bool) data_get($section, 'is_visible', true)
            && $this->hasRenderableOutput($section)
            && (bool) data_get($section, 'settings.show_in_navigation', false);
    }

    private function hasRenderableOutput(mixed $section): bool
    {
        $type = (string) data_get($section, 'type', '');

        if ($type === 'collaboration') {
            return $this->visibleItemsFor($section)->isNotEmpty();
        }

        if ($type === 'social_links') {
            $hasItemLinks = $this->visibleItemsFor($section)
                ->contains(fn (mixed $item): bool => filled(data_get($item, 'url')));
            $hasLegacyLinks = collect((array) data_get($section, 'settings.links', []))
                ->filter(fn (mixed $url): bool => filled($url))
                ->isNotEmpty();

            return $hasItemLinks || $hasLegacyLinks;
        }

        return true;
    }

    private function sectionLabel(mixed $section): string
    {
        $customLabel = trim((string) data_get($section, 'settings.navigation_label', ''));

        if ($customLabel !== '') {
            return $customLabel;
        }

        if (is_object($section) && method_exists($section, 'localized')) {
            $localizedTitle = trim((string) $section->localized('title'));

            if ($localizedTitle !== '') {
                return $localizedTitle;
            }
        }

        $type = (string) data_get($section, 'type', '');
        $configuredLabel = trim((string) data_get(config('pages.section_types'), $type.'.label', ''));

        if ($configuredLabel !== '') {
            return $configuredLabel;
        }

        return $type !== '' ? (string) str($type)->headline() : '';
    }

    /** @return Collection<int, mixed> */
    private function sectionsFor(mixed $homePage): Collection
    {
        if ($homePage instanceof Model && $homePage->relationLoaded('visibleSections')) {
            return $this->collectionFrom($homePage->getRelation('visibleSections'));
        }

        if (is_object($homePage) && method_exists($homePage, 'visibleSections')) {
            return $homePage->visibleSections()->get();
        }

        $sections = data_get($homePage, 'visibleSections', data_get($homePage, 'sections', []));

        return $this->collectionFrom($sections);
    }

    /** @return Collection<int, mixed> */
    private function visibleItemsFor(mixed $section): Collection
    {
        if ($section instanceof Model && $section->relationLoaded('visibleItems')) {
            return $this->collectionFrom($section->getRelation('visibleItems'));
        }

        if (is_object($section) && method_exists($section, 'visibleItems')) {
            return $section->visibleItems()->get();
        }

        $items = data_get($section, 'visibleItems', data_get($section, 'items', []));

        return $this->collectionFrom($items);
    }

    /** @return Collection<int, mixed> */
    private function pages(mixed $publicPages): Collection
    {
        return $publicPages instanceof Collection
            ? $publicPages->values()
            : $this->collectionFrom($publicPages);
    }

    /** @return Collection<int, mixed> */
    private function collectionFrom(mixed $value): Collection
    {
        if ($value instanceof Collection) {
            return $value->values();
        }

        if (is_array($value)) {
            return new Collection(array_values($value));
        }

        if ($value instanceof \Traversable) {
            return new Collection(iterator_to_array($value, false));
        }

        return new Collection;
    }

    private function normalizeBaseUrl(string $homeUrl): string
    {
        return $homeUrl === '/' ? '/' : rtrim($homeUrl, '/');
    }
}
