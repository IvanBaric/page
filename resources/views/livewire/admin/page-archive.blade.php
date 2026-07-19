<x-admin-ui::page>
    @if ($embedded)
        <div class="flex justify-end">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                :placeholder="__('Pretraži arhivu...')"
                class="w-full md:w-96"
                clearable
            />
        </div>
    @else
        <x-admin-ui::page-header
            :title="__('Arhiva')"
            :description="__('Arhivirani sadržaj možete vratiti kada vam ponovno zatreba.')"
            icon="document-text"
        >
            <x-slot:actions>
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    icon="magnifying-glass"
                    :placeholder="__('Pretraži arhivu...')"
                    class="w-full md:w-96"
                    clearable
                />
            </x-slot:actions>
        </x-admin-ui::page-header>
    @endif

    <x-admin-ui::panel loading loading-target="search,restore,delete" loading-text="{{ __('Učitavam arhivu...') }}">
        @php($recordCount = count($this->archivedRecords->items()))

        @unless ($embedded)
            <div class="admin-panel-header">
                <div>
                    <h2 class="admin-panel-title">{{ __('Arhivirani zapisi') }}</h2>
                    <p class="admin-panel-description">
                        {{ trans_choice('{0} Nema arhiviranih zapisa na ovoj stranici.|{1} :count zapis na ovoj stranici.|[2,*] :count zapisa na ovoj stranici.', $recordCount, ['count' => $recordCount]) }}
                    </p>
                </div>
            </div>
        @endunless

        @if ($recordCount === 0)
            <x-admin-ui::empty-state
                :title="__('Arhiva je prazna')"
                :description="__('Nema arhiviranih stranica, sekcija ili zapisa za trenutnu pretragu.')"
            >
                <x-slot:icon><flux:icon name="archive-box" class="size-6" /></x-slot:icon>
            </x-admin-ui::empty-state>
        @else
            <div class="admin-list-header hidden grid-cols-[minmax(0,1fr)_10rem_9rem_11rem] lg:grid">
                <span>{{ __('Zapis') }}</span>
                <span>{{ __('Arhivirano') }}</span>
                <span>{{ __('Vrsta') }}</span>
                <span class="text-right">{{ __('Akcije') }}</span>
            </div>

            <div id="table" class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @foreach ($this->archivedRecords as $record)
                    <article wire:key="archive-{{ $record['key'] }}" class="admin-list-row admin-archive-list-row grid-cols-1 gap-4 p-5 lg:grid-cols-[minmax(0,1fr)_10rem_9rem_11rem]">
                        <div class="flex min-w-0 gap-4">
                            <div class="admin-list-thumbnail h-20 aspect-auto">
                                <div class="flex h-full w-full items-center justify-center">
                                    <flux:icon icon="archive-box" class="size-8" />
                                </div>
                            </div>

                            <div class="min-w-0 py-1">
                                <h2 class="truncate text-[15px] font-semibold text-zinc-950 dark:text-white">{{ $record['name'] }}</h2>

                                @if ($record['context'])
                                    <p class="mt-2 truncate text-[13px] text-zinc-500 dark:text-zinc-400">{{ $record['context'] }}</p>
                                @endif
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-3 lg:block">
                            <span class="text-xs font-medium uppercase text-zinc-500 lg:hidden">{{ __('Arhivirano') }}</span>
                            <span class="text-sm tabular-nums text-zinc-600 dark:text-zinc-300">{{ $record['archived_at'] }}</span>
                        </div>

                        <div class="flex items-center justify-between gap-3 lg:block">
                            <span class="text-xs font-medium uppercase text-zinc-500 lg:hidden">{{ __('Vrsta') }}</span>
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-2.5 py-1 text-[11px] font-medium uppercase tracking-[0.12em] text-zinc-600 ring-1 ring-inset ring-zinc-950/5 dark:bg-zinc-900 dark:text-zinc-300 dark:ring-white/10">
                                {{ $record['type_label'] }}
                            </span>
                        </div>

                        <div class="flex items-center justify-end gap-1">
                            <flux:dropdown position="bottom" align="end">
                                <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" :aria-label="__('Akcije')" />

                                <flux:menu>
                                    <flux:menu.item as="button" type="button" wire:click="confirmRestore('{{ $record['type'] }}', '{{ $record['uuid'] }}')" icon="arrow-uturn-left">
                                        {{ __('Vrati') }}
                                    </flux:menu.item>

                                    <flux:menu.separator />

                                    <flux:menu.item as="button" type="button" wire:click="confirmDelete('{{ $record['type'] }}', '{{ $record['uuid'] }}')" icon="trash" variant="danger">
                                        {{ __('Obriši') }}
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif

        @if ($this->archivedRecords->hasPages())
            <div class="border-t border-zinc-200 px-5 py-4 dark:border-zinc-800">
                <flux:pagination :paginator="$this->archivedRecords" scroll-to="#table" />
            </div>
        @endif
    </x-admin-ui::panel>

    <flux:modal name="archive-restore" x-on:close="$wire.cancelRestore()" class="max-w-lg">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Vratiti zapis iz arhive?') }}</flux:heading>
                <flux:text class="mt-2">
                    {{ __('Zapis ":name" bit će ponovno dostupan u administraciji.', ['name' => $restoringName ?: __('odabrani zapis')]) }}
                </flux:text>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button type="button" variant="ghost">{{ __('Odustani') }}</flux:button>
                </flux:modal.close>

                <flux:button type="button" wire:click="restore" variant="primary" icon="arrow-uturn-left">
                    {{ __('Vrati zapis') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="archive-delete" x-on:close="$wire.cancelDelete()" class="max-w-lg">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Trajno obrisati zapis?') }}</flux:heading>
                <flux:text class="mt-2">
                    {{ __('Zapis ":name" bit će trajno obrisan iz arhive i više ga neće biti moguće vratiti.', ['name' => $deletingName ?: __('odabrani zapis')]) }}
                </flux:text>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button type="button" variant="ghost">{{ __('Odustani') }}</flux:button>
                </flux:modal.close>

                <flux:button type="button" wire:click="delete" variant="danger" icon="trash">
                    {{ __('Trajno obriši') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</x-admin-ui::page>
