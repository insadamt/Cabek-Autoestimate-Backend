function initJsonIngestor(root) {
    const input = root.querySelector('[data-json-input]');
    const validateButton = root.querySelector('[data-validate]');
    const ingestButton = root.querySelector('[data-ingest]');
    const loadExampleButton = root.querySelector('[data-load-example]');
    const status = root.querySelector('[data-status]');
    const validationEmpty = root.querySelector('[data-validation-empty]');
    const validationResult = root.querySelector('[data-validation-result]');
    const previewEmpty = root.querySelector('[data-preview-empty]');
    const preview = root.querySelector('[data-preview]');
    const previewBody = root.querySelector('[data-preview-body]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    let validatedRaw = null;

    const setBusy = (button, busy, busyText, normalText) => {
        button.disabled = busy;
        button.textContent = busy ? busyText : normalText;
    };

    const invalidate = () => {
        validatedRaw = null;
        ingestButton.disabled = true;
        validationResult.classList.add('hidden');
        validationEmpty.classList.remove('hidden');
        preview.classList.add('hidden');
        previewEmpty.classList.remove('hidden');
        previewBody.replaceChildren();
        status.textContent = input.value.trim() ? 'Payload changed. Validate it before ingesting.' : 'Paste a JSON payload to begin.';
        status.className = 'text-sm text-slate-500';
    };

    const renderMessages = (items, success = false) => {
        validationResult.replaceChildren();
        validationResult.classList.remove('hidden');
        validationEmpty.classList.add('hidden');

        for (const message of items) {
            const row = document.createElement('div');
            row.className = success
                ? 'rounded-xl border border-emerald-400/15 bg-emerald-400/[0.06] px-4 py-3 text-sm text-emerald-200'
                : 'rounded-xl border border-rose-400/15 bg-rose-400/[0.06] px-4 py-3 text-sm text-rose-200';
            row.textContent = message;
            validationResult.append(row);
        }
    };

    const renderPreview = (observations) => {
        previewBody.replaceChildren();

        observations.slice(0, 50).forEach((observation, index) => {
            const row = document.createElement('tr');
            row.className = 'text-slate-300';

            const cells = [
                String(index + 1),
                `${observation.marque} · ${observation.model}`,
                observation.part,
                observation.type,
                `${Number(observation.price).toFixed(2)} MAD`,
                observation.source,
            ];

            cells.forEach((value, cellIndex) => {
                const cell = document.createElement('td');
                cell.className = cellIndex === 4
                    ? 'whitespace-nowrap px-4 py-3 font-semibold text-white'
                    : 'whitespace-nowrap px-4 py-3';
                cell.textContent = value;
                row.append(cell);
            });

            previewBody.append(row);
        });

        previewEmpty.classList.add('hidden');
        preview.classList.remove('hidden');
    };

    const parsePayload = () => {
        const raw = input.value.trim();
        if (!raw) throw new Error('Paste a JSON payload first.');

        try {
            return { raw, payload: JSON.parse(raw) };
        } catch (error) {
            throw new Error(`Invalid JSON syntax: ${error.message}`);
        }
    };

    const postJson = async (url, payload) => {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(payload),
        });

        const body = await response.json().catch(() => ({}));

        if (!response.ok) {
            const messages = body.errors
                ? Object.values(body.errors).flat()
                : [body.message || `Request failed with status ${response.status}.`];
            const error = new Error(messages[0]);
            error.messages = messages;
            throw error;
        }

        return body;
    };

    validateButton.addEventListener('click', async () => {
        let parsed;

        try {
            parsed = parsePayload();
        } catch (error) {
            renderMessages([error.message]);
            status.textContent = 'JSON syntax is invalid.';
            status.className = 'text-sm text-rose-300';
            return;
        }

        setBusy(validateButton, true, 'Validating…', 'Validate JSON');
        ingestButton.disabled = true;

        try {
            const result = await postJson(root.dataset.validateUrl, parsed.payload);
            const observations = result.data?.observations ?? [];
            const received = Number(result.data?.received ?? observations.length);

            validatedRaw = parsed.raw;
            ingestButton.disabled = false;
            renderMessages([
                'JSON syntax is valid.',
                `${received} observation${received === 1 ? '' : 's'} passed canonical backend validation.`,
                'Marques, parts and sources match the controlled database vocabulary.',
            ], true);
            renderPreview(observations);
            status.textContent = 'Validated. Review the preview, then ingest when ready.';
            status.className = 'text-sm text-emerald-300';
        } catch (error) {
            validatedRaw = null;
            renderMessages(error.messages || [error.message]);
            status.textContent = 'Backend validation failed.';
            status.className = 'text-sm text-rose-300';
        } finally {
            setBusy(validateButton, false, 'Validating…', 'Validate JSON');
            ingestButton.disabled = validatedRaw === null;
        }
    });

    ingestButton.addEventListener('click', async () => {
        let parsed;

        try {
            parsed = parsePayload();
        } catch (error) {
            renderMessages([error.message]);
            return;
        }

        if (validatedRaw !== parsed.raw) {
            invalidate();
            status.textContent = 'The payload changed after validation. Validate it again.';
            status.className = 'text-sm text-amber-300';
            return;
        }

        setBusy(ingestButton, true, 'Ingesting…', 'Ingest');
        validateButton.disabled = true;

        try {
            const result = await postJson(root.dataset.ingestUrl, parsed.payload);
            const data = result.data ?? {};

            renderMessages([
                `${Number(data.inserted ?? 0)} new observation${Number(data.inserted ?? 0) === 1 ? '' : 's'} inserted.`,
                `${Number(data.skipped_duplicates ?? 0)} duplicate${Number(data.skipped_duplicates ?? 0) === 1 ? '' : 's'} skipped.`,
                `${Number(data.received ?? 0)} observation${Number(data.received ?? 0) === 1 ? '' : 's'} processed in total.`,
            ], true);
            status.textContent = 'Ingestion completed successfully.';
            status.className = 'text-sm text-emerald-300';
            validatedRaw = null;
        } catch (error) {
            renderMessages(error.messages || [error.message]);
            status.textContent = 'Ingestion failed. No partial batch should be committed.';
            status.className = 'text-sm text-rose-300';
        } finally {
            validateButton.disabled = false;
            setBusy(ingestButton, false, 'Ingesting…', 'Ingest');
            ingestButton.disabled = true;
        }
    });

    loadExampleButton.addEventListener('click', () => {
        input.value = JSON.stringify({
            observations: [
                {
                    marque: 'Renault',
                    model: 'Clio',
                    part: 'Pare-choc Avant',
                    production_from: 2016,
                    production_to: 2019,
                    type: 'adaptable',
                    price: 850,
                    source: 'AtlasAuto',
                    source_reference: 'example-123456',
                    source_url: 'https://atlasauto.ma/example',
                    details: null,
                    observed_at: '2026-09-08 10:00:00',
                },
            ],
        }, null, 2);
        invalidate();
    });

    input.addEventListener('input', invalidate);
}

window.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-json-ingestor]').forEach(initJsonIngestor);
});
