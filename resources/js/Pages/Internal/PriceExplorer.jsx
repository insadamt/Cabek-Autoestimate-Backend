import { Head, Link, router } from '@inertiajs/react';
import { useMemo, useState } from 'react';

import InternalLayout from '../../Layouts/InternalLayout';

const explorerPath = '/internal/explorer';

export default function PriceExplorer({
    marques = [],
    models = [],
    parts = [],
    sources = [],
    selected = {},
    filters = {},
    statistics = null,
    observations = null,
    maxYear,
}) {
    const marque = selected.marque ?? null;
    const model = selected.model ?? null;
    const part = selected.part ?? null;
    const [year, setYear] = useState(filters.year ?? '');
    const [type, setType] = useState(filters.type ?? '');
    const [sourceId, setSourceId] = useState(filters.source_id ?? '');

    const navigate = (params) => {
        router.get(explorerPath, params, {
            preserveScroll: false,
            preserveState: false,
            replace: true,
        });
    };

    const submitFilters = (event) => {
        event.preventDefault();
        navigate(compactParams({ marque: marque.id, model: model.id, part: part.id, year, type, source_id: sourceId }));
    };

    return (
        <>
            <Head title="Price Explorer" />

            <div className="mb-6 flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <div className="flex items-center gap-2 text-[11px] font-bold uppercase tracking-[0.18em] text-teal-700">
                        <span className="size-1.5 rounded-full bg-teal-600" />
                        Market pricing workspace
                    </div>
                    <h1 className="mt-2 text-3xl font-semibold tracking-[-0.03em] text-slate-950 sm:text-4xl">Price Explorer</h1>
                    <p className="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                        Navigate canonical vehicle data and inspect the evidence behind each estimated replacement-part price.
                    </p>
                </div>

                {part ? (
                    <button
                        type="button"
                        onClick={() => navigate({})}
                        className="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-400 hover:text-slate-950"
                    >
                        + New search
                    </button>
                ) : null}
            </div>

            {!marque ? (
                <SelectionPanel
                    step={1}
                    title="Select a marque"
                    description="Start from the canonical manufacturer list."
                    placeholder="Search marques…"
                    items={marques}
                    count={(item) => `${item.models_count} models`}
                    onSelect={(item) => navigate({ marque: item.id })}
                />
            ) : !model ? (
                <SelectionPanel
                    step={2}
                    context={marque.libelle}
                    title="Select a model"
                    description="Choose the vehicle model whose market evidence you want to inspect."
                    placeholder="Search models…"
                    items={models}
                    count={(item) => `${item.price_observations_count} observations`}
                    onSelect={(item) => navigate({ marque: marque.id, model: item.id })}
                    onBack={() => navigate({})}
                />
            ) : !part ? (
                <SelectionPanel
                    step={3}
                    context={`${marque.libelle} / ${model.libelle}`}
                    title="Select an observed part"
                    description="Only parts with pricing observations for this vehicle are shown."
                    placeholder="Search parts…"
                    items={parts}
                    count={(item) => `${item.observations_count} observations`}
                    onSelect={(item) => navigate({ marque: marque.id, model: model.id, part: item.id })}
                    onBack={() => navigate({ marque: marque.id })}
                />
            ) : (
                <div className="space-y-5">
                    <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                        <div className="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <div className="flex flex-wrap items-center gap-2 text-xs font-semibold text-slate-400">
                                    <span>{marque.libelle}</span><span>/</span><span>{model.libelle}</span><span>/</span><span className="text-teal-700">{part.libelle}</span>
                                </div>
                                <h2 className="mt-3 text-2xl font-semibold tracking-tight text-slate-950">{part.libelle}</h2>
                                <p className="mt-1 text-sm text-slate-500">{marque.libelle} · {model.libelle}</p>
                            </div>
                            <div className="flex flex-wrap gap-2">
                                <button onClick={() => navigate({ marque: marque.id, model: model.id })} className="rounded-xl border border-slate-300 px-3.5 py-2 text-sm font-semibold text-slate-600 hover:text-slate-950">Change part</button>
                                <button onClick={() => navigate({ marque: marque.id })} className="rounded-xl border border-slate-300 px-3.5 py-2 text-sm font-semibold text-slate-600 hover:text-slate-950">Change model</button>
                            </div>
                        </div>
                    </section>

                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <StatCard label="Market estimate" value={formatMoney(statistics?.median)} detail="Median · primary estimate" primary />
                        <StatCard label="Average" value={formatMoney(statistics?.average)} detail="Mean of matching prices" />
                        <StatCard label="Observed range" value={formatRange(statistics?.minimum, statistics?.maximum)} detail="Minimum to maximum" />
                        <StatCard label="Evidence" value={statistics?.count ?? 0} detail={evidenceLabel(statistics?.count ?? 0)} />
                    </div>

                    <section className="grid gap-4 xl:grid-cols-[minmax(0,1fr)_360px]">
                        <div className="rounded-2xl border border-teal-200 bg-teal-50 p-5 shadow-sm sm:p-6">
                            <div className="text-[11px] font-bold uppercase tracking-[0.17em] text-teal-700">Estimate basis</div>
                            <p className="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                                The estimate is calculated from matching historical market observations. Filters narrow the evidence set by production year, market type, and source without changing stored prices.
                            </p>
                        </div>

                        <form onSubmit={submitFilters} className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <div className="mb-4 flex items-center justify-between">
                                <div>
                                    <div className="text-sm font-semibold text-slate-950">Evidence filters</div>
                                    <div className="mt-0.5 text-xs text-slate-500">Refine matching observations</div>
                                </div>
                                <button type="button" onClick={() => navigate({ marque: marque.id, model: model.id, part: part.id })} className="text-xs font-semibold text-slate-500 hover:text-slate-950">Reset</button>
                            </div>
                            <div className="grid gap-3 sm:grid-cols-3 xl:grid-cols-1">
                                <Field label="Production year">
                                    <input type="number" min="1900" max={maxYear} value={year} onChange={(event) => setYear(event.target.value)} placeholder="All years" className="field" />
                                </Field>
                                <Field label="Type">
                                    <select value={type} onChange={(event) => setType(event.target.value)} className="field">
                                        <option value="">All types</option>
                                        <option value="original">Original</option>
                                        <option value="adaptable">Adaptable</option>
                                        <option value="occasion">Occasion</option>
                                    </select>
                                </Field>
                                <Field label="Source">
                                    <select value={sourceId} onChange={(event) => setSourceId(event.target.value)} className="field">
                                        <option value="">All sources</option>
                                        {sources.map((source) => <option key={source.id} value={source.id}>{source.libelle}</option>)}
                                    </select>
                                </Field>
                            </div>
                            <button className="mt-4 w-full rounded-xl bg-teal-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-teal-800">Apply filters</button>
                        </form>
                    </section>

                    <ObservationTable observations={observations} />
                </div>
            )}
        </>
    );
}

PriceExplorer.layout = (page) => <InternalLayout>{page}</InternalLayout>;

function SelectionPanel({ step, context, title, description, placeholder, items, count, onSelect, onBack }) {
    const [query, setQuery] = useState('');
    const filtered = useMemo(() => {
        const normalized = query.trim().toLocaleLowerCase();
        return normalized ? items.filter((item) => item.libelle.toLocaleLowerCase().includes(normalized)) : items;
    }, [items, query]);

    return (
        <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div className="flex items-center gap-2 border-b border-slate-200 bg-slate-50 px-5 py-4 text-xs font-semibold text-slate-400 sm:px-6">
                {[1, 2, 3].map((number) => (
                    <div key={number} className="flex items-center gap-2">
                        <span className={`grid size-7 place-items-center rounded-full ${number === step ? 'bg-teal-700 text-white' : number < step ? 'bg-teal-100 text-teal-800' : 'bg-white text-slate-400 ring-1 ring-slate-200'}`}>{number}</span>
                        <span className={number === step ? 'text-slate-900' : ''}>{['Marque', 'Model', 'Part'][number - 1]}</span>
                        {number < 3 ? <span className="text-slate-300">/</span> : null}
                    </div>
                ))}
            </div>

            <div className="mx-auto max-w-4xl p-5 sm:p-8 lg:p-10">
                {onBack ? <button type="button" onClick={onBack} className="mb-5 text-sm font-semibold text-slate-500 hover:text-slate-950">← Back</button> : null}
                <div className="text-xs font-bold uppercase tracking-[0.16em] text-teal-700">Step {step} of 3{context ? ` · ${context}` : ''}</div>
                <h2 className="mt-2 text-2xl font-semibold tracking-tight text-slate-950 sm:text-3xl">{title}</h2>
                <p className="mt-2 text-sm text-slate-500">{description}</p>

                <input type="search" value={query} onChange={(event) => setQuery(event.target.value)} placeholder={placeholder} className="mt-6 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-950 outline-none transition placeholder:text-slate-400 focus:border-teal-600 focus:ring-2 focus:ring-teal-100" />

                <div className="mt-4 grid max-h-[470px] gap-2 overflow-y-auto pr-1 sm:grid-cols-2">
                    {filtered.map((item) => (
                        <button key={item.id} type="button" onClick={() => onSelect(item)} className="group flex items-center justify-between rounded-xl border border-slate-200 px-4 py-3.5 text-left transition hover:border-teal-300 hover:bg-teal-50/50">
                            <span className="font-semibold text-slate-800 group-hover:text-slate-950">{item.libelle}</span>
                            <span className="ml-3 text-xs font-medium text-slate-400">{count(item)}</span>
                        </button>
                    ))}
                </div>

                {filtered.length === 0 ? <div className="py-12 text-center text-sm text-slate-500">No canonical record matches this search.</div> : null}
            </div>
        </section>
    );
}

function StatCard({ label, value, detail, primary = false }) {
    return (
        <div className={`rounded-2xl border p-5 shadow-sm ${primary ? 'border-teal-200 bg-teal-50 text-teal-950' : 'border-slate-200 bg-white text-slate-950'}`}>
            <div className={`text-[11px] font-bold uppercase tracking-[0.15em] ${primary ? 'text-teal-700' : 'text-slate-400'}`}>{label}</div>
            <div className="mt-4 text-2xl font-semibold tracking-tight">{value}</div>
            <div className={`mt-1.5 text-xs ${primary ? 'text-teal-700' : 'text-slate-500'}`}>{detail}</div>
        </div>
    );
}

function Field({ label, children }) {
    return <label><span className="mb-1.5 block text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400">{label}</span>{children}</label>;
}

function ObservationTable({ observations }) {
    const rows = observations?.data ?? [];

    return (
        <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div className="flex items-center justify-between border-b border-slate-200 px-5 py-4 sm:px-6">
                <div>
                    <h3 className="text-sm font-semibold text-slate-950">Market observations</h3>
                    <p className="mt-0.5 text-xs text-slate-500">Historical evidence used by the current query</p>
                </div>
                <div className="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">{observations?.total ?? 0}</div>
            </div>

            {rows.length ? (
                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-slate-200">
                        <thead className="bg-slate-50 text-left text-[10px] font-bold uppercase tracking-[0.12em] text-slate-400">
                            <tr><th className="px-5 py-3">Price</th><th className="px-5 py-3">Type</th><th className="px-5 py-3">Compatibility</th><th className="px-5 py-3">Source</th><th className="px-5 py-3">Observed</th><th className="px-5 py-3">Reference</th></tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {rows.map((row) => (
                                <tr key={row.id} className="hover:bg-slate-50/80">
                                    <td className="whitespace-nowrap px-5 py-4 text-sm font-semibold text-slate-950">{formatMoney(row.price)}</td>
                                    <td className="whitespace-nowrap px-5 py-4"><TypeBadge type={row.type} /></td>
                                    <td className="whitespace-nowrap px-5 py-4 text-sm text-slate-600">{formatProductionRange(row.production_from, row.production_to)}</td>
                                    <td className="whitespace-nowrap px-5 py-4 text-sm font-medium text-slate-700">{row.source?.libelle ?? '—'}</td>
                                    <td className="whitespace-nowrap px-5 py-4 text-sm text-slate-500">{formatDate(row.observed_at)}</td>
                                    <td className="max-w-[240px] px-5 py-4">{row.source_url ? <a href={row.source_url} target="_blank" rel="noreferrer" className="block truncate text-sm font-semibold text-teal-700 hover:text-teal-900">{row.source_reference || 'Open source'} ↗</a> : <span className="block truncate text-sm text-slate-400">{row.source_reference || '—'}</span>}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            ) : <div className="px-6 py-14 text-center text-sm text-slate-500">No observations match these filters.</div>}

            {observations?.last_page > 1 ? (
                <div className="flex items-center justify-between border-t border-slate-200 px-5 py-4 text-xs font-semibold text-slate-500">
                    {observations.prev_page_url ? <Link href={observations.prev_page_url} preserveScroll className="rounded-lg border border-slate-300 px-3 py-2 hover:text-slate-950">← Previous</Link> : <span />}
                    <span>Page <b className="text-slate-900">{observations.current_page}</b> of {observations.last_page}</span>
                    {observations.next_page_url ? <Link href={observations.next_page_url} preserveScroll className="rounded-lg border border-slate-300 px-3 py-2 hover:text-slate-950">Next →</Link> : <span />}
                </div>
            ) : null}
        </section>
    );
}

function TypeBadge({ type }) {
    const classes = { original: 'bg-blue-50 text-blue-700 ring-blue-200', adaptable: 'bg-amber-50 text-amber-700 ring-amber-200', occasion: 'bg-violet-50 text-violet-700 ring-violet-200' };
    return <span className={`inline-flex rounded-full px-2.5 py-1 text-[11px] font-bold capitalize ring-1 ring-inset ${classes[type] ?? 'bg-slate-100 text-slate-600 ring-slate-200'}`}>{type}</span>;
}

function compactParams(params) {
    return Object.fromEntries(Object.entries(params).filter(([, value]) => value !== '' && value !== null && value !== undefined));
}

function formatMoney(value) {
    if (value === null || value === undefined) return '—';
    return `${Number(value).toLocaleString('fr-MA', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} MAD`;
}

function formatRange(minimum, maximum) {
    if (minimum === null || minimum === undefined || maximum === null || maximum === undefined) return '—';
    const format = (value) => Number(value).toLocaleString('fr-MA', { maximumFractionDigits: 0 });
    return `${format(minimum)}–${format(maximum)} MAD`;
}

function formatProductionRange(from, to) {
    if (!from && !to) return 'Unknown';
    if (!from) return `≤ ${to}`;
    if (!to) return `${from}+`;
    return `${from}–${to}`;
}

function formatDate(value) {
    if (!value) return '—';
    return new Intl.DateTimeFormat('en-GB', { year: 'numeric', month: 'short', day: '2-digit' }).format(new Date(value));
}

function evidenceLabel(count) {
    if (count >= 10) return 'Broad evidence set';
    if (count >= 4) return 'Moderate evidence set';
    if (count > 0) return 'Limited evidence set';
    return 'No matching evidence';
}
