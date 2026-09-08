import { Link, usePage } from '@inertiajs/react';

const navigation = [
    {
        label: 'Price Explorer',
        href: '/internal/explorer',
        icon: SearchIcon,
        enabled: true,
    },
    {
        label: 'JSON Ingestor',
        href: '#',
        icon: ImportIcon,
        enabled: false,
    },
    {
        label: 'Models',
        href: '#',
        icon: VehicleIcon,
        enabled: false,
    },
    {
        label: 'Reference Data',
        href: '#',
        icon: DatabaseIcon,
        enabled: false,
    },
];

export default function InternalLayout({ children }) {
    const { url } = usePage();

    return (
        <div className="min-h-screen bg-slate-50 lg:grid lg:grid-cols-[260px_minmax(0,1fr)]">
            <aside className="border-b border-slate-200 bg-white text-slate-700 shadow-sm lg:sticky lg:top-0 lg:h-screen lg:border-b-0 lg:border-r lg:shadow-none">
                <div className="flex h-16 items-center justify-between px-4 sm:px-6 lg:h-auto lg:block lg:px-5 lg:pb-5 lg:pt-6">
                    <Link href="/internal/explorer" className="flex items-center gap-3">
                        <div className="grid size-10 place-items-center rounded-xl bg-teal-700 text-sm font-black tracking-tight text-white shadow-sm shadow-teal-900/10">
                            CA
                        </div>
                        <div>
                            <div className="text-sm font-semibold tracking-tight text-slate-950">Cabek AutoEstimate</div>
                            <div className="mt-0.5 text-[11px] font-medium uppercase tracking-[0.16em] text-slate-400">
                                Market intelligence
                            </div>
                        </div>
                    </Link>

                    <div className="rounded-full border border-teal-200 bg-teal-50 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-teal-700 lg:mt-5 lg:inline-flex">
                        Internal
                    </div>
                </div>

                <nav className="flex gap-2 overflow-x-auto px-4 pb-4 sm:px-6 lg:block lg:space-y-1.5 lg:overflow-visible lg:px-4">
                    {navigation.map((item) => {
                        const Icon = item.icon;
                        const active = item.enabled && url.startsWith(item.href);

                        if (!item.enabled) {
                            return (
                                <div
                                    key={item.label}
                                    className="hidden items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-400 lg:flex"
                                >
                                    <Icon className="size-4" />
                                    <span>{item.label}</span>
                                    <span className="ml-auto text-[9px] uppercase tracking-widest text-slate-300">Soon</span>
                                </div>
                            );
                        }

                        return (
                            <Link
                                key={item.label}
                                href={item.href}
                                className={`flex min-w-max items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition ${
                                    active
                                        ? 'bg-teal-50 text-teal-800 ring-1 ring-inset ring-teal-200'
                                        : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950'
                                }`}
                            >
                                <Icon className="size-4" />
                                <span>{item.label}</span>
                            </Link>
                        );
                    })}
                </nav>

                <div className="mt-auto hidden px-5 pb-6 pt-8 lg:block">
                    <div className="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div className="flex items-center gap-2 text-xs font-semibold text-slate-700">
                            <span className="size-2 rounded-full bg-emerald-500 shadow-[0_0_0_4px_rgba(16,185,129,0.08)]" />
                            Pricing service
                        </div>
                        <p className="mt-2 text-xs leading-5 text-slate-500">
                            Historical market observations. Median remains the primary estimate.
                        </p>
                    </div>
                </div>
            </aside>

            <main className="min-w-0">
                <div className="mx-auto max-w-[1500px] px-4 py-5 sm:px-6 lg:px-8 lg:py-8">{children}</div>
            </main>
        </div>
    );
}

function SearchIcon({ className }) {
    return (
        <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true">
            <circle cx="11" cy="11" r="6.5" />
            <path d="m16 16 4 4" />
        </svg>
    );
}

function ImportIcon({ className }) {
    return (
        <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true">
            <path d="M12 3v11" />
            <path d="m8 10 4 4 4-4" />
            <path d="M5 20h14" />
        </svg>
    );
}

function VehicleIcon({ className }) {
    return (
        <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true">
            <path d="m5 11 1.8-4.2A2 2 0 0 1 8.6 5.5h6.8a2 2 0 0 1 1.8 1.3L19 11" />
            <path d="M4 11h16v6H4z" />
            <circle cx="7" cy="17" r="1.5" />
            <circle cx="17" cy="17" r="1.5" />
        </svg>
    );
}

function DatabaseIcon({ className }) {
    return (
        <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true">
            <ellipse cx="12" cy="5" rx="7" ry="3" />
            <path d="M5 5v6c0 1.7 3.1 3 7 3s7-1.3 7-3V5" />
            <path d="M5 11v6c0 1.7 3.1 3 7 3s7-1.3 7-3v-6" />
        </svg>
    );
}
