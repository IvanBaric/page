@php
    $layout = (string) config('pages.public_site.layout', 'layouts.public');
    $templateKey = function_exists('template_engine')
        ? template_engine()->resolveTemplateKey($page)
        : (string) $page->getAttribute('template');
@endphp

<x-dynamic-component
    :component="$layout"
    :title="$page->localized('title') ?: __('Stranica')"
    :subject="$subject"
    :organization="$subject"
    :seo-data="$seoData ?? null"
    :public-pages="$publicPages"
    :template-key="$templateKey"
    :small-header="! (bool) $page->is_home"
    :small-header-title="$page->localized('title')"
    :small-header-subtitle="($page->localized('excerpt') || $page->localized('content')) ? ($page->localized('excerpt') ?: str($page->localized('content'))->stripTags()->squish()->limit(150)->toString()) : null"
>
    <main>
        @livewire(
            \IvanBaric\TemplateEngine\Livewire\PublicPage::class,
            ['page' => $page, 'templateKey' => $templateKey],
            key('template-engine-public-page-'.$page->uuid)
        )
    </main>

    @php
        $canManagePublicPage = auth()->check()
            && is_numeric(corexis_tenant_id())
            && (string) $page->team_id === (string) corexis_tenant_id()
            && corexis_can('pages.update', $page);
    @endphp

    @if ($canManagePublicPage)
        <div class="bg-white px-4 py-10 dark:bg-zinc-950 sm:px-6">
            <button
                type="button"
                x-data
                x-on:click="Livewire.dispatch('pages-open-public-section-creator')"
                class="group mx-auto flex min-h-32 w-full max-w-6xl cursor-pointer flex-col items-center justify-center gap-3 rounded-lg border border-dashed border-zinc-300 bg-zinc-50/70 px-6 text-center transition duration-200 hover:border-[color:var(--niva-primary-200)] hover:bg-[color:var(--niva-primary-50)] dark:border-zinc-700 dark:bg-zinc-900/50 dark:hover:border-[color:var(--niva-primary-700)] dark:hover:bg-zinc-900"
            >
                <span class="grid size-10 place-items-center rounded-full bg-white text-[color:var(--niva-primary-700)] shadow-sm ring-1 ring-[color:var(--niva-primary-100)] transition group-hover:scale-105 group-hover:bg-[color:var(--niva-primary-50)] group-hover:text-[color:var(--niva-primary-800)] dark:bg-zinc-950 dark:text-[color:var(--niva-primary-300)] dark:ring-[color:var(--niva-primary-900)] dark:group-hover:bg-zinc-900 dark:group-hover:text-[color:var(--niva-primary-200)]">
                    <flux:icon name="plus" class="size-5" />
                </span>
                <span>
                    <span class="block text-sm font-semibold text-zinc-950 dark:text-white">{{ __('Dodaj sekciju') }}</span>
                    <span class="mt-1 block text-sm text-zinc-500 dark:text-zinc-400">{{ __('Dodajte novi sadržajni blok na kraj ove stranice.') }}</span>
                </span>
            </button>
        </div>

        <livewire:pages.public.section-editor-flyout />
        <livewire:pages.public.template-part-editor-flyout />
        <livewire:pages.public.page-structure-flyout :page="$page" />
        @livewire(
            \IvanBaric\Pages\Livewire\Admin\PageShow::class,
            ['page' => $page, 'embedded' => true, 'publicActions' => true],
            key('public-page-action-handler-'.$page->uuid)
        )
    @endif
</x-dynamic-component>
