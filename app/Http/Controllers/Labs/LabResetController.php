<?php

namespace App\Http\Controllers\Labs;

use App\Enums\Labs\LabMode;
use App\Services\Labs\Core\LabResponseFactory;
use App\Services\Labs\Core\LabScenarioRegistry;
use Illuminate\Http\JsonResponse;

final class LabResetController
{
    public function __invoke(
        string $scenario,
        string $mode,
        LabScenarioRegistry $registry,
        LabResponseFactory $responseFactory,
    ): JsonResponse {
        $result = $registry
            ->get($scenario)
            ->reset(LabMode::from($mode));

        return $responseFactory->action($result);
    }

    public function resetAll(
        string $scenario,
        LabScenarioRegistry $registry,
        LabResponseFactory $responseFactory,
    ): JsonResponse {
        $result = $registry
            ->get($scenario)
            ->resetAll();

        return $responseFactory->action($result);
    }
}
