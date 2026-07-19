@php($editorHeader = $this->isTemplatePart() ? $this->editorHeader($part) : null)

<x-admin-ui::page
    x-data="{
        saving: false,
        editorTitle: $el.dataset.editorTitle,
        editorDescription: $el.dataset.editorDescription,
    }"
    :data-editor-title="$editorHeader['title'] ?? ''"
    :data-editor-description="$editorHeader['description'] ?? ''"
    x-on:pages-save-finished.window="saving = false"
    x-on:pages-editor-tab-changed.window="editorDescription = $event.detail.description"
>
    <x-admin-ui::page-header
        :title="$part === 'pages' ? __('Stranice') : null"
        :description="$part === 'pages' ? __('Složite stranice, uredite njihov redoslijed i dodajte nove stranice za vlastiti sadržaj.') : null"
        icon="document-text"
    >
        @if ($this->isTemplatePart())
            <x-slot:headingContent><span x-text="editorTitle"></span></x-slot:headingContent>
            <x-slot:descriptionContent><span x-text="editorDescription"></span></x-slot:descriptionContent>
        @endif

        @if ($part === 'pages')
            <x-slot:actions>
                <flux:button type="button" wire:click="openCreatePage" wire:loading.attr="disabled" wire:target="openCreatePage" variant="primary" icon="plus">{{ __('Nova stranica') }}</flux:button>
            </x-slot:actions>
        @elseif ($this->isTemplatePart())
            <x-slot:actions>
                <flux:button
                    type="button"
                    variant="primary"
                    data-admin-submit-button
                    x-on:click="saving = true"
                    x-bind:disabled="saving"
                    wire:click="$dispatch('pages-save-singleton-editor')"
                >
                    <span x-show="! saving" class="inline-flex items-center gap-2">
                        <flux:icon name="check" class="size-4 shrink-0" />
                        <span>{{ __('Spremi promjene') }}</span>
                    </span>
                    <span x-cloak x-show="saving" class="inline-flex items-center gap-2">
                        <span class="admin-submit-spinner" aria-hidden="true"></span>
                        <span>{{ __('Spremanje...') }}</span>
                    </span>
                </flux:button>
            </x-slot:actions>
        @endif
    </x-admin-ui::page-header>

    @if ($this->isTemplatePart())
        @if ($this->organization && $this->canRenderTemplatePart($part))
            <div class="relative">
                <div x-cloak x-show="saving" x-transition.opacity.duration.150ms class="pointer-events-none absolute inset-0 z-30 flex items-start justify-center bg-white/35 pt-4 backdrop-blur-[1px] dark:bg-zinc-950/25">
                    <div class="admin-loading-pill">
                        <span class="admin-loading-spinner" aria-hidden="true"></span>
                        <span>{{ __('Spremanje...') }}</span>
                    </div>
                </div>

                <div x-bind:class="{ 'admin-panel-content-loading': saving }">
                    @livewire(\IvanBaric\Pages\Livewire\Admin\ConfiguredSingletonEditor::class, ['model' => $this->organization, 'definitionKey' => $this->templatePartDefinitionKey($part)], key('admin-'.$part.'-editor-'.$this->organization->getKey()))
                </div>
            </div>
        @elseif ($this->organization)
            <x-admin-ui::panel class="p-6">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $this->unsupportedTemplatePartText($part) }}</p>
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

            @if ($this->pages->isNotEmpty())
                <div class="admin-list-header hidden grid-cols-[3rem_minmax(0,1fr)_8rem_8rem_13rem] lg:grid">
                    <span></span>
                    <span>{{ __('Stranica') }}</span>
                    <span>{{ __('Sekcije') }}</span>
                    <span>{{ __('Status') }}</span>
                    <span class="text-right">{{ __('Akcije') }}</span>
                </div>
            @endif

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
                                @if ($page->navigationType() === 'url')
                                    <flux:badge size="sm" icon="link">{{ __('Poveznica') }}</flux:badge>
                                @endif
                            </div>
                            <p class="mt-1 truncate text-[13px] text-zinc-500 dark:text-zinc-400">{{ $page->navigationUrl() ?: ($page->localized('excerpt') ?: $page->slug) }}</p>
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
                                    <flux:menu.item as="button" type="button" wire:click="editPage('{{ $page->uuid }}')" icon="pencil-square">
                                        {{ __('Uredi stranicu') }}
                                    </flux:menu.item>
                                    <flux:menu.item :href="$this->publicPageUrl($page)" target="_blank" rel="noopener noreferrer" icon="eye">
                                        {{ __('Javni prikaz') }}
                                    </flux:menu.item>
                                    @if ($this->canDeletePage($page))
                                        <flux:menu.item as="button" type="button" wire:click="confirmDeletePage('{{ $page->uuid }}')" icon="archive-box" variant="danger">
                                            {{ __('Arhiviraj') }}
                                        </flux:menu.item>
                                    @endif
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                    </article>
                @empty
                    <x-admin-ui::empty-state
                        :title="__('Nema stranica')"
                        :description="__('Dodajte prvu stranicu ili promijenite pretragu i filtre.')"
                    >
                        <x-slot:icon>
                            <flux:icon name="document-text" class="size-5" />
                        </x-slot:icon>
                    </x-admin-ui::empty-state>
                @endforelse
            </div>

            @if ($this->pages->hasPages())
                <div class="border-t border-zinc-200 px-5 py-4 dark:border-zinc-800">
                    <flux:pagination :paginator="$this->pages" scroll-to="#table" />
                </div>
            @endif
        </x-admin-ui::panel>

        <flux:modal name="page-create-form" x-on:close="$wire.cancelCreatePage()" class="max-w-xl">
            <form wire:submit="createPage" wire:loading.class="admin-panel-content-loading" wire:target="createPage" class="relative space-y-6">
                <x-admin-ui::loading-overlay target="createPage" :text="__('Spremanje...')" />
                <div>
                    <flux:heading size="lg">{{ __('Nova stranica') }}</flux:heading>
                    <flux:text class="mt-2">{{ __('Upišite naziv stranice.') }}</flux:text>
                </div>

                <flux:input wire:model="newPageTitle" :label="__('Naziv stranice')" :placeholder="__('Npr. Projekti, Radionice ili Donacije')" data-required autofocus />
                <flux:radio.group wire:model.live="newPageNavigationType" variant="segmented" :label="__('Vrsta stavke izbornika')">
                    <flux:radio value="page" :label="__('Stranica')" icon="document-text" />
                    <flux:radio value="url" :label="__('Poveznica')" icon="link" />
                </flux:radio.group>
                @if ($newPageNavigationType === 'url')
                    <flux:input wire:model="newPageNavigationUrl" :label="__('URL poveznice')" placeholder="https://primjer.hr" inputmode="url" data-required />
                    <flux:checkbox wire:model="newPageNavigationNewTab" :label="__('Otvori u novoj kartici')" />
                @endif
                <flux:select wire:model="newPageParentUuid" :label="__('Nadređena stranica')" :description="__('Odaberite putanju. Struktura može imati najviše tri razine.')">
                    @include('pages::livewire.partials.hierarchy-parent-options', ['options' => $this->parentPageOptions])
                </flux:select>

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost">{{ __('Odustani') }}</flux:button>
                    </flux:modal.close>
                    <x-admin-ui::submit-button target="createPage" icon="plus">{{ __('Izradi stranicu') }}</x-admin-ui::submit-button>
                </div>
            </form>
        </flux:modal>

        <flux:modal name="page-title-form" x-on:close="$wire.cancelPageTitleForm()" class="max-w-xl">
            <form wire:submit="savePage" wire:loading.class="admin-panel-content-loading" wire:target="savePage" class="relative space-y-6">
                <x-admin-ui::loading-overlay target="savePage" :text="__('Spremanje...')" />
                <div>
                    <flux:heading size="lg">{{ __('Naziv stranice') }}</flux:heading>
                    <flux:text class="mt-2">{{ __('Promijenite naziv i kratki opis koji se prikazuju u administraciji, navigaciji i javnom prikazu.') }}</flux:text>
                </div>

                <div class="grid gap-5">
                    <flux:input wire:model="pageTitle" :label="__('Naziv stranice')" data-required autofocus />
                    <flux:textarea wire:model="pageExcerpt" :label="__('Kratki opis')" rows="3" />
                    @if (! $pageIsHome)
                        <flux:radio.group wire:model.live="pageNavigationType" variant="segmented" :label="__('Vrsta stavke izbornika')">
                            <flux:radio value="page" :label="__('Stranica')" icon="document-text" />
                            <flux:radio value="url" :label="__('Poveznica')" icon="link" />
                        </flux:radio.group>
                        @if ($pageNavigationType === 'url')
                            <flux:input wire:model="pageNavigationUrl" :label="__('URL poveznice')" placeholder="https://primjer.hr" inputmode="url" data-required />
                            <flux:checkbox wire:model="pageNavigationNewTab" :label="__('Otvori u novoj kartici')" />
                        @endif
                    @endif
                    <flux:select wire:model="pageParentUuid" :label="__('Nadređena stranica')" :description="__('Odaberite razinu i punu putanju u izborniku.')">
                        @include('pages::livewire.partials.hierarchy-parent-options', ['options' => $this->parentPageOptions])
                    </flux:select>
                </div>

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost">{{ __('Odustani') }}</flux:button>
                    </flux:modal.close>
                    <x-admin-ui::submit-button target="savePage">{{ __('Spremi naziv') }}</x-admin-ui::submit-button>
                </div>
            </form>
        </flux:modal>

        <flux:modal name="page-delete" x-on:close="$wire.cancelDeletePage()" class="max-w-lg">
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
