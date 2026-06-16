<?php

namespace IvanBaric\Pages\Livewire\Admin;

use Flux\Flux;
use Illuminate\Support\Carbon;
use IvanBaric\Pages\Actions\CreatePageAction;
use IvanBaric\Pages\Actions\UpdatePageAction;
use IvanBaric\Pages\Models\Page;
use Livewire\Component;

final class PageForm extends Component
{
    public ?Page $page = null;

    public string $locale = 'en';

    public array $title = ['en' => ''];

    public array $excerpt = ['en' => ''];

    public array $content = ['en' => ''];

    public string $status = '';

    public ?string $template = null;

    public bool $is_home = false;

    public bool $is_published = false;

    public ?string $published_at = null;

    public int $sort_order = 0;

    public function mount(?Page $page = null): void
    {
        corexis_authorize($page?->exists ? 'pages.update' : 'pages.create', $page?->exists ? $page : []);

        $this->locale = $this->currentLocaleCode();
        $this->title = [$this->locale => ''];
        $this->excerpt = [$this->locale => ''];
        $this->content = [$this->locale => ''];
        $this->status = config('pages.default_status', 'draft');
        $this->template = config('pages.default_template', 'classic');

        if ($page?->exists) {
            $this->page = $page;
            $this->fillFromPage($page);
        }
    }

    public function save(): void
    {
        $payload = $this->payload();
        $result = $this->page?->exists
            ? app(UpdatePageAction::class)->handle($this->page->uuid, $payload)
            : app(CreatePageAction::class)->handle($payload);

        if (! $result->successful) {
            foreach ($result->data?->messages() ?? [] as $field => $messages) {
                $this->addError($field, $messages[0]);
            }

            Flux::toast(variant: 'danger', text: $result->message);

            return;
        }

        $this->page = $result->data;

        Flux::toast(variant: 'success', text: $result->message);
        $this->redirectRoute(config('pages.admin.name_prefix', 'admin.pages.').'edit', ['page' => $this->page->uuid], navigate: true);
    }

    public function render()
    {
        return view('pages::livewire.admin.page-form')
            ->layout(config('pages.admin_ui.layout', 'layouts.app'), [
                'title' => $this->page?->exists ? __('Edit page') : __('Create page'),
            ]);
    }

    private function currentLocaleCode(): string
    {
        return corexis_locale_code() ?: config('app.locale', 'en');
    }

    private function fillFromPage(Page $page): void
    {
        $this->title = is_array($page->title) ? $page->title : [$this->locale => (string) $page->title];
        $this->excerpt = is_array($page->excerpt) ? $page->excerpt : [$this->locale => (string) $page->excerpt];
        $this->content = is_array($page->content) ? $page->content : [$this->locale => (string) $page->content];
        $this->status = $page->status;
        $this->template = $page->template;
        $this->is_home = $page->is_home;
        $this->is_published = $page->is_published;
        $this->published_at = $page->published_at?->format('Y-m-d\TH:i');
        $this->sort_order = $page->sort_order;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'title' => $this->title,
            'excerpt' => array_filter($this->excerpt) === [] ? null : $this->excerpt,
            'content' => array_filter($this->content) === [] ? null : $this->content,
            'status' => $this->status,
            'template' => $this->template,
            'is_home' => $this->is_home,
            'is_published' => $this->is_published,
            'published_at' => $this->published_at ? Carbon::parse($this->published_at) : null,
            'sort_order' => $this->sort_order,
        ];
    }
}
