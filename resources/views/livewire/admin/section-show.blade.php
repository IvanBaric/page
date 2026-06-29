<section class="admin-page">
    <div class="admin-page-header">
        <div class="admin-page-header-copy">
            <div class="mb-6">
                <flux:breadcrumbs>
                    <flux:breadcrumbs.item :href="route($this->pageIndexRouteName())" wire:navigate>{{ __('Stranice') }}</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item :href="route($this->pageShowRouteName(), ['page' => $section->page->uuid])" wire:navigate>{{ $section->page->localized('title') }}</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item>{{ $section->localized('title') ?: __('Sekcija') }}</flux:breadcrumbs.item>
                </flux:breadcrumbs>
            </div>
            <h1 class="admin-page-title">{{ $section->localized('title') ?: __('Sekcija') }}</h1>
            <flux:text class="admin-page-description">{{ __('Uredite stavke ove sekcije i prilagodite sadržaj stranice.') }}</flux:text>
        </div>
        <div class="admin-page-actions">
            <flux:button
                type="button"
                variant="primary"
                icon="check"
                wire:click="$dispatch('pages-save-section-editor')"
            >
                {{ __('Spremi promjene') }}
            </flux:button>
        </div>
    </div>

    <livewire:dynamic-component :component="$this->editorComponent" :section="$section" :key="'section-editor-'.$section->uuid" />
</section>
