# Locale Switch Architecture

> **Mục tiêu:** xây dựng chức năng đổi ngôn ngữ linh hoạt, dễ mở rộng, không hard-code `['vi', 'en']` rải rác trong Controller, Middleware, View.

---

## 1. Mục tiêu thiết kế

Chức năng đổi ngôn ngữ cần đảm bảo:

```text
- Dễ thêm ngôn ngữ mới
- Không hard-code locale nhiều nơi
- View không chứa logic phức tạp
- Middleware tự set locale cho mỗi request
- Header hiển thị rõ ngôn ngữ đang active
- Có thể biết ngôn ngữ mặc định
- Dễ mở rộng sang JP, FR, DE...
```

---

## 2. Cấu trúc file

```text
config/
└── locales.php

app/
├── Supports/
│   └── Locale.php
│
├── Http/
│   ├── Controllers/
│   │   └── LocaleSwitchController.php
│   │
│   └── Middleware/
│       └── SetLocale.php
│
└── View/
    └── Components/
        └── Labs/
            └── LanguageSwitch.php

resources/
└── views/
    └── components/
        └── labs/
            └── language-switch.blade.php

routes/
└── web.php
```

---

## 3. Config locale

File:

```text
config/locales.php
```

```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Locale
    |--------------------------------------------------------------------------
    |
    | Ngôn ngữ mặc định của hệ thống.
    | Có thể sync với APP_LOCALE trong .env.
    |
    */

    'default' => env('APP_LOCALE', 'vi'),

    /*
    |--------------------------------------------------------------------------
    | Available Locales
    |--------------------------------------------------------------------------
    |
    | Danh sách ngôn ngữ được hỗ trợ.
    | Muốn thêm ngôn ngữ mới chỉ cần thêm vào đây.
    |
    */

    'available' => [

        'vi' => [
            'label' => 'VI',
            'name' => 'Tiếng Việt',
            'flag' => '🇻🇳',
        ],

        'en' => [
            'label' => 'EN',
            'name' => 'English',
            'flag' => '🇺🇸',
        ],

        'ja' => [
            'label' => 'JP',
            'name' => '日本語',
            'flag' => '🇯🇵',
        ],

        'fr' => [
            'label' => 'FR',
            'name' => 'Français',
            'flag' => '🇫🇷',
        ],

    ],

];
```

---

## 4. Locale Support Class

File:

```text
app/Supports/Locale.php
```

```php
<?php

namespace App\Supports;

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
        return in_array($locale, self::codes(), true);
    }

    public static function options(): array
    {
        $current = app()->getLocale();

        return collect(self::available())
            ->map(fn (array $locale, string $code) => [
                'code' => $code,
                'label' => $locale['label'] ?? strtoupper($code),
                'name' => $locale['name'] ?? strtoupper($code),
                'flag' => $locale['flag'] ?? '🌐',

                'url' => route('locale.switch', [
                    'locale' => $code,
                ]),

                'active' => $current === $code,
                'default' => self::default() === $code,
            ])
            ->values()
            ->all();
    }
}
```

---

## 5. Middleware set locale

File:

```text
app/Http/Middleware/SetLocale.php
```

```php
<?php

namespace App\Http\Middleware;

use App\Supports\Locale;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SetLocale
{
    public function handle(
        Request $request,
        Closure $next,
    ): Response {
        $locale = session('locale', Locale::default());

        if (! Locale::isSupported($locale)) {
            $locale = Locale::default();
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
```

---

## 6. Đăng ký middleware

Laravel 11/12:

File:

```text
bootstrap/app.php
```

```php
use Illuminate\Foundation\Configuration\Middleware;

->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \App\Http\Middleware\SetLocale::class,
    ]);
})
```

Ý nghĩa:

```text
Mỗi request web đều đọc locale trong session.
Nếu hợp lệ thì set app locale.
Nếu không hợp lệ thì fallback về locale mặc định.
```

---

## 7. Locale Switch Controller

File:

```text
app/Http/Controllers/LocaleSwitchController.php
```

```php
<?php

namespace App\Http\Controllers;

use App\Supports\Locale;
use Illuminate\Http\RedirectResponse;

final class LocaleSwitchController
{
    public function __invoke(string $locale): RedirectResponse
    {
        abort_unless(Locale::isSupported($locale), 404);

        session([
            'locale' => $locale,
        ]);

        app()->setLocale($locale);

        return redirect()->back();
    }
}
```

---

## 8. Route

File:

```text
routes/web.php
```

```php
use App\Http\Controllers\LocaleSwitchController;

Route::get('/locale/{locale}', LocaleSwitchController::class)
    ->name('locale.switch');
```

Không cần hard-code:

```php
->whereIn('locale', ['vi', 'en'])
```

Vì đã validate bằng:

```php
Locale::isSupported($locale)
```

---

## 9. Blade Component Class

Tạo component:

```bash
php artisan make:component Labs/LanguageSwitch
```

File:

```text
app/View/Components/Labs/LanguageSwitch.php
```

```php
<?php

namespace App\View\Components\Labs;

use App\Supports\Locale;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

final class LanguageSwitch extends Component
{
    public array $locales;

    public function __construct()
    {
        $this->locales = Locale::options();
    }

    public function render(): View
    {
        return view('components.labs.language-switch');
    }
}
```

---

## 10. Blade Component View

File:

```text
resources/views/components/labs/language-switch.blade.php
```

```blade
<div
    class="hidden items-center rounded-full border border-slate-200 bg-slate-50 p-1 text-xs font-semibold dark:border-slate-700 dark:bg-slate-900 sm:flex"
>
    @foreach ($locales as $locale)
        <a
            href="{{ $locale['url'] }}"
            title="{{ $locale['default'] ? 'Default language' : 'Switch language' }}"
            @class([
                'rounded-full px-3 py-1 transition',
                'bg-indigo-600 text-white shadow-sm' => $locale['active'],
                'text-slate-600 hover:bg-white dark:text-slate-300 dark:hover:bg-slate-800' => ! $locale['active'],
            ])
        >
            <span>{{ $locale['flag'] }}</span>
            <span>{{ $locale['label'] }}</span>

            @if ($locale['default'])
                <span class="ml-1 text-[10px] opacity-70">
                    default
                </span>
            @endif
        </a>
    @endforeach
</div>
```

---

## 11. Sử dụng trong Header

Ví dụ:

```text
resources/views/components/labs/header.blade.php
```

```blade
<x-labs.language-switch />
```

Header không cần biết:

```text
- Có bao nhiêu ngôn ngữ
- Ngôn ngữ nào active
- Ngôn ngữ nào default
- URL switch là gì
```

Tất cả đã nằm trong:

```text
config/locales.php
App\Supports\Locale
LanguageSwitch Component
```

---

## 12. Luồng hoạt động

```text
User mở website

↓

SetLocale Middleware chạy

↓

Middleware đọc session('locale')

↓

Nếu session locale hợp lệ:
    app()->setLocale(locale)

Nếu không:
    app()->setLocale(default locale)

↓

Header render LanguageSwitch component

↓

Component lấy Locale::options()

↓

View hiển thị danh sách ngôn ngữ

↓

User click EN

↓

GET /locale/en

↓

LocaleSwitchController validate locale

↓

Lưu session(['locale' => 'en'])

↓

Redirect back

↓

Request sau middleware set app locale = en

↓

Header active EN
```

---

## 13. Cách thêm ngôn ngữ mới

Ví dụ thêm tiếng Đức.

Chỉ cần sửa:

```text
config/locales.php
```

```php
'de' => [
    'label' => 'DE',
    'name' => 'Deutsch',
    'flag' => '🇩🇪',
],
```

Không cần sửa:

```text
Controller
Middleware
Route
Component class
Component view
Header
```

---

## 14. Cách đổi ngôn ngữ mặc định

Trong `.env`:

```env
APP_LOCALE=vi
```

Hoặc trong:

```text
config/locales.php
```

```php
'default' => env('APP_LOCALE', 'vi'),
```

Nếu muốn mặc định tiếng Anh:

```env
APP_LOCALE=en
```

Sau đó chạy:

```bash
php artisan config:clear
```

Nếu production đang cache config:

```bash
php artisan config:cache
```

---

## 15. Vì sao không hard-code `['vi', 'en']`

Không nên hard-code nhiều nơi:

```php
['vi', 'en']
```

Vì khi thêm ngôn ngữ mới như:

```text
ja
fr
de
ko
```

bạn sẽ phải sửa:

```text
Controller
Middleware
View
Route
Validation
Component
```

Dễ sót, dễ bug.

Cách đúng:

```text
Một nguồn cấu hình duy nhất:
config/locales.php
```

Các nơi khác chỉ gọi:

```php
Locale::codes()
Locale::isSupported($locale)
Locale::options()
Locale::default()
```

---

## 16. Vì sao dùng Support Class thay vì Service inject

Có thể dùng Service, nhưng với case này `Locale` chủ yếu chỉ đọc config và format option.

Dùng Support Class giúp:

```text
- Không cần inject vào Middleware
- Tránh lỗi handle middleware chỉ nhận 2 tham số
- Dễ gọi từ Controller, Component, Middleware
- Logic đơn giản, ít state
- Phù hợp dạng configuration helper
```

Ví dụ:

```php
Locale::isSupported($locale)
Locale::options()
```

rõ ràng và ngắn gọn.

---

## 17. Lưu ý middleware injection

Không nên viết middleware như sau:

```php
public function handle(
    Request $request,
    Closure $next,
    LocaleService $localeService,
): Response
```

Vì Laravel middleware `handle()` mặc định chỉ được truyền:

```text
$request
$next
```

Nếu cần inject service, hãy inject qua constructor.

Nhưng trong thiết kế này ta dùng:

```php
App\Supports\Locale
```

nên không cần inject.

---

## 18. Test case nên có

### Locale support test

```php
it('detects supported locales', function () {
    expect(\App\Supports\Locale::isSupported('vi'))->toBeTrue()
        ->and(\App\Supports\Locale::isSupported('en'))->toBeTrue()
        ->and(\App\Supports\Locale::isSupported('unknown'))->toBeFalse();
});
```

### Locale switch test

```php
it('can switch locale', function () {
    $response = $this->get(route('locale.switch', [
        'locale' => 'en',
    ]));

    $response->assertRedirect();

    expect(session('locale'))->toBe('en');
});
```

### Invalid locale test

```php
it('rejects unsupported locale', function () {
    $this->get(route('locale.switch', [
        'locale' => 'xx',
    ]))->assertNotFound();
});
```

### Middleware fallback test

```php
it('falls back to default locale when session locale is invalid', function () {
    session(['locale' => 'xx']);

    $this->get('/');

    expect(app()->getLocale())->toBe(\App\Supports\Locale::default());
});
```

---

### 19. Best practices

```text
Nên:
- Dùng config/locales.php làm single source of truth
- Validate locale bằng Locale::isSupported()
- Set locale bằng middleware cho mỗi request
- Lưu locale trong session
- View chỉ render data đã chuẩn bị
- Active locale phải có màu rõ
- Default locale nên có badge nhỏ
- Sau khi đổi APP_LOCALE nhớ clear/cache config

Không nên:
- Hard-code ['vi', 'en'] nhiều nơi
- Để view tự xử lý logic locale
- Inject service trực tiếp vào middleware handle()
- Cho phép route /locale/{locale} set bất kỳ string nào
- Lưu locale không validate
```

---

## 20. Kết luận

Kiến trúc change locale nên đi theo flow:

```text
config/locales.php
    ↓
App\Supports\Locale
    ↓
SetLocale Middleware
    ↓
LocaleSwitchController
    ↓
LanguageSwitch Component
    ↓
Header View
```

Ưu điểm:

```text
Dễ mở rộng
Ít lặp code
View sạch
Controller gọn
Middleware an toàn
Không hard-code locale nhiều nơi
```

Khi muốn thêm ngôn ngữ mới:

```text
Chỉ sửa config/locales.php
```
