<section class="admin-page" x-data="{ saving: false }" x-on:pages-save-finished.window="saving = false">
    <x-admin-ui::page-header
        :title="$section->localized('title') ?: __('Sekcija')"
        :description="__('Uredite stavke ove sekcije i prilagodite sadržaj stranice.')"
        icon="document-text"
    >
        <x-slot:before>
            <flux:breadcrumbs class="mb-6">
                <flux:breadcrumbs.item :href="route($this->pageIndexRouteName())" wire:navigate>{{ __('Stranice') }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item :href="route($this->pageShowRouteName(), ['page' => $section->page->uuid])" wire:navigate>{{ $section->page->localized('title') }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ $section->localized('title') ?: __('Sekcija') }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </x-slot:before>

        <x-slot:actions>
            <flux:button
                type="button"
                variant="primary"
                data-admin-submit-button
                class="relative isolate overflow-hidden"
                x-on:click="if (saving) return; saving = true"
                x-bind:aria-disabled="saving ? 'true' : 'false'"
                x-bind:class="{ 'pointer-events-none': saving }"
                wire:click="$dispatch('pages-save-section-editor')"
            >
                <span x-show="! saving" class="relative z-10 inline-flex items-center gap-2">
                    <flux:icon name="check" class="size-4 shrink-0" />
                    <span>{{ __('Spremi promjene') }}</span>
                </span>
                <span x-cloak x-show="saving" class="relative z-10 inline-flex items-center gap-2 no-underline">
                    <span class="admin-submit-spinner" aria-hidden="true"></span>
                    <span>{{ __('Spremanje...') }}</span>
                </span>
            </flux:button>
        </x-slot:actions>
    </x-admin-ui::page-header>

    <div class="relative">
        <div x-cloak x-show="saving" x-transition.opacity.duration.150ms class="pointer-events-none absolute inset-0 z-30 flex items-start justify-center bg-white/35 pt-4 backdrop-blur-[1px] dark:bg-zinc-950/25">
            <div class="admin-loading-pill">
                <span class="admin-loading-spinner" aria-hidden="true"></span>
                <span>{{ __('Spremanje...') }}</span>
            </div>
        </div>

        <div x-bind:class="{ 'admin-panel-content-loading': saving }">
            <livewire:dynamic-component :component="$this->editorComponent" :section="$section" :key="'section-editor-'.$section->uuid" />
        </div>
    </div>
</section>
