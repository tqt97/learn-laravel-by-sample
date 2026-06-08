<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <title>{{ __('lab.production_pattern_comparison') }}</title>
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
            <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0 flex-1">
                    <p id="scenario-subtitle"
                        class="mb-2 text-sm font-semibold uppercase tracking-wide text-indigo-600 dark:text-indigo-400">
                        {{ $defaultScenarioMeta['subtitle'] }}
                    </p>

                    <h1 id="scenario-title"
                        class="text-2xl font-bold tracking-tight text-slate-950 dark:text-white sm:text-3xl">
                        {{ $defaultScenarioMeta['title'] }}
                    </h1>

                    <div
                        class="mt-3 max-w-4xl space-y-2 text-sm leading-6 text-slate-600 dark:text-slate-400 sm:text-base">
                        <p id="scenario-description">
                            {{ $defaultScenarioMeta['description'] }}
                        </p>

                        <p id="scenario-action-hint"
                            class="rounded-2xl border border-indigo-100 bg-indigo-50 px-4 py-3 text-sm text-indigo-800 dark:border-indigo-900 dark:bg-indigo-950/40 dark:text-indigo-200">
                            {{ $defaultScenarioMeta['action_hint'] }}
                        </p>
                    </div>

                    <div class="mt-5 grid gap-4 lg:grid-cols-2">
                        {{-- Learning Goals --}}
                        <div
                            class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                            <div class="mb-3 flex items-center gap-2">
                                <span
                                    class="flex h-7 w-7 items-center justify-center rounded-full bg-emerald-100 text-sm text-emerald-700 dark:bg-emerald-900 dark:text-emerald-200">
                                    ✓
                                </span>

                                <h2
                                    class="text-sm font-bold uppercase tracking-wide text-slate-700 dark:text-slate-300">
                                    {{ __('lab.learning_goals') }}
                                </h2>
                            </div>

                            <ul id="scenario-learning-goals"
                                class="space-y-2 text-sm leading-6 text-slate-600 dark:text-slate-400">
                                @foreach ($defaultScenarioMeta['learning_goals'] ?? [] as $goal)
                                    <li class="flex gap-2">
                                        <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-emerald-500"></span>
                                        <span>{{ $goal }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        {{-- How To Use --}}
                        <div
                            class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                            <div class="mb-3 flex items-center gap-2">
                                <span
                                    class="flex h-7 w-7 items-center justify-center rounded-full bg-indigo-100 text-sm text-indigo-700 dark:bg-indigo-900 dark:text-indigo-200">
                                    ?
                                </span>

                                <h2
                                    class="text-sm font-bold uppercase tracking-wide text-slate-700 dark:text-slate-300">
                                    {{ __('lab.how_to_use') }}
                                </h2>
                            </div>

                            <ol id="scenario-how-to-use"
                                class="space-y-2 text-sm leading-6 text-slate-600 dark:text-slate-400">
                                @foreach ($defaultScenarioMeta['how_to_use'] ?? [] as $step)
                                    <li class="flex gap-2">
                                        <span
                                            class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700 dark:bg-indigo-900 dark:text-indigo-200">
                                            {{ $loop->iteration }}
                                        </span>
                                        <span>{{ $step }}</span>
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                    </div>

                    {{-- Action Type Legend --}}
                    <div class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                        <div
                            class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                            <div class="font-semibold text-slate-900 dark:text-white">
                                ⚡ {{ __('lab.real_requests') }}
                            </div>
                            <p class="mt-1 text-slate-600 dark:text-slate-400">
                                {{ __('lab.real_requests_description') }}
                            </p>
                        </div>

                        <div
                            class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                            <div class="font-semibold text-slate-900 dark:text-white">
                                🧪 {{ __('lab.race_simulation') }}
                            </div>
                            <p class="mt-1 text-slate-600 dark:text-slate-400">
                                {{ __('lab.race_simulation_description') }}
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    class="w-full shrink-0 rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-200 dark:bg-slate-950 dark:ring-slate-800 lg:w-auto">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center lg:flex-col lg:items-stretch">
                        <label for="scenario-select" class="text-sm font-medium text-slate-700 dark:text-slate-300">
                            {{ __('lab.scenario') }}
                        </label>

                        <select id="scenario-select"
                            class="min-w-64 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                            @foreach ($scenarios as $scenario)
                                <option value="{{ $scenario['key'] }}" @selected($scenario['key'] === $defaultScenario)>
                                    {{ $scenario['title'] }}
                                </option>
                            @endforeach
                        </select>

                        <button type="button" data-reset-all class="btn-danger">
                            {{ __('lab.reset_all') }}
                        </button>
                    </div>
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
                    <h2 class="text-xl font-bold text-rose-700 dark:text-rose-300">
                        {{ __('lab.naive') }}
                    </h2>

                    <p class="mt-1 text-sm text-rose-700/80 dark:text-rose-300/80">
                        {{ __('lab.naive_description') }}
                    </p>

                    <div class="mt-4">
                        <div
                            class="mb-2 text-xs font-bold uppercase tracking-wide text-rose-700/70 dark:text-rose-300/70">
                            {{ __('lab.techniques_used') }}
                        </div>

                        <ul id="naive-techniques" class="flex flex-wrap gap-2">
                            @foreach ($defaultScenarioMeta['naive_techniques'] ?? [] as $technique)
                                <li
                                    class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-rose-700 ring-1 ring-rose-200 dark:bg-rose-950 dark:text-rose-200 dark:ring-rose-800">
                                    {{ $technique }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <div class="border-b border-slate-200 bg-emerald-50 p-5 dark:border-slate-800 dark:bg-emerald-950/40">
                    <h2 class="text-xl font-bold text-emerald-700 dark:text-emerald-300">
                        {{ __('lab.production') }}
                    </h2>

                    <p class="mt-1 text-sm text-emerald-700/80 dark:text-emerald-300/80">
                        {{ __('lab.production_description') }}
                    </p>

                    <div class="mt-4">
                        <div
                            class="mb-2 text-xs font-bold uppercase tracking-wide text-emerald-700/70 dark:text-emerald-300/70">
                            {{ __('lab.techniques_used') }}
                        </div>

                        <ul id="production-techniques" class="flex flex-wrap gap-2">
                            @foreach ($defaultScenarioMeta['production_techniques'] ?? [] as $technique)
                                <li
                                    class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-950 dark:text-emerald-200 dark:ring-emerald-800">
                                    {{ $technique }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Actions row --}}
            <div class="grid lg:grid-cols-2">
                {{-- Naive Actions --}}
                <div class="border-b border-slate-200 p-5 dark:border-slate-800 lg:border-r">
                    <h3 class="section-title">{{ __('lab.actions') }}</h3>

                    <div class="space-y-4">
                        <div>
                            <div
                                class="mb-2 flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-300">
                                <span>⚡</span>
                                <span id="naive-real-requests-label">{{ __('lab.real_requests') }}</span>
                            </div>

                            <div id="naive-real-request-buttons" class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                @foreach ($defaultScenarioMeta['action_presets']['real_requests'] ?? [] as $count)
                                    <button data-run mode="naive" count="{{ $count }}" run-mode="single"
                                        class="btn-primary">
                                        {{ $count }} {{ __('lab.requests') }}
                                    </button>
                                @endforeach
                            </div>

                            <div class="mt-3 flex gap-2">
                                <input type="number" min="1"
                                    max="{{ $defaultScenarioMeta['limits']['real_requests_max'] ?? 20 }}" value="10"
                                    data-custom-count="naive-real"
                                    class="w-28 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900">

                                <button data-run-custom mode="naive" run-mode="single" input-target="naive-real"
                                    class="btn-primary">
                                    {{ __('lab.run_custom') }}
                                </button>
                            </div>
                        </div>

                        <div>
                            <div
                                class="mb-2 flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-300">
                                <span>🧪</span>
                                <span id="naive-simulation-label">{{ __('lab.race_simulation') }}</span>
                            </div>

                            <div id="naive-simulation-buttons" class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                @foreach ($defaultScenarioMeta['action_presets']['race_simulation'] ?? [] as $count)
                                    <button data-run mode="naive" count="{{ $count }}" run-mode="batch_race"
                                        class="btn-primary">
                                        Simulate {{ $count }}
                                    </button>
                                @endforeach
                            </div>

                            <div class="mt-3 flex gap-2">
                                <input type="number" min="1"
                                    max="{{ $defaultScenarioMeta['limits']['race_simulation_max'] ?? 500 }}" value="50"
                                    data-custom-count="naive-simulation"
                                    class="w-28 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900">

                                <button data-run-custom mode="naive" run-mode="batch_race"
                                    input-target="naive-simulation" class="btn-primary">
                                    {{ __('lab.run_custom') }}
                                </button>

                                <button data-reset mode="naive" class="btn-danger">
                                    {{ __('lab.reset') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Production Actions --}}
                <div class="border-b border-slate-200 p-5 dark:border-slate-800">
                    <h3 class="section-title">{{ __('lab.actions') }}</h3>

                    <div class="space-y-4">
                        <div>
                            <div
                                class="mb-2 flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-300">
                                <span>⚡</span>
                                <span id="production-real-requests-label">{{ __('lab.real_requests') }}</span>
                            </div>

                            <div id="production-real-request-buttons" class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                @foreach ($defaultScenarioMeta['action_presets']['real_requests'] ?? [] as $count)
                                    <button data-run mode="production" count="{{ $count }}" run-mode="single"
                                        class="btn-success">
                                        {{ $count }} {{ __('lab.requests') }}
                                    </button>
                                @endforeach
                            </div>

                            <div class="mt-3 flex gap-2">
                                <input type="number" min="1"
                                    max="{{ $defaultScenarioMeta['limits']['real_requests_max'] ?? 20 }}" value="10"
                                    data-custom-count="production-real"
                                    class="w-28 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900">

                                <button data-run-custom mode="production" run-mode="single"
                                    input-target="production-real" class="btn-success">
                                    {{ __('lab.run_custom') }}
                                </button>
                            </div>
                        </div>

                        <div>
                            <div
                                class="mb-2 flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-300">
                                <span>🧪</span>
                                <span id="production-simulation-label">{{ __('lab.race_simulation') }}</span>
                            </div>

                            <div id="production-simulation-buttons" class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                @foreach ($defaultScenarioMeta['action_presets']['race_simulation'] ?? [] as $count)
                                    <button data-run mode="production" count="{{ $count }}" run-mode="batch_race"
                                        class="btn-success">
                                        Simulate {{ $count }}
                                    </button>
                                @endforeach
                            </div>

                            <div class="mt-3 flex gap-2">
                                <input type="number" min="1"
                                    max="{{ $defaultScenarioMeta['limits']['race_simulation_max'] ?? 500 }}" value="50"
                                    data-custom-count="production-simulation"
                                    class="w-28 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900">

                                <button data-run-custom mode="production" run-mode="batch_race"
                                    input-target="production-simulation" class="btn-success">
                                    {{ __('lab.run_custom') }}
                                </button>

                                <button data-reset mode="production" class="btn-danger">
                                    {{ __('lab.reset') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Metrics row --}}
            <div class="grid lg:grid-cols-2">
                <div class="border-b border-slate-200 p-5 dark:border-slate-800 lg:border-r">
                    <h3 class="section-title">{{ __('lab.metrics') }}</h3>
                    <div data-metrics="naive" class="grid gap-3 sm:grid-cols-3"></div>
                </div>

                <div class="border-b border-slate-200 p-5 dark:border-slate-800">
                    <h3 class="section-title">{{ __('lab.metrics') }}</h3>
                    <div data-metrics="production" class="grid gap-3 sm:grid-cols-3"></div>
                </div>
            </div>

            {{-- Invariants row --}}
            <div class="grid lg:grid-cols-2">
                <div class="border-b border-slate-200 p-5 dark:border-slate-800 lg:border-r">
                    <h3 class="section-title">{{ __('lab.invariants') }}</h3>
                    <div data-invariants="naive" class="space-y-3"></div>
                </div>

                <div class="border-b border-slate-200 p-5 dark:border-slate-800">
                    <h3 class="section-title">{{ __('lab.invariants') }}</h3>
                    <div data-invariants="production" class="space-y-3"></div>
                </div>
            </div>

            {{-- Logs row --}}
            <div class="grid lg:grid-cols-2">
                <div class="p-5 lg:border-r dark:border-slate-800">
                    <h3 class="section-title">{{ __('lab.realtime_log') }}</h3>
                    <div data-log="naive"
                        class="h-80 overflow-y-auto rounded-2xl bg-slate-950 p-4 font-mono text-xs text-slate-100 ring-1 ring-slate-800">
                    </div>
                </div>

                <div class="p-5">
                    <h3 class="section-title">{{ __('lab.realtime_log') }}</h3>
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
                <h2 id="chart-title" class="text-xl font-bold text-slate-950 dark:text-white">
                    {{ __('lab.visualization') }}
                </h2>
                <p id="chart-description" class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                    {{ __('lab.visualization_description') }}
                </p>
            </div>

            <div class="h-80">
                <canvas id="lab-chart"></canvas>
            </div>
        </section>

        {{-- Learning Center --}}
        <section
            class="mt-6 rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800 sm:p-6">
            <div class="mb-5">
                <h2 class="text-xl font-bold text-slate-950 dark:text-white">
                    {{ __('lab.learning_center') }}
                </h2>

                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                    {{ __('lab.learning_center_description') }}
                </p>
            </div>

            <div id="learning-center-tabs"
                class="mb-5 flex flex-wrap gap-2 border-b border-slate-200 pb-4 dark:border-slate-800">
                <button type="button" data-learning-tab="overview" class="learning-tab learning-tab-active">
                    {{ __('lab.overview') }}
                </button>

                <button type="button" data-learning-tab="code" class="learning-tab">
                    {{ __('lab.code') }}
                </button>

                <button type="button" data-learning-tab="sequence" class="learning-tab">
                    {{ __('lab.sequence') }}
                </button>

                <button type="button" data-learning-tab="database" class="learning-tab">
                    {{ __('lab.database') }}
                </button>

                <button type="button" data-learning-tab="tradeoff" class="learning-tab">
                    {{ __('lab.tradeoff') }}
                </button>
            </div>

            <div id="learning-center-content"
                data-learning-center='@json($defaultScenarioMeta['learning_center'] ?? [])'></div>
        </section>

    </main>

    <x-back-to-top />
</body>

</html>
