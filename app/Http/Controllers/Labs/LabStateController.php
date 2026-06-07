<?php

namespace App\Http\Controllers\Labs;

use App\Enums\Labs\LabMode;
use App\Services\Labs\Core\LabScenarioRegistry;
use Illuminate\Http\JsonResponse;

final class LabStateController
{
    public function __invoke(
        string $scenario,
        LabScenarioRegistry $registry,
    ): JsonResponse {
        $lab = $registry->get($scenario);

        return response()->json([
            'scenario' => $registry->meta($scenario),
            'naive' => $lab->state(LabMode::Naive)->toArray(),
            'production' => $lab->state(LabMode::Production)->toArray(),
        ]);
    }
}
