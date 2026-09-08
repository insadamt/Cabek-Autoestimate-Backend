@extends('internal.layout')

@section('title', 'JSON Ingestor · Cabek AutoEstimate')

@section('content')
    <div class="mb-7">
        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Internal data ingestion</div>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-white">JSON Ingestor</h1>
        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-400">
            Paste AI-cleaned canonical JSON, validate it against the backend contract, review the parsed observations, then ingest it.
        </p>
    </div>

    <section
        data-json-ingestor
        data-validate-url="{{ route('internal.ingest.validate') }}"
        data-ingest-url="{{ route('internal.ingest.store') }}"
        class="grid gap-5 xl:grid-cols-[minmax(0,1.1fr)_minmax(380px,0.9fr)]"
    >
        <div class="rounded-2xl border border-white/10 bg-slate-900 p-5 sm:p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="font-semibold text-white">Canonical JSON</h2>
                    <p class="mt-1 text-xs text-slate-500">Only the canonical observations payload is accepted.</p>
                </div>
                <button type="button" data-load-example class="rounded-lg border border-white/10 px-3 py-1.5 text-xs font-medium text-slate-400 transition hover:bg-white/5 hover:text-white">
                    Load example
                </button>
            </div>

            <textarea
                data-json-input
                spellcheck="false"
                placeholder='{"observations":[...]}'
                class="mt-5 min-h-[520px] w-full resize-y rounded-2xl border border-white/10 bg-slate-950 p-4 font-mono text-sm leading-6 text-slate-200 outline-none placeholder:text-slate-700 focus:border-white/25"
            ></textarea>

            <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div data-status class="text-sm text-slate-500">Paste a JSON payload to begin.</div>
                <div class="flex gap-2">
                    <button type="button" data-validate class="rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm font-semibold text-slate-200 transition hover:bg-white/10">
                        Validate JSON
                    </button>
                    <button type="button" data-ingest disabled class="rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-slate-200 disabled:cursor-not-allowed disabled:opacity-30">
                        Ingest
                    </button>
                </div>
            </div>
        </div>

        <div class="space-y-5">
            <div class="rounded-2xl border border-white/10 bg-slate-900 p-5 sm:p-6">
                <h2 class="font-semibold text-white">Validation</h2>
                <div data-validation-empty class="mt-5 rounded-xl border border-dashed border-white/10 px-4 py-8 text-center text-sm text-slate-600">
                    Validation results will appear here.
                </div>
                <div data-validation-result class="mt-5 hidden space-y-3"></div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-white/10 bg-slate-900">
                <div class="border-b border-white/10 px-5 py-4 sm:px-6">
                    <h2 class="font-semibold text-white">Preview</h2>
                    <p class="mt-1 text-xs text-slate-500">First 50 observations are shown after validation.</p>
                </div>

                <div data-preview-empty class="px-5 py-12 text-center text-sm text-slate-600 sm:px-6">
                    No validated payload yet.
                </div>

                <div data-preview class="hidden overflow-x-auto">
                    <table class="min-w-full divide-y divide-white/10 text-left text-xs">
                        <thead class="bg-slate-950/60 uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3 font-medium">#</th>
                                <th class="px-4 py-3 font-medium">Vehicle</th>
                                <th class="px-4 py-3 font-medium">Part</th>
                                <th class="px-4 py-3 font-medium">Type</th>
                                <th class="px-4 py-3 font-medium">Price</th>
                                <th class="px-4 py-3 font-medium">Source</th>
                            </tr>
                        </thead>
                        <tbody data-preview-body class="divide-y divide-white/[0.06]"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
@endsection
