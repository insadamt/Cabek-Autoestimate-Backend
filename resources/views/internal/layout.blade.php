<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Cabek AutoEstimate')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 antialiased">
    <div class="min-h-screen lg:grid lg:grid-cols-[240px_1fr]">
        <aside class="border-b border-white/10 bg-slate-950/95 lg:min-h-screen lg:border-b-0 lg:border-r">
            <div class="flex h-16 items-center px-6 lg:h-20">
                <div>
                    <div class="text-sm font-semibold tracking-wide text-white">Cabek AutoEstimate</div>
                    <div class="mt-0.5 text-xs text-slate-500">Internal tools</div>
                </div>
            </div>

            <nav class="flex gap-2 overflow-x-auto px-4 pb-4 lg:block lg:space-y-1 lg:overflow-visible">
                <a
                    href="{{ route('internal.explorer') }}"
                    class="flex min-w-max items-center gap-3 rounded-xl bg-white px-3 py-2.5 text-sm font-medium text-slate-950"
                >
                    <span class="flex size-7 items-center justify-center rounded-lg bg-slate-950 text-xs font-bold text-white">P</span>
                    Price Explorer
                </a>

                <div class="mt-3 hidden px-3 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-600 lg:block">
                    Coming next
                </div>
                <div class="hidden rounded-xl px-3 py-2.5 text-sm text-slate-600 lg:block">JSON Ingestor</div>
                <div class="hidden rounded-xl px-3 py-2.5 text-sm text-slate-600 lg:block">Models</div>
                <div class="hidden rounded-xl px-3 py-2.5 text-sm text-slate-600 lg:block">Reference Data</div>
            </nav>
        </aside>

        <main class="min-w-0">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>
