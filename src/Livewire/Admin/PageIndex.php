<?php

namespace IvanBaric\Pages\Livewire\Admin;

use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use IvanBaric\Pages\Actions\DeletePageAction;
use IvanBaric\Pages\Actions\PublishPageAction;
use IvanBaric\Pages\Actions\UnpublishPageAction;
use IvanBaric\Pages\Models\Page;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

final class PageIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = '';

    public string $template = '';

    public function mount(): void
    {
        corexis_authorize('pages.view');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedTemplate(): void
    {
        $this->resetPage();
    }

    public function publish(string $uuid): void
    {
        $page = $this->findPage($uuid);
        $result = $page?->isPublished()
            ? app(UnpublishPageAction::class)->handle($uuid)
            : app(PublishPageAction::class)->handle($uuid);

        Flux::toast(variant: $result->successful ? 'success' : 'danger', text: $result->message);
    }

    public function delete(string $uuid): void
    {
        $result = app(DeletePageAction::class)->handle($uuid);

        Flux::toast(variant: $result->successful ? 'success' : 'danger', text: $result->message);
    }

    #[Computed]
    public function pages(): LengthAwarePaginator
    {
        $model = config('pages.models.page', Page::class);

        return $model::query()
            ->when($this->search !== '', function (Builder $query): void {
                $query->where(function (Builder $query): void {
                    $query->where('slug', 'like', "%{$this->search}%")
                        ->orWhere('title', 'like', "%{$this->search}%");
                });
            })
            ->when($this->status !== '', fn (Builder $query) => $query->where('status', $this->status))
            ->when($this->template !== '', fn (Builder $query) => $query->where('template', $this->template))
            ->ordered()
            ->paginate(config('pages.pagination.admin', 15));
    }

    public function render()
    {
        return view('pages::livewire.admin.page-index')
            ->layout(config('pages.admin_ui.layout', 'layouts.app'), [
                'title' => __('Pages'),
            ]);
    }

    private function findPage(string $uuid): ?Page
    {
        $model = config('pages.models.page', Page::class);

        return $model::query()->where('uuid', $uuid)->first();
    }
}
