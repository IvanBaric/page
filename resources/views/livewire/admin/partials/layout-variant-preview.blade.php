@php
    $preview = data_get($variant, 'options.preview', $variant['value']);
    $isFeaturedValuesPreview = in_array($preview, ['featured_values_strip', 'featured_values_columns_2', 'featured_values_columns_3', 'featured_values_columns_4'], true);
    $featuredValuesPreviewColumns = match ($preview) {
        'featured_values_columns_2' => 2,
        'featured_values_columns_4' => 4,
        default => 3,
    };
@endphp

@if ($preview === 'header_hero')
    <span class="flex h-full flex-col justify-between rounded-md bg-white p-2 ring-1 ring-emerald-100">
        <span class="flex items-center justify-between rounded-full bg-white/90 px-2 py-1 shadow-sm">
            <span class="h-2.5 w-12 rounded bg-zinc-900"></span>
            <span class="flex gap-1">
                <span class="h-2.5 w-6 rounded bg-yellow-100"></span>
                <span class="h-2.5 w-6 rounded bg-sky-100"></span>
            </span>
        </span>
        <span class="flex w-3/4 gap-2 rounded-md bg-emerald-50 p-2 shadow-sm">
            <span class="size-7 shrink-0 overflow-hidden rounded-md bg-white ring-1 ring-zinc-200">
                <span class="block size-full bg-emerald-100"></span>
            </span>
            <span class="grid flex-1 gap-1.5">
                <span class="h-1.5 w-10 rounded bg-emerald-200"></span>
                <span class="h-2.5 rounded bg-zinc-900"></span>
                <span class="h-1.5 w-4/5 rounded bg-zinc-300"></span>
                <span class="mt-1 flex gap-1">
                    <span class="h-2 w-9 rounded-full bg-emerald-200"></span>
                    <span class="h-2 w-8 rounded-full bg-white ring-1 ring-zinc-200"></span>
                </span>
            </span>
        </span>
    </span>
@elseif ($preview === 'header_editorial')
    <span class="relative block h-full overflow-hidden rounded-md bg-white shadow-sm ring-1 ring-emerald-100">
        <span class="absolute inset-0 bg-sky-50"></span>
        <span class="absolute left-4 top-6 h-12 w-20 rounded-md bg-yellow-100"></span>
        <span class="absolute bottom-4 right-5 h-12 w-20 rounded-md bg-emerald-100"></span>
        <span class="absolute right-10 top-10 size-10 rounded-full bg-pink-100"></span>
        <span class="absolute left-10 bottom-7 size-8 rounded-full bg-sky-100"></span>
        <span class="absolute inset-0 bg-white/20"></span>
        <span class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,rgba(39,39,42,0.30)_0%,rgba(39,39,42,0.14)_42%,rgba(255,255,255,0.08)_72%,rgba(255,255,255,0.20)_100%)]"></span>
        <span class="absolute inset-x-0 bottom-0 h-10 bg-gradient-to-t from-white/55 to-transparent"></span>
        <span class="absolute left-2 right-2 top-2 flex items-center justify-between rounded-full bg-white/95 px-2 py-1 shadow-sm ring-1 ring-pink-100">
            <span class="flex items-center gap-1.5">
                <span class="size-4 rounded-full bg-emerald-100"></span>
                <span class="h-2 w-12 rounded bg-zinc-900"></span>
            </span>
            <span class="flex gap-1">
                <span class="h-2 w-7 rounded bg-pink-200"></span>
                <span class="h-2 w-6 rounded bg-yellow-100"></span>
                <span class="h-2 w-6 rounded bg-sky-100"></span>
            </span>
        </span>
        <span class="absolute inset-x-[24%] top-10 flex flex-col items-center gap-1.5">
            <span class="size-8 rounded-md bg-white shadow-sm ring-1 ring-zinc-200">
                <span class="m-2 block size-4 rounded-full bg-emerald-100"></span>
            </span>
            <span class="h-1.5 w-20 rounded bg-white/90"></span>
            <span class="h-3 w-24 rounded bg-white shadow-sm"></span>
            <span class="h-3 w-20 rounded bg-white shadow-sm"></span>
            <span class="h-1.5 w-24 rounded bg-white/80"></span>
            <span class="mt-1 flex gap-1">
                <span class="h-2 w-9 rounded-full bg-white"></span>
                <span class="h-2 w-8 rounded-full bg-white/45 ring-1 ring-white/80"></span>
            </span>
        </span>
    </span>
@elseif ($preview === 'header_sticky')
    <span class="flex h-full flex-col justify-between rounded-md bg-white p-2 ring-1 ring-green-100">
        <span class="flex items-center justify-between rounded-full bg-white/90 px-2 py-1 shadow-sm">
            <span class="h-2.5 w-12 rounded bg-zinc-900"></span>
            <span class="flex gap-1">
                <span class="h-2.5 w-6 rounded bg-yellow-100"></span>
                <span class="h-2.5 w-6 rounded bg-sky-100"></span>
            </span>
        </span>
        <span class="ml-auto flex w-3/5 gap-2 rounded-md bg-green-50 p-2 shadow-sm">
            <span class="size-7 shrink-0 overflow-hidden rounded-md bg-white ring-1 ring-zinc-200">
                <span class="m-1.5 block size-4 rounded-full bg-emerald-100"></span>
            </span>
            <span class="grid flex-1 gap-1.5">
                <span class="h-1.5 w-9 rounded bg-green-200"></span>
                <span class="h-2.5 rounded bg-zinc-900"></span>
                <span class="h-1.5 w-4/5 rounded bg-zinc-300"></span>
                <span class="mt-1 flex gap-1">
                    <span class="h-2 w-9 rounded-full bg-emerald-200"></span>
                    <span class="h-2 w-8 rounded-full bg-white ring-1 ring-zinc-200"></span>
                </span>
            </span>
        </span>
    </span>
@elseif ($preview === 'header_split')
    <span class="relative block h-full overflow-hidden rounded-md bg-white shadow-sm ring-1 ring-emerald-100">
        <span class="absolute inset-y-0 -right-6 w-[72%] origin-top-left skew-x-[12deg] overflow-hidden rounded-bl-[3rem] bg-emerald-100">
            <span class="absolute inset-y-0 -left-5 -right-5 -skew-x-[12deg]">
                <span class="absolute bottom-3 right-8 h-7 w-12 rounded-md bg-yellow-100"></span>
                <span class="absolute right-12 top-5 h-8 w-8 rounded-full bg-sky-100"></span>
            </span>
        </span>
        <span class="absolute inset-y-0 left-0 w-[50%]">
            <span class="flex h-full flex-col justify-center p-2 pr-5 pt-8">
                <span class="mb-2 size-7 rounded-full bg-white shadow-sm ring-1 ring-zinc-200">
                    <span class="m-1.5 block size-4 rounded-full bg-emerald-100"></span>
                </span>
                <span class="h-1.5 w-14 rounded bg-emerald-200"></span>
                <span class="mt-2 h-3 w-24 rounded bg-zinc-900"></span>
                <span class="mt-2 h-1.5 w-20 rounded bg-zinc-300"></span>
                <span class="mt-3 flex gap-1">
                    <span class="h-2.5 w-11 rounded-full bg-emerald-200"></span>
                    <span class="h-2.5 w-9 rounded-full bg-white ring-1 ring-zinc-200"></span>
                </span>
            </span>
        </span>
        <span class="absolute left-2 right-2 top-2 flex items-center justify-between rounded-full bg-white/95 px-2 py-1 shadow-sm ring-1 ring-pink-100">
            <span class="flex items-center gap-1.5">
                <span class="size-4 rounded-full bg-emerald-100"></span>
                <span class="h-2 w-12 rounded bg-zinc-900"></span>
            </span>
            <span class="flex gap-1">
                <span class="h-2 w-7 rounded bg-pink-200"></span>
                <span class="h-2 w-6 rounded bg-yellow-100"></span>
                <span class="h-2 w-6 rounded bg-sky-100"></span>
            </span>
        </span>
    </span>
@elseif ($preview === 'header_craft')
    <span class="relative block h-full overflow-hidden rounded-md bg-[#fffaf1] shadow-sm ring-1 ring-emerald-100">
        <span class="absolute inset-y-0 right-0 w-[64%] bg-emerald-100">
            <span class="absolute right-7 top-4 h-8 w-11 rounded-md bg-yellow-100"></span>
            <span class="absolute bottom-4 right-3 h-8 w-14 rounded-md bg-sky-100"></span>
            <span class="absolute bottom-7 left-7 size-8 rounded-full bg-green-100"></span>
            <span class="absolute inset-y-0 left-0 w-[42%] bg-gradient-to-r from-[#fffaf1] via-[#fffaf1]/80 to-transparent"></span>
        </span>
        <span class="absolute left-2 right-2 top-2 flex items-center justify-between rounded-full bg-white/95 px-2 py-1 shadow-sm ring-1 ring-pink-100">
            <span class="flex items-center gap-1.5">
                <span class="size-4 rounded-full bg-emerald-100"></span>
                <span class="h-2 w-14 rounded bg-zinc-900"></span>
            </span>
            <span class="flex gap-1">
                <span class="h-2 w-7 rounded bg-emerald-200"></span>
                <span class="h-2 w-6 rounded bg-yellow-100"></span>
                <span class="h-2 w-6 rounded bg-sky-100"></span>
            </span>
        </span>
        <span class="absolute left-3 top-10 grid w-[42%] gap-2">
            <span class="size-7 rounded-md bg-white shadow-sm ring-1 ring-zinc-200">
                <span class="m-1.5 block size-4 rounded-full bg-emerald-100"></span>
            </span>
            <span class="h-1.5 w-20 rounded bg-emerald-200"></span>
            <span class="h-4 w-24 rounded bg-zinc-900"></span>
            <span class="h-4 w-20 rounded bg-zinc-900"></span>
            <span class="h-1.5 w-24 rounded bg-zinc-300"></span>
            <span class="h-1.5 w-20 rounded bg-zinc-300"></span>
            <span class="mt-1 flex gap-1">
                <span class="h-2.5 w-11 rounded-full bg-emerald-200"></span>
                <span class="h-2.5 w-9 rounded-full bg-white ring-1 ring-emerald-200"></span>
            </span>
        </span>
    </span>
@elseif ($preview === 'header_primary_split')
    <span class="relative grid h-full grid-cols-2 overflow-hidden rounded-md bg-white shadow-sm ring-1 ring-emerald-100">
        <span class="flex h-full flex-col justify-center bg-emerald-200 p-3 pt-8">
            <span class="mb-2 size-7 rounded-md bg-white shadow-sm ring-1 ring-white/70">
                <span class="m-1.5 block size-4 rounded-full bg-emerald-100"></span>
            </span>
            <span class="h-1.5 w-20 rounded bg-white/80"></span>
            <span class="mt-2 h-4 w-24 rounded bg-white"></span>
            <span class="mt-2 h-1.5 w-24 rounded bg-white/70"></span>
            <span class="mt-3 flex gap-1">
                <span class="h-2.5 w-11 rounded-full bg-white"></span>
                <span class="h-2.5 w-9 rounded-full border border-white/80 bg-emerald-200"></span>
            </span>
        </span>
        <span class="relative bg-sky-100">
            <span class="absolute right-7 top-5 size-8 rounded-full bg-yellow-100"></span>
            <span class="absolute bottom-5 right-4 h-7 w-14 rounded-md bg-emerald-100"></span>
        </span>
        <span class="absolute left-2 right-2 top-2 flex items-center justify-between rounded-full bg-white/95 px-2 py-1 shadow-sm ring-1 ring-pink-100">
            <span class="flex items-center gap-1.5">
                <span class="size-4 rounded-full bg-emerald-100"></span>
                <span class="h-2 w-14 rounded bg-zinc-900"></span>
            </span>
            <span class="flex gap-1">
                <span class="h-2 w-7 rounded bg-emerald-200"></span>
                <span class="h-2 w-6 rounded bg-yellow-100"></span>
                <span class="h-2 w-6 rounded bg-sky-100"></span>
            </span>
        </span>
    </span>
@elseif ($preview === 'header_showcase')
    <span class="relative flex h-full flex-col overflow-hidden rounded-md bg-white shadow-sm ring-1 ring-emerald-100">
        <span class="flex h-7 items-center justify-between bg-white px-2 shadow-sm ring-1 ring-zinc-100">
            <span class="flex items-center gap-1.5">
                <span class="size-4 rounded-full bg-yellow-100 ring-1 ring-yellow-200"></span>
                <span class="grid gap-0.5">
                    <span class="h-1.5 w-14 rounded bg-zinc-900"></span>
                    <span class="h-1 w-10 rounded bg-zinc-300"></span>
                </span>
            </span>
            <span class="hidden gap-1 sm:flex">
                <span class="h-1.5 w-7 rounded bg-emerald-200"></span>
                <span class="h-1.5 w-7 rounded bg-sky-100"></span>
                <span class="h-1.5 w-7 rounded bg-yellow-100"></span>
            </span>
            <span class="flex gap-1">
                <span class="h-3 w-7 rounded border border-zinc-300 bg-white"></span>
                <span class="h-3 w-8 rounded bg-yellow-200"></span>
            </span>
        </span>
        <span class="relative flex-1 overflow-hidden bg-emerald-100">
            <span class="absolute inset-0 bg-gradient-to-r from-emerald-200 via-emerald-100 to-yellow-100"></span>
            <span class="absolute bottom-3 right-5 h-8 w-14 rounded-md bg-white/70"></span>
            <span class="absolute right-10 top-4 size-8 rounded-full bg-emerald-200"></span>
            <span class="absolute inset-y-0 left-0 w-[56%] bg-emerald-900/40"></span>
            <span class="absolute left-3 top-5 inline-flex items-center gap-1 rounded-full border border-white/30 px-2 py-1">
                <span class="size-2 rounded-full bg-yellow-200"></span>
                <span class="h-1.5 w-12 rounded bg-white/80"></span>
            </span>
            <span class="absolute left-3 top-14 grid w-[42%] gap-1.5">
                <span class="h-4 w-28 rounded bg-white"></span>
                <span class="h-4 w-24 rounded bg-white"></span>
                <span class="h-1.5 w-24 rounded bg-white/70"></span>
                <span class="h-1.5 w-20 rounded bg-white/60"></span>
                <span class="mt-2 flex gap-1.5">
                    <span class="h-3 w-12 rounded bg-yellow-200"></span>
                    <span class="h-3 w-11 rounded border border-white/70 bg-white/10"></span>
                </span>
            </span>
        </span>
    </span>
@elseif ($preview === 'header_gallery_frame')
    <span class="relative flex h-full flex-col overflow-hidden rounded-md bg-white p-2 shadow-sm ring-1 ring-emerald-100">
        <span class="flex h-5 items-center justify-between px-1">
            <span class="flex items-center gap-1.5">
                <span class="size-3.5 rounded-full bg-emerald-100 ring-1 ring-emerald-200"></span>
                <span class="h-1.5 w-12 rounded bg-zinc-900"></span>
            </span>
            <span class="flex gap-1">
                <span class="relative h-1.5 w-6 rounded bg-emerald-100">
                    <span class="absolute inset-x-0 -bottom-1 h-0.5 rounded-full bg-emerald-300"></span>
                </span>
                <span class="h-1.5 w-6 rounded bg-sky-100"></span>
                <span class="h-1.5 w-6 rounded bg-yellow-100"></span>
            </span>
        </span>
        <span class="relative mt-1.5 min-h-0 flex-1 overflow-hidden rounded-md bg-emerald-100">
            <span class="absolute inset-0 bg-gradient-to-r from-sky-100 via-emerald-100 to-yellow-100"></span>
            <span class="absolute bottom-3 left-6 h-8 w-14 rounded-md bg-white/55"></span>
            <span class="absolute right-7 top-4 size-9 rounded-full bg-emerald-200"></span>
            <span class="absolute bottom-4 right-12 h-7 w-12 rounded-md bg-yellow-100"></span>
        </span>
        <span class="grid grid-cols-[1.08fr_0.92fr] items-end gap-2 px-1 pb-0.5 pt-2">
            <span class="flex items-start gap-1.5">
                <span class="size-6 shrink-0 rounded-md bg-white shadow-sm ring-1 ring-zinc-200">
                    <span class="m-1.5 block size-3 rounded-full bg-emerald-100"></span>
                </span>
                <span class="grid flex-1 gap-1">
                    <span class="h-1.5 w-14 rounded bg-emerald-200"></span>
                    <span class="h-3 w-full rounded bg-zinc-900"></span>
                    <span class="h-3 w-4/5 rounded bg-zinc-900"></span>
                </span>
            </span>
            <span class="grid gap-1.5">
                <span class="h-1.5 w-full rounded bg-zinc-300"></span>
                <span class="h-1.5 w-4/5 rounded bg-zinc-300"></span>
                <span class="mt-0.5 flex gap-1">
                    <span class="h-2.5 w-11 rounded bg-emerald-200"></span>
                    <span class="h-2.5 w-9 rounded border border-emerald-200 bg-white"></span>
                </span>
            </span>
        </span>
    </span>
@elseif ($preview === 'footer_classic')
    <span class="flex h-full flex-col justify-between rounded-md bg-white p-3 ring-1 ring-emerald-100">
        <span class="grid grid-cols-[1.15fr_0.75fr_0.85fr] gap-3">
            <span class="grid content-start gap-2">
                <span class="flex items-center gap-2">
                    <span class="grid size-7 shrink-0 place-items-center rounded-md bg-emerald-100 shadow-sm">
                        <span class="size-4 rounded-full bg-yellow-100"></span>
                    </span>
                    <span class="grid flex-1 gap-1">
                        <span class="h-1.5 w-14 rounded bg-emerald-200"></span>
                        <span class="h-2 w-20 rounded bg-zinc-900"></span>
                    </span>
                </span>
                <span class="h-1.5 rounded bg-zinc-300"></span>
                <span class="h-1.5 w-5/6 rounded bg-zinc-300"></span>
                <span class="h-1.5 w-2/3 rounded bg-zinc-300"></span>
            </span>

            <span class="grid content-start gap-1.5">
                <span class="mb-1 h-1.5 w-12 rounded bg-zinc-900"></span>
                @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100', 'bg-green-100'] as $color)
                    <span class="h-1.5 rounded {{ $color }}"></span>
                @endforeach
            </span>

            <span class="grid content-start gap-1.5">
                <span class="mb-1 h-1.5 w-12 rounded bg-zinc-900"></span>
                <span class="h-1.5 w-16 rounded bg-sky-100"></span>
                <span class="h-1.5 w-14 rounded bg-emerald-100"></span>
                <span class="h-1.5 w-20 rounded bg-yellow-100"></span>
            </span>
        </span>

        <span class="flex items-center justify-between border-t border-emerald-100 pt-2">
            <span class="h-1.5 w-28 rounded bg-zinc-500"></span>
            <span class="h-1.5 w-16 rounded bg-sky-200"></span>
        </span>
    </span>
@elseif (in_array($preview, ['section_header_left', 'section_header_center', 'section_header_center_rule', 'section_header_split', 'section_header_side_label', 'section_header_marker', 'section_header_left_accent', 'section_header_center_accent', 'section_header_split_accent', 'section_header_left_colored', 'section_header_center_colored', 'section_header_split_colored'], true))
    @php
        $isCenteredSectionHeader = in_array($preview, ['section_header_center', 'section_header_center_rule', 'section_header_marker', 'section_header_center_accent', 'section_header_center_colored'], true);
        $isCenterRuleSectionHeader = $preview === 'section_header_center_rule';
        $isSplitSectionHeader = in_array($preview, ['section_header_split', 'section_header_split_accent', 'section_header_split_colored'], true);
        $isSideLabelSectionHeader = $preview === 'section_header_side_label';
        $isAccentSectionHeader = in_array($preview, ['section_header_left_accent', 'section_header_center_accent', 'section_header_split_accent'], true);
        $isColoredSectionHeader = in_array($preview, ['section_header_left_colored', 'section_header_center_colored', 'section_header_split_colored'], true);
        $isMarkerSectionHeader = $preview === 'section_header_marker';
    @endphp

    <span class="flex h-full flex-col justify-center rounded-md bg-white p-3 ring-1 ring-emerald-100">
        @if ($isSideLabelSectionHeader)
            <span class="grid grid-cols-[0.25rem_minmax(0,1fr)] items-stretch gap-3">
                <span class="rounded-full bg-emerald-300"></span>

                <span class="grid gap-1.5 py-1">
                    <span class="h-2 w-16 rounded-full bg-emerald-100 ring-1 ring-emerald-200"></span>
                    <span class="h-3.5 w-28 rounded bg-zinc-900"></span>
                    <span class="h-3.5 w-20 rounded bg-zinc-900"></span>
                    <span class="mt-1 h-1.5 w-32 max-w-full rounded bg-zinc-300"></span>
                    <span class="h-1.5 w-24 max-w-full rounded bg-zinc-300"></span>
                </span>
            </span>
        @elseif ($isSplitSectionHeader)
            <span class="grid w-full gap-2.5">
                <span @class([
                    'h-2 w-16 rounded-full',
                    'bg-emerald-100 px-2 ring-1 ring-emerald-200' => $isAccentSectionHeader,
                    'bg-emerald-200' => ! $isAccentSectionHeader,
                ])></span>

                <span class="grid grid-cols-[1.05fr_0.95fr] items-end gap-2.5">
                    <span class="grid gap-1.5">
                        <span @class([
                            'h-3.5 w-24 rounded',
                            'bg-emerald-700' => $isAccentSectionHeader,
                            'bg-zinc-900' => ! $isAccentSectionHeader,
                        ])></span>
                        <span @class([
                            'h-3.5 w-16 rounded',
                            'bg-emerald-700' => $isAccentSectionHeader || $isColoredSectionHeader,
                            'bg-zinc-900' => ! $isAccentSectionHeader && ! $isColoredSectionHeader,
                        ])></span>
                    </span>

                    <span class="grid gap-1.5">
                        <span @class([
                            'h-1.5 w-full rounded',
                            'bg-emerald-200' => $isAccentSectionHeader,
                            'bg-zinc-300' => ! $isAccentSectionHeader,
                        ])></span>
                        <span @class([
                            'h-1.5 w-4/5 rounded',
                            'bg-emerald-200' => $isAccentSectionHeader,
                            'bg-zinc-300' => ! $isAccentSectionHeader,
                        ])></span>
                    </span>
                </span>

                @if ($isAccentSectionHeader)
                    <span class="mt-1 flex gap-1">
                        <span class="size-2 rounded-full bg-yellow-100 ring-1 ring-yellow-200"></span>
                        <span class="size-2 rounded-full bg-sky-100 ring-1 ring-sky-200"></span>
                        <span class="size-2 rounded-full bg-green-100 ring-1 ring-green-200"></span>
                    </span>
                @endif
            </span>
        @else
            <span @class([
                'flex w-full max-w-[9.5rem] flex-col gap-2',
                'mx-auto items-center text-center' => $isCenteredSectionHeader,
                'items-start' => ! $isCenteredSectionHeader,
            ])>
                <span @class([
                    'h-2 w-16 rounded-full',
                    'bg-emerald-100 px-2 ring-1 ring-emerald-200' => $isAccentSectionHeader,
                    'bg-emerald-200' => ! $isAccentSectionHeader,
                ])></span>

                <span @class([
                    'grid gap-1.5',
                    'justify-items-center' => $isCenteredSectionHeader,
                ])>
                    <span @class([
                        'h-3.5 w-28 rounded',
                        'bg-emerald-700' => $isAccentSectionHeader,
                        'bg-zinc-900' => ! $isAccentSectionHeader,
                    ])></span>
                    <span @class([
                        'h-3.5 w-20 rounded',
                        'bg-emerald-700' => $isAccentSectionHeader || $isColoredSectionHeader,
                        'bg-emerald-100 ring-1 ring-emerald-200' => $isMarkerSectionHeader,
                        'bg-zinc-900' => ! $isAccentSectionHeader && ! $isColoredSectionHeader && ! $isMarkerSectionHeader,
                    ])></span>
                </span>

                @if ($isMarkerSectionHeader)
                    <span class="-mt-1 h-1.5 w-20 rounded bg-emerald-200/80"></span>
                @endif

                @if ($isCenterRuleSectionHeader)
                    <span class="h-0.5 w-16 rounded-full bg-emerald-300"></span>
                @endif

                <span @class([
                    'grid w-full gap-1.5',
                    'justify-items-center' => $isCenteredSectionHeader,
                ])>
                    <span @class([
                        'h-1.5 w-32 max-w-full rounded',
                        'bg-emerald-200' => $isAccentSectionHeader,
                        'bg-zinc-300' => ! $isAccentSectionHeader,
                    ])></span>
                    <span @class([
                        'h-1.5 w-24 max-w-full rounded',
                        'bg-emerald-200' => $isAccentSectionHeader,
                        'bg-zinc-300' => ! $isAccentSectionHeader,
                    ])></span>
                </span>

                @if ($isAccentSectionHeader)
                    <span @class([
                        'mt-1 flex gap-1',
                        'justify-center' => $isCenteredSectionHeader,
                    ])>
                        <span class="size-2 rounded-full bg-yellow-100 ring-1 ring-yellow-200"></span>
                        <span class="size-2 rounded-full bg-sky-100 ring-1 ring-sky-200"></span>
                        <span class="size-2 rounded-full bg-green-100 ring-1 ring-green-200"></span>
                    </span>
                @endif
            </span>
        @endif
    </span>
@elseif ($preview === 'faq_cards')
    <span class="grid h-full grid-cols-2 gap-2">
        @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100', 'bg-green-100'] as $color)
            <span class="rounded-md bg-white p-2 shadow-sm">
                <span class="mb-1.5 block size-4 rounded-full {{ $color }}"></span>
                <span class="block h-1.5 rounded bg-zinc-900"></span>
                <span class="mt-2 block h-1.5 rounded bg-zinc-300"></span>
                <span class="mt-1.5 block h-1.5 w-4/5 rounded bg-zinc-300"></span>
            </span>
        @endforeach
    </span>
@elseif ($preview === 'cards')
    <span class="grid h-full grid-cols-3 gap-2">
        @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100'] as $color)
            <span class="rounded-md bg-white p-2 shadow-sm">
                <span class="mb-2 flex items-center gap-1.5">
                    <span class="size-4 rounded-full {{ $color }}"></span>
                    <span class="h-1.5 flex-1 rounded bg-zinc-900"></span>
                </span>
                <span class="block h-1.5 rounded bg-zinc-300"></span>
                <span class="mt-1.5 block h-1.5 rounded bg-zinc-300"></span>
                <span class="mt-1.5 block h-1.5 w-3/4 rounded bg-zinc-300"></span>
            </span>
        @endforeach
    </span>
@elseif ($preview === 'accordion')
    <span class="grid h-full gap-2">
        @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100', 'bg-green-100'] as $color)
            <span class="grid grid-cols-[1rem_1fr_0.75rem] items-center gap-2 rounded-md bg-white p-2 shadow-sm">
                <span class="size-4 rounded-full {{ $color }}"></span>
                <span class="block h-1.5 rounded bg-zinc-900"></span>
                <span class="h-1.5 rounded bg-green-300"></span>
            </span>
        @endforeach
    </span>
@elseif ($preview === 'notebook')
    <span class="grid h-full grid-cols-3 gap-2">
        @foreach ([['bg-emerald-100', '-rotate-2'], ['bg-yellow-100', 'rotate-1'], ['bg-sky-100', 'rotate-2']] as $note)
            <span class="{{ $note[1] }} rounded-md bg-white p-2 shadow-sm">
                <span class="mb-1.5 block size-4 rounded-full {{ $note[0] }}"></span>
                <span class="block h-1.5 rounded bg-zinc-900"></span>
                <span class="mt-2 block h-1.5 rounded bg-zinc-300"></span>
                <span class="mt-1.5 block h-1.5 rounded bg-zinc-300"></span>
            </span>
        @endforeach
    </span>
@elseif ($preview === 'compact_accordion')
    <span class="grid h-full gap-2">
        @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100', 'bg-green-100'] as $color)
            <span class="grid grid-cols-[1rem_1fr_0.75rem] items-center gap-2 rounded-md bg-white p-2 shadow-sm">
                <span class="size-4 rounded-full {{ $color }}"></span>
                <span class="h-1.5 rounded bg-zinc-900"></span>
                <span class="h-1.5 rounded bg-green-300"></span>
            </span>
        @endforeach
    </span>
@elseif ($preview === 'answer_grid')
    <span class="grid h-full grid-cols-2 gap-2">
        @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100', 'bg-green-100'] as $color)
            <span class="rounded-md bg-white p-2 shadow-sm">
                <span class="mb-2 inline-block size-4 rounded-full {{ $color }}"></span>
                <span class="block h-1.5 rounded bg-zinc-900"></span>
                <span class="mt-1.5 block h-1.5 rounded bg-zinc-300"></span>
            </span>
        @endforeach
    </span>
@elseif ($preview === 'expanded_accordion')
    <span class="grid h-full gap-2">
        <span class="grid gap-1 rounded-md bg-white p-2 shadow-sm">
            <span class="grid grid-cols-[1rem_1fr_0.75rem] items-center gap-2">
                <span class="size-4 rounded-full bg-emerald-100"></span>
                <span class="h-1.5 rounded bg-zinc-900"></span>
                <span class="h-1.5 rounded bg-green-300"></span>
            </span>
            <span class="ml-6 h-1.5 rounded bg-zinc-300"></span>
        </span>
        @foreach (['bg-yellow-100', 'bg-sky-100', 'bg-green-100'] as $color)
            <span class="grid grid-cols-[1rem_1fr_0.75rem] items-center gap-2 rounded-md bg-white p-2 shadow-sm">
                <span class="size-4 rounded-full {{ $color }}"></span>
                <span class="h-1.5 rounded bg-zinc-900"></span>
                <span class="h-1.5 rounded bg-green-300"></span>
            </span>
        @endforeach
    </span>
@elseif ($preview === 'timeline_accordion')
    <span class="relative block h-full pl-5">
        <span class="absolute left-2 top-1 h-[calc(100%-0.5rem)] w-px bg-pink-300"></span>
        <span class="grid h-full gap-2">
            @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100', 'bg-green-100'] as $color)
                <span class="relative grid grid-cols-[1rem_1fr_0.75rem] items-center gap-2 rounded-md bg-white p-2 shadow-sm">
                    <span class="absolute -left-[1.05rem] top-2 size-3 rounded-full border border-pink-400 {{ $color }}"></span>
                    <span class="size-4 rounded-full {{ $color }}"></span>
                    <span class="block h-1.5 rounded bg-zinc-900"></span>
                    <span class="h-1.5 rounded bg-green-300"></span>
                </span>
            @endforeach
        </span>
    </span>
@elseif ($isFeaturedValuesPreview)
    <span class="flex h-full items-center rounded-md bg-white p-2">
        <span @class([
            'grid w-full gap-2',
            'grid-cols-2' => $featuredValuesPreviewColumns === 2,
            'grid-cols-3' => $featuredValuesPreviewColumns === 3,
            'grid-cols-4' => $featuredValuesPreviewColumns === 4,
        ])>
            @foreach (array_slice(['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100', 'bg-green-100'], 0, $featuredValuesPreviewColumns) as $color)
                <span @class([
                    'grid content-start gap-2 rounded-md bg-white p-2 shadow-sm ring-1 ring-zinc-100',
                ])>
                    <span class="size-7 rounded-md {{ $color }}"></span>
                    <span class="grid content-start gap-1.5">
                        <span class="h-1.5 rounded bg-zinc-900"></span>
                        <span class="h-1.5 rounded bg-zinc-300"></span>
                        <span class="h-1.5 w-4/5 rounded bg-zinc-300"></span>
                    </span>
                </span>
            @endforeach
        </span>
    </span>
@elseif ($preview === 'features_mosaic')
    <span class="grid h-full grid-cols-3 gap-2">
        @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100'] as $color)
            <span class="overflow-hidden rounded-md bg-white shadow-sm">
                <span class="block h-10 {{ $color }}"></span>
                <span class="grid gap-1.5 p-2">
                    <span class="h-1.5 rounded bg-zinc-900"></span>
                    <span class="h-1.5 rounded bg-zinc-300"></span>
                    <span class="h-1.5 w-4/5 rounded bg-zinc-300"></span>
                </span>
            </span>
        @endforeach
    </span>
@elseif ($preview === 'features_photo_cards')
    <span class="grid h-full grid-cols-4 gap-2">
        @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100', 'bg-green-100'] as $color)
            <span class="overflow-hidden rounded-md bg-white shadow-sm">
                <span class="relative block h-9 {{ $color }}">
                    <span class="absolute -bottom-2 left-2 size-4 rounded bg-orange-200 ring-2 ring-white"></span>
                </span>
                <span class="block p-2 pt-3">
                    <span class="block h-1.5 rounded bg-zinc-900"></span>
                    <span class="mt-1.5 block h-1.5 rounded bg-zinc-300"></span>
                    <span class="mt-1 block h-1.5 w-2/3 rounded bg-zinc-300"></span>
                </span>
            </span>
        @endforeach
    </span>
@elseif ($preview === 'features_editorial')
    <span class="grid h-full gap-2">
        <span class="h-9 rounded-md bg-emerald-100"></span>
        <span class="grid grid-cols-[auto_minmax(0,1fr)] items-start gap-2 px-2">
            <span class="size-5 rounded-full bg-yellow-100"></span>
            <span>
                <span class="block h-2 rounded bg-zinc-900"></span>
                <span class="mt-2 block h-1.5 rounded bg-zinc-300"></span>
                <span class="mt-1.5 block h-1.5 w-4/5 rounded bg-zinc-300"></span>
            </span>
        </span>
    </span>
@elseif ($preview === 'features_spotlight')
    <span class="grid h-full gap-2">
        <span class="grid grid-cols-[1.15fr_0.85fr] gap-2">
            <span class="rounded-md bg-emerald-100"></span>
            <span class="self-center">
                <span class="block size-5 rounded-full bg-yellow-100"></span>
                <span class="mt-2 block h-2 rounded bg-zinc-900"></span>
                <span class="mt-2 block h-1.5 rounded bg-zinc-300"></span>
            </span>
        </span>
        <span class="grid grid-cols-4 gap-1.5 pt-2">
            @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100', 'bg-green-100'] as $color)
                <span>
                    <span class="block h-6 rounded {{ $color }}"></span>
                    <span class="mt-1 block h-1.5 rounded bg-zinc-900"></span>
                </span>
            @endforeach
        </span>
    </span>
@elseif ($preview === 'features_alternating')
    <span class="grid h-full gap-2">
        <span class="grid grid-cols-[1fr_0.9fr] items-center gap-2">
            <span class="h-8 rounded-md bg-emerald-100"></span>
            <span>
                <span class="mb-1.5 block size-4 rounded-full bg-yellow-100"></span>
                <span class="block h-2 rounded bg-zinc-900"></span>
                <span class="mt-1.5 block h-1.5 rounded bg-zinc-300"></span>
            </span>
        </span>
        <span class="grid grid-cols-[0.9fr_1fr] items-center gap-2">
            <span>
                <span class="mb-1.5 block size-4 rounded-full bg-sky-100"></span>
                <span class="block h-2 rounded bg-zinc-900"></span>
                <span class="mt-1.5 block h-1.5 rounded bg-zinc-300"></span>
            </span>
            <span class="h-8 rounded-md bg-yellow-100"></span>
        </span>
    </span>
@elseif ($preview === 'features_path')
    <span class="grid h-full gap-2">
        <span class="grid grid-cols-2 items-center gap-3">
            <span class="rounded-md bg-white p-2 shadow-sm">
                <span class="block h-1.5 rounded bg-zinc-900"></span>
                <span class="mt-2 block h-1.5 rounded bg-zinc-300"></span>
            </span>
            <span class="h-10 rounded-md bg-emerald-100"></span>
        </span>
        <span class="grid grid-cols-2 items-center gap-3">
            <span class="h-10 rounded-md bg-yellow-100"></span>
            <span class="rounded-md bg-white p-2 shadow-sm">
                <span class="block h-1.5 rounded bg-zinc-900"></span>
                <span class="mt-2 block h-1.5 rounded bg-zinc-300"></span>
            </span>
        </span>
    </span>
@elseif ($preview === 'features_studio')
    <span class="grid h-full grid-cols-3 gap-2">
        @foreach ([['bg-emerald-100', 'rotate-[-2deg]'], ['bg-yellow-100', 'rotate-[1deg]'], ['bg-sky-100', 'rotate-[-1deg]']] as $tile)
            <span class="{{ $tile[1] }} rounded-md bg-white p-1.5 shadow-sm">
                <span class="block h-12 rounded {{ $tile[0] }}"></span>
                <span class="mt-2 block h-1.5 rounded bg-zinc-900"></span>
                <span class="mt-1 block h-1.5 w-2/3 rounded bg-zinc-300"></span>
            </span>
        @endforeach
    </span>
@elseif ($preview === 'partners_cards')
    <span class="grid h-full grid-cols-3 gap-2">
        @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100'] as $color)
            <span class="rounded-md bg-white p-2 shadow-sm">
                <span class="mb-2 flex items-center gap-1.5">
                    <span class="grid h-6 w-8 shrink-0 place-items-center rounded-md bg-white ring-1 ring-zinc-200">
                        <span class="size-3 rounded-full {{ $color }}"></span>
                    </span>
                    <span class="h-1.5 flex-1 rounded bg-zinc-900"></span>
                </span>
                <span class="block h-2 rounded bg-zinc-900"></span>
                <span class="mt-1.5 block h-1.5 rounded bg-zinc-300"></span>
                <span class="mt-1 block h-1.5 w-4/5 rounded bg-zinc-300"></span>
            </span>
        @endforeach
    </span>
@elseif ($preview === 'partners_logos')
    <span class="grid h-full grid-cols-4 gap-2">
        @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100', 'bg-green-100', 'bg-emerald-100', 'bg-yellow-100', 'bg-sky-100', 'bg-green-100'] as $color)
            <span class="rounded-md bg-white p-2 shadow-sm">
                <span class="block h-full rounded-md {{ $color }}"></span>
            </span>
        @endforeach
    </span>
@elseif ($preview === 'partners_list')
    <span class="grid h-full gap-2">
        @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100'] as $color)
            <span class="grid grid-cols-[2.5rem_1fr] gap-2 rounded-md bg-white p-2 shadow-sm">
                <span class="rounded-md {{ $color }}"></span>
                <span class="grid content-center gap-1">
                    <span class="h-1.5 rounded bg-zinc-900"></span>
                    <span class="h-1.5 rounded bg-zinc-300"></span>
                </span>
            </span>
        @endforeach
    </span>
@elseif ($preview === 'partners_featured_list')
    <span class="grid h-full grid-cols-2 gap-2">
        <span class="col-span-2 grid grid-cols-[3rem_1fr_auto] gap-2 rounded-md bg-white p-2 shadow-sm">
            <span class="rounded-md bg-emerald-100"></span>
            <span class="grid content-center gap-1">
                <span class="h-1.5 rounded bg-zinc-900"></span>
                <span class="h-1.5 rounded bg-zinc-300"></span>
            </span>
            <span class="my-auto h-1.5 w-5 rounded bg-green-100"></span>
        </span>
        @foreach (['bg-yellow-100', 'bg-sky-100'] as $color)
            <span class="grid grid-cols-[1.75rem_1fr] gap-2 rounded-md bg-white p-2 shadow-sm">
                <span class="rounded-md {{ $color }}"></span>
                <span class="grid content-center gap-1">
                    <span class="h-1.5 rounded bg-zinc-900"></span>
                    <span class="h-1.5 rounded bg-zinc-300"></span>
                </span>
            </span>
        @endforeach
    </span>
@elseif ($preview === 'stats_cards')
    <span class="grid h-full grid-cols-4 gap-2">
        @foreach ([['bg-emerald-100', 'bg-emerald-200'], ['bg-yellow-100', 'bg-yellow-200'], ['bg-sky-100', 'bg-sky-200'], ['bg-green-100', 'bg-green-200']] as $color)
            <span class="rounded-md bg-white p-2 shadow-sm">
                <span class="mb-2 block size-4 rounded-full {{ $color[0] }}"></span>
                <span class="block h-2 rounded {{ $color[1] }}"></span>
                <span class="mt-2 block h-1.5 rounded bg-zinc-900"></span>
                <span class="mt-1.5 block h-1.5 w-4/5 rounded bg-zinc-300"></span>
            </span>
        @endforeach
    </span>
@elseif ($preview === 'stats_story')
    <span class="block h-full rounded-md bg-stone-100/80 p-2">
        <span class="grid h-full grid-cols-4 gap-2">
            @foreach ([['bg-emerald-100', 'bg-emerald-200'], ['bg-yellow-100', 'bg-yellow-200'], ['bg-sky-100', 'bg-sky-200'], ['bg-green-100', 'bg-green-200']] as $color)
                <span class="flex flex-col rounded-md bg-white p-2 shadow-sm">
                    <span class="mb-2 flex items-center justify-between gap-1">
                        <span class="h-2.5 w-6 rounded {{ $color[1] }}"></span>
                        <span class="size-3.5 shrink-0 rounded-full {{ $color[0] }}"></span>
                    </span>
                    <span class="mt-2 block h-1.5 rounded bg-zinc-900"></span>
                    <span class="mt-1 block h-1.5 w-4/5 rounded bg-zinc-300"></span>
                </span>
            @endforeach
        </span>
    </span>
@elseif ($preview === 'stats_ribbon')
    <span class="grid h-full grid-cols-4 content-center gap-2">
        @foreach ([['bg-emerald-100', 'bg-emerald-200'], ['bg-yellow-100', 'bg-yellow-200'], ['bg-sky-100', 'bg-sky-200'], ['bg-green-100', 'bg-green-200']] as $color)
            <span class="rounded-md bg-white p-2 shadow-sm">
                <span class="mb-2 block size-4 rounded-full {{ $color[0] }}"></span>
                <span class="block h-2 rounded {{ $color[1] }}"></span>
                <span class="mt-1.5 block h-1.5 rounded bg-zinc-900"></span>
            </span>
        @endforeach
    </span>
@elseif ($preview === 'stats_split_grid')
    <span class="grid h-full grid-cols-2 gap-2">
        @foreach ([['bg-emerald-100', 'bg-emerald-200'], ['bg-yellow-100', 'bg-yellow-200'], ['bg-sky-100', 'bg-sky-200'], ['bg-green-100', 'bg-green-200']] as $color)
            <span class="grid grid-cols-[1.6rem_1fr] items-center gap-2 rounded-md bg-white p-2 shadow-sm">
                <span class="size-6 shrink-0 rounded-full {{ $color[0] }}"></span>
                <span class="min-w-0 flex-1">
                    <span class="block h-2.5 w-8 rounded {{ $color[1] }}"></span>
                    <span class="mt-1.5 block h-1.5 rounded bg-zinc-900"></span>
                    <span class="mt-1 block h-1.5 w-4/5 rounded bg-zinc-300"></span>
                </span>
            </span>
        @endforeach
    </span>
@elseif ($preview === 'stats_compact_grid')
    <span class="grid h-full grid-cols-4 gap-1.5">
        @foreach ([['bg-emerald-100', 'bg-emerald-200'], ['bg-yellow-100', 'bg-yellow-200'], ['bg-sky-100', 'bg-sky-200'], ['bg-green-100', 'bg-green-200']] as $color)
            <span class="flex flex-col justify-center rounded-md bg-white p-2">
                <span class="mx-auto block h-2.5 w-8 rounded {{ $color[1] }}"></span>
                <span class="mx-auto mt-2 block size-3 rounded-full {{ $color[0] }}"></span>
                <span class="mt-1.5 block h-1.5 rounded bg-zinc-300"></span>
            </span>
        @endforeach
    </span>
@elseif ($preview === 'story_media_right')
    <span class="grid h-full grid-cols-[0.95fr_1fr] items-center gap-3">
        <span class="grid gap-2">
            <span class="block h-2 rounded bg-zinc-900"></span>
            <span class="block h-1.5 rounded bg-zinc-300"></span>
            <span class="block h-1.5 w-4/5 rounded bg-zinc-300"></span>
        </span>
        <span class="h-16 rounded-md bg-emerald-100"></span>
    </span>
@elseif ($preview === 'story_media_left')
    <span class="grid h-full grid-cols-[1fr_0.95fr] items-center gap-3">
        <span class="h-16 rounded-md bg-sky-100"></span>
        <span class="grid gap-2">
            <span class="block h-2 rounded bg-zinc-900"></span>
            <span class="block h-1.5 rounded bg-zinc-300"></span>
            <span class="block h-1.5 w-4/5 rounded bg-zinc-300"></span>
        </span>
    </span>
@elseif ($preview === 'story_path')
    <span class="grid h-full gap-2">
        <span class="grid grid-cols-2 items-center gap-3">
            <span class="grid gap-1.5">
                <span class="h-2 rounded bg-zinc-900"></span>
                <span class="h-1.5 rounded bg-zinc-300"></span>
            </span>
            <span class="h-8 rounded-md bg-emerald-100"></span>
        </span>
        <span class="grid grid-cols-2 items-center gap-3">
            <span class="h-8 rounded-md bg-yellow-100"></span>
            <span class="grid gap-1.5">
                <span class="h-2 rounded bg-zinc-900"></span>
                <span class="h-1.5 rounded bg-zinc-300"></span>
            </span>
        </span>
    </span>
@elseif ($preview === 'story_cards')
    <span class="grid h-full grid-cols-3 gap-2">
        @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100'] as $color)
            <span class="overflow-hidden rounded-md bg-white shadow-sm">
                <span class="block h-10 {{ $color }}"></span>
                <span class="grid gap-1.5 p-2">
                    <span class="h-1.5 rounded bg-zinc-900"></span>
                    <span class="h-1.5 rounded bg-zinc-300"></span>
                </span>
            </span>
        @endforeach
    </span>
@elseif ($preview === 'story_showcase')
    <span class="grid h-full grid-cols-[1.15fr_0.85fr] gap-2">
        <span class="overflow-hidden rounded-md bg-white shadow-sm">
            <span class="block h-12 bg-emerald-100"></span>
            <span class="grid gap-1.5 p-2">
                <span class="h-2 rounded bg-zinc-900"></span>
                <span class="h-1.5 rounded bg-zinc-300"></span>
            </span>
        </span>
        <span class="grid gap-2">
            <span class="rounded-md bg-white shadow-sm"></span>
            <span class="rounded-md bg-yellow-100"></span>
        </span>
    </span>
@elseif ($preview === 'story_journal')
    <span class="grid h-full gap-2">
        @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100'] as $color)
            <span class="grid grid-cols-[3rem_1fr] gap-2 rounded-md bg-white p-1.5 shadow-sm">
                <span class="rounded {{ $color }}"></span>
                <span class="grid content-center gap-1">
                    <span class="h-1.5 rounded bg-zinc-900"></span>
                    <span class="h-1.5 rounded bg-zinc-300"></span>
                </span>
            </span>
        @endforeach
    </span>
@elseif ($preview === 'order_cards')
    <span class="grid h-full grid-cols-3 gap-2">
        @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100'] as $color)
            <span class="rounded-md bg-white p-2 shadow-sm">
                <span class="flex items-center justify-between gap-2">
                    <span class="size-5 rounded-full {{ $color }}"></span>
                    <span class="h-1.5 w-5 rounded bg-zinc-300"></span>
                </span>
                <span class="mt-3 block h-1.5 rounded bg-zinc-900"></span>
                <span class="mt-1.5 block h-1.5 rounded bg-zinc-300"></span>
            </span>
        @endforeach
    </span>
@elseif ($preview === 'order_showcase')
    <span class="grid h-full grid-cols-[1.15fr_0.85fr] gap-2">
        <span class="rounded-md bg-emerald-100 p-2 shadow-sm">
            <span class="block h-1.5 w-8 rounded bg-zinc-900"></span>
            <span class="mt-3 block h-1.5 rounded bg-zinc-300"></span>
        </span>
        <span class="grid gap-2">
            <span class="rounded-md bg-white p-2 shadow-sm">
                <span class="h-1.5 block rounded bg-zinc-900"></span>
            </span>
            <span class="rounded-md bg-white p-2 shadow-sm">
                <span class="h-1.5 block rounded bg-zinc-300"></span>
            </span>
        </span>
    </span>
@elseif ($preview === 'order_journal')
    <span class="grid h-full gap-2">
        @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100'] as $color)
            <span class="grid grid-cols-[2.5rem_1fr] gap-2 rounded-md bg-white p-2 shadow-sm">
                <span class="rounded-full {{ $color }}"></span>
                <span class="grid content-center gap-1">
                    <span class="h-1.5 rounded bg-zinc-900"></span>
                    <span class="h-1.5 rounded bg-zinc-300"></span>
                </span>
            </span>
        @endforeach
    </span>
@elseif ($preview === 'products_cards')
    <span class="grid h-full grid-cols-3 gap-2">
        @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100'] as $color)
            <span class="overflow-hidden rounded-md bg-white shadow-sm">
                <span class="block h-10 {{ $color }}"></span>
                <span class="grid gap-1.5 p-2">
                    <span class="h-1.5 rounded bg-zinc-900"></span>
                    <span class="h-1.5 rounded bg-zinc-300"></span>
                    <span class="h-1.5 w-1/2 rounded bg-green-300"></span>
                </span>
            </span>
        @endforeach
    </span>
@elseif ($preview === 'products_highlighted')
    <span class="grid h-full grid-cols-[1.05fr_1fr] gap-2">
        <span class="overflow-hidden rounded-md bg-white shadow-sm">
            <span class="block h-14 rounded-t-md bg-emerald-100"></span>
            <span class="grid gap-1.5 p-2">
                <span class="h-2 rounded bg-zinc-900"></span>
                <span class="h-1.5 w-2/3 rounded bg-zinc-300"></span>
                <span class="h-1.5 w-1/2 rounded bg-green-300"></span>
            </span>
        </span>
        <span class="grid gap-2">
            @foreach (['bg-yellow-100', 'bg-sky-100'] as $color)
                <span class="grid grid-cols-[2.5rem_1fr] gap-2 rounded-md bg-white p-1.5 shadow-sm">
                    <span class="rounded-md {{ $color }}"></span>
                    <span class="self-center">
                        <span class="block h-1.5 rounded bg-zinc-900"></span>
                        <span class="mt-1.5 block h-1.5 rounded bg-zinc-300"></span>
                        <span class="mt-1.5 block h-1.5 w-1/2 rounded bg-green-300"></span>
                    </span>
                </span>
            @endforeach
        </span>
    </span>
@elseif ($preview === 'products_showcase')
    <span class="grid h-full grid-cols-3 gap-2 rounded-md bg-white/70 p-1.5">
        @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100'] as $color)
            <span class="overflow-hidden rounded-md bg-white p-1 shadow-sm">
                <span class="block h-11 rounded {{ $color }}"></span>
                <span class="grid gap-1.5 px-1 pt-2">
                    <span class="h-1.5 rounded bg-zinc-900"></span>
                    <span class="h-1.5 rounded bg-zinc-300"></span>
                    <span class="h-1.5 w-1/2 rounded bg-green-300"></span>
                </span>
            </span>
        @endforeach
    </span>
@elseif ($preview === 'products_store_grid')
    <span class="grid h-full grid-cols-3 gap-2">
        @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100'] as $color)
            <span class="overflow-hidden rounded-md bg-white shadow-sm">
                <span class="block h-12 {{ $color }}"></span>
                <span class="grid gap-1.5 p-1.5">
                    <span class="flex items-start justify-between gap-1">
                        <span class="h-1.5 flex-1 rounded bg-zinc-900"></span>
                        <span class="h-1.5 w-4 rounded bg-green-300"></span>
                    </span>
                    <span class="h-1.5 rounded bg-zinc-300"></span>
                    <span class="h-1.5 w-2/3 rounded bg-zinc-300"></span>
                </span>
            </span>
        @endforeach
    </span>
@elseif ($preview === 'products_scroll_showcase')
    <span class="flex h-full gap-2 overflow-hidden">
        @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100', 'bg-green-100'] as $color)
            <span class="w-[32%] shrink-0 text-center">
                <span class="block h-14 rounded-md {{ $color }} shadow-sm"></span>
                <span class="mt-2 block h-1.5 rounded bg-zinc-300"></span>
                <span class="mx-auto mt-1.5 block h-1.5 w-4/5 rounded bg-zinc-900"></span>
                <span class="mx-auto mt-1.5 block h-1.5 w-1/2 rounded bg-green-300"></span>
            </span>
        @endforeach
    </span>
@elseif ($preview === 'product_single_classic')
    <span class="grid h-full gap-2">
        <span class="flex items-end justify-between gap-2">
            <span class="grid flex-1 gap-1">
                <span class="h-2.5 w-24 rounded bg-zinc-900"></span>
                <span class="h-1.5 w-16 rounded bg-zinc-300"></span>
            </span>
            <span class="h-3 w-10 rounded-full bg-green-300"></span>
        </span>
        <span class="rounded-md bg-emerald-100 shadow-sm ring-1 ring-white/70"></span>
        <span class="grid gap-1">
            <span class="h-1.5 rounded bg-zinc-300"></span>
            <span class="h-1.5 w-4/5 rounded bg-zinc-300"></span>
        </span>
    </span>
@elseif ($preview === 'product_single_split')
    <span class="grid h-full grid-cols-[1.05fr_0.95fr] gap-2">
        <span class="rounded-md bg-emerald-100 shadow-sm ring-1 ring-white/70"></span>
        <span class="grid content-start gap-1.5 rounded-md bg-white p-2 shadow-sm">
            <span class="h-2.5 rounded bg-zinc-900"></span>
            <span class="h-2 w-10 rounded-full bg-green-300"></span>
            <span class="mt-1 h-1.5 rounded bg-zinc-300"></span>
            <span class="h-1.5 rounded bg-zinc-300"></span>
            <span class="h-1.5 w-2/3 rounded bg-zinc-300"></span>
        </span>
    </span>
@elseif ($preview === 'product_single_editorial')
    <span class="flex h-full flex-col items-center gap-2 text-center">
        <span class="grid justify-items-center gap-1">
            <span class="h-2.5 w-24 rounded bg-zinc-900"></span>
            <span class="h-1.5 w-12 rounded-full bg-green-300"></span>
        </span>
        <span class="w-full flex-1 rounded-md bg-emerald-100 shadow-sm ring-1 ring-white/70"></span>
        <span class="grid w-4/5 gap-1">
            <span class="h-1.5 rounded bg-zinc-300"></span>
            <span class="mx-auto h-1.5 w-3/4 rounded bg-zinc-300"></span>
        </span>
    </span>
@elseif ($preview === 'products_catalog')
    <span class="grid h-full gap-2">
        @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100'] as $color)
            <span class="grid grid-cols-[3rem_1fr_auto] items-center gap-2 rounded-md bg-white p-1.5 shadow-sm">
                <span class="h-7 rounded-md {{ $color }}"></span>
                <span class="grid content-center gap-1">
                    <span class="h-1.5 rounded bg-zinc-900"></span>
                    <span class="h-1.5 rounded bg-zinc-300"></span>
                    <span class="h-1.5 w-2/3 rounded bg-zinc-300"></span>
                </span>
                <span class="h-2 w-5 rounded bg-green-300"></span>
            </span>
        @endforeach
    </span>
@elseif ($preview === 'products_carousel')
    <span class="flex h-full flex-col gap-2">
        <span class="flex items-center justify-between">
            <span class="grid gap-1">
                <span class="h-2 w-20 rounded bg-zinc-900"></span>
                <span class="h-1.5 w-24 rounded bg-zinc-300"></span>
            </span>
            <span class="flex gap-1">
                <span class="grid size-5 place-items-center rounded-full bg-white shadow-sm ring-1 ring-pink-200">
                    <span class="h-1.5 w-2 rounded bg-pink-300"></span>
                </span>
                <span class="grid size-5 place-items-center rounded-full bg-white shadow-sm ring-1 ring-pink-200">
                    <span class="h-1.5 w-2 rounded bg-pink-300"></span>
                </span>
            </span>
        </span>
        <span class="flex min-w-0 flex-1 gap-2 overflow-hidden">
            @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100', 'bg-green-100'] as $color)
                <span class="w-[38%] shrink-0 overflow-hidden rounded-md bg-white shadow-sm">
                    <span class="block h-8 {{ $color }}"></span>
                    <span class="grid gap-1 p-1.5">
                        <span class="h-1.5 rounded bg-zinc-900"></span>
                        <span class="h-1.5 w-4/5 rounded bg-zinc-300"></span>
                        <span class="h-1.5 w-1/2 rounded bg-green-300"></span>
                    </span>
                </span>
            @endforeach
        </span>
    </span>
@elseif ($preview === 'gallery_cards')
    <span class="grid h-full grid-cols-3 gap-2">
        @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100'] as $color)
            <span class="overflow-hidden rounded-md bg-white shadow-sm">
                <span class="block h-10 {{ $color }}"></span>
                <span class="grid gap-1.5 p-2">
                    <span class="h-1.5 rounded bg-zinc-900"></span>
                    <span class="h-1.5 w-2/3 rounded bg-zinc-300"></span>
                </span>
            </span>
        @endforeach
    </span>
@elseif ($preview === 'gallery_text_cards')
    <span class="grid h-full grid-cols-3 gap-2">
        @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100'] as $color)
            <span class="flex flex-col rounded-md bg-white p-1.5 shadow-sm">
                <span class="grid gap-1">
                    <span class="h-1.5 rounded bg-zinc-900"></span>
                    <span class="h-1.5 w-5/6 rounded bg-zinc-300"></span>
                    <span class="h-1.5 w-1/2 rounded bg-green-300"></span>
                </span>
                <span class="mt-1.5 block flex-1 rounded-md {{ $color }}"></span>
            </span>
        @endforeach
    </span>
@elseif ($preview === 'gallery_masonry')
    <span class="grid h-full grid-cols-4 gap-1.5">
        @foreach ([['h-7 bg-emerald-100', 'h-11 bg-yellow-100', 'h-8 bg-sky-100'], ['h-12 bg-sky-100', 'h-9 bg-green-100'], ['h-8 bg-yellow-100', 'h-12 bg-emerald-100', 'h-7 bg-sky-100'], ['h-10 bg-green-100', 'h-9 bg-yellow-100']] as $column)
            <span class="grid content-start gap-1.5">
                @foreach ($column as $block)
                    <span class="relative overflow-hidden rounded-md {{ $block }} shadow-sm">
                        <span class="absolute inset-x-1 bottom-1 rounded bg-white/90 p-0.5">
                            <span class="block h-1 rounded bg-zinc-900"></span>
                        </span>
                    </span>
                @endforeach
            </span>
        @endforeach
    </span>
@elseif ($preview === 'gallery_featured')
    <span class="grid h-full grid-cols-[1.35fr_0.9fr] gap-2">
        <span class="grid gap-1.5">
            <span class="rounded-md bg-emerald-100"></span>
            <span class="flex items-center justify-between gap-2">
                <span class="h-1.5 w-2/3 rounded bg-zinc-900"></span>
                <span class="h-1.5 w-8 rounded bg-green-300"></span>
            </span>
        </span>
        <span class="grid gap-2">
            @foreach (['bg-yellow-100', 'bg-sky-100'] as $color)
                <span class="grid gap-1.5">
                    <span class="rounded-md {{ $color }}"></span>
                    <span class="flex items-center justify-between gap-2">
                        <span class="h-1.5 w-2/3 rounded bg-zinc-900"></span>
                        <span class="h-1.5 w-6 rounded bg-green-300"></span>
                    </span>
                </span>
            @endforeach
        </span>
    </span>
@elseif ($preview === 'gallery_wall')
    <span class="grid h-full grid-cols-3 gap-2 rounded-md bg-white/70 p-1.5">
        @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100'] as $color)
            <span class="relative overflow-hidden rounded-md {{ $color }} shadow-sm">
                <span class="absolute inset-x-1 bottom-1 rounded bg-white/95 p-1 shadow-sm">
                    <span class="block h-1.5 rounded bg-zinc-900"></span>
                    <span class="mt-1 block h-1.5 w-2/3 rounded bg-zinc-300"></span>
                </span>
            </span>
        @endforeach
    </span>
@elseif ($preview === 'gallery_journal')
    <span class="grid h-full gap-2">
        @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100'] as $color)
            <span class="grid grid-cols-[3rem_1fr] gap-2 rounded-md bg-white p-1.5 shadow-sm">
                <span class="rounded-md {{ $color }}"></span>
                <span class="grid content-center gap-1">
                    <span class="h-1.5 rounded bg-zinc-900"></span>
                    <span class="h-1.5 rounded bg-zinc-300"></span>
                    <span class="h-1.5 w-2/3 rounded bg-zinc-300"></span>
                </span>
            </span>
        @endforeach
    </span>
@elseif ($preview === 'gallery_carousel')
    <span class="flex h-full flex-col gap-2">
        <span class="flex items-center justify-between">
            <span class="grid gap-1">
                <span class="h-2 w-20 rounded bg-zinc-900"></span>
                <span class="h-1.5 w-24 rounded bg-zinc-300"></span>
            </span>
            <span class="flex gap-1">
                <span class="size-5 rounded-full bg-white shadow-sm ring-1 ring-pink-200"></span>
                <span class="size-5 rounded-full bg-white shadow-sm ring-1 ring-pink-200"></span>
            </span>
        </span>
        <span class="flex min-w-0 flex-1 gap-2 overflow-hidden">
            @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100', 'bg-green-100'] as $color)
                <span class="w-[38%] shrink-0 overflow-hidden rounded-md bg-white shadow-sm">
                    <span class="block h-9 {{ $color }}"></span>
                    <span class="grid gap-1 p-1.5">
                        <span class="h-1.5 rounded bg-zinc-900"></span>
                        <span class="h-1.5 w-2/3 rounded bg-zinc-300"></span>
                    </span>
                </span>
            @endforeach
        </span>
    </span>
@elseif ($preview === 'gallery_single_masonry')
    <span class="grid h-full grid-cols-4 gap-1.5">
        @foreach ([['h-7 bg-emerald-100', 'h-11 bg-yellow-100', 'h-8 bg-sky-100'], ['h-12 bg-sky-100', 'h-9 bg-green-100'], ['h-8 bg-yellow-100', 'h-12 bg-emerald-100', 'h-7 bg-sky-100'], ['h-10 bg-green-100', 'h-9 bg-yellow-100']] as $column)
            <span class="grid content-start gap-1.5">
                @foreach ($column as $block)
                    <span class="rounded-md {{ $block }} shadow-sm ring-1 ring-white/70"></span>
                @endforeach
            </span>
        @endforeach
    </span>
@elseif ($preview === 'gallery_single_featured')
    <span class="grid h-full grid-cols-[1.3fr_0.9fr] gap-2">
        <span class="rounded-md bg-emerald-100 shadow-sm ring-1 ring-white/70"></span>
        <span class="grid grid-cols-2 gap-1.5">
            @foreach (['bg-yellow-100', 'bg-sky-100', 'bg-green-100', 'bg-emerald-100'] as $color)
                <span class="rounded-md {{ $color }} shadow-sm ring-1 ring-white/70"></span>
            @endforeach
        </span>
    </span>
@elseif ($preview === 'gallery_single_grid')
    <span class="grid h-full grid-cols-3 gap-1.5">
        @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100', 'bg-green-100', 'bg-sky-100', 'bg-emerald-100', 'bg-green-100', 'bg-yellow-100', 'bg-sky-100'] as $color)
            <span class="rounded-md {{ $color }} shadow-sm ring-1 ring-white/70"></span>
        @endforeach
    </span>
@elseif ($preview === 'photo_gallery_grid')
    <span class="grid h-full grid-cols-4 gap-1.5">
        @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100', 'bg-green-100', 'bg-sky-100', 'bg-emerald-100', 'bg-green-100', 'bg-yellow-100', 'bg-green-100', 'bg-sky-100', 'bg-yellow-100', 'bg-emerald-100', 'bg-yellow-100', 'bg-green-100', 'bg-emerald-100', 'bg-sky-100'] as $color)
            <span class="rounded-md {{ $color }} shadow-sm ring-1 ring-white/70"></span>
        @endforeach
    </span>
@elseif ($preview === 'photo_gallery_grid_2x2')
    <span class="grid h-full grid-cols-2 gap-1.5">
        @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100', 'bg-green-100'] as $color)
            <span class="rounded-md {{ $color }} shadow-sm ring-1 ring-white/70"></span>
        @endforeach
    </span>
@elseif ($preview === 'photo_gallery_grid_3x3')
    <span class="grid h-full grid-cols-3 gap-1.5">
        @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100', 'bg-green-100', 'bg-sky-100', 'bg-emerald-100', 'bg-green-100', 'bg-yellow-100', 'bg-sky-100'] as $color)
            <span class="rounded-md {{ $color }} shadow-sm ring-1 ring-white/70"></span>
        @endforeach
    </span>
@elseif ($preview === 'photo_gallery_featured')
    <span class="grid h-full grid-cols-[1.3fr_0.9fr] gap-2">
        <span class="rounded-md bg-emerald-100 shadow-sm ring-1 ring-white/70"></span>
        <span class="grid grid-cols-2 gap-1.5">
            @foreach (['bg-yellow-100', 'bg-sky-100', 'bg-green-100', 'bg-emerald-100'] as $color)
                <span class="rounded-md {{ $color }} shadow-sm ring-1 ring-white/70"></span>
            @endforeach
        </span>
    </span>
@elseif ($preview === 'photo_gallery_mosaic')
    <span class="grid h-full grid-cols-4 grid-rows-2 gap-1.5">
        <span class="col-span-2 row-span-2 rounded-md bg-emerald-100 shadow-sm ring-1 ring-white/70"></span>
        <span class="rounded-md bg-yellow-100 shadow-sm ring-1 ring-white/70"></span>
        <span class="rounded-md bg-sky-100 shadow-sm ring-1 ring-white/70"></span>
        <span class="col-span-2 rounded-md bg-green-100 shadow-sm ring-1 ring-white/70"></span>
    </span>
@elseif ($preview === 'photo_gallery_carousel')
    <span class="flex h-full flex-col gap-2">
        <span class="flex items-center justify-between">
            <span class="h-2 w-24 rounded bg-zinc-900"></span>
            <span class="flex gap-1">
                <span class="size-5 rounded-full bg-white shadow-sm ring-1 ring-pink-200"></span>
                <span class="size-5 rounded-full bg-white shadow-sm ring-1 ring-pink-200"></span>
            </span>
        </span>
        <span class="flex min-w-0 flex-1 gap-2 overflow-hidden">
            @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100', 'bg-green-100'] as $color)
                <span class="w-[40%] shrink-0 rounded-md {{ $color }} shadow-sm ring-1 ring-white/70"></span>
            @endforeach
        </span>
    </span>
@elseif ($preview === 'video_cards')
    <span class="grid h-full grid-cols-2 gap-2">
        @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100', 'bg-green-100'] as $color)
            <span class="overflow-hidden rounded-md bg-white shadow-sm">
                <span class="grid h-8 place-items-center {{ $color }}">
                    <span class="grid size-4 place-items-center rounded-full bg-white shadow-sm">
                        <flux:icon name="play" class="ml-0.5 size-2.5 text-zinc-800" />
                    </span>
                </span>
                <span class="grid gap-1.5 p-1.5">
                    <span class="h-1.5 rounded bg-zinc-900"></span>
                    <span class="h-1.5 w-3/4 rounded bg-zinc-300"></span>
                </span>
            </span>
        @endforeach
    </span>
@elseif ($preview === 'video_featured')
    <span class="grid h-full grid-cols-[1.25fr_0.75fr] gap-2">
        <span class="overflow-hidden rounded-md bg-white shadow-sm">
            <span class="grid h-14 place-items-center bg-emerald-100">
                <span class="grid size-6 place-items-center rounded-full bg-white shadow-sm">
                    <flux:icon name="play" class="ml-0.5 size-3.5 text-zinc-800" />
                </span>
            </span>
            <span class="grid gap-1.5 p-2">
                <span class="h-2 rounded bg-zinc-900"></span>
                <span class="h-1.5 w-4/5 rounded bg-zinc-300"></span>
            </span>
        </span>
        <span class="grid gap-2">
            @foreach (['bg-yellow-100', 'bg-sky-100'] as $color)
                <span class="grid grid-cols-[2.25rem_1fr] gap-1.5 rounded-md bg-white p-1.5 shadow-sm">
                    <span class="grid place-items-center rounded {{ $color }}">
                        <span class="grid size-3.5 place-items-center rounded-full bg-white">
                            <flux:icon name="play" class="ml-0.5 size-2 text-zinc-800" />
                        </span>
                    </span>
                    <span class="grid content-center gap-1">
                        <span class="h-1.5 rounded bg-zinc-900"></span>
                        <span class="h-1.5 w-4/5 rounded bg-zinc-300"></span>
                    </span>
                </span>
            @endforeach
        </span>
    </span>
@elseif ($preview === 'video_list')
    <span class="grid h-full gap-2">
        @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100'] as $color)
            <span class="grid grid-cols-[3.5rem_1fr] gap-2 rounded-md bg-white p-1.5 shadow-sm">
                <span class="grid place-items-center rounded {{ $color }}">
                    <span class="grid size-4 place-items-center rounded-full bg-white shadow-sm">
                        <flux:icon name="play" class="ml-0.5 size-2.5 text-zinc-800" />
                    </span>
                </span>
                <span class="grid content-center gap-1">
                    <span class="h-1.5 rounded bg-zinc-900"></span>
                    <span class="h-1.5 rounded bg-zinc-300"></span>
                    <span class="h-1.5 w-2/3 rounded bg-zinc-300"></span>
                </span>
            </span>
        @endforeach
    </span>
@elseif ($preview === 'video_focus')
    <span class="grid h-full gap-2">
        <span class="mx-auto grid w-4/5 place-items-center rounded-md bg-emerald-100 shadow-sm">
            <span class="grid size-7 place-items-center rounded-full bg-white shadow-sm">
                <flux:icon name="play" class="ml-0.5 size-4 text-zinc-800" />
            </span>
        </span>
        <span class="grid grid-cols-3 gap-2">
            @foreach (['bg-yellow-100', 'bg-sky-100', 'bg-green-100'] as $color)
                <span class="overflow-hidden rounded-md bg-white p-1.5 shadow-sm">
                    <span class="grid h-5 place-items-center rounded {{ $color }}">
                        <span class="size-2.5 rounded-full bg-white"></span>
                    </span>
                    <span class="mt-1.5 h-1.5 rounded bg-zinc-900"></span>
                </span>
            @endforeach
        </span>
    </span>
@elseif ($preview === 'video_grid_3x2')
    <span class="grid h-full grid-cols-3 gap-2">
        @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100', 'bg-green-100', 'bg-emerald-100', 'bg-yellow-100'] as $color)
            <span class="overflow-hidden rounded-md bg-white p-1 shadow-sm">
                <span class="grid h-7 place-items-center rounded {{ $color }}">
                    <span class="grid size-4 place-items-center rounded-full bg-white shadow-sm">
                        <flux:icon name="play" class="ml-0.5 size-2.5 text-zinc-800" />
                    </span>
                </span>
                <span class="mt-1.5 h-1.5 rounded bg-zinc-900"></span>
                <span class="mt-1 h-1.5 w-3/4 rounded bg-zinc-300"></span>
            </span>
        @endforeach
    </span>
@elseif ($preview === 'video_grid_4x2')
    <span class="grid h-full grid-cols-4 gap-1.5">
        @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100', 'bg-green-100', 'bg-emerald-100', 'bg-yellow-100', 'bg-sky-100', 'bg-green-100'] as $color)
            <span class="overflow-hidden rounded-md bg-white p-1 shadow-sm">
                <span class="grid h-6 place-items-center rounded {{ $color }}">
                    <span class="grid size-3.5 place-items-center rounded-full bg-white shadow-sm">
                        <flux:icon name="play" class="ml-0.5 size-2 text-zinc-800" />
                    </span>
                </span>
                <span class="mt-1.5 h-1 rounded bg-zinc-900"></span>
                <span class="mt-1 h-1 w-2/3 rounded bg-zinc-300"></span>
            </span>
        @endforeach
    </span>
@elseif ($preview === 'calendar_split')
    <span class="grid h-full grid-cols-[0.72fr_1.28fr] gap-2">
        <span class="rounded-md bg-white p-2 shadow-sm">
            <span class="h-1.5 w-12 rounded bg-emerald-200"></span>
            <span class="mt-2 block h-2.5 w-20 rounded bg-zinc-900"></span>
            <span class="mt-2 block h-1.5 rounded bg-zinc-300"></span>
            <span class="mt-1.5 block h-1.5 w-4/5 rounded bg-zinc-300"></span>
            <span class="mt-3 block h-2.5 w-14 rounded-full bg-yellow-100"></span>
        </span>
        <span class="grid gap-1.5 rounded-md bg-white p-1.5 shadow-sm">
            @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100'] as $color)
                <span class="grid grid-cols-[1.55rem_1fr] items-center gap-1.5">
                    <span class="grid h-7 place-items-center rounded {{ $color }}">
                        <span class="h-2 w-2.5 rounded bg-white"></span>
                    </span>
                    <span class="grid gap-1">
                        <span class="h-1.5 rounded bg-zinc-900"></span>
                        <span class="h-1 w-4/5 rounded bg-zinc-300"></span>
                    </span>
                </span>
            @endforeach
        </span>
    </span>
@elseif ($preview === 'calendar_list')
    <span class="relative block h-full pl-8">
        <span class="absolute left-[1.9rem] top-2 h-[calc(100%-1rem)] w-px bg-pink-200"></span>
        <span class="grid h-full gap-2">
            @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100'] as $color)
                <span class="relative grid grid-cols-[2.3rem_1fr] gap-2 rounded-md bg-white p-1.5 shadow-sm">
                    <span class="text-center">
                        <span class="block h-1.5 rounded bg-zinc-900"></span>
                        <span class="mt-1 block h-1 rounded bg-zinc-300"></span>
                    </span>
                    <span class="grid gap-1">
                        <span class="absolute left-[-0.52rem] top-2.5 size-2.5 rounded-full border border-pink-300 {{ $color }}"></span>
                        <span class="h-1.5 rounded bg-zinc-900"></span>
                        <span class="h-1 w-4/5 rounded bg-zinc-300"></span>
                    </span>
                </span>
            @endforeach
        </span>
    </span>
@elseif ($preview === 'calendar_cards')
    <span class="grid h-full grid-cols-3 gap-2">
        @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100'] as $color)
            <span class="rounded-md bg-white p-2 shadow-sm">
                <span class="mb-2 grid h-8 w-8 place-items-center rounded {{ $color }}">
                    <span class="h-2 w-3 rounded bg-white"></span>
                </span>
                <span class="block h-1.5 rounded bg-zinc-900"></span>
                <span class="mt-1.5 block h-1.5 rounded bg-zinc-300"></span>
                <span class="mt-1.5 block h-1.5 w-2/3 rounded bg-zinc-300"></span>
            </span>
        @endforeach
    </span>
@elseif ($preview === 'calendar_carousel')
    <span class="flex h-full flex-col gap-2">
        <span class="flex items-center justify-between">
            <span class="grid gap-1">
                <span class="h-2 w-20 rounded bg-zinc-900"></span>
                <span class="h-1.5 w-24 rounded bg-zinc-300"></span>
            </span>
            <span class="flex gap-1">
                <span class="size-5 rounded-full bg-white shadow-sm ring-1 ring-pink-200"></span>
                <span class="size-5 rounded-full bg-white shadow-sm ring-1 ring-pink-200"></span>
            </span>
        </span>
        <span class="flex min-w-0 flex-1 gap-2 overflow-hidden">
            @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100', 'bg-green-100'] as $color)
                <span class="w-[38%] shrink-0 rounded-md bg-white p-1.5 shadow-sm">
                    <span class="mb-1.5 grid h-6 w-7 place-items-center rounded {{ $color }}">
                        <span class="h-1.5 w-2.5 rounded bg-white"></span>
                    </span>
                    <span class="block h-1.5 rounded bg-zinc-900"></span>
                    <span class="mt-1 block h-1.5 w-4/5 rounded bg-zinc-300"></span>
                </span>
            @endforeach
        </span>
    </span>
@elseif ($preview === 'post_single_classic')
    <span class="grid h-full gap-2">
        <span class="grid gap-1">
            <span class="h-2.5 w-24 rounded bg-zinc-900"></span>
            <span class="h-1.5 w-16 rounded bg-zinc-300"></span>
        </span>
        <span class="rounded-md bg-emerald-100 shadow-sm ring-1 ring-white/70"></span>
        <span class="grid gap-1">
            <span class="h-1.5 rounded bg-zinc-300"></span>
            <span class="h-1.5 rounded bg-zinc-300"></span>
            <span class="h-1.5 w-3/4 rounded bg-zinc-300"></span>
        </span>
    </span>
@elseif ($preview === 'post_single_hero')
    <span class="grid h-full gap-2">
        <span class="grid justify-items-center gap-1 text-center">
            <span class="h-3 w-28 rounded bg-zinc-900"></span>
            <span class="h-2 w-20 rounded bg-zinc-900"></span>
            <span class="h-1.5 w-16 rounded bg-zinc-300"></span>
        </span>
        <span class="rounded-md bg-emerald-100 shadow-sm ring-1 ring-white/70"></span>
    </span>
@elseif ($preview === 'post_single_compact')
    <span class="mx-auto grid h-full max-w-[7.5rem] content-start gap-2">
        <span class="grid gap-1">
            <span class="h-2.5 rounded bg-zinc-900"></span>
            <span class="h-1.5 w-2/3 rounded bg-zinc-300"></span>
        </span>
        <span class="rounded-md bg-emerald-100 shadow-sm ring-1 ring-white/70"></span>
        <span class="grid gap-1">
            <span class="h-1.5 rounded bg-zinc-300"></span>
            <span class="h-1.5 rounded bg-zinc-300"></span>
            <span class="h-1.5 w-4/5 rounded bg-zinc-300"></span>
        </span>
    </span>
@elseif ($preview === 'post_single_cover')
    <span class="grid h-full gap-2">
        <span class="relative overflow-hidden rounded-md bg-emerald-100 shadow-sm ring-1 ring-white/70">
            <span class="absolute inset-0 bg-gradient-to-t from-zinc-900/45 via-zinc-900/10 to-transparent"></span>
            <span class="absolute inset-x-2 bottom-2 grid gap-1">
                <span class="h-2.5 w-24 rounded bg-white"></span>
                <span class="h-1.5 w-16 rounded bg-white/75"></span>
            </span>
        </span>
        <span class="mx-auto grid w-4/5 gap-1">
            <span class="h-1.5 rounded bg-zinc-300"></span>
            <span class="h-1.5 rounded bg-zinc-300"></span>
            <span class="h-1.5 w-3/4 rounded bg-zinc-300"></span>
        </span>
    </span>
@elseif ($preview === 'post_single_sidebar')
    <span class="grid h-full grid-cols-[1fr_0.42fr] gap-2">
        <span class="grid content-start gap-2">
            <span class="grid gap-1">
                <span class="h-2.5 w-24 rounded bg-zinc-900"></span>
                <span class="h-1.5 w-16 rounded bg-zinc-300"></span>
            </span>
            <span class="rounded-md bg-emerald-100 shadow-sm ring-1 ring-white/70"></span>
            <span class="grid gap-1">
                <span class="h-1.5 rounded bg-zinc-300"></span>
                <span class="h-1.5 w-4/5 rounded bg-zinc-300"></span>
            </span>
        </span>
        <span class="grid content-start gap-2 rounded-md bg-white p-2 shadow-sm ring-1 ring-pink-100">
            <span class="h-1.5 w-10 rounded bg-zinc-900"></span>
            <span class="h-1.5 rounded bg-emerald-100"></span>
            <span class="h-1.5 rounded bg-yellow-100"></span>
            <span class="h-1.5 rounded bg-sky-100"></span>
        </span>
    </span>
@elseif ($preview === 'news_cards')
    <span class="grid h-full grid-cols-3 gap-2">
        @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100'] as $color)
            <span class="overflow-hidden rounded-md bg-white shadow-sm">
                <span class="block h-9 {{ $color }}"></span>
                <span class="grid gap-1 p-1.5">
                    <span class="h-1.5 rounded bg-zinc-900"></span>
                    <span class="h-1.5 w-3/4 rounded bg-zinc-300"></span>
                </span>
            </span>
        @endforeach
    </span>
@elseif ($preview === 'news_featured')
    <span class="grid h-full grid-cols-[1.2fr_0.8fr] gap-2">
        <span class="overflow-hidden rounded-md bg-white shadow-sm">
            <span class="block h-12 bg-emerald-100"></span>
            <span class="grid gap-1 p-1.5">
                <span class="h-2 rounded bg-zinc-900"></span>
                <span class="h-1.5 w-4/5 rounded bg-zinc-300"></span>
            </span>
        </span>
        <span class="grid gap-2">
            @foreach (['bg-yellow-100', 'bg-sky-100'] as $color)
                <span class="grid grid-cols-[2rem_1fr] gap-1.5 rounded-md bg-white p-1.5 shadow-sm">
                    <span class="rounded {{ $color }}"></span>
                    <span class="grid content-center gap-1">
                        <span class="h-1.5 rounded bg-zinc-900"></span>
                        <span class="h-1.5 w-4/5 rounded bg-zinc-300"></span>
                    </span>
                </span>
            @endforeach
        </span>
    </span>
@elseif ($preview === 'news_stacked')
    <span class="grid h-full gap-2">
        <span class="grid grid-cols-[1.2fr_0.8fr] gap-2 rounded-md bg-white p-1.5 shadow-sm">
            <span class="rounded-md bg-emerald-100"></span>
            <span class="grid content-center gap-1">
                <span class="h-2 rounded bg-zinc-900"></span>
                <span class="h-1.5 w-4/5 rounded bg-zinc-300"></span>
            </span>
        </span>
        <span class="grid grid-cols-2 gap-2">
            @foreach (['bg-yellow-100', 'bg-sky-100'] as $color)
                <span class="grid grid-cols-[2rem_1fr] gap-1.5 rounded-md bg-white p-1.5 shadow-sm">
                    <span class="rounded {{ $color }}"></span>
                    <span class="grid content-center gap-1">
                        <span class="h-1.5 rounded bg-zinc-900"></span>
                        <span class="h-1.5 w-4/5 rounded bg-zinc-300"></span>
                    </span>
                </span>
            @endforeach
        </span>
    </span>
@elseif ($preview === 'news_journal')
    <span class="grid h-full gap-2">
        @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100'] as $color)
            <span class="grid grid-cols-[2.5rem_1fr] gap-2 rounded-md bg-white p-1.5 shadow-sm">
                <span class="rounded {{ $color }}"></span>
                <span class="grid gap-1">
                    <span class="h-1.5 rounded bg-zinc-900"></span>
                    <span class="h-1.5 w-4/5 rounded bg-zinc-300"></span>
                </span>
            </span>
        @endforeach
    </span>
@elseif ($preview === 'news_blog_grid')
    <span class="grid h-full grid-cols-3 gap-2">
        @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100'] as $color)
            <span class="grid content-start gap-1.5">
                <span class="block h-9 rounded-md {{ $color }} shadow-sm"></span>
                <span class="block h-1 w-1/2 rounded bg-zinc-300"></span>
                <span class="block h-1.5 rounded bg-zinc-900"></span>
                <span class="block h-1.5 w-4/5 rounded bg-zinc-300"></span>
                <span class="mt-1 flex items-center gap-1">
                    <span class="size-3 rounded-full bg-pink-100"></span>
                    <span class="h-1 w-7 rounded bg-zinc-300"></span>
                </span>
            </span>
        @endforeach
    </span>
@elseif ($preview === 'news_image_cards')
    <span class="grid h-full grid-cols-3 gap-2">
        @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100'] as $color)
            <span class="relative overflow-hidden rounded-md {{ $color }} shadow-sm">
                <span class="absolute inset-x-1 bottom-1 grid gap-1 rounded bg-white/95 p-1 shadow-sm">
                    <span class="h-1 w-1/2 rounded bg-zinc-300"></span>
                    <span class="h-1.5 rounded bg-zinc-900"></span>
                    <span class="h-1.5 w-4/5 rounded bg-zinc-300"></span>
                </span>
            </span>
        @endforeach
    </span>
@elseif ($preview === 'news_editorial_list')
    <span class="grid h-full gap-2">
        @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100'] as $color)
            <span class="grid grid-cols-[2.75rem_1fr] items-center gap-2">
                <span class="aspect-square rounded-md {{ $color }} shadow-sm"></span>
                <span class="grid gap-1">
                    <span class="h-1 w-1/2 rounded bg-zinc-300"></span>
                    <span class="h-1.5 rounded bg-zinc-900"></span>
                    <span class="h-1.5 w-4/5 rounded bg-zinc-300"></span>
                    <span class="mt-0.5 flex items-center gap-1">
                        <span class="size-3 rounded-full bg-pink-100"></span>
                        <span class="h-1 w-8 rounded bg-zinc-300"></span>
                    </span>
                </span>
            </span>
        @endforeach
    </span>
@elseif ($preview === 'about_split')
    <span class="grid h-full grid-cols-[1fr_0.85fr] gap-2">
        <span class="rounded-md bg-white p-3 shadow-sm">
            <span class="mb-2 inline-flex size-4 rounded-full bg-yellow-100"></span>
            <span class="block h-2 rounded bg-zinc-900"></span>
            <span class="mt-2 block h-1.5 rounded bg-zinc-300"></span>
            <span class="mt-1.5 block h-1.5 w-4/5 rounded bg-zinc-300"></span>
        </span>
        <span class="rounded-md bg-emerald-100"></span>
    </span>
@elseif ($preview === 'about_cover')
    <span class="grid h-full gap-2">
        <span class="rounded-md bg-emerald-100"></span>
        <span class="grid grid-cols-2 gap-2">
            @foreach (['bg-yellow-100', 'bg-sky-100'] as $color)
                <span class="rounded-md bg-white p-2 shadow-sm">
                    <span class="mb-1.5 block size-3 rounded-full {{ $color }}"></span>
                    <span class="block h-1.5 rounded bg-zinc-900"></span>
                    <span class="mt-2 block h-1.5 rounded bg-zinc-300"></span>
                </span>
            @endforeach
        </span>
    </span>
@elseif ($preview === 'about_background')
    <span class="relative block h-full overflow-hidden rounded-md bg-sky-100">
        <span class="absolute left-3 top-3 size-7 rounded-md bg-emerald-100/90"></span>
        <span class="absolute right-4 top-5 size-5 rounded-full bg-yellow-100/90"></span>
        <span class="absolute inset-x-4 bottom-4 rounded-md bg-white/90 p-3 shadow-sm">
            <span class="block h-2 rounded bg-zinc-900"></span>
            <span class="mt-2 block h-1.5 rounded bg-zinc-400"></span>
            <span class="mt-1.5 block h-1.5 w-4/5 rounded bg-zinc-400"></span>
        </span>
    </span>
@elseif ($preview === 'about_diagonal')
    <span class="grid h-full grid-cols-[1.05fr_0.95fr] gap-2">
        <span class="rounded-md bg-yellow-100 [clip-path:polygon(0_0,100%_0,82%_100%,0_100%)]"></span>
        <span class="self-center">
            <span class="mb-2 block size-4 rounded-full bg-emerald-100"></span>
            <span class="block h-2 w-3/5 rounded bg-zinc-900"></span>
            <span class="mt-2 block h-1.5 rounded bg-zinc-300"></span>
            <span class="mt-1.5 block h-1.5 w-4/5 rounded bg-zinc-300"></span>
        </span>
    </span>
@elseif ($preview === 'about_curved_image')
    <span class="grid h-full grid-cols-[0.95fr_1.05fr] items-center gap-2">
        <svg class="h-full w-full" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
            <path d="M4 0 H96 C98.5 0 100 1.8 99.5 4.5 L91.4 44.5 C90.6 47.5 90.6 50.5 91.4 53.5 L99.5 95.5 C100 98.2 98.5 100 96 100 H4 C1.8 100 0 98.2 0 96 V4 C0 1.8 1.8 0 4 0 Z" fill="#bbf7d0" />
            <circle cx="34" cy="34" r="14" fill="#fef3c7" />
            <rect x="14" y="62" width="44" height="16" rx="5" fill="#bae6fd" />
        </svg>
        <span class="self-center">
            <span class="block h-2 w-3/5 rounded bg-zinc-900"></span>
            <span class="mt-2 block h-1.5 rounded bg-zinc-300"></span>
            <span class="mt-1.5 block h-1.5 rounded bg-zinc-300"></span>
            <span class="mt-1.5 block h-1.5 w-4/5 rounded bg-zinc-300"></span>
        </span>
    </span>
@elseif ($preview === 'about_letter')
    <span class="grid h-full grid-cols-[0.9fr_1.1fr] items-center gap-2">
        <span class="rounded-md bg-green-100 shadow-sm"></span>
        <span class="self-center">
            <span class="block rounded-md border-l-2 border-pink-200 bg-pink-50 p-2">
                <span class="block h-1.5 rounded bg-zinc-900"></span>
                <span class="mt-1.5 block h-1.5 w-4/5 rounded bg-zinc-300"></span>
            </span>
            <span class="mt-2 block h-1.5 rounded bg-zinc-300"></span>
            <span class="mt-1.5 block h-1.5 w-5/6 rounded bg-zinc-300"></span>
        </span>
    </span>
@elseif ($preview === 'about_editorial_frame')
    <span class="grid h-full grid-rows-[1fr_auto] gap-2">
        <span class="relative block min-h-0">
            <span class="absolute inset-y-0 right-0 w-3/4 rounded-md bg-emerald-100">
                <span class="absolute bottom-2 right-3 h-5 w-12 rounded bg-yellow-100"></span>
                <span class="absolute right-5 top-2 size-5 rounded-full bg-sky-100"></span>
            </span>
            <span class="absolute left-0 top-1/2 w-[46%] -translate-y-1/2 rounded-md bg-pink-50 p-2 shadow-sm ring-1 ring-pink-100">
                <span class="block h-2 rounded bg-zinc-900"></span>
                <span class="mt-1.5 block h-2 w-4/5 rounded bg-zinc-900"></span>
            </span>
        </span>
        <span class="mx-auto grid w-2/3 grid-cols-2 gap-3">
            <span class="space-y-1">
                <span class="block h-1.5 rounded bg-zinc-300"></span>
                <span class="block h-1.5 w-4/5 rounded bg-zinc-300"></span>
            </span>
            <span class="space-y-1">
                <span class="block h-1.5 rounded bg-zinc-300"></span>
                <span class="block h-1.5 w-3/4 rounded bg-zinc-300"></span>
            </span>
        </span>
    </span>
@elseif ($preview === 'collaboration_banner')
    <span class="grid h-full grid-cols-[1.04fr_0.96fr] overflow-hidden rounded-md bg-white shadow-sm ring-1 ring-emerald-100">
        <span class="relative bg-sky-100">
            <span class="absolute left-5 top-5 size-8 rounded-full bg-yellow-100"></span>
            <span class="absolute bottom-5 left-4 h-6 w-14 rounded bg-emerald-100"></span>
        </span>
        <span class="flex flex-col justify-center bg-emerald-100 p-3">
            <span class="block h-2.5 w-4/5 rounded bg-zinc-900"></span>
            <span class="mt-2 block h-1.5 rounded bg-zinc-500"></span>
            <span class="mt-1.5 block h-1.5 w-5/6 rounded bg-zinc-500"></span>
            <span class="mt-3 block h-2.5 w-16 rounded-full bg-white ring-1 ring-emerald-200"></span>
        </span>
    </span>
@elseif ($preview === 'collaboration_background')
    <span class="relative flex h-full overflow-hidden rounded-md bg-sky-100 shadow-sm ring-1 ring-emerald-100">
        <span class="absolute left-5 top-4 size-8 rounded-full bg-yellow-100"></span>
        <span class="absolute bottom-5 left-4 h-7 w-16 rounded bg-emerald-100"></span>
        <span class="absolute right-0 top-0 h-full w-3/4 bg-gradient-to-l from-emerald-200 via-emerald-100 to-transparent"></span>
        <span class="relative ml-auto flex w-1/2 flex-col justify-center p-3">
            <span class="block h-2.5 w-4/5 rounded bg-zinc-900"></span>
            <span class="mt-2 block h-1.5 rounded bg-zinc-500"></span>
            <span class="mt-1.5 block h-1.5 w-5/6 rounded bg-zinc-500"></span>
            <span class="mt-3 block h-2.5 w-16 rounded-full bg-white ring-1 ring-emerald-200"></span>
        </span>
    </span>
@elseif ($preview === 'collaboration_image_card')
    <span class="relative flex h-full overflow-hidden rounded-md bg-sky-100 p-3 shadow-sm ring-1 ring-emerald-100">
        <span class="absolute left-4 top-4 size-8 rounded-full bg-yellow-100"></span>
        <span class="absolute bottom-4 left-5 h-7 w-14 rounded bg-emerald-100"></span>
        <span class="absolute inset-0 bg-emerald-900/20"></span>
        <span class="relative ml-auto flex w-[48%] flex-col justify-center rounded-md bg-white/95 p-2 shadow-sm ring-1 ring-white/70">
            <span class="block h-2.5 w-4/5 rounded bg-zinc-900"></span>
            <span class="mt-2 block h-1.5 rounded bg-zinc-300"></span>
            <span class="mt-1.5 block h-1.5 w-5/6 rounded bg-zinc-300"></span>
            <span class="mt-3 block h-2.5 w-16 rounded-full bg-emerald-200"></span>
        </span>
    </span>
@elseif ($preview === 'contact_split')
    <span class="grid h-full grid-cols-[0.92fr_1.08fr] gap-2">
        <span class="rounded-md bg-white p-2 shadow-sm ring-1 ring-pink-200">
            <span class="inline-flex size-5 rounded-full bg-emerald-100"></span>
            <span class="mt-3 block h-2 rounded bg-zinc-900"></span>
            <span class="mt-2 block h-1.5 rounded bg-zinc-300"></span>
            <span class="mt-1.5 block h-1.5 w-2/3 rounded bg-zinc-300"></span>
        </span>
        <span class="grid gap-2">
            @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100'] as $color)
                <span class="grid grid-cols-[1.5rem_1fr] items-center gap-2 rounded-md bg-white p-1.5 shadow-sm">
                    <span class="size-5 rounded-full {{ $color }}"></span>
                    <span class="grid gap-1">
                        <span class="h-1.5 rounded bg-zinc-900"></span>
                        <span class="h-1.5 w-3/4 rounded bg-zinc-300"></span>
                    </span>
                </span>
            @endforeach
        </span>
    </span>
@elseif ($preview === 'contact_cards')
    <span class="grid h-full grid-cols-2 gap-2">
        @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100', 'bg-green-100'] as $color)
            <span class="rounded-md bg-white p-2 shadow-sm">
                <span class="inline-flex size-4 rounded-full {{ $color }}"></span>
                <span class="mt-2 block h-1.5 rounded bg-zinc-900"></span>
                <span class="mt-1.5 block h-1.5 w-3/4 rounded bg-zinc-300"></span>
            </span>
        @endforeach
    </span>
@elseif ($preview === 'contact_letter')
    <span class="grid h-full grid-cols-[1.1fr_0.9fr] gap-2">
        <span class="rounded-md bg-white p-3 shadow-sm">
            <span class="inline-flex size-5 rounded-full bg-yellow-100"></span>
            <span class="mt-2 block h-2 rounded bg-zinc-900"></span>
            <span class="mt-2 block h-1.5 rounded bg-zinc-300"></span>
            <span class="mt-1.5 block h-1.5 w-4/5 rounded bg-zinc-300"></span>
        </span>
        <span class="grid gap-2">
            @foreach (['bg-emerald-100', 'bg-sky-100', 'bg-green-100'] as $color)
                <span class="grid grid-cols-[1.25rem_1fr] items-center gap-1.5 rounded-md bg-white p-1.5 shadow-sm">
                    <span class="size-4 rounded-full {{ $color }}"></span>
                    <span class="grid gap-1">
                        <span class="h-1.5 rounded bg-zinc-900"></span>
                        <span class="h-1.5 w-3/4 rounded bg-zinc-300"></span>
                    </span>
                </span>
            @endforeach
        </span>
    </span>
@elseif ($preview === 'social_cards')
    <span class="grid h-full grid-cols-3 gap-2">
        @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100', 'bg-green-100', 'bg-emerald-100', 'bg-yellow-100'] as $color)
            <span class="rounded-md bg-white p-2 shadow-sm">
                <span class="inline-flex size-5 rounded-lg {{ $color }}"></span>
                <span class="mt-2 block h-1.5 rounded bg-zinc-900"></span>
                <span class="mt-1.5 block h-1.5 w-3/4 rounded bg-zinc-300"></span>
            </span>
        @endforeach
    </span>
@elseif ($preview === 'social_strip')
    <span class="flex h-full items-center gap-2 rounded-md bg-white p-2 shadow-sm">
        @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100', 'bg-green-100'] as $color)
            <span class="flex min-w-0 flex-1 items-center gap-1.5 rounded-full bg-zinc-50 p-1.5">
                <span class="size-4 shrink-0 rounded-full {{ $color }}"></span>
                <span class="h-1.5 min-w-0 flex-1 rounded bg-zinc-900"></span>
            </span>
        @endforeach
    </span>
@elseif ($preview === 'social_icons')
    <span class="flex h-full items-center justify-center gap-2">
        @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100', 'bg-green-100', 'bg-emerald-100'] as $color)
            <span class="inline-flex size-10 items-center justify-center rounded-2xl bg-white shadow-sm ring-1 ring-pink-200">
                <span class="size-5 rounded-full {{ $color }}"></span>
            </span>
        @endforeach
    </span>
@elseif ($preview === 'portraits')
    <span class="grid h-full grid-cols-3 gap-2">
        @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100'] as $color)
            <span class="flex flex-col items-center rounded-md bg-white p-2 text-center shadow-sm">
                <span class="size-5 rounded-full {{ $color }}"></span>
                <span class="mt-3 block h-1.5 w-full rounded bg-zinc-300"></span>
                <span class="mt-1.5 block h-1.5 w-4/5 rounded bg-zinc-300"></span>
                <span class="mt-auto block h-1.5 w-8 rounded bg-zinc-900"></span>
            </span>
        @endforeach
    </span>
@elseif ($preview === 'quotes')
    <span class="grid h-full grid-cols-3 gap-2">
        @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100'] as $color)
            <span class="flex flex-col rounded-md bg-white p-2 shadow-sm">
                <span class="text-lg font-semibold leading-none text-emerald-600">&ldquo;</span>
                <span class="mt-2 block h-1.5 rounded bg-zinc-300"></span>
                <span class="mt-1.5 block h-1.5 w-4/5 rounded bg-zinc-300"></span>
                <span class="mt-auto flex items-center gap-1.5">
                    <span class="size-4 rounded-full {{ $color }}"></span>
                    <span class="h-1.5 flex-1 rounded bg-zinc-900"></span>
                </span>
            </span>
        @endforeach
    </span>
@elseif ($preview === 'split_grid')
    <span class="grid h-full grid-cols-2 gap-2">
        @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100', 'bg-green-100'] as $color)
            <span class="flex items-center gap-2 rounded-md bg-white p-2 shadow-sm">
                <span class="size-6 shrink-0 rounded-full {{ $color }}"></span>
                <span class="min-w-0 flex-1">
                    <span class="block h-1.5 rounded bg-zinc-900"></span>
                    <span class="mt-1.5 block h-1.5 rounded bg-zinc-300"></span>
                    <span class="mt-1.5 block h-1.5 w-2/3 rounded bg-zinc-300"></span>
                </span>
            </span>
        @endforeach
    </span>
@elseif ($preview === 'spotlight')
    <span class="grid h-full grid-cols-[1.25fr_0.75fr] gap-2">
        <span class="rounded-md bg-white p-2 shadow-sm">
            <span class="mb-2 block size-7 rounded-full bg-emerald-100"></span>
            <span class="block h-2 rounded bg-zinc-900"></span>
            <span class="mt-2 block h-1.5 rounded bg-zinc-300"></span>
            <span class="mt-1.5 block h-1.5 w-4/5 rounded bg-zinc-300"></span>
        </span>
        <span class="grid gap-2">
            <span class="rounded-md bg-white p-2 shadow-sm"><span class="block h-2 rounded bg-zinc-900"></span><span class="mt-2 block h-1.5 rounded bg-zinc-300"></span></span>
            <span class="rounded-md bg-white p-2 shadow-sm"><span class="block h-2 rounded bg-zinc-900"></span><span class="mt-2 block h-1.5 rounded bg-zinc-300"></span></span>
        </span>
    </span>
@elseif ($preview === 'notes')
    <span class="grid h-full grid-cols-3 gap-2">
        @foreach (['-rotate-2', 'rotate-1', 'rotate-2'] as $rotate)
            <span class="{{ $rotate }} rounded-md bg-white p-2 shadow-sm">
                <span class="block h-1.5 rounded bg-zinc-900"></span>
                <span class="mt-2 block h-1.5 rounded bg-zinc-300"></span>
                <span class="mt-1.5 block h-1.5 rounded bg-zinc-300"></span>
                <span class="mt-2 block size-5 rounded-full bg-white shadow-sm"></span>
            </span>
        @endforeach
    </span>
@elseif ($preview === 'testimonials_carousel')
    <span class="flex h-full flex-col gap-2">
        <span class="flex items-center justify-between">
            <span class="grid gap-1">
                <span class="h-2 w-20 rounded bg-zinc-900"></span>
                <span class="h-1.5 w-24 rounded bg-zinc-300"></span>
            </span>
            <span class="flex gap-1">
                <span class="size-5 rounded-full bg-white shadow-sm ring-1 ring-pink-200"></span>
                <span class="size-5 rounded-full bg-white shadow-sm ring-1 ring-pink-200"></span>
            </span>
        </span>
        <span class="flex min-w-0 flex-1 gap-2 overflow-hidden">
            @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100', 'bg-green-100'] as $color)
                <span class="w-[38%] shrink-0 rounded-md bg-white p-2 shadow-sm">
                    <span class="block h-1.5 rounded bg-zinc-300"></span>
                    <span class="mt-1.5 block h-1.5 rounded bg-zinc-300"></span>
                    <span class="mt-3 flex items-center gap-1.5">
                        <span class="size-4 rounded-full {{ $color }}"></span>
                        <span class="h-1.5 flex-1 rounded bg-zinc-900"></span>
                    </span>
                </span>
            @endforeach
        </span>
    </span>
@elseif ($preview === 'testimonials_featured_mosaic')
    <span class="grid h-full grid-cols-[1.35fr_0.9fr] gap-2">
        <span class="rounded-md bg-white p-2.5 shadow-sm">
            <span class="block h-2 rounded bg-zinc-900"></span>
            <span class="mt-2 block h-1.5 rounded bg-zinc-300"></span>
            <span class="mt-1.5 block h-1.5 w-4/5 rounded bg-zinc-300"></span>
            <span class="mt-3 flex items-center gap-1.5">
                <span class="size-5 rounded-full bg-emerald-100"></span>
                <span class="h-1.5 w-14 rounded bg-zinc-900"></span>
            </span>
        </span>
        <span class="grid gap-2">
            @foreach (['bg-yellow-100', 'bg-sky-100'] as $color)
                <span class="rounded-md bg-white p-2 shadow-sm">
                    <span class="block h-1.5 rounded bg-zinc-300"></span>
                    <span class="mt-1.5 block h-1.5 w-4/5 rounded bg-zinc-300"></span>
                    <span class="mt-2 flex items-center gap-1.5">
                        <span class="size-4 rounded-full {{ $color }}"></span>
                        <span class="h-1.5 flex-1 rounded bg-zinc-900"></span>
                    </span>
                </span>
            @endforeach
        </span>
    </span>
@elseif ($preview === 'testimonials_masonry_columns')
    <span class="grid h-full grid-cols-3 gap-2">
        @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100'] as $color)
            <span class="flex flex-col justify-between rounded-md bg-white p-2">
                <span class="block h-1.5 rounded bg-zinc-300"></span>
                <span class="mt-1.5 block h-1.5 w-4/5 rounded bg-zinc-300"></span>
                <span class="mt-2 flex items-center gap-1">
                    <span class="size-3 rounded-full {{ $color }}"></span>
                    <span class="h-1 flex-1 rounded bg-zinc-900"></span>
                </span>
            </span>
        @endforeach
    </span>
@else
    <span class="grid h-full grid-cols-3 gap-2">
        @foreach (['bg-emerald-100', 'bg-yellow-100', 'bg-sky-100'] as $color)
            <span class="rounded-md bg-white p-2 shadow-sm">
                <span class="block h-8 rounded {{ $color }}"></span>
                <span class="mt-2 block h-1.5 rounded bg-zinc-900"></span>
                <span class="mt-1.5 block h-1.5 rounded bg-zinc-300"></span>
            </span>
        @endforeach
    </span>
@endif
