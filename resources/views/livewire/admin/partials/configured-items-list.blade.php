@php
    $titleLabel = $titleLabel ?? __('Naziv');
    $subtitleLabel = $subtitleLabel ?? __('Podnaslov');
    $contentLabel = $contentLabel ?? __('Sadržaj');
    $showIcon = $showIcon ?? false;
    $showImage = $showImage ?? false;
    $showUrl = $showUrl ?? false;
    $showYoutubeUrl = $showYoutubeUrl ?? false;
    $showButton = $showButton ?? false;
    $showValue = $showValue ?? false;
    $showTitle = $showTitle ?? true;
    $showSubtitle = $showSubtitle ?? true;
    $showContent = $showContent ?? true;
    $showValueSuffix = $showValueSuffix ?? true;
    $showIconHelp = $showIconHelp ?? false;
    $showSortOrder = $showSortOrder ?? false;
    $showVisibility = $showVisibility ?? true;
    $customFields = $customFields ?? [];
    $inlineForm = $inlineForm ?? false;
    $modalFlyout = $modalFlyout ?? false;
    $singleColumnFields = $singleColumnFields ?? false;
    $contentRows = $contentRows ?? 7;
    $imageUploadSize = $imageUploadSize ?? 'w-full aspect-[4/3]';
    $imageLabel = $imageLabel ?? __('Slika ili logo');
    $imageHelp = $imageHelp ?? corexis_image_upload()->helpText();
    $iconLabel = $iconLabel ?? __('Ikona');
    $urlLabel = $urlLabel ?? __('Web stranica');
    $youtubeUrlLabel = $youtubeUrlLabel ?? __('YouTube URL');
    $buttonLabelLabel = $buttonLabelLabel ?? __('Tekst gumba');
    $buttonUrlLabel = $buttonUrlLabel ?? __('URL gumba');
    $valueLabel = $valueLabel ?? __('Vrijednost');
    $valueSuffixLabel = $valueSuffixLabel ?? __('Sufiks');
    $itemModalDescription = $itemModalDescription ?? __('Unesite podatke za ovu stavku.');
    $inlineSubmitLabel = $inlineSubmitLabel ?? __('Spremi');
    $editActionLabel = $editActionLabel ?? __('Uredi');
    $deleteActionLabel = $deleteActionLabel ?? __('Arhiviraj');
    $deleteConfirmTitle = $deleteConfirmTitle ?? __('Arhivirati stavku?');
    $deleteConfirmDescription = $deleteConfirmDescription ?? __('Stavka će se premjestiti u arhivu. Možete je kasnije vratiti iz Arhive.');
    $modalClass = $modalFlyout ? 'md:w-lg' : 'max-w-5xl';
    $hasSidebar = $showImage || $showUrl || $showButton || $showIcon || $showValue || $showSortOrder || $showVisibility;
@endphp

<x-admin-ui::panel as="section" loading loading-target="saveItem,toggleItem,reorderItem,deleteItem,form.imageUpload" loading-text="{{ __('Spremam promjene...') }}">
    @if ($inlineForm)
        <div class="p-6">
            <div class="mb-6">
                <h2 class="admin-panel-title">{{ $heading }}</h2>
                <p class="admin-panel-description mt-1">{{ $description }}</p>
            </div>

            @include('pages::livewire.admin.partials.configured-item-form', [
                'inlineForm' => true,
                'showFormHeader' => false,
                'showCancel' => false,
                'submitLabel' => $inlineSubmitLabel,
            ])
        </div>
    @else
        <div class="admin-panel-header">
            <div>
                <h2 class="admin-panel-title">{{ $heading }}</h2>
                <p class="admin-panel-description">{{ $description }}</p>
            </div>
            @if ($this->canCreateItem())
                <flux:modal.trigger :name="$this->modalName()">
                    <flux:button wire:click="createItem" variant="filled" icon="plus">{{ $this->addButtonLabel() }}</flux:button>
                </flux:modal.trigger>
            @endif
        </div>

        @if ($this->items->isEmpty())
            <div class="px-6 py-14 text-center">
                <h3 class="text-base font-semibold text-zinc-950 dark:text-white">{{ $this->emptyText() }}</h3>
                <p class="mx-auto mt-2 max-w-sm text-sm text-zinc-500 dark:text-zinc-400">{{ __('Dodajte prvu stavku za ovu sekciju.') }}</p>
            </div>
        @else
            <div wire:sort="reorderItem" class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @foreach ($this->items as $item)
                    <article wire:key="item-{{ $item->uuid }}" wire:sort:item="{{ $item->uuid }}" class="grid grid-cols-1 gap-4 px-5 py-4 transition hover:bg-zinc-50/80 dark:hover:bg-white/[0.03] lg:grid-cols-[3rem_minmax(0,1fr)_9rem_4rem] lg:items-center">
                        <flux:tooltip :content="__('Povucite za promjenu redoslijeda')">
                            <span wire:sort:handle class="inline-flex size-10 cursor-grab items-center justify-center rounded-md bg-zinc-100 text-zinc-500 ring-1 ring-zinc-950/5 transition hover:bg-zinc-200 hover:text-zinc-700 active:cursor-grabbing dark:bg-zinc-900 dark:ring-white/10 dark:hover:bg-zinc-800">
                                <flux:icon name="bars-3" class="size-4" />
                            </span>
                        </flux:tooltip>
                        <div class="min-w-0">
                            <flux:modal.trigger :name="$this->modalName()">
                                <button type="button" wire:click="editItem('{{ $item->uuid }}')" class="block max-w-full truncate text-left text-[15px] font-semibold text-zinc-950 transition hover:text-accent dark:text-white dark:hover:text-accent">
                                    {{ $item->localized('title') ?: __('Neimenovana stavka') }}
                                </button>
                            </flux:modal.trigger>
                            <p class="mt-1 line-clamp-2 text-[13px] leading-5 text-zinc-500 dark:text-zinc-400">{{ $item->localized('content') ?: $item->localized('description') ?: $item->url }}</p>
                        </div>
                        <flux:tooltip :content="$item->is_visible ? __('Sakrij stavku u ovoj sekciji') : __('Prikaži stavku u ovoj sekciji')">
                            <flux:switch wire:click="toggleItem('{{ $item->uuid }}')" :checked="(bool) $item->is_visible" :label="''" :aria-label="$item->is_visible ? __('Uključeno') : __('Isključeno')" />
                        </flux:tooltip>
                        <div wire:sort:ignore class="flex items-center justify-start lg:justify-end">
                            <flux:dropdown position="bottom" align="end">
                                <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" :aria-label="__('Akcije')" />
                                <flux:menu>
                                    <flux:modal.trigger :name="$this->modalName()">
                                        <flux:menu.item as="button" type="button" wire:click="editItem('{{ $item->uuid }}')" icon="pencil">{{ $editActionLabel }}</flux:menu.item>
                                    </flux:modal.trigger>
                                    <flux:menu.separator />
                                    <flux:modal.trigger :name="$this->deleteModalName()">
                                        <flux:menu.item as="button" type="button" wire:click="confirmDeleteItem('{{ $item->uuid }}')" icon="archive-box" variant="danger">{{ $deleteActionLabel }}</flux:menu.item>
                                    </flux:modal.trigger>
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif

        <flux:modal :name="$this->modalName()" flyout variant="floating" :class="$modalClass">
            @include('pages::livewire.admin.partials.configured-item-form', [
                'inlineForm' => false,
                'showFormHeader' => true,
                'showCancel' => true,
                'submitLabel' => __('Spremi'),
            ])
        </flux:modal>

        <flux:modal :name="$this->deleteModalName()" class="max-w-lg">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ $deleteConfirmTitle }}</flux:heading>
                    <flux:text class="mt-2">{{ $deleteConfirmDescription }}</flux:text>
                </div>
                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost">{{ __('Odustani') }}</flux:button>
                    </flux:modal.close>
                    <flux:button wire:click="deleteItem" type="button" variant="danger" icon="archive-box">{{ $deleteActionLabel }}</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</x-admin-ui::panel>
