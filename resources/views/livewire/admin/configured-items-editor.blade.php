<div class="space-y-6">
    <flux:tab.group>
        <flux:tabs wire:model.live="tab" variant="segmented" scrollable>
            @foreach ($this->editorTabs() as $editorTab)
                <flux:tab :name="$editorTab['key']" :icon="$editorTab['icon']">{{ $editorTab['label'] }}</flux:tab>
            @endforeach
        </flux:tabs>

        @if ($this->hasItemsTab())
            <flux:tab.panel :name="$this->itemsTabKey()" class="pt-6">
                @include('pages::livewire.admin.partials.configured-items-list', $this->itemsEditorConfig())
            </flux:tab.panel>
        @endif

        @if ($this->hasSourceTab())
            <flux:tab.panel :name="$this->sourceTabKey()" class="pt-6">
                @include('pages::livewire.admin.partials.configured-source-panel')
            </flux:tab.panel>
        @endif

        @if ($this->hasLayoutTab())
            <flux:tab.panel :name="$this->layoutTabKey()" class="pt-6">
                <section class="admin-panel">
                    <div class="admin-panel-header">
                        <div>
                            <h2 class="admin-panel-title">{{ $this->layoutHeading() }}</h2>
                            <p class="admin-panel-description">
                                {{ $this->layoutDescription() }}
                            </p>
                        </div>
                    </div>

                    <form wire:submit="saveSettings" wire:loading.class="admin-panel-content-loading" wire:target="saveSettings,saveAllChanges" class="relative min-w-0 space-y-6 p-4 sm:p-6">
                        <x-admin-ui::loading-overlay target="saveSettings,saveAllChanges" :text="__('Spremanje...')" />
                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            @foreach ($this->layoutVariants() as $variant)
                                @php
                                    $variantBadge = data_get($variant, 'options.badge');
                                    $variantBadge = filled($variantBadge)
                                        ? (string) $variantBadge
                                        : (data_get($variant, 'options.animated') ? __('Animacija') : null);
                                @endphp
                                <label @class([
                                    'cursor-pointer rounded-xl border p-4 transition',
                                    'border-pink-500 bg-pink-50 ring-1 ring-pink-200 dark:border-pink-400 dark:bg-pink-500/10' => $layoutVariant === $variant['value'],
                                    'border-zinc-200 bg-white hover:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-950 dark:hover:border-zinc-700' => $layoutVariant !== $variant['value'],
                                ])>
                                    <input type="radio" wire:model.live="layoutVariant" value="{{ $variant['value'] }}" class="sr-only">

                                    <span class="relative mb-4 block h-28 overflow-hidden rounded-lg bg-pink-50 p-3 ring-1 ring-pink-200 dark:bg-zinc-900 dark:ring-white/10">
                                        @if ($variantBadge)
                                            <span class="absolute right-2 top-2 z-10 rounded-full bg-pink-100 px-2 py-0.5 text-[10px] font-semibold leading-4 text-pink-700 ring-1 ring-pink-200 dark:bg-pink-500/15 dark:text-pink-200 dark:ring-pink-400/30">
                                                {{ $variantBadge }}
                                            </span>
                                        @endif

                                        @include('pages::livewire.admin.partials.layout-variant-preview', ['variant' => $variant])
                                    </span>

                                    <span class="block text-sm font-semibold text-zinc-950 dark:text-white">{{ $variant['label'] }}</span>
                                    <span class="mt-1 block text-xs leading-5 text-zinc-500 dark:text-zinc-400">{{ $variant['description'] }}</span>
                                </label>
                            @endforeach
                        </div>

                    </form>
                </section>
            </flux:tab.panel>
        @endif

        @if ($this->hasViewTabs())
            @foreach ($this->viewTabs() as $viewTab)
                <flux:tab.panel :name="$viewTab['key']" class="pt-6">
                    @if ($viewTab['view'] !== '' && view()->exists($viewTab['view']))
                        @include($viewTab['view'], [
                            'section' => $section,
                            'viewTab' => $viewTab,
                        ])
                    @endif
                </flux:tab.panel>
            @endforeach
        @endif

        @if ($this->hasLivewireTabs())
            @foreach ($this->livewireTabs() as $livewireTab)
                <flux:tab.panel :name="$livewireTab['key']" class="pt-6">
                    @livewire(
                        $livewireTab['component'],
                        $livewireTab['parameters'],
                        key('section-livewire-tab-'.$section->uuid.'-'.$livewireTab['key'])
                    )
                </flux:tab.panel>
            @endforeach
        @endif

        @if ($this->hasSettingsTab())
            @foreach ($this->settingsPanels() as $settingsPanel)
                <flux:tab.panel :name="$settingsPanel['key']" class="pt-6">
                    @include('pages::livewire.admin.partials.configured-settings-panel', ['settingsPanel' => $settingsPanel])
                </flux:tab.panel>
            @endforeach
        @endif

        <flux:tab.panel name="section" class="pt-6">
            <section class="admin-panel">
                <div class="admin-panel-header">
                    <div>
                        <h2 class="admin-panel-title">{{ __('Uredi sekciju') }}</h2>
                        <p class="admin-panel-description">{{ __('Uredite naziv i opis sekcije.') }}</p>
                    </div>
                </div>

                <form wire:submit="saveSectionDetails" wire:loading.class="admin-panel-content-loading" wire:target="saveSectionDetails,saveAllChanges" class="relative space-y-6 p-4 sm:p-6">
                    <x-admin-ui::loading-overlay target="saveSectionDetails,saveAllChanges" :text="__('Spremanje...')" />

                    <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_22rem]">
                        <div class="space-y-5">
                            <flux:input wire:model="sectionTitle" :label="__('Naziv')" data-required />
                            <flux:textarea wire:model="sectionDescription" :label="__('Opis')" rows="5" />
                        </div>

                        <div class="space-y-5">
                            <flux:fieldset>
                                <flux:legend>{{ __('Prikaz na javnoj stranici') }}</flux:legend>
                                <div class="space-y-4">
                                    <flux:switch wire:model="sectionShowTitle" :label="__('Prikaži naziv sekcije')" :description="__('Kada je uključeno, naziv sekcije prikazuje se iznad sadržaja na javnoj stranici.')" />
                                    <flux:separator variant="subtle" />
                                    <flux:switch wire:model="sectionShowDescription" :label="__('Prikaži opis sekcije')" :description="__('Kada je uključeno, opis sekcije prikazuje se ispod naziva sekcije na javnoj stranici.')" />
                                </div>
                            </flux:fieldset>

                            @if ($this->sectionNavigationSettingsAvailable)
                                <flux:fieldset class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-900/60">
                                    <flux:legend>{{ __('Izbornik naslovnice') }}</flux:legend>
                                    <div class="space-y-4">
                                        <flux:switch wire:model.live="sectionShowInNavigation" :label="__('Prikaži u izborniku')" :description="__('Sekcija može biti poveznica u glavnom izborniku naslovnice.')" />
                                        @if ($sectionShowInNavigation)
                                            <flux:input wire:model="sectionNavigationLabel" :label="__('Naziv u izborniku')" :placeholder="$sectionTitle ?: __('Naziv sekcije')" />
                                        @endif
                                    </div>
                                </flux:fieldset>
                            @endif
                        </div>
                    </div>
                </form>
            </section>
        </flux:tab.panel>
    </flux:tab.group>

    @if ($pendingGalleryContentSource !== null || $this->hasHiddenDirectGalleryMedia())
    <flux:modal name="{{ $this->gallerySourceChangeModalName() }}" x-on:close="$wire.cancelGallerySourceChange()" class="max-w-2xl">
        @php
            $directPhotos = $this->hiddenDirectGalleryMedia();
        @endphp
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Promijeniti izvor fotografija?') }}</flux:heading>
                <flux:text class="mt-2">{{ __('Ova sekcija već ima izravno vezane fotografije. Nakon prelaska na postojeće galerije one se više neće prikazivati, ali mogu ostati spremljene uz sekciju.') }}</flux:text>
            </div>

            <flux:callout variant="warning" icon="exclamation-triangle">
                <flux:callout.heading>{{ trans_choice('{1} Vezana je jedna fotografija|[2,*] Vezano je :count fotografija', count($directPhotos), ['count' => count($directPhotos)]) }}</flux:callout.heading>
                <flux:callout.text>{{ __('Odaberite želite li ih sačuvati za kasnije ili trajno obrisati prije promjene izvora.') }}</flux:callout.text>
            </flux:callout>

            <div class="max-h-48 overflow-y-auto rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-800 dark:bg-zinc-900/60">
                <ul class="space-y-2">
                    @foreach ($directPhotos as $photo)
                        <li class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-200">
                            <flux:icon name="photo" class="size-4 shrink-0 text-zinc-400" />
                            <span class="truncate">{{ $photo['name'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <flux:modal.close><flux:button type="button" variant="ghost">{{ __('Odustani') }}</flux:button></flux:modal.close>
                <flux:button type="button" wire:click="keepDirectGalleryMedia" icon="archive-box">{{ __('Zadrži fotografije') }}</flux:button>
                <flux:button type="button" wire:click="deleteDirectGalleryMedia" wire:loading.attr="disabled" wire:target="deleteDirectGalleryMedia" variant="danger" icon="trash">
                    <span wire:loading.remove wire:target="deleteDirectGalleryMedia">{{ __('Obriši fotografije') }}</span>
                    <span wire:loading wire:target="deleteDirectGalleryMedia">{{ __('Brisanje...') }}</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>
    @endif
</div>
