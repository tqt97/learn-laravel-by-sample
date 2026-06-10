<?php

namespace App\Services\Labs\Core;

use App\Services\Labs\Contracts\LabScenarioContract;
use App\Services\Labs\Core\Scenarios\Concurrency\BookingDoubleSubmitScenario;
use App\Services\Labs\Core\Scenarios\Concurrency\InventoryOversellScenario;
use App\Services\Labs\Core\Scenarios\Concurrency\PaymentIdempotencyScenario;
use App\Services\Labs\Core\Scenarios\Concurrency\QueueRetrySafeJobScenario;
use InvalidArgumentException;

final class LabScenarioRegistry
{
    /**
     * @return array<string, class-string<LabScenarioContract>>
     */
    private function scenarios(): array
    {
        return [
            // for concurrency scenarios
            'inventory-oversell' => InventoryOversellScenario::class,
            'booking-double-submit' => BookingDoubleSubmitScenario::class,
            'payment-idempotency' => PaymentIdempotencyScenario::class,
            'queue-retry-safe-job' => QueueRetrySafeJobScenario::class,
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
            ->map(fn (string $class, string $key) => $this->meta($key))
            ->sortBy([
                ['group', 'asc'],
                ['part', 'asc'],
                ['order', 'asc'],
            ])
            ->values()
            ->all();
    }

    public function meta(string $scenario): array
    {
        $lab = $this->get($scenario);

        return [
            'key' => $lab->key(),
            'group' => $lab->group(),
            'part' => $lab->part(),
            'order' => $lab->order(),
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
            'ui' => $lab->uiConfig(),
            'learning_center' => $lab->learningCenter(),
        ];
    }
}
