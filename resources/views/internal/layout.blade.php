<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Cabek AutoEstimate')</title>
    @vite('resources/css/app.css')
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
    <header class="sticky top-0 z-30 border-b border-slate-300 bg-white">
        <div class="mx-auto flex min-h-14 max-w-[1500px] flex-wrap items-center gap-x-6 px-4 sm:px-6 lg:px-8">
            <a href="{{ route('internal.statistics') }}" class="py-3 text-sm font-semibold text-slate-950">
                Cabek AutoEstimate
            </a>

            <span class="hidden border-l border-slate-300 pl-4 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 sm:inline">
                Internal pricing tools
            </span>

            <nav class="order-3 flex w-full gap-5 border-t border-slate-200 text-sm sm:order-none sm:ml-auto sm:w-auto sm:border-t-0" aria-label="Internal tools">
                <a
                    href="{{ route('internal.statistics') }}"
                    @if (request()->routeIs('internal.statistics')) aria-current="page" @endif
                    class="border-b-2 py-3 font-medium {{ request()->routeIs('internal.statistics') ? 'border-slate-950 text-slate-950' : 'border-transparent text-slate-500 hover:text-slate-900' }}"
                >
                    Statistics
                </a>
                <a
                    href="{{ route('internal.explorer') }}"
                    @if (request()->routeIs('internal.explorer')) aria-current="page" @endif
                    class="border-b-2 py-3 font-medium {{ request()->routeIs('internal.explorer') ? 'border-slate-950 text-slate-950' : 'border-transparent text-slate-500 hover:text-slate-900' }}"
                >
                    Price Explorer
                </a>
                <a
                    href="{{ route('internal.ingest') }}"
                    @if (request()->routeIs('internal.ingest*', 'internal.imports.*')) aria-current="page" @endif
                    class="border-b-2 py-3 font-medium {{ request()->routeIs('internal.ingest*', 'internal.imports.*') ? 'border-slate-950 text-slate-950' : 'border-transparent text-slate-500 hover:text-slate-900' }}"
                >
                    Data Import
                </a>
            </nav>
        </div>
    </header>

    <main>
        <div class="mx-auto max-w-[1500px] px-4 py-6 sm:px-6 lg:px-8">
            @yield('content')
        </div>
    </main>
</body>
</html>
