@extends('internal.layout')

@section('title', 'Price Explorer · Cabek AutoEstimate')

@section('content')
    @php
        $stage = ! $selectedMarque ? 'marque' : (! $selectedModel ? 'model' : (! $selectedPart ? 'part' : null));
        $search = $filters['q'] ?? '';
        $activeFilterCount = collect([
            $filters['year'] ?? null,
            $filters['type'] ?? null,
            $filters['source_id'] ?? null,
        ])->filter(fn ($value) => $value !== null && $value !== '')->count();
    @endphp

    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-slate-950">Price Explorer</h1>
            <p class="mt-1 text-sm text-slate-600">Select a vehicle and part, then inspect the observations behind the estimate.</p>
        </div>

        @if ($selectedMarque || $selectedModel || $selectedPart)
            <a href="{{ route('internal.explorer') }}" class="button-secondary">
                New search
            </a>
        @endif
    </div>

    <section class="border border-slate-300 bg-white">
        <div class="grid sm:grid-cols-3 sm:divide-x sm:divide-slate-300">
            <div class="border-b border-slate-300 px-4 py-3 sm:border-b-0">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-xs font-medium uppercase tracking-wide text-slate-500">Marque</span>
                    @if ($selectedMarque)
                        <a href="{{ route('internal.explorer') }}" class="text-xs font-medium text-slate-500 hover:text-slate-950">Change</a>
                    @endif
                </div>
                <div class="mt-1 truncate text-sm font-medium {{ $selectedMarque ? 'text-slate-950' : 'text-slate-400' }}">
                    {{ $selectedMarque?->libelle ?? 'Not selected' }}
                </div>
            </div>

            <div class="border-b border-slate-300 px-4 py-3 sm:border-b-0">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-xs font-medium uppercase tracking-wide text-slate-500">Model</span>
                    @if ($selectedModel)
                        <a href="{{ route('internal.explorer', ['marque' => $selectedMarque->id]) }}" class="text-xs font-medium text-slate-500 hover:text-slate-950">Change</a>
                    @endif
                </div>
                <div class="mt-1 truncate text-sm font-medium {{ $selectedModel ? 'text-slate-950' : 'text-slate-400' }}">
                    {{ $selectedModel?->libelle ?? ($selectedMarque ? 'Choose next' : 'Not selected') }}
                </div>
            </div>

            <div class="px-4 py-3">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-xs font-medium uppercase tracking-wide text-slate-500">Part</span>
                    @if ($selectedPart)
                        <a href="{{ route('internal.explorer', ['marque' => $selectedMarque->id, 'model' => $selectedModel->id]) }}" class="text-xs font-medium text-slate-500 hover:text-slate-950">Change</a>
                    @endif
                </div>
                <div class="mt-1 truncate text-sm font-medium {{ $selectedPart ? 'text-slate-950' : 'text-slate-400' }}">
                    {{ $selectedPart?->libelle ?? ($selectedModel ? 'Choose next' : 'Not selected') }}
                </div>
            </div>
        </div>
    </section>

    @if ($stage)
        @php
            $chooserTitle = match ($stage) {
                'marque' => 'Choose a marque',
                'model' => 'Choose a model',
                'part' => 'Choose a part',
            };

            $chooserDescription = match ($stage) {
                'marque' => 'Start with the manufacturer.',
                'model' => 'Models available for '.$selectedMarque->libelle.'.',
                'part' => 'Only parts with observations for '.$selectedModel->libelle.' are listed.',
            };

            $chooserCount = match ($stage) {
                'marque' => $marques->count(),
                'model' => $models->count(),
                'part' => $parts->count(),
            };

            $clearSearchParams = match ($stage) {
                'marque' => [],
                'model' => ['marque' => $selectedMarque->id],
                'part' => ['marque' => $selectedMarque->id, 'model' => $selectedModel->id],
            };
        @endphp

        <section class="mt-4 border border-slate-300 bg-white">
            <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-300 px-4 py-3">
                <div>
                    <h2 class="text-sm font-semibold text-slate-950">{{ $chooserTitle }}</h2>
                    <p class="mt-0.5 text-xs text-slate-500">{{ $chooserDescription }}</p>
                </div>
                <span class="text-xs text-slate-500">{{ number_format($chooserCount) }} {{ $search !== '' ? 'matches' : 'available' }}</span>
            </div>

            <form method="GET" action="{{ route('internal.explorer') }}" class="flex flex-col gap-2 border-b border-slate-200 bg-slate-50 p-3 sm:flex-row">
                @if ($selectedMarque)
                    <input type="hidden" name="marque" value="{{ $selectedMarque->id }}">
                @endif
                @if ($selectedModel)
                    <input type="hidden" name="model" value="{{ $selectedModel->id }}">
                @endif

                <label class="sr-only" for="picker-search">Search {{ $stage }}</label>
                <input
                    id="picker-search"
                    type="search"
                    name="q"
                    value="{{ $search }}"
                    placeholder="Search {{ $stage }}..."
                    class="field sm:max-w-md"
                    autofocus
                >
                <button type="submit" class="button-primary">Search</button>
                @if ($search !== '')
                    <a href="{{ route('internal.explorer', $clearSearchParams) }}" class="button-secondary">Clear</a>
                @endif
            </form>

            <div class="max-h-[560px] overflow-y-auto divide-y divide-slate-200">
                @if ($stage === 'marque')
                    @forelse ($marques as $marque)
                        <a
                            href="{{ route('internal.explorer', ['marque' => $marque->id]) }}"
                            class="flex items-center justify-between gap-4 px-4 py-3 text-sm text-slate-700 hover:bg-slate-50 hover:text-slate-950"
                        >
                            <span class="font-medium">{{ $marque->libelle }}</span>
                            <span class="shrink-0 text-xs text-slate-400">{{ $marque->models_count }} models</span>
                        </a>
                    @empty
                        <div class="px-4 py-10 text-center text-sm text-slate-500">
                            No marques match “{{ $search }}”.
                        </div>
                    @endforelse
                @elseif ($stage === 'model')
                    @forelse ($models as $model)
                        <a
                            href="{{ route('internal.explorer', ['marque' => $selectedMarque->id, 'model' => $model->id]) }}"
                            class="flex items-center justify-between gap-4 px-4 py-3 text-sm text-slate-700 hover:bg-slate-50 hover:text-slate-950"
                        >
                            <span class="font-medium">{{ $model->libelle }}</span>
                            <span class="shrink-0 text-xs text-slate-400">{{ $model->price_observations_count }} observations</span>
                        </a>
                    @empty
                        <div class="px-4 py-10 text-center text-sm text-slate-500">
                            No models match “{{ $search }}”.
                        </div>
                    @endforelse
                @else
                    @forelse ($parts as $part)
                        <a
                            href="{{ route('internal.explorer', ['marque' => $selectedMarque->id, 'model' => $selectedModel->id, 'part' => $part->id]) }}"
                            class="flex items-center justify-between gap-4 px-4 py-3 text-sm text-slate-700 hover:bg-slate-50 hover:text-slate-950"
                        >
                            <span class="font-medium">{{ $part->libelle }}</span>
                            <span class="shrink-0 text-xs text-slate-400">{{ $part->observations_count }} observations</span>
                        </a>
                    @empty
                        <div class="px-4 py-10 text-center text-sm text-slate-500">
                            No parts match “{{ $search }}”.
                        </div>
                    @endforelse
                @endif
            </div>
        </section>
    @else
        <div class="mt-5 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-base font-semibold text-slate-950">{{ $selectedPart->libelle }}</h2>
                <p class="mt-0.5 text-sm text-slate-500">{{ $selectedMarque->libelle }} · {{ $selectedModel->libelle }}</p>
            </div>
            <a
                href="{{ route('internal.explorer', ['marque' => $selectedMarque->id, 'model' => $selectedModel->id]) }}"
                class="text-sm font-medium text-slate-600 hover:text-slate-950"
            >
                Choose another part
            </a>
        </div>

        <dl class="mt-3 grid border border-slate-300 bg-white sm:grid-cols-2 xl:grid-cols-4">
            <div class="border-b border-slate-300 bg-slate-50 p-4 sm:border-r xl:border-b-0">
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Median estimate</dt>
                <dd class="mt-1 text-2xl font-semibold tracking-tight text-slate-950">
                    {{ $statistics['median'] !== null ? number_format($statistics['median'], 2).' MAD' : '—' }}
                </dd>
                <div class="mt-1 text-xs text-slate-500">Primary estimate</div>
            </div>
            <div class="border-b border-slate-300 p-4 xl:border-b-0 xl:border-r">
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Average</dt>
                <dd class="mt-1 text-lg font-semibold text-slate-950">
                    {{ $statistics['average'] !== null ? number_format($statistics['average'], 2).' MAD' : '—' }}
                </dd>
            </div>
            <div class="border-b border-slate-300 p-4 sm:border-b-0 sm:border-r">
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Range</dt>
                <dd class="mt-1 text-lg font-semibold text-slate-950">
                    @if ($statistics['minimum'] !== null && $statistics['maximum'] !== null)
                        {{ number_format($statistics['minimum'], 2) }} – {{ number_format($statistics['maximum'], 2) }} MAD
                    @else
                        —
                    @endif
                </dd>
            </div>
            <div class="p-4">
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Observations</dt>
                <dd class="mt-1 text-lg font-semibold text-slate-950">{{ number_format($statistics['count']) }}</dd>
            </div>
        </dl>

        <section class="mt-5 border border-slate-300 bg-white">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-300 px-4 py-3">
                <div class="flex items-center gap-2">
                    <h2 class="text-sm font-semibold text-slate-950">Filters</h2>
                    @if ($activeFilterCount > 0)
                        <span class="text-xs text-slate-500">{{ $activeFilterCount }} active</span>
                    @endif
                </div>
                @if ($activeFilterCount > 0)
                    <a
                        href="{{ route('internal.explorer', ['marque' => $selectedMarque->id, 'model' => $selectedModel->id, 'part' => $selectedPart->id]) }}"
                        class="text-xs font-medium text-slate-500 hover:text-slate-950"
                    >
                        Clear filters
                    </a>
                @endif
            </div>

            <form method="GET" action="{{ route('internal.explorer') }}" class="grid gap-3 p-4 md:grid-cols-[1fr_1fr_1.4fr_auto] md:items-end">
                <input type="hidden" name="marque" value="{{ $selectedMarque->id }}">
                <input type="hidden" name="model" value="{{ $selectedModel->id }}">
                <input type="hidden" name="part" value="{{ $selectedPart->id }}">

                <label>
                    <span class="mb-1.5 block text-xs font-medium text-slate-600">Production year</span>
                    <input
                        type="number"
                        name="year"
                        min="1900"
                        max="{{ now()->year + 1 }}"
                        value="{{ $filters['year'] ?? '' }}"
                        placeholder="Any year"
                        class="field"
                    >
                </label>

                <label>
                    <span class="mb-1.5 block text-xs font-medium text-slate-600">Type</span>
                    <select name="type" class="field">
                        <option value="">Any type</option>
                        @foreach (['original', 'adaptable', 'occasion'] as $type)
                            <option value="{{ $type }}" @selected(($filters['type'] ?? null) === $type)>{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                </label>

                <label>
                    <span class="mb-1.5 block text-xs font-medium text-slate-600">Source</span>
                    <select name="source_id" class="field">
                        <option value="">Any source</option>
                        @foreach ($sources as $source)
                            <option value="{{ $source->id }}" @selected((string) ($filters['source_id'] ?? '') === (string) $source->id)>
                                {{ $source->libelle }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <button type="submit" class="button-primary">Apply filters</button>
            </form>
        </section>

        <section class="mt-5 border border-slate-300 bg-white">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-300 px-4 py-3">
                <h2 class="text-sm font-semibold text-slate-950">Price observations</h2>
                <span class="text-xs text-slate-500">{{ number_format($statistics['count']) }} matching rows</span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-2.5">Years</th>
                            <th class="px-4 py-2.5">Type</th>
                            <th class="px-4 py-2.5 text-right">Price</th>
                            <th class="px-4 py-2.5">Source</th>
                            <th class="px-4 py-2.5">Observed</th>
                            <th class="px-4 py-2.5">Reference</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse ($observations as $observation)
                            <tr class="align-top hover:bg-slate-50/70">
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">
                                    {{ $observation->production_from ?? '—' }}–{{ $observation->production_to ?? '—' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 capitalize">{{ $observation->type }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-medium text-slate-950">
                                    {{ number_format((float) $observation->price, 2) }} MAD
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">{{ $observation->source?->libelle ?? '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $observation->observed_at?->format('Y-m-d') ?? '—' }}</td>
                                <td class="min-w-56 px-4 py-3 text-slate-600">
                                    @if ($observation->source_url)
                                        <a href="{{ $observation->source_url }}" target="_blank" rel="noreferrer" class="font-medium text-slate-900 underline underline-offset-2">
                                            {{ $observation->source_reference ?: 'Open source' }}
                                        </a>
                                    @elseif ($observation->source_reference)
                                        <span>{{ $observation->source_reference }}</span>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">
                                    No observations match the current filters.
                                    @if ($activeFilterCount > 0)
                                        <a
                                            href="{{ route('internal.explorer', ['marque' => $selectedMarque->id, 'model' => $selectedModel->id, 'part' => $selectedPart->id]) }}"
                                            class="ml-1 font-medium text-slate-900 underline underline-offset-2"
                                        >
                                            Clear filters
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($observations->hasPages())
                <div class="border-t border-slate-300 px-4 py-3">
                    {{ $observations->links() }}
                </div>
            @endif
        </section>
    @endif
@endsection
