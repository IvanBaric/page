@php
    $depth = (int) ($depth ?? 1);
    $children = $this->pages->where('parent_id', $listedPage->getKey());
    $isRoot = $depth === 1;
@endphp

<div
    wire:key="public-page-branch-{{ $listedPage->uuid }}"
    wire:sort:item="{{ $listedPage->uuid }}"
    @class([
        'border-b border-zinc-200 last:border-b-0 dark:border-zinc-800' => $isRoot,
    ])
>
    @include('pages::livewire.public-site.partials.page-structure-row', [
        'listedPage' => $listedPage,
        'depth' => $depth,
        'showSectionEditor' => $showSectionEditor,
        'showPublicPreview' => $showPublicPreview,
    ])

    @if ($depth < $this->maxPageDepth())
        <div
            wire:sort="movePageInStructure"
            wire:sort:group="public-page-structure"
            wire:sort:group-id="{{ $listedPage->uuid }}"
            @class([
                'min-h-3 divide-y divide-zinc-200 border-t border-zinc-100 dark:divide-zinc-800 dark:border-zinc-900',
                'ml-5 border-l border-l-zinc-200 dark:border-l-zinc-800' => $depth > 1,
            ])
        >
            @foreach ($children as $childPage)
                @include('pages::livewire.public-site.partials.page-structure-branch', [
                    'listedPage' => $childPage,
                    'depth' => $depth + 1,
                    'showSectionEditor' => $showSectionEditor,
                    'showPublicPreview' => $showPublicPreview,
                ])
            @endforeach
        </div>
    @endif
</div>
