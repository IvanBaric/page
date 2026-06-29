<?php

namespace IvanBaric\Pages\Livewire\Admin;

use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use IvanBaric\Pages\Models\Page;
use IvanBaric\Pages\Models\Section;
use IvanBaric\Pages\Models\SectionItem;
use IvanBaric\Pages\Support\TeamResolver;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class PageArchive extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function restore(string $type, string $uuid): void
    {
        $model = $this->modelClassForType($type);

        if (! $model) {
            Flux::toast(variant: 'danger', text: __('Arhivirani zapis nije pronađen.'));

            return;
        }

        /** @var Model|null $record */
        $record = $model::onlyTrashed()
            ->forTeam($this->currentTeamId())
            ->where('uuid', $uuid)
            ->first();

        if (! $record instanceof Model || ! method_exists($record, 'restore')) {
            Flux::toast(variant: 'danger', text: __('Arhivirani zapis nije pronađen.'));

            return;
        }

        $record->restore();

        unset($this->archivedRecords);
        $this->resetPage();

        Flux::toast(variant: 'success', text: __('Zapis je vraćen iz arhive.'));
    }

    /** @return Paginator<int, array<string, mixed>> */
    #[Computed]
    public function archivedRecords(): Paginator
    {
        $records = $this->archivedRecordRows()
            ->when(trim($this->search) !== '', fn (Collection $records): Collection => $this->filterRecords($records))
            ->sortByDesc('archived_timestamp')
            ->values();

        $perPage = 6;
        $page = $this->getPage();

        $pageItems = $records
            ->slice(($page - 1) * $perPage, $perPage + 1)
            ->values();

        return new Paginator(
            $pageItems,
            $perPage,
            $page,
            ['path' => request()->url()],
        );
    }

    public function render(): View
    {
        return view('pages::livewire.admin.page-archive')
            ->layout('layouts.app', ['title' => __('Arhiva')]);
    }

    private function currentTeamId(): ?int
    {
        return app(TeamResolver::class)->resolve();
    }

    /** @return Collection<int, array<string, mixed>> */
    private function archivedRecordRows(): Collection
    {
        return collect()
            ->merge($this->archivedPages()->map(fn (Model $page): array => $this->recordRow($page, 'page')))
            ->merge($this->archivedSections()->map(fn (Model $section): array => $this->recordRow($section, 'section')))
            ->merge($this->archivedItems()->map(fn (Model $item): array => $this->recordRow($item, 'item')));
    }

    /** @return EloquentCollection<int, Model> */
    private function archivedPages(): EloquentCollection
    {
        $model = config('pages.models.page', Page::class);

        return $model::onlyTrashed()
            ->forTeam($this->currentTeamId())
            ->get();
    }

    /** @return EloquentCollection<int, Model> */
    private function archivedSections(): EloquentCollection
    {
        $model = config('pages.models.section', Section::class);

        return $model::onlyTrashed()
            ->forTeam($this->currentTeamId())
            ->with(['page' => fn ($query) => $query->withTrashed()])
            ->get();
    }

    /** @return EloquentCollection<int, Model> */
    private function archivedItems(): EloquentCollection
    {
        $model = config('pages.models.section_item', SectionItem::class);

        return $model::onlyTrashed()
            ->forTeam($this->currentTeamId())
            ->with([
                'section' => fn ($query) => $query
                    ->withTrashed()
                    ->with(['page' => fn ($query) => $query->withTrashed()]),
            ])
            ->get();
    }

    /** @return array<string, mixed> */
    private function recordRow(Model $record, string $type): array
    {
        $archivedAt = $record->getAttribute('deleted_at');

        return [
            'key' => $type.'-'.$record->getAttribute('uuid'),
            'uuid' => (string) $record->getAttribute('uuid'),
            'type' => $type,
            'type_label' => $this->typeLabel($type),
            'name' => $this->recordName($record, $type),
            'context' => $this->recordContext($record, $type),
            'archived_at' => $archivedAt?->format('d.m.Y. H:i') ?? '',
            'archived_timestamp' => $archivedAt?->getTimestamp() ?? 0,
            'search' => $this->recordSearchText($record, $type),
        ];
    }

    private function recordName(Model $record, string $type): string
    {
        $name = method_exists($record, 'localized') ? (string) $record->localized('title') : '';

        if ($name !== '') {
            return $name;
        }

        if ($type === 'item' && method_exists($record, 'localized')) {
            $content = (string) ($record->localized('content') ?: $record->localized('description'));

            if ($content !== '') {
                return str($content)->limit(80)->toString();
            }
        }

        return match ($type) {
            'page' => __('Neimenovana stranica'),
            'section' => __('Neimenovana sekcija'),
            'item' => __('Neimenovani zapis'),
            default => __('Neimenovani zapis'),
        };
    }

    private function recordContext(Model $record, string $type): string
    {
        if ($type === 'page') {
            return (string) $record->getAttribute('slug');
        }

        if ($type === 'section') {
            $page = $record->getRelationValue('page');

            return $page instanceof Model && method_exists($page, 'localized')
                ? trim(__('Stranica').': '.$page->localized('title'))
                : '';
        }

        if ($type === 'item') {
            $section = $record->getRelationValue('section');

            if (! $section instanceof Model) {
                return '';
            }

            $parts = [];

            if (method_exists($section, 'localized') && $section->localized('title') !== '') {
                $parts[] = __('Sekcija').': '.$section->localized('title');
            }

            $page = $section->getRelationValue('page');

            if ($page instanceof Model && method_exists($page, 'localized') && $page->localized('title') !== '') {
                $parts[] = __('Stranica').': '.$page->localized('title');
            }

            return implode(' · ', $parts);
        }

        return '';
    }

    private function recordSearchText(Model $record, string $type): string
    {
        return collect([
            $this->recordName($record, $type),
            $this->recordContext($record, $type),
            $this->typeLabel($type),
            $type,
        ])->filter()->implode(' ');
    }

    /** @param Collection<int, array<string, mixed>> $records */
    private function filterRecords(Collection $records): Collection
    {
        $search = str(trim($this->search))->lower()->toString();

        return $records->filter(
            static fn (array $record): bool => str((string) $record['search'])->lower()->contains($search),
        );
    }

    private function typeLabel(string $type): string
    {
        return match ($type) {
            'page' => __('Stranica'),
            'section' => __('Sekcija'),
            'item' => __('Zapis'),
            default => __('Zapis'),
        };
    }

    private function modelClassForType(string $type): ?string
    {
        return match ($type) {
            'page' => (string) config('pages.models.page', Page::class),
            'section' => (string) config('pages.models.section', Section::class),
            'item' => (string) config('pages.models.section_item', SectionItem::class),
            default => null,
        };
    }
}
