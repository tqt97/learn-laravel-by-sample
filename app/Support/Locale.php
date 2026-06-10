<?php

namespace App\Support;

final class Locale
{
    public static function available(): array
    {
        return config('locales.available', []);
    }

    public static function codes(): array
    {
        return array_keys(self::available());
    }

    public static function default(): string
    {
        return config(
            'locales.default',
            config('app.locale', 'vi')
        );
    }

    public static function isSupported(string $locale): bool
    {
        return in_array(
            $locale,
            self::codes(),
            true
        );
    }

    public static function options(): array
    {
        $current = app()->getLocale();

        return collect(self::available())
            ->map(fn (array $locale, string $code) => [

                'code' => $code,

                'label' => $locale['label'],

                'name' => $locale['name'],

                'flag' => $locale['flag'],

                'url' => route(
                    'locale.switch',
                    ['locale' => $code]
                ),

                'active' => $current === $code,

                'default' => self::default() === $code,

            ])
            ->values()
            ->all();
    }
}
