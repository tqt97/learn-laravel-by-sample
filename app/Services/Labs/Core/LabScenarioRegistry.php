<?php

namespace App\Services\Labs\Core;

use App\Services\Labs\Contracts\LabScenarioContract;
use App\Services\Labs\Core\Scenarios\InventoryOversellScenario;
use InvalidArgumentException;

final class LabScenarioRegistry
{
    /**
     * @return array<string, class-string<LabScenarioContract>>
     */
    private function scenarios(): array
    {
        return [
            'inventory-oversell' => InventoryOversellScenario::class,
        ];
    }

    public function get(string $scenario): LabScenarioContract
    {
        $class = $this->scenarios()[$scenario] ?? null;

        if (! $class) {
            throw new InvalidArgumentException("Unknown lab scenario [{$scenario}].");
        }

        return app($class);
    }

    public function all(): array
    {
        return collect($this->scenarios())
            ->map(fn (string $class, string $key) => [
                'key' => $key,
                'title' => app($class)->title(),
                'subtitle' => app($class)->subtitle(),
                'description' => app($class)->description(),
                'action_hint' => app($class)->actionHint(),
                'how_to_use' => app($class)->howToUse(),
                'learning_goals' => app($class)->learningGoals(),
                'naive_techniques' => app($class)->naiveTechniques(),
                'production_techniques' => app($class)->productionTechniques(),
                'action_presets' => app($class)->actionPresets(),
                'limits' => app($class)->limits(),
                'learning_center' => app($class)->learningCenter(),
            ])
            ->values()
            ->all();
    }

    public function meta(string $scenario): array
    {
        $lab = $this->get($scenario);

        return [
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
        ];
    }
}
