<?php

namespace App\View\Components\Labs;

use App\Support\Locale;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class LanguageSwitch extends Component
{
    public array $locales;

    /**
     * Create a new component instance.
     */
    public function __construct()
    {
        $this->locales = Locale::options();
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.labs.language-switch');
    }
}
