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

        @if ($this->hasSettingsTab())
            @foreach ($this->settingsPanels() as $settingsPanel)
                <flux:tab.panel :name="$settingsPanel['key']" class="pt-6">
                    @include('pages::livewire.admin.partials.configured-settings-panel', ['settingsPanel' => $settingsPanel])
                </flux:tab.panel>
            @endforeach
        @endif
    </flux:tab.group>
</div>
