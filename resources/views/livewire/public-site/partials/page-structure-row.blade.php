@php
    $isChild = (bool) ($isChild ?? false);
    $showSectionEditor = (bool) ($showSectionEditor ?? false);
    $showPublicPreview = (bool) ($showPublicPreview ?? false);
@endphp

<article
    @class([
        'grid grid-cols-1 gap-4 px-5 py-4 transition hover:bg-zinc-50/80 dark:hover:bg-white/[0.03] lg:grid-cols-[3rem_minmax(0,1fr)_8rem_8rem_7rem] lg:items-center',
        'bg-zinc-50/60 pl-10 dark:bg-zinc-900/30 lg:pl-10' => $isChild,
    ])
>
    <div>
        <flux:tooltip :content="$isChild ? __('Povucite za promjenu redoslijeda ili nadređene stranice') : __('Povucite za promjenu redoslijeda')">
            <span wire:sort:handle class="inline-flex size-10 cursor-grab items-center justify-center rounded-md bg-zinc-100 text-zinc-500 ring-1 ring-zinc-950/5 transition hover:bg-zinc-200 hover:text-zinc-700 active:cursor-grabbing dark:bg-zinc-900 dark:ring-white/10 dark:hover:bg-zinc-800">
                <flux:icon :name="$isChild ? 'arrow-turn-down-right' : 'bars-3'" class="size-4" />
            </span>
        </flux:tooltip>
    </div>

    <div wire:sort:ignore class="min-w-0">
        <div class="flex min-w-0 items-center gap-2">
            @if ($showSectionEditor)
                <button type="button" wire:click="selectPage('{{ $listedPage->uuid }}')" class="block max-w-full cursor-pointer truncate text-left text-[15px] font-semibold text-zinc-950 transition hover:text-accent dark:text-white">
                    {{ $listedPage->localized('title') ?: __('Neimenovana stranica') }}
                </button>
            @else
                <p class="truncate text-[15px] font-semibold text-zinc-950 dark:text-white">{{ $listedPage->localized('title') ?: __('Neimenovana stranica') }}</p>
            @endif

            @if ($isChild)
                <span class="shrink-0 rounded-full bg-zinc-200 px-2 py-0.5 text-[11px] font-semibold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">{{ __('Podstranica') }}</span>
            @endif
        </div>
        <p class="mt-1 truncate text-[13px] text-zinc-500 dark:text-zinc-400">{{ $listedPage->localized('excerpt') ?: $listedPage->slug }}</p>
    </div>

    <div class="text-sm tabular-nums text-zinc-600 dark:text-zinc-300">
        {{ trans_choice('{0} bez sekcija|{1} :count sekcija|[2,*] :count sekcija', $listedPage->sections_count, ['count' => $listedPage->sections_count]) }}
    </div>

    <div wire:sort:ignore class="min-w-0">
        @if ($this->canTogglePublished($listedPage))
            <flux:tooltip :content="$listedPage->is_published ? __('Sakrij stranicu iz javnog prikaza') : __('Objavi stranicu u javnom prikazu')">
                <flux:switch wire:click="togglePublished('{{ $listedPage->uuid }}')" :checked="(bool) $listedPage->is_published" :label="''" :aria-label="$listedPage->is_published ? __('Uključeno') : __('Isključeno')" />
            </flux:tooltip>
        @endif
    </div>

    <div wire:sort:ignore class="flex justify-end">
        <flux:dropdown position="bottom" align="end">
            <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" :aria-label="__('Akcije')" />

            <flux:menu>
                @if ($showSectionEditor)
                    <flux:menu.item as="button" type="button" wire:click="selectPage('{{ $listedPage->uuid }}')" icon="rectangle-stack">
                        {{ __('Uredi sekcije') }}
                    </flux:menu.item>
                @endif

                <flux:menu.item as="button" type="button" wire:click="editPage('{{ $listedPage->uuid }}')" icon="pencil-square">
                    {{ __('Promijeni naziv') }}
                </flux:menu.item>

                @if (! $listedPage->is_home)
                    <flux:menu.item as="button" type="button" wire:click="openPageMover('{{ $listedPage->uuid }}')" icon="arrows-right-left">
                        {{ __('Premjesti') }}
                    </flux:menu.item>
                @endif

                @if ($showPublicPreview)
                    <flux:menu.item :href="$this->publicPageUrl($listedPage)" target="_blank" rel="noopener noreferrer" icon="eye">
                        {{ __('Javni prikaz') }}
                    </flux:menu.item>
                @endif

                @if ($this->canDeletePage($listedPage))
                    <flux:menu.separator />
                    <flux:menu.item as="button" type="button" wire:click="confirmDeletePage('{{ $listedPage->uuid }}')" icon="archive-box" variant="danger">
                        {{ __('Arhiviraj') }}
                    </flux:menu.item>
                @endif
            </flux:menu>
        </flux:dropdown>
    </div>
</article>
