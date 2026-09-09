@extends('internal.layout')

@section('title', 'Import #'.$import->id.' · Cabek AutoEstimate')

@section('content')
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <a href="{{ route('internal.ingest') }}" class="text-xs font-medium text-slate-500 hover:text-slate-900">← Back to import</a>
            <h1 class="mt-1 text-xl font-semibold text-slate-950">Import #{{ $import->id }}</h1>
            <p class="mt-1 break-all text-sm text-slate-600">{{ $import->original_filename }}</p>
        </div>
        <span id="status-badge" class="border border-slate-300 bg-white px-2.5 py-1 text-xs font-semibold uppercase tracking-wide text-slate-700">
            {{ $import->status }}
        </span>
    </div>

    @if ($errors->any())
        <div class="mb-5 border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-900">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <section class="border border-slate-300 bg-white">
        <div class="border-b border-slate-300 px-4 py-4 sm:px-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div id="phase" class="text-sm font-semibold text-slate-950">Preparing archive</div>
                    <p id="current-file" class="mt-1 text-xs text-slate-500">Waiting for the queue worker…</p>
                </div>
                <div id="progress-label" class="text-sm font-semibold tabular-nums text-slate-800">0%</div>
            </div>
            <div class="mt-3 h-2 overflow-hidden bg-slate-200">
                <div id="progress-bar" class="h-full bg-slate-900 transition-[width] duration-300" style="width: 0%"></div>
            </div>
        </div>

        <div class="grid grid-cols-2 divide-x divide-y divide-slate-200 sm:grid-cols-3 lg:grid-cols-6 lg:divide-y-0">
            <div class="p-4">
                <div class="text-[11px] font-medium uppercase tracking-wide text-slate-500">Files</div>
                <div id="files-value" class="mt-1 text-lg font-semibold tabular-nums text-slate-950">0 / 0</div>
            </div>
            <div class="p-4">
                <div class="text-[11px] font-medium uppercase tracking-wide text-slate-500">Validated</div>
                <div id="validated-value" class="mt-1 text-lg font-semibold tabular-nums text-slate-950">0</div>
            </div>
            <div class="p-4">
                <div class="text-[11px] font-medium uppercase tracking-wide text-slate-500">Observations</div>
                <div id="observations-value" class="mt-1 text-lg font-semibold tabular-nums text-slate-950">0 / 0</div>
            </div>
            <div class="p-4">
                <div class="text-[11px] font-medium uppercase tracking-wide text-slate-500">Inserted</div>
                <div id="inserted-value" class="mt-1 text-lg font-semibold tabular-nums text-emerald-700">0</div>
            </div>
            <div class="p-4">
                <div class="text-[11px] font-medium uppercase tracking-wide text-slate-500">Duplicates</div>
                <div id="duplicates-value" class="mt-1 text-lg font-semibold tabular-nums text-slate-950">0</div>
            </div>
            <div class="p-4">
                <div class="text-[11px] font-medium uppercase tracking-wide text-slate-500">Failed files</div>
                <div id="failed-value" class="mt-1 text-lg font-semibold tabular-nums text-red-700">0</div>
            </div>
        </div>
    </section>

    <div id="import-error" class="mt-5 hidden border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-900"></div>

    <form
        id="start-import-form"
        method="POST"
        action="{{ route('internal.imports.start', $import) }}"
        class="mt-5 {{ $import->status === 'ready' ? '' : 'hidden' }} border border-emerald-300 bg-emerald-50 p-4"
    >
        @csrf
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="text-sm font-semibold text-emerald-950">All JSON files are valid.</div>
                <p class="mt-1 text-xs text-emerald-800">This was a validation-only run. Start the queued import when ready.</p>
            </div>
            <button type="submit" class="button-primary shrink-0">Start import</button>
        </div>
    </form>

    <section class="mt-5 border border-slate-300 bg-white">
        <div class="border-b border-slate-300 px-4 py-3">
            <h2 class="text-sm font-semibold text-slate-950">JSON queue</h2>
            <p class="mt-0.5 text-xs text-slate-500">The table follows the current position instead of loading every file in a large archive.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-2.5">#</th>
                        <th class="px-4 py-2.5">File</th>
                        <th class="px-4 py-2.5">Status</th>
                        <th class="px-4 py-2.5 text-right">Observations</th>
                        <th class="px-4 py-2.5 text-right">Inserted</th>
                        <th class="px-4 py-2.5 text-right">Duplicates</th>
                    </tr>
                </thead>
                <tbody id="progress-files" class="divide-y divide-slate-200">
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-sm text-slate-500">Waiting for archive preparation…</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <section id="failed-files-section" class="mt-5 hidden border border-red-300 bg-white">
        <div class="border-b border-red-200 px-4 py-3">
            <h2 class="text-sm font-semibold text-red-950">Failed files</h2>
        </div>
        <div id="failed-files-list" class="divide-y divide-red-100"></div>
    </section>

    <script>
        const statusUrl = @json(route('internal.imports.status', $import));
        const pollMs = @json((int) config('observation_imports.status_poll_ms'));
        const numberFormatter = new Intl.NumberFormat();

        const elements = {
            statusBadge: document.getElementById('status-badge'),
            phase: document.getElementById('phase'),
            currentFile: document.getElementById('current-file'),
            progressLabel: document.getElementById('progress-label'),
            progressBar: document.getElementById('progress-bar'),
            files: document.getElementById('files-value'),
            validated: document.getElementById('validated-value'),
            observations: document.getElementById('observations-value'),
            inserted: document.getElementById('inserted-value'),
            duplicates: document.getElementById('duplicates-value'),
            failed: document.getElementById('failed-value'),
            error: document.getElementById('import-error'),
            startForm: document.getElementById('start-import-form'),
            progressFiles: document.getElementById('progress-files'),
            failedSection: document.getElementById('failed-files-section'),
            failedList: document.getElementById('failed-files-list'),
        };

        function formatNumber(value) {
            return numberFormatter.format(Number(value ?? 0));
        }

        function renderFiles(files) {
            elements.progressFiles.replaceChildren();

            if (!files.length) {
                const row = document.createElement('tr');
                const cell = document.createElement('td');
                cell.colSpan = 6;
                cell.className = 'px-4 py-6 text-center text-sm text-slate-500';
                cell.textContent = 'No JSON files discovered yet.';
                row.appendChild(cell);
                elements.progressFiles.appendChild(row);
                return;
            }

            for (const file of files) {
                const row = document.createElement('tr');
                row.className = file.status === 'importing' || file.status === 'validating' ? 'bg-amber-50' : '';

                const values = [
                    file.position,
                    file.filename,
                    file.status.replaceAll('_', ' '),
                    formatNumber(file.observations),
                    formatNumber(file.inserted),
                    formatNumber(file.duplicates),
                ];

                values.forEach((value, index) => {
                    const cell = document.createElement('td');
                    cell.className = index >= 3
                        ? 'whitespace-nowrap px-4 py-2.5 text-right tabular-nums'
                        : 'px-4 py-2.5';
                    cell.textContent = value;
                    row.appendChild(cell);
                });

                elements.progressFiles.appendChild(row);
            }
        }

        function renderFailedFiles(files) {
            elements.failedList.replaceChildren();
            elements.failedSection.classList.toggle('hidden', !files.length);

            for (const file of files) {
                const item = document.createElement('div');
                item.className = 'px-4 py-3 text-xs';

                const title = document.createElement('div');
                title.className = 'font-semibold text-red-950';
                title.textContent = `#${file.position} ${file.filename}`;

                const error = document.createElement('div');
                error.className = 'mt-1 text-red-800';
                error.textContent = file.error ?? 'Unknown validation error.';

                item.append(title, error);
                elements.failedList.appendChild(item);
            }
        }

        function applyStatus(data) {
            elements.statusBadge.textContent = data.status;
            elements.phase.textContent = data.phase;
            elements.progressLabel.textContent = `${data.progress}%`;
            elements.progressBar.style.width = `${data.progress}%`;
            elements.currentFile.textContent = data.current_file
                ? `Current file: ${data.current_file}`
                : data.status === 'completed'
                    ? 'All JSON files were processed.'
                    : data.status === 'ready'
                        ? 'Validation finished. Waiting for import to start.'
                        : 'Waiting for the next queued step…';
            elements.files.textContent = `${formatNumber(data.processed_files)} / ${formatNumber(data.total_files)}`;
            elements.validated.textContent = `${formatNumber(data.validated_files)} / ${formatNumber(data.total_files)}`;
            elements.observations.textContent = `${formatNumber(data.processed_observations)} / ${formatNumber(data.total_observations)}`;
            elements.inserted.textContent = formatNumber(data.inserted);
            elements.duplicates.textContent = formatNumber(data.duplicates);
            elements.failed.textContent = formatNumber(data.failed_files);
            elements.startForm.classList.toggle('hidden', !data.can_start);

            if (data.error) {
                elements.error.textContent = data.error;
                elements.error.classList.remove('hidden');
            } else {
                elements.error.textContent = '';
                elements.error.classList.add('hidden');
            }

            renderFiles(data.files ?? []);
            renderFailedFiles(data.failed_file_details ?? []);
        }

        async function refreshStatus() {
            try {
                const response = await fetch(statusUrl, {
                    headers: { 'Accept': 'application/json' },
                    cache: 'no-store',
                });

                if (!response.ok) {
                    throw new Error(`Status request failed with ${response.status}`);
                }

                const data = await response.json();
                applyStatus(data);

                if (!['completed', 'failed', 'ready'].includes(data.status)) {
                    window.setTimeout(refreshStatus, pollMs);
                }
            } catch (error) {
                elements.currentFile.textContent = 'Progress refresh failed. Retrying…';
                window.setTimeout(refreshStatus, Math.max(pollMs, 3000));
            }
        }

        refreshStatus();
    </script>
@endsection
