<!DOCTYPE html>
<html lang="vi" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <title>Laravel Production Pattern</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script>
        if (
            localStorage.theme === 'dark' ||
            (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)
        ) {
            document.documentElement.classList.add('dark');
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-100 text-slate-900 dark:bg-slate-950 dark:text-slate-100">
    <x-labs.header />

    <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:py-8">
        {{-- Page Header --}}
        <section
            class="mb-6 rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800 sm:p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="mb-2 text-sm font-semibold uppercase tracking-wide text-indigo-600 dark:text-indigo-400">
                        Laravel System Design Lab
                    </p>

                    <h1 class="text-2xl font-bold tracking-tight text-slate-950 dark:text-white sm:text-3xl">
                        Production Pattern Comparison
                    </h1>

                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600 dark:text-slate-400 sm:text-base">
                        So sánh trực quan giữa code chưa áp dụng system design và code production-grade.
                        Bấm nhiều request liên tục để quan sát bug, invariant, stock và order count thay đổi realtime.
                    </p>
                </div>

                <div
                    class="flex flex-col gap-3 rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-200 dark:bg-slate-950 dark:ring-slate-800 sm:flex-row sm:items-center">
                    <label for="scenario-select" class="text-sm font-medium text-slate-700 dark:text-slate-300">
                        Scenario
                    </label>

                    <select id="scenario-select"
                        class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                        @foreach ($scenarios as $scenario)
                            <option value="{{ $scenario['key'] }}" @selected($scenario['key'] === $defaultScenario)>
                                {{ $scenario['title'] }}
                            </option>
                        @endforeach
                    </select>

                    <button type="button" data-reset-all class="btn-danger">
                        Reset All
                    </button>
                </div>
            </div>
        </section>

        {{-- Comparison Grid --}}
        <section
            class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
            {{-- Header row --}}
            <div class="grid lg:grid-cols-2">
                <div
                    class="border-b border-slate-200 bg-rose-50 p-5 dark:border-slate-800 dark:bg-rose-950/40 lg:border-r">
                    <h2 class="text-xl font-bold text-rose-700 dark:text-rose-300">Naive</h2>
                    <p class="mt-1 text-sm text-rose-700/80 dark:text-rose-300/80">
                        Chưa áp dụng production pattern.
                    </p>
                </div>

                <div class="border-b border-slate-200 bg-emerald-50 p-5 dark:border-slate-800 dark:bg-emerald-950/40">
                    <h2 class="text-xl font-bold text-emerald-700 dark:text-emerald-300">Production</h2>
                    <p class="mt-1 text-sm text-emerald-700/80 dark:text-emerald-300/80">
                        Có áp dụng production-grade pattern.
                    </p>
                </div>
            </div>

            {{-- Actions row --}}
            <div class="grid lg:grid-cols-2">
                <div class="border-b border-slate-200 p-5 dark:border-slate-800 lg:border-r">
                    <h3 class="section-title">Actions</h3>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-5">
                        <button data-run mode="naive" count="1" class="btn-primary">1 lần</button>
                        <button data-run mode="naive" count="2" class="btn-primary">2 lần</button>
                        <button data-run mode="naive" count="5" class="btn-primary">5 lần</button>
                        <button data-run mode="naive" count="20" class="btn-primary">20 req</button>
                        <button data-reset mode="naive" class="btn-danger">Reset</button>
                    </div>
                </div>

                <div class="border-b border-slate-200 p-5 dark:border-slate-800">
                    <h3 class="section-title">Actions</h3>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-5">
                        <button data-run mode="production" count="1" class="btn-success">1 lần</button>
                        <button data-run mode="production" count="2" class="btn-success">2 lần</button>
                        <button data-run mode="production" count="5" class="btn-success">5 lần</button>
                        <button data-run mode="production" count="20" class="btn-success">20 req</button>
                        <button data-reset mode="production" class="btn-danger">Reset</button>
                    </div>
                </div>
            </div>

            {{-- Metrics row --}}
            <div class="grid lg:grid-cols-2">
                <div class="border-b border-slate-200 p-5 dark:border-slate-800 lg:border-r">
                    <h3 class="section-title">Metrics</h3>
                    <div data-metrics="naive" class="grid gap-3 sm:grid-cols-3"></div>
                </div>

                <div class="border-b border-slate-200 p-5 dark:border-slate-800">
                    <h3 class="section-title">Metrics</h3>
                    <div data-metrics="production" class="grid gap-3 sm:grid-cols-3"></div>
                </div>
            </div>

            {{-- Invariants row --}}
            <div class="grid lg:grid-cols-2">
                <div class="border-b border-slate-200 p-5 dark:border-slate-800 lg:border-r">
                    <h3 class="section-title">Invariants</h3>
                    <div data-invariants="naive" class="space-y-3"></div>
                </div>

                <div class="border-b border-slate-200 p-5 dark:border-slate-800">
                    <h3 class="section-title">Invariants</h3>
                    <div data-invariants="production" class="space-y-3"></div>
                </div>
            </div>

            {{-- Logs row --}}
            <div class="grid lg:grid-cols-2">
                <div class="p-5 lg:border-r dark:border-slate-800">
                    <h3 class="section-title">Realtime Log</h3>
                    <div data-log="naive"
                        class="h-80 overflow-y-auto rounded-2xl bg-slate-950 p-4 font-mono text-xs text-slate-100 ring-1 ring-slate-800">
                    </div>
                </div>

                <div class="p-5">
                    <h3 class="section-title">Realtime Log</h3>
                    <div data-log="production"
                        class="h-80 overflow-y-auto rounded-2xl bg-slate-950 p-4 font-mono text-xs text-slate-100 ring-1 ring-slate-800">
                    </div>
                </div>
            </div>
        </section>

        {{-- Chart --}}
        <section
            class="mt-6 rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800 sm:p-6">
            <div class="mb-4">
                <h2 class="text-xl font-bold text-slate-950 dark:text-white">
                    Visualization
                </h2>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                    So sánh số lượng order được tạo với giới hạn stock hợp lệ.
                </p>
            </div>

            <div class="h-80">
                <canvas id="lab-chart"></canvas>
            </div>
        </section>
    </main>

    <x-back-to-top />
</body>

</html>
