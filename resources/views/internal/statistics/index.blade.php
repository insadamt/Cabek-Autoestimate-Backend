@extends('internal.layout')

@section('title', 'Statistics · Cabek AutoEstimate')

@section('content')
    @php
        $maxSourceCount = max(1, (int) ($sourceStats->max('observations_count') ?? 0));
        $maxMarqueCount = max(1, (int) ($topMarques->max('observations_count') ?? 0));
        $maxModelCount = max(1, (int) ($topModels->max('observations_count') ?? 0));
        $maxPartCount = max(1, (int) ($topParts->max('observations_count') ?? 0));
    @endphp

    <div class="mb-5">
        <h1 class="text-xl font-semibold text-slate-950">Statistics</h1>
        <p class="mt-1 text-sm text-slate-600">Current coverage and activity of the pricing database.</p>
    </div>

    <section class="border border-slate-300 bg-white">
        <div class="grid grid-cols-2 divide-x divide-y divide-slate-300 sm:grid-cols-3 xl:grid-cols-5 xl:divide-y-0">
            @foreach ([
                'marques' => 'Marques',
                'models' => 'Models',
                'parts' => 'Parts',
                'sources' => 'Sources',
                'observations' => 'Observations',
            ] as $key => $label)
                <div class="p-4">
                    <div class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ $label }}</div>
                    <div class="mt-1 text-2xl font-semibold tracking-tight text-slate-950">{{ number_format($summary[$key]) }}</div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="mt-5 border border-slate-300 bg-white">
        <div class="border-b border-slate-300 px-4 py-3">
            <h2 class="text-sm font-semibold text-slate-950">Import activity</h2>
            <p class="mt-0.5 text-xs text-slate-500">Observations added to the database by created date.</p>
        </div>
        <dl class="grid sm:grid-cols-3 sm:divide-x sm:divide-slate-300">
            <div class="border-b border-slate-300 p-4 sm:border-b-0">
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Today</dt>
                <dd class="mt-1 text-xl font-semibold text-slate-950">{{ number_format($activity['today']) }}</dd>
            </div>
            <div class="border-b border-slate-300 p-4 sm:border-b-0">
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">This week</dt>
                <dd class="mt-1 text-xl font-semibold text-slate-950">{{ number_format($activity['week']) }}</dd>
            </div>
            <div class="p-4">
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">This month</dt>
                <dd class="mt-1 text-xl font-semibold text-slate-950">{{ number_format($activity['month']) }}</dd>
            </div>
        </dl>
    </section>

    <div class="mt-5 grid gap-5 xl:grid-cols-2">
        <section class="border border-slate-300 bg-white">
            <div class="border-b border-slate-300 px-4 py-3">
                <h2 class="text-sm font-semibold text-slate-950">Observations by type</h2>
            </div>
            <div class="divide-y divide-slate-200">
                @foreach ($typeCounts as $type => $count)
                    <div class="px-4 py-3">
                        <div class="flex items-center justify-between gap-4 text-sm">
                            <span class="font-medium capitalize text-slate-800">{{ $type }}</span>
                            <span class="tabular-nums text-slate-600">
                                {{ number_format($count) }}
                                <span class="text-xs text-slate-400">
                                    {{ number_format($summary['observations'] > 0 ? ($count / $summary['observations']) * 100 : 0, 1) }}%
                                </span>
                            </span>
                        </div>
                        <div class="mt-2 h-1.5 overflow-hidden bg-slate-100">
                            <div
                                class="h-full bg-slate-700"
                                style="width: {{ min(100, $summary['observations'] > 0 ? ($count / $summary['observations']) * 100 : 0) }}%"
                            ></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="border border-slate-300 bg-white">
            <div class="border-b border-slate-300 px-4 py-3">
                <h2 class="text-sm font-semibold text-slate-950">Observations by source</h2>
            </div>
            <div class="max-h-[330px] divide-y divide-slate-200 overflow-y-auto">
                @foreach ($sourceStats as $source)
                    <div class="px-4 py-3">
                        <div class="flex items-center justify-between gap-4 text-sm">
                            <span class="truncate font-medium text-slate-800">{{ $source->libelle }}</span>
                            <span class="shrink-0 tabular-nums text-slate-600">
                                {{ number_format($source->observations_count) }}
                                <span class="text-xs text-slate-400">
                                    {{ number_format($summary['observations'] > 0 ? ((int) $source->observations_count / $summary['observations']) * 100 : 0, 1) }}%
                                </span>
                            </span>
                        </div>
                        <div class="mt-2 h-1.5 overflow-hidden bg-slate-100">
                            <div
                                class="h-full bg-slate-700"
                                style="width: {{ min(100, ((int) $source->observations_count / $maxSourceCount) * 100) }}%"
                            ></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    <div class="mt-5 grid gap-5 xl:grid-cols-3">
        <section class="border border-slate-300 bg-white">
            <div class="flex items-center justify-between border-b border-slate-300 px-4 py-3">
                <h2 class="text-sm font-semibold text-slate-950">Top marques</h2>
                <span class="text-xs text-slate-500">By observations</span>
            </div>
            <div class="divide-y divide-slate-200">
                @foreach ($topMarques as $index => $marque)
                    <div class="px-4 py-2.5">
                        <div class="flex items-center gap-3 text-sm">
                            <span class="w-5 shrink-0 text-right tabular-nums text-xs text-slate-400">{{ $index + 1 }}</span>
                            <span class="min-w-0 flex-1 truncate font-medium text-slate-800">{{ $marque->libelle }}</span>
                            <span class="shrink-0 tabular-nums text-slate-600">{{ number_format($marque->observations_count) }}</span>
                        </div>
                        <div class="ml-8 mt-1.5 h-1 bg-slate-100">
                            <div
                                class="h-full bg-slate-600"
                                style="width: {{ min(100, ((int) $marque->observations_count / $maxMarqueCount) * 100) }}%"
                            ></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="border border-slate-300 bg-white">
            <div class="flex items-center justify-between border-b border-slate-300 px-4 py-3">
                <h2 class="text-sm font-semibold text-slate-950">Top models</h2>
                <span class="text-xs text-slate-500">By observations</span>
            </div>
            <div class="divide-y divide-slate-200">
                @foreach ($topModels as $index => $model)
                    <div class="px-4 py-2.5">
                        <div class="flex items-center gap-3 text-sm">
                            <span class="w-5 shrink-0 text-right tabular-nums text-xs text-slate-400">{{ $index + 1 }}</span>
                            <div class="min-w-0 flex-1">
                                <div class="truncate font-medium text-slate-800">{{ $model->libelle }}</div>
                                <div class="truncate text-xs text-slate-400">{{ $model->marque_libelle }}</div>
                            </div>
                            <span class="shrink-0 tabular-nums text-slate-600">{{ number_format($model->observations_count) }}</span>
                        </div>
                        <div class="ml-8 mt-1.5 h-1 bg-slate-100">
                            <div
                                class="h-full bg-slate-600"
                                style="width: {{ min(100, ((int) $model->observations_count / $maxModelCount) * 100) }}%"
                            ></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="border border-slate-300 bg-white">
            <div class="flex items-center justify-between border-b border-slate-300 px-4 py-3">
                <h2 class="text-sm font-semibold text-slate-950">Top parts</h2>
                <span class="text-xs text-slate-500">By observations</span>
            </div>
            <div class="divide-y divide-slate-200">
                @foreach ($topParts as $index => $part)
                    <div class="px-4 py-2.5">
                        <div class="flex items-center gap-3 text-sm">
                            <span class="w-5 shrink-0 text-right tabular-nums text-xs text-slate-400">{{ $index + 1 }}</span>
                            <span class="min-w-0 flex-1 truncate font-medium text-slate-800">{{ $part->libelle }}</span>
                            <span class="shrink-0 tabular-nums text-slate-600">{{ number_format($part->observations_count) }}</span>
                        </div>
                        <div class="ml-8 mt-1.5 h-1 bg-slate-100">
                            <div
                                class="h-full bg-slate-600"
                                style="width: {{ min(100, ((int) $part->observations_count / $maxPartCount) * 100) }}%"
                            ></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    <section class="mt-5 border border-slate-300 bg-white">
        <div class="border-b border-slate-300 px-4 py-3">
            <h2 class="text-sm font-semibold text-slate-950">Coverage</h2>
        </div>

        <div class="grid lg:grid-cols-2 lg:divide-x lg:divide-slate-300">
            <div>
                <div class="flex items-center justify-between gap-4 border-b border-slate-200 bg-slate-50 px-4 py-3">
                    <div>
                        <div class="text-sm font-medium text-slate-800">Models with zero observations</div>
                        <div class="mt-0.5 text-xs text-slate-500">First 10 models are listed below.</div>
                    </div>
                    <span class="text-xl font-semibold tabular-nums text-slate-950">{{ number_format($coverage['models_without_observations']) }}</span>
                </div>
                <div class="divide-y divide-slate-200">
                    @foreach ($modelsWithoutObservationSample as $model)
                        <div class="flex items-center justify-between gap-4 px-4 py-2.5 text-sm">
                            <span class="truncate font-medium text-slate-800">{{ $model->libelle }}</span>
                            <span class="shrink-0 text-xs text-slate-500">{{ $model->marque?->libelle }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="border-t border-slate-300 lg:border-t-0">
                <div class="flex items-center justify-between gap-4 border-b border-slate-200 bg-slate-50 px-4 py-3">
                    <div>
                        <div class="text-sm font-medium text-slate-800">Parts with fewer than {{ $coverage['low_observation_threshold'] }} observations</div>
                        <div class="mt-0.5 text-xs text-slate-500">Lowest-covered parts are listed first.</div>
                    </div>
                    <span class="text-xl font-semibold tabular-nums text-slate-950">{{ number_format($coverage['parts_with_few_observations']) }}</span>
                </div>
                <div class="divide-y divide-slate-200">
                    @foreach ($lowObservationPartSample as $part)
                        <div class="flex items-center justify-between gap-4 px-4 py-2.5 text-sm">
                            <span class="truncate font-medium text-slate-800">{{ $part->libelle }}</span>
                            <span class="shrink-0 tabular-nums text-slate-500">{{ number_format($part->price_observations_count) }} observations</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endsection
