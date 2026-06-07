<?php

namespace App\Http\Controllers\Labs;

use App\Enums\Labs\LabMode;
use App\Http\Requests\Labs\LabActionRequest;
use App\Services\Labs\Core\LabResponseFactory;
use App\Services\Labs\Core\LabScenarioRegistry;
use Illuminate\Http\JsonResponse;

final class LabActionController
{
    public function __invoke(
        string $scenario,
        string $mode,
        LabActionRequest $request,
        LabScenarioRegistry $registry,
        LabResponseFactory $responseFactory,
    ): JsonResponse {
        $result = $registry
            ->get($scenario)
            ->action(
                mode: LabMode::from($mode),
                payload: $request->validated(),
            );

        return $responseFactory->action($result);
    }
}
