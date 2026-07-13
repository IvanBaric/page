<?php

namespace IvanBaric\Pages\Livewire\Admin;

use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use IvanBaric\Pages\Actions\ForceDeleteArchivedRecordAction;
use IvanBaric\Pages\Actions\RestoreArchivedRecordAction;
use IvanBaric\Pages\Models\Page;
use IvanBaric\Pages\Models\Section;
use IvanBaric\Pages\Models\SectionItem;
use IvanBaric\Pages\Support\PagesModels;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class PageArchive extends Component
{
    use WithPagination;

    public string $search = '';

    #[Locked]
    public ?string $restoringType = null;

    #[Locked]
    public ?string $restoringUuid = null;

    #[Locked]
    public string $restoringName = '';

    #[Locked]
    public ?string $deletingType = null;

    #[Locked]
    public ?string $deletingUuid = null;

    #[Locked]
    public string $deletingName = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function confirmRestore(string $type, string $uuid): void
    {
        $record = $this->findArchivedRecord($type, $uuid);

        if (! $record instanceof Model) {
            Flux::toast(variant: 'danger', text: __('Arhivirani zapis nije pronađen.'));

            return;
        }

        $this->restoringType = $type;
        $this->restoringUuid = $uuid;
        $this->restoringName = $this->recordName($record, $type);

        Flux::modal('archive-restore')->show();
    }

    public function cancelRestore(): void
    {
        $this->reset('restoringType', 'restoringUuid', 'restoringName');
    }

    public function restore(RestoreArchivedRecordAction $action): void
    {
        if (! $this->restoringType || ! $this->restoringUuid) {
            Flux::toast(variant: 'danger', text: __('Arhivirani zapis nije pronađen.'));

            return;
        }

        $result = $action->handle($this->restoringType, $this->restoringUuid);

        if ($result->failed()) {
            Flux::toast(variant: 'danger', text: $result->message);

            return;
        }

        $this->reset('restoringType', 'restoringUuid', 'restoringName');
        unset($this->archivedRecords);
        $this->resetPage();

        Flux::modal('archive-restore')->close();
        Flux::toast(variant: 'success', text: $result->message);
    }

    public function confirmDelete(string $type, string $uuid): void
    {
        $record = $this->findArchivedRecord($type, $uuid);

        if (! $record instanceof Model) {
            Flux::toast(variant: 'danger', text: __('Arhivirani zapis nije pronađen.'));

            return;
        }

        $this->deletingType = $type;
        $this->deletingUuid = $uuid;
        $this->deletingName = $this->recordName($record, $type);

        Flux::modal('archive-delete')->show();
    }

    public function cancelDelete(): void
    {
        $this->reset('deletingType', 'deletingUuid', 'deletingName');
    }

    public function delete(ForceDeleteArchivedRecordAction $action): void
    {
        if (! $this->deletingType || ! $this->deletingUuid) {
            Flux::toast(variant: 'danger', text: __('Arhivirani zapis nije pronađen.'));

            return;
        }

        $result = $action->handle($this->deletingType, $this->deletingUuid);

        if ($result->failed()) {
            Flux::toast(variant: 'danger', text: $result->message);

            return;
        }

        $this->reset('deletingType', 'deletingUuid', 'deletingName');
        unset($this->archivedRecords);
        $this->resetPage();

        Flux::modal('archive-delete')->close();
        Flux::toast(variant: 'success', text: $result->message);
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

    private function findArchivedRecord(string $type, string $uuid): ?Model
    {
        $model = $this->modelClassForType($type);

        if (! $model) {
            return null;
        }

        /** @var Model|null $record */
        $record = $model::onlyTrashed()
            ->where('uuid', $uuid)
            ->first();

        return $record;
    }

    /** @return Collection<int, array<string, mixed>> */
    private function archivedRecordRows(): Collection
    {
        return collect()
            ->merge($this->archivedPages()->map(fn (Model $page): array => $this->recordRow($page, 'page')))
            ->merge($this->archivedSections()->map(fn (Model $section): array => $this->recordRow($section, 'section')))
            ->merge($this->archivedItems()->map(fn (Model $item): array => $this->recordRow($item, 'item')));
    }

    /** @return EloquentCollection<int, Page> */
    private function archivedPages(): EloquentCollection
    {
        $model = PagesModels::page();

        return $model::onlyTrashed()
            ->get();
    }

    /** @return EloquentCollection<int, Section> */
    private function archivedSections(): EloquentCollection
    {
        $model = PagesModels::section();

        return $model::onlyTrashed()
            ->with(['page' => fn ($query) => $query->withTrashed()])
            ->get();
    }

    /** @return EloquentCollection<int, SectionItem> */
    private function archivedItems(): EloquentCollection
    {
        $model = PagesModels::sectionItem();

        return $model::onlyTrashed()
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

    /**
     * @param  Collection<int, array<string, mixed>>  $records
     * @return Collection<int, array<string, mixed>>
     */
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
            'page' => PagesModels::page(),
            'section' => PagesModels::section(),
            'item' => PagesModels::sectionItem(),
            default => null,
        };
    }
}
