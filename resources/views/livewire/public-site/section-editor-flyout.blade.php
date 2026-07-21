<div>
    <x-admin-ui::action-loading target="__dispatch" :text="__('Učitavanje...')" />

    <flux:modal
        name="public-section-editor"
        x-on:close="$wire.cancelSectionEditor()"
        flyout
        variant="floating"
        :closable="false"
        class="w-full p-0! md:w-2xl xl:w-5xl"
    >
        @if ($this->section)
            <div
                x-data="{ saving: false }"
                x-on:pages-save-finished.window="saving = false"
                class="min-w-0"
            >
                <div class="sticky top-0 z-40 flex min-h-14 items-center justify-between gap-3 border-b border-zinc-200 bg-white/95 px-4 py-2 backdrop-blur sm:px-6 dark:border-zinc-700 dark:bg-zinc-800/95">
                    <div class="min-w-0 flex-1">
                        <flux:heading size="lg" class="truncate">
                            {{ $this->section->localized('title') ?: __('Uredi sekciju') }}
                        </flux:heading>
                    </div>

                    <div class="flex shrink-0 items-center gap-1">
                        <flux:button
                            type="button"
                            variant="primary"
                            size="sm"
                            data-admin-submit-button
                            class="relative isolate overflow-hidden"
                            x-on:click="if (saving) return; saving = true"
                            x-bind:aria-disabled="saving ? 'true' : 'false'"
                            x-bind:class="{ 'pointer-events-none': saving }"
                            wire:click="$dispatch('pages-save-section-editor')"
                        >
                            <span x-show="! saving" class="relative z-10 inline-flex items-center gap-2">
                                <flux:icon name="check" class="size-4 shrink-0" />
                                <span>{{ __('Spremi') }}</span>
                            </span>
                            <span x-cloak x-show="saving" class="relative z-10 inline-flex items-center gap-2 no-underline">
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
                            $this->editorComponent,
                            ['section' => $this->section, 'initialTab' => $editorTab],
                            key('public-section-editor-'.$this->section->uuid.'-'.$editorTab)
                        )
                    </div>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
