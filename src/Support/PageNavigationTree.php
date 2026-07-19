<?php

declare(strict_types=1);

namespace IvanBaric\Pages\Support;

use Illuminate\Support\Collection;

final class PageNavigationTree
{
    /**
     * @param  Collection<int, mixed>  $pages
     * @param  callable(mixed): array{label: string, href: string, active?: bool, target?: string}  $mapPage
     * @return array<int, array{label: string, href: string, active: bool, target: string, children: array<mixed>}>
     */
    public function build(Collection $pages, callable $mapPage): array
    {
        $pageIds = $pages->pluck('id')->filter()->map('strval');
        $roots = $pages->filter(fn (mixed $page): bool => ! filled(data_get($page, 'parent_id'))
            || ! $pageIds->contains((string) data_get($page, 'parent_id')));

        return $roots
            ->map(fn (mixed $page): ?array => $this->branch($page, $pages, $mapPage, 1))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, mixed>  $pages
     * @param  callable(mixed): array{label: string, href: string, active?: bool, target?: string}  $mapPage
     * @param  array<string, bool>  $ancestors
     * @return array{label: string, href: string, active: bool, target: string, children: array<mixed>}|null
     */
    private function branch(mixed $page, Collection $pages, callable $mapPage, int $depth, array $ancestors = []): ?array
    {
        $key = (string) data_get($page, 'id', data_get($page, 'uuid'));

        if ($key === '' || isset($ancestors[$key]) || $depth > max(1, (int) config('pages.hierarchy.max_depth', 3))) {
            return null;
        }

        $ancestors[$key] = true;
        $item = $mapPage($page);

        if (! filled($item['label'] ?? null)) {
            return null;
        }

        $children = $pages
            ->filter(fn (mixed $child): bool => (string) data_get($child, 'parent_id') === (string) data_get($page, 'id'))
            ->map(fn (mixed $child): ?array => $this->branch($child, $pages, $mapPage, $depth + 1, $ancestors))
            ->filter()
            ->values()
            ->all();

        return [
            'label' => (string) $item['label'],
            'href' => (string) $item['href'],
            'active' => (bool) ($item['active'] ?? false) || collect($children)->contains('active', true),
            'target' => ($item['target'] ?? '_self') === '_blank' ? '_blank' : '_self',
            'children' => $children,
        ];
    }
}
