<?php

namespace App\Http\Controllers\Labs;

use App\Services\Labs\Core\LabScenarioRegistry;
use Illuminate\Contracts\View\View;

final class LabDashboardController
{
    public function __invoke(LabScenarioRegistry $registry): View
    {
        $defaultScenario = 'inventory-oversell';

        return view('welcome', [
            'scenarios' => $registry->all(),
            'defaultScenario' => $defaultScenario,
            'defaultScenarioMeta' => $registry->meta($defaultScenario),
        ]);
    }
}
