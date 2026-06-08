<?php

namespace App\Services\Labs\Contracts;

use App\DTOs\Labs\LabActionResult;
use App\DTOs\Labs\LabStateResult;
use App\Enums\Labs\LabMode;

interface LabScenarioContract
{
    public function key(): string;

    public function title(): string;

    public function subtitle(): string;

    public function description(): string;

    public function actionHint(): string;

    public function howToUse(): array;

    public function learningGoals(): array;

    public function naiveTechniques(): array;

    public function productionTechniques(): array;

    public function actionPresets(): array;

    public function limits(): array;

    public function learningCenter(): array;

    public function uiConfig(): array;

    public function action(LabMode $mode, array $payload = []): LabActionResult;

    public function state(LabMode $mode): LabStateResult;

    public function reset(LabMode $mode): LabActionResult;

    public function resetAll(): LabActionResult;
}
