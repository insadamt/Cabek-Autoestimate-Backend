@extends('internal.layout')

@section('title', 'JSON Import · Cabek AutoEstimate')

@section('content')
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-slate-950">JSON Import</h1>
            <p class="mt-1 text-sm text-slate-600">Validate and import historical part-price observations.</p>
        </div>
        <div class="text-xs text-slate-500">Up to 500 observations · 5 MB maximum</div>
    </div>

    @if ($errors->any())
        <div class="mb-5 border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-900">
            <div class="font-medium">Nothing was imported.</div>
            <p class="mt-1 text-xs text-red-700">Fix the validation errors below and submit the payload again.</p>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-xs leading-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('ingest_result'))
        @php($result = session('ingest_result'))
        <div class="mb-5 border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-950">
            <div class="font-medium">Import completed.</div>
            <div class="mt-2 flex flex-wrap gap-x-6 gap-y-1 text-xs text-emerald-800">
                <span>Received <strong>{{ $result['received'] }}</strong></span>
                <span>Inserted <strong>{{ $result['inserted'] }}</strong></span>
                <span>Duplicates skipped <strong>{{ $result['skipped_duplicates'] }}</strong></span>
            </div>
        </div>
    @endif

    @if (session('ingest_preview'))
        @php($preview = session('ingest_preview'))
        <div class="mb-5 border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-950">
            <div class="font-medium">Payload is valid — {{ $preview['received'] }} observations.</div>
            @if (($preview['source'] ?? null) === 'file')
                <p class="mt-1 text-xs text-emerald-800">Re-select the same file below, then click Import observations. Browsers do not retain file selections after validation.</p>
            @else
                <p class="mt-1 text-xs text-emerald-800">The validated pasted JSON is still in the form below. You can import it now.</p>
            @endif
        </div>
    @endif

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
        <section class="border border-slate-300 bg-white">
            <div class="border-b border-slate-300 px-4 py-3">
                <h2 class="text-sm font-semibold text-slate-950">Payload</h2>
                <p class="mt-0.5 text-xs text-slate-500">All rows are validated before anything is written to the database.</p>
            </div>

            <form method="POST" action="{{ route('internal.ingest.store') }}" enctype="multipart/form-data" class="space-y-5 p-4 sm:p-5">
                @csrf

                <div>
                    <label for="json_file" class="mb-1.5 block text-sm font-medium text-slate-800">Upload a JSON file</label>
                    <input
                        id="json_file"
                        name="json_file"
                        type="file"
                        accept="application/json,.json"
                        class="block w-full rounded-md border border-slate-300 bg-white text-sm text-slate-700 file:mr-4 file:border-0 file:border-r file:border-slate-300 file:bg-slate-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-slate-700"
                    >
                    <p class="mt-1.5 text-xs text-slate-500">Only .json files are accepted. If a file is selected, it takes priority over pasted JSON.</p>
                    @error('json_file')
                        <p class="mt-1.5 text-xs font-medium text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-3 text-xs uppercase tracking-wide text-slate-400">
                    <span class="h-px flex-1 bg-slate-200"></span>
                    or
                    <span class="h-px flex-1 bg-slate-200"></span>
                </div>

                <div>
                    <label for="json_payload" class="mb-1.5 block text-sm font-medium text-slate-800">Paste JSON</label>
                    <textarea
                        id="json_payload"
                        name="json_payload"
                        rows="14"
                        spellcheck="false"
                        class="field font-mono text-xs leading-5"
                        placeholder='{"observations": [...]}'
                    >{{ old('json_payload') }}</textarea>
                    @error('json_payload')
                        <p class="mt-1.5 text-xs font-medium text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-col gap-3 border-t border-slate-200 pt-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="max-w-xl text-xs leading-5 text-slate-500">
                        Import is safe to submit directly: validation runs first, and invalid payloads are rejected before insertion.
                    </p>
                    <div class="flex shrink-0 flex-wrap gap-2">
                        <button
                            type="submit"
                            formaction="{{ route('internal.ingest.validate') }}"
                            class="button-secondary"
                        >
                            Validate only
                        </button>
                        <button type="submit" class="button-primary">
                            Import observations
                        </button>
                    </div>
                </div>
            </form>
        </section>

        <aside class="space-y-3">
            <details class="border border-slate-300 bg-white" open>
                <summary class="cursor-pointer select-none px-4 py-3 text-sm font-semibold text-slate-950">Expected JSON format</summary>
                <pre class="overflow-x-auto border-t border-slate-200 p-4 text-xs leading-5 text-slate-700"><code>{
  "observations": [
    {
      "marque": "Renault",
      "model": "Clio",
      "part": "Pare-choc Avant",
      "production_from": 2012,
      "production_to": 2016,
      "type": "adaptable",
      "price": 1200,
      "source": "Pieces Auto Maroc",
      "source_reference": null,
      "source_url": null,
      "details": null,
      "observed_at": "2026-09-08 12:00:00"
    }
  ]
}</code></pre>
            </details>

            <details class="border border-slate-300 bg-white">
                <summary class="cursor-pointer select-none px-4 py-3 text-sm font-semibold text-slate-950">Validation rules</summary>
                <div class="border-t border-slate-200 p-4 text-xs leading-5 text-slate-600">
                    <ul class="list-disc space-y-1.5 pl-5">
                        <li>The root object may contain only <code>observations</code>.</li>
                        <li>Marque, model, part, and source must already exist in reference data.</li>
                        <li>The model must belong to the submitted marque.</li>
                        <li>Type must be original, adaptable, or occasion.</li>
                        <li>Price must be greater than zero.</li>
                        <li>Production years are optional, but the end year cannot precede the start year.</li>
                        <li><code>observed_at</code> must use <code>Y-m-d H:i:s</code> and cannot be in the future.</li>
                    </ul>
                </div>
            </details>
        </aside>
    </div>

    @if (session('ingest_preview'))
        @php($preview = session('ingest_preview'))
        <section class="mt-5 border border-slate-300 bg-white">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-300 px-4 py-3">
                <div>
                    <h2 class="text-sm font-semibold text-slate-950">Validation preview</h2>
                    <p class="mt-0.5 text-xs text-slate-500">
                        Showing {{ count($preview['observations']) }} of {{ $preview['received'] }} valid observations{{ ($preview['truncated'] ?? false) ? '.' : '' }}
                    </p>
                </div>
                <span class="text-xs font-medium text-emerald-700">Valid</span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-2.5">Marque</th>
                            <th class="px-4 py-2.5">Model</th>
                            <th class="px-4 py-2.5">Part</th>
                            <th class="px-4 py-2.5">Years</th>
                            <th class="px-4 py-2.5">Type</th>
                            <th class="px-4 py-2.5 text-right">Price</th>
                            <th class="px-4 py-2.5">Source</th>
                            <th class="px-4 py-2.5">Observed</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach ($preview['observations'] as $observation)
                            <tr class="hover:bg-slate-50/70">
                                <td class="whitespace-nowrap px-4 py-2.5">{{ $observation['marque'] }}</td>
                                <td class="whitespace-nowrap px-4 py-2.5">{{ $observation['model'] }}</td>
                                <td class="min-w-56 px-4 py-2.5">{{ $observation['part'] }}</td>
                                <td class="whitespace-nowrap px-4 py-2.5 text-slate-600">
                                    {{ $observation['production_from'] ?? '—' }}–{{ $observation['production_to'] ?? '—' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-2.5 capitalize">{{ $observation['type'] }}</td>
                                <td class="whitespace-nowrap px-4 py-2.5 text-right font-medium">{{ number_format((float) $observation['price'], 2) }} MAD</td>
                                <td class="whitespace-nowrap px-4 py-2.5">{{ $observation['source'] }}</td>
                                <td class="whitespace-nowrap px-4 py-2.5 text-slate-600">{{ $observation['observed_at'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
@endsection
