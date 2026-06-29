<x-admin-ui::page>
    <div class="admin-page-header">
        <div class="admin-page-header-copy">
            <h1 class="admin-page-title">
                @if ($part === 'header')
                    {{ __('Zaglavlje') }}
                @elseif ($part === 'footer')
                    {{ __('Podnožje') }}
                @else
                    {{ __('Stranice') }}
                @endif
            </h1>
            <flux:text class="admin-page-description">
                @if ($part === 'header')
                    {{ __('Pregled javnog zaglavlja. Linkovi dolaze iz objavljenih stranica i njihovog redoslijeda.') }}
                @elseif ($part === 'footer')
                    {{ __('Uredite tekst javnog podnožja. Linkovi dolaze iz objavljenih stranica i njihovog redoslijeda.') }}
                @else
                    {{ __('Složite stranice, uredite njihov redoslijed i dodajte nove stranice za vlastiti sadržaj.') }}
                @endif
            </flux:text>
        </div>
        @if ($part === 'pages')
            <div class="admin-page-actions">
                <flux:modal.trigger name="page-create-form">
                    <flux:button variant="primary" icon="plus">{{ __('Nova stranica') }}</flux:button>
                </flux:modal.trigger>
            </div>
        @elseif ($part === 'header' || $part === 'footer')
            <div class="admin-page-actions">
                <flux:button
                    type="button"
                    variant="primary"
                    icon="check"
                    wire:click="$dispatch('pages-save-singleton-editor')"
                >
                    {{ __('Spremi promjene') }}
                </flux:button>
            </div>
        @endif
    </div>

    @if ($part === 'header')
        @if ($this->organization && $this->canRenderTemplatePart('header'))
            @livewire(\IvanBaric\Pages\Livewire\Admin\ConfiguredSingletonEditor::class, ['model' => $this->organization, 'definitionKey' => $this->templatePartDefinitionKey('header')], key('admin-header-editor-'.$this->organization->getKey()))
        @elseif ($this->organization)
            <x-admin-ui::panel class="p-6">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $this->unsupportedTemplatePartText('header') }}</p>
            </x-admin-ui::panel>
        @else
            <x-admin-ui::panel class="p-6">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $this->missingSingletonSubjectText() }}</p>
            </x-admin-ui::panel>
        @endif
    @elseif ($part === 'footer')
        @if ($this->organization && $this->canRenderTemplatePart('footer'))
            @livewire(\IvanBaric\Pages\Livewire\Admin\ConfiguredSingletonEditor::class, ['model' => $this->organization, 'definitionKey' => $this->templatePartDefinitionKey('footer')], key('admin-footer-editor-'.$this->organization->getKey()))
        @elseif ($this->organization)
            <x-admin-ui::panel class="p-6">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $this->unsupportedTemplatePartText('footer') }}</p>
            </x-admin-ui::panel>
        @else
            <x-admin-ui::panel class="p-6">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $this->missingSingletonSubjectText() }}</p>
            </x-admin-ui::panel>
        @endif
    @else
        <x-admin-ui::stat-grid>
            <x-admin-ui::stat-card :label="__('CMS stranice')" :value="$this->stats['total']" accent="bg-zinc-900 dark:bg-white">
                <x-slot:icon>
                    <flux:icon icon="document-text" variant="micro" class="size-4" />
                </x-slot:icon>
            </x-admin-ui::stat-card>
            <x-admin-ui::stat-card :label="__('Aktivne')" :value="$this->stats['active']" accent="bg-emerald-500">
                <x-slot:icon>
                    <flux:icon icon="check-circle" variant="micro" class="size-4" />
                </x-slot:icon>
            </x-admin-ui::stat-card>
            <x-admin-ui::stat-card :label="__('Neaktivne')" :value="$this->stats['inactive']" accent="bg-sky-500">
                <x-slot:icon>
                    <flux:icon icon="eye-slash" variant="micro" class="size-4" />
                </x-slot:icon>
            </x-admin-ui::stat-card>
            <x-admin-ui::stat-card :label="__('Sekcije')" :value="$this->stats['sections']" accent="bg-amber-400">
                <x-slot:icon>
                    <flux:icon icon="rectangle-stack" variant="micro" class="size-4" />
                </x-slot:icon>
            </x-admin-ui::stat-card>
        </x-admin-ui::stat-grid>

        <x-admin-ui::search-filter-toolbar
            :placeholder="__('Pretraži stranice po nazivu...')"
            :items="$this->filterOptions"
            :active="$status"
            method="setStatus"
            clearable
            align="end"
        />

        <x-admin-ui::panel class="admin-panel-strong-border" loading loading-target="search,setStatus,reorderPage,togglePublished,editPage,savePage,deletePage" loading-text="{{ __('Učitavam stranice...') }}">
            <div class="admin-panel-header bg-zinc-50 dark:bg-zinc-900/60">
                <div>
                    <h2 class="admin-panel-title">{{ __('Stranice') }}</h2>
                    <p class="admin-panel-description">{{ __('Posložite stranice, uredite redoslijed i otvorite stranicu koju želite urediti.') }}</p>
                </div>
            </div>

            <div id="table" @if ($this->canReorder()) wire:sort="reorderPage" @endif class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse ($this->pages as $page)
                    <article wire:key="page-{{ $page->uuid }}" @if ($this->canReorder()) wire:sort:item="{{ $page->uuid }}" @endif class="grid grid-cols-1 gap-4 px-5 py-4 transition hover:bg-zinc-50/80 dark:hover:bg-white/[0.03] lg:grid-cols-[3rem_minmax(0,1fr)_8rem_8rem_13rem] lg:items-center">
                        <div>
                            @if ($this->canReorder())
                                <flux:tooltip :content="__('Povucite za promjenu redoslijeda')">
                                    <span wire:sort:handle class="inline-flex size-10 cursor-grab items-center justify-center rounded-md bg-zinc-100 text-zinc-500 ring-1 ring-zinc-950/5 transition hover:bg-zinc-200 hover:text-zinc-700 active:cursor-grabbing dark:bg-zinc-900 dark:ring-white/10 dark:hover:bg-zinc-800">
                                        <flux:icon name="bars-3" class="size-4" />
                                    </span>
                                </flux:tooltip>
                            @endif
                        </div>
                        <div class="min-w-0">
                            <div class="flex min-w-0 items-center gap-2">
                                <a href="{{ route($this->pageShowRouteName(), ['page' => $page->uuid]) }}" wire:navigate class="truncate text-[15px] font-semibold text-zinc-950 transition hover:text-accent dark:text-white">{{ $page->localized('title') ?: __('Neimenovana stranica') }}</a>
                            </div>
                            <p class="mt-1 truncate text-[13px] text-zinc-500 dark:text-zinc-400">{{ $page->localized('excerpt') ?: $page->slug }}</p>
                        </div>
                        <div class="text-sm tabular-nums text-zinc-600 dark:text-zinc-300">{{ trans_choice('{0} bez sekcija|{1} :count sekcija|[2,*] :count sekcija', $page->sections_count, ['count' => $page->sections_count]) }}</div>
                        <div wire:sort:ignore class="min-w-0">
                            @if ($page->slug !== 'home' && ! $page->is_home)
                                <flux:tooltip :content="$page->is_published ? __('Sakrij stranicu iz javnog prikaza') : __('Objavi stranicu u javnom prikazu')">
                                    <flux:switch wire:click="togglePublished('{{ $page->uuid }}')" :checked="(bool) $page->is_published" :label="''" :aria-label="$page->is_published ? __('Uključeno') : __('Isključeno')" />
                                </flux:tooltip>
                            @endif
                        </div>
                        <div wire:sort:ignore class="flex justify-end gap-1">
                            <flux:dropdown position="bottom" align="end">
                                <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" :aria-label="__('Akcije')" />

                                <flux:menu>
                                    <flux:modal.trigger name="page-title-form">
                                        <flux:menu.item as="button" type="button" wire:click="editPage('{{ $page->uuid }}')" icon="pencil-square">
                                            {{ __('Promijeni naziv') }}
                                        </flux:menu.item>
                                    </flux:modal.trigger>
                                    <flux:menu.item :href="$this->publicPageUrl($page)" target="_blank" icon="eye">
                                        {{ __('Javni prikaz') }}
                                    </flux:menu.item>
                                    @if ($this->canDeletePage($page))
                                        <flux:modal.trigger name="page-delete">
                                            <flux:menu.item as="button" type="button" wire:click="confirmDeletePage('{{ $page->uuid }}')" icon="archive-box" variant="danger">
                                                {{ __('Arhiviraj') }}
                                            </flux:menu.item>
                                        </flux:modal.trigger>
                                    @endif
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                    </article>
                @empty
                    <div class="px-6 py-14 text-center">
                        <h3 class="text-base font-semibold text-zinc-950 dark:text-white">{{ __('Nema stranica') }}</h3>
                        <p class="mx-auto mt-2 max-w-sm text-sm text-zinc-500 dark:text-zinc-400">{{ __('Pokrenite inicijalni seeder ili promijenite pretragu.') }}</p>
                    </div>
                @endforelse
            </div>

            <div class="border-t border-zinc-200 px-5 py-4 dark:border-zinc-800">
                <flux:pagination :paginator="$this->pages" scroll-to="#table" />
            </div>
        </x-admin-ui::panel>

        <flux:modal name="page-create-form" class="max-w-xl">
            <form wire:submit="createPage" class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ __('Nova stranica') }}</flux:heading>
                    <flux:text class="mt-2">{{ __('Upišite samo naziv. Adresa stranice i osnovne postavke izradit će se automatski.') }}</flux:text>
                </div>

                <flux:input wire:model="newPageTitle" :label="__('Naziv stranice')" :placeholder="__('Npr. Projekti, Radionice ili Donacije')" autofocus />

                <div class="rounded-lg bg-zinc-50 p-4 text-sm leading-6 text-zinc-600 dark:bg-zinc-900 dark:text-zinc-300">
                    {{ __('Nakon izrade otvorit će se uređivanje nove stranice. Zatim možete kopirati sekcije koje vam se sviđaju s drugih stranica.') }}
                </div>

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost">{{ __('Odustani') }}</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" icon="plus">{{ __('Izradi stranicu') }}</flux:button>
                </div>
            </form>
        </flux:modal>

        <flux:modal name="page-title-form" class="max-w-xl">
            <form wire:submit="savePage" class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ __('Naziv stranice') }}</flux:heading>
                    <flux:text class="mt-2">{{ __('Promijenite naziv i kratki opis koji se prikazuju u administraciji, navigaciji i javnom prikazu.') }}</flux:text>
                </div>

                <div class="grid gap-5">
                    <flux:input wire:model="pageTitle" :label="__('Naziv stranice')" autofocus />
                    <flux:textarea wire:model="pageExcerpt" :label="__('Kratki opis')" rows="3" />
                </div>

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost">{{ __('Odustani') }}</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" icon="check">{{ __('Spremi naziv') }}</flux:button>
                </div>
            </form>
        </flux:modal>

        <flux:modal name="page-delete" class="max-w-lg">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ __('Arhivirati stranicu?') }}</flux:heading>
                    <flux:text class="mt-2">{{ __('Stranica će se premjestiti u arhivu zajedno sa svim sekcijama koje se nalaze na njoj. Osnovne stranice nije moguće arhivirati.') }}</flux:text>
                </div>

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost">{{ __('Odustani') }}</flux:button>
                    </flux:modal.close>
                    <flux:button wire:click="deletePage" type="button" variant="danger" icon="archive-box">{{ __('Arhiviraj stranicu') }}</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</x-admin-ui::page>
