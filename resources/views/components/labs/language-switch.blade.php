<div
    class="hidden items-center rounded-full border border-slate-200 bg-slate-50 p-1 text-xs font-semibold dark:border-slate-700 dark:bg-slate-900 sm:flex"
>
    @foreach ($locales as $locale)
        <a
            href="{{ $locale['url'] }}"
            @class([
                'rounded-full px-3 py-1 transition',
                'bg-indigo-600 text-white shadow-sm' => $locale['active'],
                'text-slate-600 hover:bg-white dark:text-slate-300 dark:hover:bg-slate-800' => ! $locale['active'],
            ])
            title="{{ $locale['default'] ? __('lab.default_language') : __('lab.switch_language') }}"
        >
            <span>{{ $locale['flag'] }}</span>
            <span>{{ $locale['label'] }}</span>

            @if ($locale['default'])
                <span class="ml-1 text-[10px] opacity-70">
                    {{ __('lab.default') }}
                </span>
            @endif
        </a>
    @endforeach
</div>
