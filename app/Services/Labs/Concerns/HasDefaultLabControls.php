<?php

namespace App\Services\Labs\Concerns;

trait HasDefaultLabControls
{
    public function actionPresets(): array
    {
        return [
            'real_requests' => [1, 2, 5, 10],
            'race_simulation' => [5, 10, 20, 50],
        ];
    }

    public function limits(): array
    {
        return [
            'real_requests_max' => 20,
            'race_simulation_max' => 500,
        ];
    }
}
