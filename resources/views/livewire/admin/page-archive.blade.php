<x-admin-ui::page>
    <div class="admin-page-header">
        <div class="admin-page-header-copy">
            <h1 class="admin-page-title">{{ __('Arhiva') }}</h1>
            <flux:text class="admin-page-description">{{ __('Arhivirani sadržaj možete vratiti kada vam ponovno zatreba.') }}</flux:text>
        </div>

        <div class="admin-page-actions w-full md:w-96">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                :placeholder="__('Pretraži arhivu...')"
                clearable
            />
        </div>
    </div>

    <x-admin-ui::panel loading loading-target="search,restore" loading-text="{{ __('Učitavam arhivu...') }}">
        @php($recordCount = count($this->archivedRecords->items()))

        <div class="admin-panel-header">
            <div>
                <h2 class="admin-panel-title">{{ __('Arhivirani zapisi') }}</h2>
                <p class="admin-panel-description">
                    {{ trans_choice('{0} Nema arhiviranih zapisa na ovoj stranici.|{1} :count zapis na ovoj stranici.|[2,*] :count zapisa na ovoj stranici.', $recordCount, ['count' => $recordCount]) }}
                </p>
            </div>
        </div>

        @if ($recordCount === 0)
            <div class="admin-empty">
                <div class="admin-empty-icon">
                    <flux:icon icon="archive-box" class="size-6" />
                </div>

                <h3 class="admin-empty-title">{{ __('Arhiva je prazna') }}</h3>
                <p class="admin-empty-description">{{ __('Nema arhiviranih stranica, sekcija ili zapisa za trenutnu pretragu.') }}</p>
            </div>
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
                            <div class="flex h-20 w-28 shrink-0 overflow-hidden rounded-2xl bg-zinc-100 text-zinc-400 ring-1 ring-inset ring-zinc-200 dark:bg-zinc-900 dark:text-zinc-500 dark:ring-zinc-800">
                                <div class="flex h-full w-full items-center justify-center">
                                    <span class="text-2xl font-semibold uppercase">{{ mb_substr((string) $record['type_label'], 0, 1) }}</span>
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

                        <div class="flex justify-end gap-1">
                            <button
                                type="button"
                                wire:click="restore('{{ $record['type'] }}', '{{ $record['uuid'] }}')"
                                class="inline-flex h-8 items-center justify-center gap-2 rounded-md px-3 text-sm font-medium text-zinc-800 transition hover:bg-zinc-800/5 dark:text-white dark:hover:bg-white/15"
                            >
                                {{ __('Vrati') }}
                            </button>
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
</x-admin-ui::page>
