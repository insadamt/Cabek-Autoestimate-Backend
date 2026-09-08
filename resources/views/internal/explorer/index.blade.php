@extends('internal.layout')

@section('title', 'Price Explorer · Cabek AutoEstimate')

@section('content')
    <div class="mb-7 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Internal data browser</div>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight text-white">Price Explorer</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-400">
                Search the canonical vehicle hierarchy, then inspect the evidence behind each market price.
            </p>
        </div>

        @if ($selectedPart)
            <a
                href="{{ route('internal.explorer') }}"
                class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm font-medium text-slate-200 transition hover:bg-white/10"
            >
                New search
            </a>
        @endif
    </div>

    @if (! $selectedPart)
        <section
            data-price-explorer
            data-explorer-url="{{ route('internal.explorer') }}"
            data-models-base="{{ url('/internal/explorer/marques') }}"
            data-parts-base="{{ url('/internal/explorer/models') }}"
            data-start-step="{{ $selectedModel ? 'part' : ($selectedMarque ? 'model' : 'marque') }}"
            data-selected-marque-id="{{ $selectedMarque?->id }}"
            data-selected-marque-name="{{ $selectedMarque?->libelle }}"
            data-selected-model-id="{{ $selectedModel?->id }}"
            data-selected-model-name="{{ $selectedModel?->libelle }}"
            class="overflow-hidden rounded-2xl border border-white/10 bg-slate-900 shadow-2xl shadow-black/10"
        >
            <div class="border-b border-white/10 px-5 py-4 sm:px-6">
                <div class="flex items-center gap-2 text-xs font-medium text-slate-500">
                    <span data-progress="marque" class="rounded-full bg-white px-2.5 py-1 text-slate-950">1 · Marque</span>
                    <span class="text-slate-700">›</span>
                    <span data-progress="model" class="rounded-full px-2.5 py-1">2 · Model</span>
                    <span class="text-slate-700">›</span>
                    <span data-progress="part" class="rounded-full px-2.5 py-1">3 · Part</span>
                </div>
            </div>

            <div class="p-5 sm:p-6 lg:p-8">
                <div data-step="marque" class="hidden">
                    <div class="mx-auto max-w-3xl">
                        <p class="text-sm font-medium text-slate-400">Step 1</p>
                        <h2 class="mt-1 text-2xl font-semibold text-white">Choose a marque</h2>

                        <label class="mt-6 block">
                            <span class="sr-only">Search marque</span>
                            <div class="relative">
                                <svg class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <circle cx="11" cy="11" r="7"></circle>
                                    <path d="m20 20-3.5-3.5"></path>
                                </svg>
                                <input
                                    data-search-input="marque"
                                    type="search"
                                    autocomplete="off"
                                    placeholder="Search a marque…"
                                    class="w-full rounded-2xl border border-white/10 bg-slate-950 py-4 pl-12 pr-4 text-base text-white outline-none ring-0 placeholder:text-slate-600 focus:border-white/25"
                                >
                            </div>
                        </label>

                        <div data-choice-list="marque" class="mt-4 grid max-h-[430px] gap-2 overflow-y-auto pr-1 sm:grid-cols-2">
                            @foreach ($marques as $marque)
                                <button
                                    type="button"
                                    data-choice="marque"
                                    data-id="{{ $marque->id }}"
                                    data-name="{{ $marque->libelle }}"
                                    data-search-text="{{ Str::lower($marque->libelle) }}"
                                    class="group flex items-center justify-between rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3 text-left transition hover:border-white/20 hover:bg-white/[0.07]"
                                >
                                    <span class="font-medium text-slate-200 group-hover:text-white">{{ $marque->libelle }}</span>
                                    <span class="text-xs text-slate-600">{{ $marque->models_count }} models</span>
                                </button>
                            @endforeach
                        </div>

                        <div data-empty-state="marque" class="mt-8 hidden text-center text-sm text-slate-500">
                            No canonical marque matches this search.
                        </div>
                    </div>
                </div>

                <div data-step="model" class="hidden">
                    <div class="mx-auto max-w-3xl">
                        <button type="button" data-back="marque" class="text-sm font-medium text-slate-500 transition hover:text-white">← Back to marques</button>
                        <p class="mt-5 text-sm font-medium text-slate-400">Step 2 · <span data-selected-marque-label>{{ $selectedMarque?->libelle }}</span></p>
                        <h2 class="mt-1 text-2xl font-semibold text-white">Choose a model</h2>

                        <label class="mt-6 block">
                            <span class="sr-only">Search model</span>
                            <div class="relative">
                                <svg class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <circle cx="11" cy="11" r="7"></circle>
                                    <path d="m20 20-3.5-3.5"></path>
                                </svg>
                                <input data-search-input="model" type="search" autocomplete="off" placeholder="Search model…" class="w-full rounded-2xl border border-white/10 bg-slate-950 py-4 pl-12 pr-4 text-base text-white outline-none placeholder:text-slate-600 focus:border-white/25">
                            </div>
                        </label>

                        <div data-loading="model" class="mt-8 text-center text-sm text-slate-500">Loading models…</div>
                        <div data-choice-list="model" class="mt-4 grid max-h-[430px] gap-2 overflow-y-auto pr-1 sm:grid-cols-2"></div>
                        <div data-empty-state="model" class="mt-8 hidden text-center text-sm text-slate-500">No model matches this search.</div>
                    </div>
                </div>

                <div data-step="part" class="hidden">
                    <div class="mx-auto max-w-3xl">
                        <button type="button" data-back="model" class="text-sm font-medium text-slate-500 transition hover:text-white">← Back to models</button>
                        <p class="mt-5 text-sm font-medium text-slate-400">
                            Step 3 · <span data-selected-marque-label>{{ $selectedMarque?->libelle }}</span> › <span data-selected-model-label>{{ $selectedModel?->libelle }}</span>
                        </p>
                        <h2 class="mt-1 text-2xl font-semibold text-white">Choose an observed part</h2>
                        <p class="mt-2 text-sm text-slate-500">Only parts with price observations for this model are shown.</p>

                        <label class="mt-6 block">
                            <span class="sr-only">Search part</span>
                            <div class="relative">
                                <svg class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <circle cx="11" cy="11" r="7"></circle>
                                    <path d="m20 20-3.5-3.5"></path>
                                </svg>
                                <input data-search-input="part" type="search" autocomplete="off" placeholder="Search part…" class="w-full rounded-2xl border border-white/10 bg-slate-950 py-4 pl-12 pr-4 text-base text-white outline-none placeholder:text-slate-600 focus:border-white/25">
                            </div>
                        </label>

                        <div data-loading="part" class="mt-8 text-center text-sm text-slate-500">Loading observed parts…</div>
                        <div data-choice-list="part" class="mt-4 grid max-h-[430px] gap-2 overflow-y-auto pr-1 sm:grid-cols-2"></div>
                        <div data-empty-state="part" class="mt-8 hidden text-center text-sm text-slate-500">No observed part matches this search.</div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    @if ($selectedPart && $selectedModel && $selectedMarque)
        <section class="space-y-5">
            <div class="rounded-2xl border border-white/10 bg-slate-900 p-5 sm:p-6">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-2 text-sm text-slate-500">
                            <a href="{{ route('internal.explorer') }}" class="transition hover:text-white">{{ $selectedMarque->libelle }}</a>
                            <span>›</span>
                            <a href="{{ route('internal.explorer', ['marque' => $selectedMarque->id]) }}" class="transition hover:text-white">{{ $selectedModel->libelle }}</a>
                            <span>›</span>
                            <span class="text-slate-300">{{ $selectedPart->libelle }}</span>
                        </div>

                        <h2 class="mt-3 text-2xl font-semibold text-white">{{ $selectedPart->libelle }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ $selectedMarque->libelle }} · {{ $selectedModel->libelle }}</p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('internal.explorer', ['marque' => $selectedMarque->id, 'model' => $selectedModel->id]) }}" class="rounded-xl border border-white/10 px-3.5 py-2 text-sm font-medium text-slate-300 transition hover:bg-white/5 hover:text-white">Change part</a>
                        <a href="{{ route('internal.explorer', ['marque' => $selectedMarque->id]) }}" class="rounded-xl border border-white/10 px-3.5 py-2 text-sm font-medium text-slate-300 transition hover:bg-white/5 hover:text-white">Change model</a>
                    </div>
                </div>
            </div>

            <form method="GET" action="{{ route('internal.explorer') }}" class="rounded-2xl border border-white/10 bg-slate-900 p-4 sm:p-5">
                <input type="hidden" name="marque" value="{{ $selectedMarque->id }}">
                <input type="hidden" name="model" value="{{ $selectedModel->id }}">
                <input type="hidden" name="part" value="{{ $selectedPart->id }}">

                <div class="flex flex-col gap-3 lg:flex-row lg:items-end">
                    <label class="block flex-1">
                        <span class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Production year</span>
                        <input name="year" type="number" min="1900" max="{{ now()->year + 1 }}" value="{{ $filters['year'] ?? '' }}" placeholder="All years" class="w-full rounded-xl border border-white/10 bg-slate-950 px-3.5 py-2.5 text-sm text-white outline-none placeholder:text-slate-600 focus:border-white/25">
                    </label>

                    <label class="block flex-1">
                        <span class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Type</span>
                        <select name="type" class="w-full rounded-xl border border-white/10 bg-slate-950 px-3.5 py-2.5 text-sm text-white outline-none focus:border-white/25">
                            <option value="">All types</option>
                            @foreach (['original', 'adaptable', 'occasion'] as $type)
                                <option value="{{ $type }}" @selected(($filters['type'] ?? null) === $type)>{{ ucfirst($type) }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="block flex-1">
                        <span class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">Source</span>
                        <select name="source_id" class="w-full rounded-xl border border-white/10 bg-slate-950 px-3.5 py-2.5 text-sm text-white outline-none focus:border-white/25">
                            <option value="">All sources</option>
                            @foreach ($sources as $source)
                                <option value="{{ $source->id }}" @selected((string) ($filters['source_id'] ?? '') === (string) $source->id)>{{ $source->libelle }}</option>
                            @endforeach
                        </select>
                    </label>

                    <div class="flex gap-2">
                        <button class="rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-slate-200">Apply</button>
                        <a href="{{ route('internal.explorer', ['marque' => $selectedMarque->id, 'model' => $selectedModel->id, 'part' => $selectedPart->id]) }}" class="rounded-xl border border-white/10 px-4 py-2.5 text-sm font-medium text-slate-400 transition hover:bg-white/5 hover:text-white">Reset</a>
                    </div>
                </div>
            </form>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                <div class="rounded-2xl border border-white/10 bg-white p-5 text-slate-950 md:col-span-2 xl:col-span-1">
                    <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Market estimate</div>
                    <div class="mt-4 text-3xl font-semibold tracking-tight">
                        {{ $statistics['median'] !== null ? number_format($statistics['median'], 2) : '—' }}
                        <span class="text-base font-medium text-slate-500">MAD</span>
                    </div>
                    <div class="mt-2 text-xs text-slate-500">Median of matching observations</div>
                </div>

                @foreach ([
                    ['Average', $statistics['average']],
                    ['Minimum', $statistics['minimum']],
                    ['Maximum', $statistics['maximum']],
                ] as [$label, $value])
                    <div class="rounded-2xl border border-white/10 bg-slate-900 p-5">
                        <div class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">{{ $label }}</div>
                        <div class="mt-4 text-2xl font-semibold text-white">{{ $value !== null ? number_format($value, 2) : '—' }} <span class="text-sm font-medium text-slate-500">MAD</span></div>
                    </div>
                @endforeach

                <div class="rounded-2xl border border-white/10 bg-slate-900 p-5">
                    <div class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Evidence</div>
                    <div class="mt-4 text-2xl font-semibold text-white">{{ number_format($statistics['count']) }}</div>
                    <div class="mt-1 text-sm text-slate-500">observations</div>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-white/10 bg-slate-900">
                <div class="flex items-center justify-between border-b border-white/10 px-5 py-4 sm:px-6">
                    <div>
                        <h3 class="font-semibold text-white">Price observations</h3>
                        <p class="mt-1 text-xs text-slate-500">Newest evidence first</p>
                    </div>
                    <div class="text-sm text-slate-500">{{ $observations->total() }} rows</div>
                </div>

                @if ($observations->isEmpty())
                    <div class="px-6 py-16 text-center">
                        <div class="text-base font-medium text-slate-300">No observations match these filters.</div>
                        <p class="mt-2 text-sm text-slate-500">Reset the filters to return to all available evidence.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-white/10 text-left text-sm">
                            <thead class="bg-slate-950/60 text-xs uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-5 py-3 font-medium sm:px-6">Price</th>
                                    <th class="px-5 py-3 font-medium">Type</th>
                                    <th class="px-5 py-3 font-medium">Compatibility</th>
                                    <th class="px-5 py-3 font-medium">Source</th>
                                    <th class="px-5 py-3 font-medium">Observed</th>
                                    <th class="px-5 py-3 font-medium">Reference</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/[0.06]">
                                @foreach ($observations as $observation)
                                    <tr class="transition hover:bg-white/[0.025]">
                                        <td class="whitespace-nowrap px-5 py-4 font-semibold text-white sm:px-6">{{ number_format((float) $observation->price, 2) }} MAD</td>
                                        <td class="whitespace-nowrap px-5 py-4">
                                            <span class="rounded-full border border-white/10 bg-white/5 px-2.5 py-1 text-xs font-medium text-slate-300">{{ ucfirst($observation->type) }}</span>
                                        </td>
                                        <td class="whitespace-nowrap px-5 py-4 text-slate-400">
                                            @if ($observation->production_from === null && $observation->production_to === null)
                                                Unknown
                                            @elseif ($observation->production_from !== null && $observation->production_to !== null)
                                                {{ $observation->production_from }}–{{ $observation->production_to }}
                                            @elseif ($observation->production_from !== null)
                                                From {{ $observation->production_from }}
                                            @else
                                                Until {{ $observation->production_to }}
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-5 py-4 text-slate-300">{{ $observation->source->libelle }}</td>
                                        <td class="whitespace-nowrap px-5 py-4 text-slate-400">{{ $observation->observed_at->format('Y-m-d H:i') }}</td>
                                        <td class="max-w-[260px] px-5 py-4">
                                            @if ($observation->source_url)
                                                <a href="{{ $observation->source_url }}" target="_blank" rel="noreferrer" class="block truncate text-slate-300 underline decoration-slate-700 underline-offset-4 transition hover:text-white" title="{{ $observation->source_url }}">
                                                    {{ $observation->source_reference ?: 'Open source' }}
                                                </a>
                                            @else
                                                <span class="text-slate-500">{{ $observation->source_reference ?: '—' }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($observations->hasPages())
                        <div class="border-t border-white/10 px-5 py-4 sm:px-6">
                            {{ $observations->links() }}
                        </div>
                    @endif
                @endif
            </div>
        </section>
    @endif
@endsection
