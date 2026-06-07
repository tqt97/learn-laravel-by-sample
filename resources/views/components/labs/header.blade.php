<header
    class="sticky top-0 z-50 border-b border-slate-200 bg-white/90 backdrop-blur dark:border-slate-800 dark:bg-slate-950/90">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
        {{-- Logo + Title --}}
        <a href="{{ url('/') }}" class="flex items-center gap-3">
            <div
                class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600 text-lg font-black text-white">
                L
            </div>

            <div class="hidden sm:block">
                <div class="text-sm font-bold text-slate-950 dark:text-white">
                    Laravel Production Lab
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400">
                    System Design Comparison
                </div>
            </div>
        </a>

        {{-- Right actions --}}
        <div class="flex items-center gap-2">
            {{-- Language Switch --}}
            <x-labs.language-switch />

            {{-- Dark Mode --}}
            <button type="button" id="theme-toggle"
                class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                <span class="dark:hidden">🌙</span>
                <span class="hidden dark:inline">☀️</span>
            </button>

            {{-- Auth Breeze style --}}
            @auth
                <a href="{{ route('dashboard') }}"
                    class="hidden rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800 sm:inline-flex">
                    Dashboard
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <button type="submit"
                        class="rounded-xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-700 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200">
                        Logout
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}"
                    class="rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800">
                    Login
                </a>

                @if (Route::has('register'))
                    <a href="{{ route('register') }}"
                        class="hidden rounded-xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-700 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200 sm:inline-flex">
                        Register
                    </a>
                @endif
            @endauth
        </div>
    </div>
</header>
