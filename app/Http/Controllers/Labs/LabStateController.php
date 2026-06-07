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
            'scenario' => [
                'key' => $lab->key(),
                'title' => $lab->title(),
                'subtitle' => $lab->subtitle(),
                'description' => $lab->description(),
                'action_hint' => $lab->actionHint(),
                'how_to_use' => $lab->howToUse(),
                'learning_goals' => $lab->learningGoals(),
                'naive_techniques' => $lab->naiveTechniques(),
                'production_techniques' => $lab->productionTechniques(),
                'action_presets' => $lab->actionPresets(),
                'limits' => $lab->limits(),
                'learning_center' => $lab->learningCenter(),
            ],
            'naive' => $lab->state(LabMode::Naive)->toArray(),
            'production' => $lab->state(LabMode::Production)->toArray(),
        ]);
    }
}
