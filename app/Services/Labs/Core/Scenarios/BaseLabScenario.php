<?php

namespace App\Services\Labs\Core\Scenarios;

use App\DTOs\Labs\LabActionResult;
use App\Services\Labs\Concerns\HasDefaultLabControls;
use App\Services\Labs\Contracts\LabScenarioContract;
use App\Services\Labs\Core\LabDatabaseResetService;

abstract class BaseLabScenario implements LabScenarioContract
{
    use HasDefaultLabControls;

    abstract public function key(): string;

    abstract protected function resetMethod(): string;

    public function resetAll(): LabActionResult
    {
        app(LabDatabaseResetService::class)->{$this->resetMethod()}();

        return LabActionResult::success(
            __('lab.reset-db-all', [
                'scenario' => $this->title(),
                'action' => $this->resetMethod(),
            ])
        );
    }

    protected function translationKey(): string
    {
        return str($this->key())->replace('_', '-')->toString();
    }

    protected function scenarioLang(string $path): mixed
    {
        return __("lab_scenarios.{$this->translationKey()}.{$path}");
    }

    public function title(): string
    {
        return $this->scenarioLang('title');
    }

    public function subtitle(): string
    {
        return $this->scenarioLang('subtitle');
    }

    public function description(): string
    {
        return $this->scenarioLang('description');
    }

    public function actionHint(): string
    {
        return $this->scenarioLang('action_hint');
    }

    public function howToUse(): array
    {
        return $this->scenarioLang('how_to_use');
    }

    public function learningGoals(): array
    {
        return $this->scenarioLang('learning_goals');
    }

    public function naiveTechniques(): array
    {
        return $this->scenarioLang('naive_techniques');
    }

    public function productionTechniques(): array
    {
        return $this->scenarioLang('production_techniques');
    }

    public function uiConfig(): array
    {
        return $this->scenarioLang('ui');
    }

    public function learningCenter(): array
    {
        return $this->scenarioLang('learning_center');
    }
}
