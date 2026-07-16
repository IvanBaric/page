<div x-on:pages-public-structure-updated.window="if ($event.detail?.reload !== false) window.location.reload()">
    <x-admin-ui::action-loading
        target="__dispatch,selectPage,showPages,openPageCreator,editPage,openPageMover,savePageMove,confirmDeletePage,togglePublished,movePageInStructure"
        :text="__('Učitavanje...')"
    />

    <flux:modal name="public-page-structure" x-on:close="$wire.closeStructure()" flyout variant="floating" class="w-full md:w-2xl xl:w-5xl">
        @if ($loaded)
        <div class="mb-5 flex items-center justify-between gap-3">
            <div class="flex min-w-0 items-center gap-3">
                @if ($this->selectedPage)
                    <flux:button type="button" wire:click="showPages" variant="ghost" size="sm" icon="arrow-left" :aria-label="__('Sve stranice')" />
                @endif

                <div class="min-w-0">
                    <flux:heading size="lg">{{ $this->selectedPage ? $this->selectedPage->localized('title') : __('Stranice') }}</flux:heading>
                    <flux:text class="mt-1">{{ $this->selectedPage ? __('Uredite sekcije odabrane stranice.') : __('Složite stranice, uredite njihov redoslijed i otvorite stranicu koju želite urediti.') }}</flux:text>
                </div>
            </div>

            @if (! $this->selectedPage && $this->canCreatePage())
                <flux:button type="button" wire:click="openPageCreator" variant="primary" size="sm" icon="plus">{{ __('Dodaj stranicu') }}</flux:button>
            @endif
        </div>

        @if ($this->selectedPage)
            @livewire(
                \IvanBaric\Pages\Livewire\Admin\PageShow::class,
                ['page' => $this->selectedPage, 'embedded' => true],
                key('public-page-sections-'.$this->selectedPage->uuid)
            )
        @else
            <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
                @if ($this->pages->isNotEmpty())
                    <div class="hidden grid-cols-[3rem_minmax(0,1fr)_8rem_8rem_7rem] border-b border-zinc-200 bg-zinc-50 px-5 py-3 text-xs font-semibold uppercase text-zinc-400 dark:border-zinc-800 dark:bg-zinc-900/60 lg:grid">
                        <span></span>
                        <span>{{ __('Stranica') }}</span>
                        <span>{{ __('Sekcije') }}</span>
                        <span>{{ __('Status') }}</span>
                        <span class="text-right">{{ __('Akcije') }}</span>
                    </div>
                @endif

                <div wire:sort="movePageInStructure" wire:sort:group="public-page-structure" wire:sort:group-id="root">
                    @forelse ($this->pages->whereNull('parent_id') as $listedPage)
                        <section wire:key="public-page-group-{{ $listedPage->uuid }}" wire:sort:item="{{ $listedPage->uuid }}" class="border-b border-zinc-200 last:border-b-0 dark:border-zinc-800">
                            @include('pages::livewire.public-site.partials.page-structure-row', [
                                'listedPage' => $listedPage,
                                'isChild' => false,
                                'showSectionEditor' => true,
                                'showPublicPreview' => true,
                            ])

                            <div
                                wire:sort="movePageInStructure"
                                wire:sort:group="public-page-structure"
                                wire:sort:group-id="{{ $listedPage->uuid }}"
                                class="min-h-3 divide-y divide-zinc-200 border-t border-zinc-100 dark:divide-zinc-800 dark:border-zinc-900"
                            >
                                @foreach ($this->pages->where('parent_id', $listedPage->getKey()) as $childPage)
                                    <div wire:key="public-page-child-{{ $childPage->uuid }}" wire:sort:item="{{ $childPage->uuid }}">
                                        @include('pages::livewire.public-site.partials.page-structure-row', [
                                            'listedPage' => $childPage,
                                            'isChild' => true,
                                            'showSectionEditor' => true,
                                            'showPublicPreview' => true,
                                        ])
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @empty
                        <x-admin-ui::empty-state
                            :title="__('Još nema stranica')"
                            :description="__('Izradite prvu stranicu kako biste joj mogli dodati sekcije i sadržaj.')"
                            class="m-5"
                        >
                            <x-slot:icon><flux:icon name="document-plus" class="size-6" /></x-slot:icon>
                        </x-admin-ui::empty-state>
                    @endforelse
                </div>
            </div>

        @endif
        @endif
    </flux:modal>

    <flux:modal name="public-page-move" x-on:close="$wire.cancelPageMover()" class="max-w-xl">
        <form wire:submit="savePageMove" wire:loading.class="admin-panel-content-loading" wire:target="savePageMove" class="relative space-y-6">
            <x-admin-ui::loading-overlay target="savePageMove" :text="__('Premještanje...')" />

            <div>
                <flux:heading size="lg">{{ __('Premjesti stranicu') }}</flux:heading>
                <flux:text class="mt-2">{{ __('Odaberite gdje će se stranica prikazivati u glavnom izborniku. Nakon premještanja možete je povući na točan položaj.') }}</flux:text>
            </div>

            <flux:select wire:model="movePageParentUuid" variant="listbox" :label="__('Mjesto u izborniku')">
                <flux:select.option value="">{{ __('Glavna razina') }}</flux:select.option>
                @foreach ($this->movePageOptions as $option)
                    <flux:select.option :value="$option['uuid']">{{ __('Podstranica: :page', ['page' => $option['label']]) }}</flux:select.option>
                @endforeach
            </flux:select>

            @if ($movingPageUuid && $this->pages->firstWhere('uuid', $movingPageUuid)?->children_exists)
                <flux:callout variant="warning" icon="exclamation-triangle">
                    <flux:callout.heading>{{ __('Stranica ima podstranice') }}</flux:callout.heading>
                    <flux:callout.text>{{ __('Stranicu s podstranicama možete premjestiti samo unutar glavne razine.') }}</flux:callout.text>
                </flux:callout>
            @endif

            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button type="button" variant="ghost">{{ __('Odustani') }}</flux:button></flux:modal.close>
                <x-admin-ui::submit-button target="savePageMove" icon="arrows-right-left">{{ __('Premjesti') }}</x-admin-ui::submit-button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="public-page-create" x-on:close="$wire.cancelPageCreator()" class="max-w-xl">
        <form wire:submit="createPage" wire:loading.class="admin-panel-content-loading" wire:target="createPage" class="relative space-y-6">
            <x-admin-ui::loading-overlay target="createPage" :text="__('Spremanje...')" />

            <div>
                <flux:heading size="lg">{{ __('Nova stranica') }}</flux:heading>
                <flux:text class="mt-2">{{ __('Upišite naziv stranice.') }}</flux:text>
            </div>

            <flux:input wire:model="newPageTitle" :label="__('Naziv stranice')" :placeholder="__('Npr. Projekti, Radionice ili Donacije')" data-required autofocus />
            <flux:select wire:model="newPageParentUuid" variant="listbox" :label="__('Nadređena stranica')" :description="__('Ostavite prazno za stavku glavnog izbornika.')">
                <flux:select.option value="">{{ __('Glavna razina') }}</flux:select.option>
                @foreach ($this->parentPageOptions as $option)
                    <flux:select.option :value="$option['uuid']">{{ $option['label'] }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button type="button" variant="ghost">{{ __('Odustani') }}</flux:button></flux:modal.close>
                <x-admin-ui::submit-button target="createPage" icon="plus">{{ __('Izradi stranicu') }}</x-admin-ui::submit-button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="public-page-title-form" x-on:close="$wire.cancelPageEditor()" class="max-w-xl">
        <form wire:submit="savePage" wire:loading.class="admin-panel-content-loading" wire:target="savePage" class="relative space-y-6">
            <x-admin-ui::loading-overlay target="savePage" :text="__('Spremanje...')" />

            <div>
                <flux:heading size="lg">{{ __('Uredi stranicu') }}</flux:heading>
                <flux:text class="mt-2">{{ __('Promijenite naziv i kratki opis stranice.') }}</flux:text>
            </div>

            <div class="grid gap-5">
                <flux:input wire:model="pageTitle" :label="__('Naziv')" data-required autofocus />
                <flux:textarea wire:model="pageExcerpt" :label="__('Opis')" rows="4" />
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button type="button" variant="ghost">{{ __('Odustani') }}</flux:button></flux:modal.close>
                <x-admin-ui::submit-button target="savePage">{{ __('Spremi promjene') }}</x-admin-ui::submit-button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="public-page-delete" x-on:close="$wire.cancelDeletePage()" class="max-w-lg">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Arhivirati stranicu?') }}</flux:heading>
                <flux:text class="mt-2">{{ __('Stranica će se premjestiti u arhivu zajedno sa svim svojim sekcijama.') }}</flux:text>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button type="button" variant="ghost">{{ __('Odustani') }}</flux:button></flux:modal.close>
                <flux:button wire:click="deletePage" wire:loading.attr="disabled" wire:target="deletePage" type="button" variant="danger" icon="archive-box">
                    <span wire:loading.remove wire:target="deletePage">{{ __('Arhiviraj stranicu') }}</span>
                    <span wire:loading wire:target="deletePage">{{ __('Arhiviranje...') }}</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
