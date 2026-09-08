function normalizeSearch(value) {
    return String(value ?? '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim();
}

function initPriceExplorer(root) {
    const explorerUrl = root.dataset.explorerUrl;
    const modelsBase = root.dataset.modelsBase;
    const partsBase = root.dataset.partsBase;

    const state = {
        marque: root.dataset.selectedMarqueId
            ? { id: root.dataset.selectedMarqueId, name: root.dataset.selectedMarqueName }
            : null,
        model: root.dataset.selectedModelId
            ? { id: root.dataset.selectedModelId, name: root.dataset.selectedModelName }
            : null,
    };

    const steps = [...root.querySelectorAll('[data-step]')];
    const progressItems = [...root.querySelectorAll('[data-progress]')];
    const inputs = [...root.querySelectorAll('[data-search-input]')];

    const showStep = (stepName) => {
        steps.forEach((step) => {
            step.classList.toggle('hidden', step.dataset.step !== stepName);
        });

        const order = ['marque', 'model', 'part'];
        const activeIndex = order.indexOf(stepName);

        progressItems.forEach((item) => {
            const itemIndex = order.indexOf(item.dataset.progress);
            const active = itemIndex === activeIndex;
            const complete = itemIndex < activeIndex;

            item.classList.toggle('bg-white', active);
            item.classList.toggle('text-slate-950', active);
            item.classList.toggle('bg-white/10', complete && !active);
            item.classList.toggle('text-slate-300', complete && !active);
            item.classList.toggle('text-slate-500', !active && !complete);
        });

        const input = root.querySelector(`[data-search-input="${stepName}"]`);
        if (input) {
            input.value = '';
            filterChoices(stepName, '');
            window.setTimeout(() => input.focus(), 30);
        }
    };

    const updateLabels = () => {
        root.querySelectorAll('[data-selected-marque-label]').forEach((element) => {
            element.textContent = state.marque?.name ?? '';
        });

        root.querySelectorAll('[data-selected-model-label]').forEach((element) => {
            element.textContent = state.model?.name ?? '';
        });
    };

    const setLoading = (kind, loading) => {
        const loadingElement = root.querySelector(`[data-loading="${kind}"]`);
        if (loadingElement) {
            loadingElement.classList.toggle('hidden', !loading);
        }
    };

    const setEmpty = (kind, empty) => {
        const emptyElement = root.querySelector(`[data-empty-state="${kind}"]`);
        if (emptyElement) {
            emptyElement.classList.toggle('hidden', !empty);
        }
    };

    const renderChoices = (kind, items) => {
        const list = root.querySelector(`[data-choice-list="${kind}"]`);
        list.replaceChildren();

        for (const item of items) {
            const button = document.createElement('button');
            button.type = 'button';
            button.dataset.choice = kind;
            button.dataset.id = item.id;
            button.dataset.name = item.libelle;
            button.dataset.searchText = item.libelle;
            button.className = 'group flex items-center justify-between rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3 text-left transition hover:border-white/20 hover:bg-white/[0.07]';

            const name = document.createElement('span');
            name.className = 'font-medium text-slate-200 group-hover:text-white';
            name.textContent = item.libelle;

            const count = document.createElement('span');
            count.className = 'text-xs text-slate-600';

            if (kind === 'model') {
                const observations = Number(item.price_observations_count ?? 0);
                count.textContent = `${observations} observation${observations === 1 ? '' : 's'}`;
            } else {
                const observations = Number(item.observations_count ?? 0);
                count.textContent = `${observations} observation${observations === 1 ? '' : 's'}`;
            }

            button.append(name, count);
            list.append(button);
        }

        setEmpty(kind, items.length === 0);
    };

    const loadModels = async () => {
        if (!state.marque) return;

        setLoading('model', true);
        setEmpty('model', false);

        try {
            const response = await fetch(`${modelsBase}/${state.marque.id}/models`, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) throw new Error('Unable to load models.');

            const payload = await response.json();
            renderChoices('model', payload.data ?? []);
        } catch (error) {
            renderChoices('model', []);
            const empty = root.querySelector('[data-empty-state="model"]');
            if (empty) empty.textContent = error.message;
        } finally {
            setLoading('model', false);
        }
    };

    const loadParts = async () => {
        if (!state.model) return;

        setLoading('part', true);
        setEmpty('part', false);

        try {
            const response = await fetch(`${partsBase}/${state.model.id}/parts`, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) throw new Error('Unable to load observed parts.');

            const payload = await response.json();
            renderChoices('part', payload.data ?? []);
        } catch (error) {
            renderChoices('part', []);
            const empty = root.querySelector('[data-empty-state="part"]');
            if (empty) empty.textContent = error.message;
        } finally {
            setLoading('part', false);
        }
    };

    const filterChoices = (kind, query) => {
        const list = root.querySelector(`[data-choice-list="${kind}"]`);
        if (!list) return;

        const normalizedQuery = normalizeSearch(query);
        const choices = [...list.querySelectorAll(`[data-choice="${kind}"]`)];
        let visible = 0;

        for (const choice of choices) {
            const text = normalizeSearch(choice.dataset.searchText || choice.dataset.name);
            const matches = text.includes(normalizedQuery);
            choice.classList.toggle('hidden', !matches);
            if (matches) visible += 1;
        }

        setEmpty(kind, visible === 0 && choices.length > 0);
    };

    root.addEventListener('click', async (event) => {
        const choice = event.target.closest('[data-choice]');
        if (choice && root.contains(choice)) {
            const kind = choice.dataset.choice;

            if (kind === 'marque') {
                state.marque = { id: choice.dataset.id, name: choice.dataset.name };
                state.model = null;
                updateLabels();
                showStep('model');
                await loadModels();
                return;
            }

            if (kind === 'model') {
                state.model = { id: choice.dataset.id, name: choice.dataset.name };
                updateLabels();
                showStep('part');
                await loadParts();
                return;
            }

            if (kind === 'part' && state.marque && state.model) {
                const url = new URL(explorerUrl, window.location.origin);
                url.searchParams.set('marque', state.marque.id);
                url.searchParams.set('model', state.model.id);
                url.searchParams.set('part', choice.dataset.id);
                window.location.assign(url.toString());
                return;
            }
        }

        const back = event.target.closest('[data-back]');
        if (!back || !root.contains(back)) return;

        if (back.dataset.back === 'marque') {
            showStep('marque');
            return;
        }

        if (back.dataset.back === 'model' && state.marque) {
            showStep('model');
            await loadModels();
        }
    });

    inputs.forEach((input) => {
        input.addEventListener('input', () => {
            filterChoices(input.dataset.searchInput, input.value);
        });
    });

    updateLabels();

    const startStep = root.dataset.startStep || 'marque';
    showStep(startStep);

    if (startStep === 'model' && state.marque) {
        loadModels();
    } else if (startStep === 'part' && state.model) {
        loadParts();
    }
}

window.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-price-explorer]').forEach(initPriceExplorer);
});
