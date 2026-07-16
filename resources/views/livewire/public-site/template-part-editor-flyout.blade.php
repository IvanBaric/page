<div>
    <x-admin-ui::action-loading target="__dispatch" :text="__('Učitavanje...')" />

    <flux:modal
        name="public-template-part-editor"
        x-on:close="$wire.cancelTemplatePartEditor()"
        flyout
        variant="floating"
        :closable="false"
        class="w-full p-0! md:w-2xl xl:w-5xl"
    >
        @if ($this->model && $part)
            <div
                x-data="{ saving: false }"
                x-on:pages-save-finished.window="saving = false"
                class="min-w-0"
            >
                <div class="sticky top-0 z-40 flex min-h-14 items-center justify-between gap-3 border-b border-zinc-200 bg-white/95 px-4 py-2 backdrop-blur sm:px-6 dark:border-zinc-700 dark:bg-zinc-800/95">
                    <div class="min-w-0 flex-1">
                        <flux:heading size="lg" class="truncate">{{ $this->title }}</flux:heading>
                    </div>

                    <div class="flex shrink-0 items-center gap-1">
                        <flux:button
                            type="button"
                            variant="primary"
                            size="sm"
                            x-on:click="saving = true"
                            x-bind:disabled="saving"
                            wire:click="$dispatch('pages-save-singleton-editor')"
                        >
                            <span x-show="! saving" class="inline-flex items-center gap-2">
                                <flux:icon name="check" class="size-4 shrink-0" />
                                <span>{{ __('Spremi') }}</span>
                            </span>
                            <span x-cloak x-show="saving" class="inline-flex items-center gap-2">
                                <span class="admin-submit-spinner" aria-hidden="true"></span>
                                <span>{{ __('Spremanje...') }}</span>
                            </span>
                        </flux:button>

                        <flux:modal.close>
                            <flux:button
                                type="button"
                                variant="ghost"
                                size="sm"
                                icon="x-mark"
                                aria-label="{{ __('Zatvori') }}"
                            />
                        </flux:modal.close>
                    </div>
                </div>

                <div class="relative min-w-0 px-4 pb-6 pt-3 sm:px-6 sm:pt-4">
                    <div x-cloak x-show="saving" x-transition.opacity.duration.150ms class="pointer-events-none absolute inset-0 z-30 flex items-start justify-center bg-white/35 pt-4 backdrop-blur-[1px] dark:bg-zinc-950/25">
                        <div class="admin-loading-pill">
                            <span class="admin-loading-spinner" aria-hidden="true"></span>
                            <span>{{ __('Spremanje...') }}</span>
                        </div>
                    </div>

                    <div x-bind:class="{ 'admin-panel-content-loading': saving }">
                        @livewire(
                            \IvanBaric\Pages\Livewire\Admin\ConfiguredSingletonEditor::class,
                            ['model' => $this->model, 'definitionKey' => $this->definitionKey(), 'initialTab' => $editorTab],
                            key('public-template-part-editor-'.$part.'-'.$editorTab.'-'.$this->model->getKey())
                        )
                    </div>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
